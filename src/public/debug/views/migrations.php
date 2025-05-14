<?php
/**
 * Migrations Module View
 */

// Prepare data if not already passed
if (empty($moduleData)) {
    $moduleData = [
        'migrations' => [],
        'applied' => [],
        'error' => null
    ];
    
    try {
        // Get list of applied migrations
        $result = $conn->query("SELECT migration FROM migrations ORDER BY applied_at");
        $appliedMigrations = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $moduleData['applied'] = array_column($appliedMigrations, 'migration');
        
        // Scan migration files
        $migrationDir = '/var/www/html/database/migrations';
        if (is_dir($migrationDir)) {
            $files = array_diff(scandir($migrationDir), ['.', '..', 'README.md']);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                    $filePath = $migrationDir . '/' . $file;
                    $firstLine = fgets(fopen($filePath, 'r'));
                    $description = trim(str_replace(['--', '/*', '*/', '#'], '', $firstLine));
                    
                    $moduleData['migrations'][] = [
                        'file' => $file,
                        'description' => $description,
                        'applied' => in_array($file, $moduleData['applied']),
                        'path' => $filePath
                    ];
                }
            }
            
            // Sort by filename (which should follow date_description.sql format)
            usort($moduleData['migrations'], function($a, $b) {
                return strcmp($a['file'], $b['file']);
            });
        }
    } catch (Exception $e) {
        $moduleData['error'] = $e->getMessage();
    }
}

// If this is a get_content request, handle it here
if (isset($_GET['action']) && $_GET['action'] === 'get_content' && isset($_GET['file'])) {
    // Set content type to plain text
    header('Content-Type: text/plain; charset=utf-8');
    
    $filePath = $_GET['file'];
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo "File not found";
        exit;
    }
    
    // Only allow reading .sql files from the migrations directory
    $realPath = realpath($filePath);
    $migrationsDir = realpath('/var/www/html/database/migrations');
    
    if (!$realPath || !str_starts_with($realPath, $migrationsDir) || !str_ends_with($realPath, '.sql')) {
        http_response_code(403);
        echo "Access denied";
        exit;
    }
    
    // Disable output buffering
    while (ob_get_level()) ob_end_clean();
    
    // Output raw file content
    readfile($filePath);
    exit;
}
?>

<!-- Load highlight.js resources -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/a11y-light.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/languages/sql.min.js"></script>
<script>
// Initialize highlight.js
hljs.highlightAll();

async function toggleMigrationContent(file, path, element) {
    const contentId = 'migration-content-' + file.replace(/[^a-zA-Z0-9]/g, '-');
    let contentDiv = document.getElementById(contentId);
    
    if (contentDiv) {
        // Toggle visibility if content already loaded
        contentDiv.style.display = contentDiv.style.display === 'none' ? 'block' : 'none';
        return;
    }
    
    // Create content div
    contentDiv = document.createElement('div');
    contentDiv.id = contentId;
    contentDiv.className = 'migration-content';
    
    // Show loading state
    contentDiv.innerHTML = '<div class="message info">Loading migration content...</div>';
    const row = element.closest('tr');
    const newRow = document.createElement('tr');
    const newCell = document.createElement('td');
    newCell.colSpan = 4;
    newCell.appendChild(contentDiv);
    newRow.appendChild(newCell);
    row.parentNode.insertBefore(newRow, row.nextSibling);
    
    try {
        // Fetch file content from dedicated endpoint
        const response = await fetch('endpoints/get_migration_content.php?file=' + encodeURIComponent(path), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const content = await response.text();
        
        // Create code block with syntax highlighting
        contentDiv.innerHTML = '<pre><code class="language-sql">' + 
            content.replace(/&/g, '&amp;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;') + 
            '</code></pre>';
        
        // Re-run highlighting on the new content
        hljs.highlightElement(contentDiv.querySelector('code'));
    } catch (error) {
        contentDiv.innerHTML = '<div class="message error">Failed to load migration content: ' + error.message + '</div>';
    }
}

// Helper function to load a script dynamically
function loadScript(src) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.onload = resolve;
        script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
        document.head.appendChild(script);
    });
}
</script>

<h2>📦 Database Migrations</h2>
<p>Manage database migrations to update the schema as the application evolves. Usually applied automatically when the application is updated.</p>

<?php if ($moduleData['error']): ?>
    <div class="message error">
        <p>Error: <?= htmlspecialchars($moduleData['error']) ?></p>
    </div>
<?php endif; ?>

<?php if (empty($moduleData['migrations'])): ?>
    <div class="message warning">
        <p>No migration files found in database/migrations directory.</p>
    </div>
<?php else: ?>
    <table>
        <tr>
            <th>Migration</th>
            <th>Description</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php foreach ($moduleData['migrations'] as $migration): ?>
            <tr>
                <td>
                    <a class="migration-link" onclick="toggleMigrationContent(<?= 
                        htmlspecialchars(json_encode($migration['file'])) ?>, <?=
                        htmlspecialchars(json_encode($migration['path'])) ?>, this)">
                        <code><?= htmlspecialchars($migration['file']) ?></code>
                    </a>
                </td>
                <td><?= htmlspecialchars($migration['description']) ?></td>
                <td class="<?= $migration['applied'] ? 'status-ok' : 'status-warning' ?>">
                    <?= $migration['applied'] ? '✅ Applied' : '⏳ Pending' ?>
                </td>
                <td>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="run_migration">
                        <input type="hidden" name="migration" value="<?= htmlspecialchars($migration['file']) ?>">
                        <button type="submit" <?= $migration['applied'] ? 'disabled' : '' ?>>Apply</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<style>
.migration-content {
    margin: 0;
    padding: 10px;
    background: #fff;
}

.migration-content pre {
    margin: 0;
    padding: 0;
    max-width: 100%;
    overflow-x: auto;
}

.migration-content code {
    padding: 1em;
    border-radius: 4px;
    font-size: 14px;
    line-height: 1.4;
    white-space: pre;
    display: block;
    width: 100%;
    box-sizing: border-box;
    overflow-x: auto;
}

/* Override table styles for the content row */
table tr td[colspan="4"] {
    padding: 0;
    border-top: none;
    max-width: 0; /* Force cell to honor width constraints */
    width: 100%;
}

/* Migration File Links */
.migration-link {
    color: #0066cc;
    text-decoration: none;
    cursor: pointer;
    display: inline-block;
    padding: 2px 4px;
}

.migration-link:hover {
    text-decoration: underline;
    background: #f0f0f0;
    border-radius: 2px;
}

.migration-link code {
    cursor: pointer;
}
</style>
