<?php
/**
 * Table Component - Component-colocated styling
 * Sophisticated table styling with hover effects and responsive design
 * 
 * Usage:
 * <?php 
 * $headers = ['Name', 'Email', 'Role'];
 * $rows = [
 *     ['John Doe', 'john@example.com', 'Admin'],
 *     ['Jane Smith', 'jane@example.com', 'User']
 * ];
 * include __DIR__ . '/table.php'; 
 * ?>
 * 
 * Or styles-only mode:
 * <?php 
 * $renderComponent = false; // Just load styles
 * include __DIR__ . '/table.php'; 
 * ?>
 */
?>

<style>
/* TABLE COMPONENT - All styles colocated */
.table-responsive {
    overflow-x: auto;
    border-radius: var(--radius-base);
    border: 1px solid var(--color-border);
}

.table-themed {
    width: 100%;
    border-collapse: collapse;
    background-color: var(--color-bg-primary);
}

.table-themed th,
.table-themed td {
    padding: var(--space-4);
    text-align: left;
    border-bottom: 1px solid var(--color-border);
}

.table-themed th {
    background-color: var(--color-bg-tertiary);
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-primary);
}

.table-themed tr:hover {
    background-color: var(--color-bg-secondary);
}

.table-themed tr:hover td {
    color: var(--color-text-primary);
}

</style>

<?php
// Check if this is styles-only mode
$renderComponent = $renderComponent ?? true;

if (!$renderComponent) {
    // Styles-only mode: just load the styles and exit
    return;
}

// Set defaults for component rendering
$headers = $headers ?? [];
$rows = $rows ?? [];
$compact = $compact ?? false;
$bordered = $bordered ?? false;
$striped = $striped ?? true;
$responsive = $responsive ?? true;

// Build table classes
$tableClasses = ['table-themed'];
if ($compact) $tableClasses[] = 'table-compact';
if ($bordered) $tableClasses[] = 'table-bordered';
if ($striped) $tableClasses[] = 'table-striped';

$tableClassString = implode(' ', $tableClasses);
?>

<?php if ($responsive): ?>
<div class="table-responsive">
<?php endif; ?>
    
    <table class="<?= $tableClassString ?>">
        <?php if (!empty($headers)): ?>
        <thead>
            <tr>
                <?php foreach ($headers as $header): ?>
                    <th><?= htmlspecialchars($header) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <?php endif; ?>
        
        <?php if (!empty($rows)): ?>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($row as $cell): ?>
                    <td><?= htmlspecialchars($cell) ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <?php endif; ?>
    </table>

<?php if ($responsive): ?>
</div>
<?php endif; ?>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Basic table -->
 * <?php 
 * $headers = ['Name', 'Email', 'Role'];
 * $rows = [
 *     ['John Doe', 'john@example.com', 'Admin'],
 *     ['Jane Smith', 'jane@example.com', 'User']
 * ];
 * include __DIR__ . '/table.php'; 
 * ?>
 * 
 * <!-- Compact bordered table -->
 * <?php 
 * $headers = ['Item', 'Qty', 'Price'];
 * $rows = [['Widget', '2', '$10.00']];
 * $compact = true;
 * $bordered = true;
 * include __DIR__ . '/table.php'; 
 * ?>
 * 
 * <!-- Just load styles -->
 * <?php 
 * $renderComponent = false;
 * include __DIR__ . '/table.php'; 
 * ?>
 */
?>
