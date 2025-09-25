<?php
/**
 * Tree View Component - Component-colocated styling  
 * Hierarchical tree interface for displaying nested data structures
 * Used for rehearsal attendance views, organization structures, etc.
 * 
 * Usage:
 * <?php 
 * $treeData = [...]; // Tree structure data
 * include __DIR__ . '/tree-view.php'; 
 * ?>
 * 
 * Or styles-only mode:
 * <?php 
 * $renderComponent = false; // Just load styles
 * include __DIR__ . '/tree-view.php'; 
 * ?>
 */
?>

<style>
/* TREE VIEW COMPONENT - All styles colocated */

/* Tree Container */
.tree-view {
  background-color: var(--color-bg-primary);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  margin: var(--space-4) 0;
}

.tree-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

/* Tree Node Base */
.tree-node {
  position: relative;
  border-bottom: 1px solid var(--color-border-light);
  transition: all var(--transition-base);
}

.tree-node:last-child {
  border-bottom: none;
}

.tree-node:hover {
  background-color: var(--color-bg-secondary);
}

/* Tree Node Header */
.tree-node-header {
  display: flex;
  align-items: center;
  padding: var(--space-4) var(--space-5);
  cursor: pointer;
  text-decoration: none;
  color: var(--color-text-primary);
  font-weight: var(--font-weight-medium);
  transition: all var(--transition-base);
  position: relative;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
  font-size: var(--font-size-base);
}

.tree-node-header:hover {
  color: var(--color-text-primary);
  background-color: var(--color-bg-tertiary);
  text-decoration: none;
}

.tree-node-header:focus {
  outline: 2px solid var(--color-primary);
  outline-offset: -2px;
  background-color: var(--color-primary-50);
}

/* Tree Node Icon */
.tree-node-icon {
  margin-right: var(--space-3);
  color: var(--color-text-secondary);
  font-size: var(--font-size-base);
  width: 20px;
  text-align: center;
  transition: color var(--transition-base);
}

.tree-node-icon.expanded {
  color: var(--color-primary);
  transform: rotate(90deg);
}

/* Tree Node Title */
.tree-node-title {
  flex: 1;
  display: flex;
  align-items: center;
  gap: var(--space-2);
  min-width: 0;
}

.tree-node-title-text {
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tree-node-subtitle {
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
  font-weight: var(--font-weight-normal);
  margin-left: var(--space-2);
}

/* Tree Node Color Indicator */
.tree-node-color {
  width: 4px;
  height: 20px;
  border-radius: var(--radius-sm);
  margin-right: var(--space-3);
  background-color: var(--color-gray-300);
}

/* Tree Node Stats */
.tree-node-stats {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  margin-left: auto;
  flex-shrink: 0;
}

.tree-node-stat {
  display: flex;
  align-items: center;
  gap: var(--space-1);
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-medium);
  color: var(--color-text-secondary);
}

.tree-node-stat-icon {
  font-size: var(--font-size-base);
}

.tree-node-stat-icon.status-attending {
  color: var(--color-success);
}

.tree-node-stat-icon.status-not-attending {
  color: var(--color-error);
}

.tree-node-stat-icon.status-no-response {
  color: var(--color-text-muted);
}

/* Tree Node Content (Collapsible) */
.tree-node-content {
  overflow: hidden;
  transition: all var(--transition-base);
  background-color: var(--color-bg-secondary);
  border-top: 1px solid var(--color-border-light);
}

.tree-node-content.collapsed {
  display: none;
}

.tree-node-content.expanded {
  display: block;
}

/* Nested Tree Lists */
.tree-node-content > .tree-list {
  margin-left: var(--space-6);
  border-left: 2px solid var(--color-border-light);
  position: relative;
}

.tree-node-content > .tree-list > .tree-node {
  position: relative;
}

/* Tree User Item */
.tree-user-item {
  display: flex;
  align-items: center;
  padding: var(--space-3) var(--space-5);
  border-bottom: 1px solid var(--color-border-light);
  transition: all var(--transition-base);
  position: relative;
}

.tree-user-item:last-child {
  border-bottom: none;
}

.tree-user-item:hover {
  background-color: var(--color-bg-primary);
  cursor: pointer;
}

.tree-user-item-icon {
  margin-right: var(--space-3);
  color: var(--color-text-muted);
  font-size: var(--font-size-sm);
  width: 16px;
  text-align: center;
}

.tree-user-item-content {
  flex: 1;
  display: flex;
  align-items: center;
  gap: var(--space-2);
  min-width: 0;
}

