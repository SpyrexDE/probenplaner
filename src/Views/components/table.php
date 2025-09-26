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
    overflow: hidden;
    border-radius: var(--radius-base);
    border: 1px solid var(--color-border);
}

.table-themed {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
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
    border-bottom: 2px solid var(--color-border);
}

/* Round header corners to align with wrapper radius */
.table-themed thead th:first-child { border-top-left-radius: var(--radius-base); }
.table-themed thead th:last-child { border-top-right-radius: var(--radius-base); }

/* Hover animation removed per user request */

/* Striped table styling for better row distinction */
/* More specific selectors to override any conflicting styles */
.table-responsive .table-themed.table-striped tbody tr:nth-child(odd),
.table-themed.table-striped tbody tr:nth-child(odd) {
    background-color: #ffffff !important;
}

.table-responsive .table-themed.table-striped tbody tr:nth-child(even),
.table-themed.table-striped tbody tr:nth-child(even) {
    background-color: #f3f4f6 !important;
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
<div class="table-responsive card-base border-default shadow-sm">
<?php endif; ?>
    
    <table class="<?= $tableClassString ?> w-full">
        <?php if (!empty($headers)): ?>
        <thead>
            <tr>
                <?php foreach ($headers as $header): ?>
                    <th class="text-heading text-sm">
                        <?= htmlspecialchars($header) ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <?php endif; ?>
        
        <?php if (!empty($rows)): ?>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr class="transition">
                <?php foreach ($row as $cell): ?>
                    <td class="text-body">
                        <?= htmlspecialchars($cell) ?>
                    </td>
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