.tree-user-item-name {
  font-weight: var(--font-weight-medium);
  color: var(--color-text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: flex;
  align-items: center;
  gap: var(--space-1);
}

/* Tree user badge styles */
.tree-user-item-name .user-badge {
  flex-shrink: 0;
  margin-left: var(--space-1);
  vertical-align: middle;
}

.tree-user-item .user-badge {
  display: inline-flex !important;
  width: 22px;
  height: 18px;
  margin-left: var(--space-1);
}

.tree-user-item .user-badge i {
  font-size: var(--font-size-xs);
}

.tree-user-item-info {
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tree-user-item-note {
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
  font-style: italic;
  margin-left: var(--space-2);
}

.tree-user-item-status {
  margin-left: auto;
  flex-shrink: 0;
}

.tree-user-item-status-icon {
  font-size: var(--font-size-lg);
}

/* Use the same styling approach as the tree node stats */
.tree-user-item-status-icon.fas.fa-check-circle {
  color: var(--color-success);
}

.tree-user-item-status-icon.fas.fa-times-circle {
  color: var(--color-error);
}

.tree-user-item-status-icon.fas.fa-question-circle {
  color: var(--color-text-muted);
}

/* Ensure status-based classes also work */
.tree-user-item-status-icon.status-attending {
  color: var(--color-success);
}

.tree-user-item-status-icon.status-not-attending {
  color: var(--color-error);
}

.tree-user-item-status-icon.status-no-response {
  color: var(--color-text-muted);
}

/* Tree Depth Levels */

/* Root level - rehearsal */
.tree-node.tree-depth-0 > .tree-node-header {
  background: linear-gradient(135deg, var(--color-bg-primary) 0%, var(--color-bg-secondary) 100%);
  font-weight: var(--font-weight-semibold);
  font-size: var(--font-size-lg);
}

/* Section level */
.tree-node.tree-depth-1 > .tree-node-header {
  padding-left: var(--space-8);
  font-size: var(--font-size-base);
}

/* Instrument level */
.tree-node.tree-depth-2 > .tree-node-header {
  padding-left: var(--space-12);
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
}

/* Sub-instrument level */
.tree-node.tree-depth-3 > .tree-node-header {
  padding-left: var(--space-16);
  font-size: var(--font-size-sm);
  color: var(--color-text-muted);
}

/* Responsive Design */
@media (max-width: 768px) {
  .tree-node-header {
    padding: var(--space-3) var(--space-4);
    font-size: var(--font-size-sm);
  }
  
  .tree-node.tree-depth-0 > .tree-node-header {
    font-size: var(--font-size-base);
  }
  
  .tree-node.tree-depth-1 > .tree-node-header {
    padding-left: var(--space-6);
  }
  
  .tree-node.tree-depth-2 > .tree-node-header {
    padding-left: var(--space-8);
  }
  
  .tree-node.tree-depth-3 > .tree-node-header {
    padding-left: var(--space-10);
  }
  
  .tree-user-item {
    padding: var(--space-2) var(--space-4);
  }
  
  .tree-node-stats {
    gap: var(--space-2);
  }
}

@media (max-width: 480px) {
  .tree-node-subtitle {
    display: none;
  }
  
  .tree-node-stats {
    gap: var(--space-1);
  }
  
  .tree-node-stat {
    font-size: var(--font-size-sm);
  }
  
  .tree-node-stat-icon {
    font-size: var(--font-size-sm);
  }
  
  .tree-user-item-info {
    font-size: var(--font-size-xs);
  }
  
  .tree-user-item-status-icon {
    font-size: var(--font-size-base);
  }
  
  /* Mobile user badge responsive sizing */
  .user-badge {
    width: 20px;
    height: 16px;
    font-size: 8px;
  }
}

/* Animation for expand/collapse */
@keyframes tree-expand {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.tree-node-content.expanding {
  animation: tree-expand var(--transition-base) ease-out;
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
$treeData = $treeData ?? [];
$maxDepth = $maxDepth ?? 3;
$showIcons = $showIcons ?? true;
$expandable = $expandable ?? true;

// This component would contain tree rendering logic
// For now, it's primarily used for styles-only mode
?>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Load just the styles -->
 * <?php 
 * $renderComponent = false;
 * include __DIR__ . '/tree-view.php'; 
 * ?>
 * 
 * <!-- Render with data -->
 * <?php 
 * $treeData = [
 *     ['id' => 1, 'title' => 'Root Node', 'children' => [...]]
 * ];
 * include __DIR__ . '/tree-view.php'; 
 * ?>
 */
?>
