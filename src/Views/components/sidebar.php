<?php
/**
 * Sidebar Component - CONSOLIDATED VERSION  
 * Now uses utilities.css for common patterns + component-specific navigation styling
 * 
 * Base classes: Uses bg-white flex-col shadow-xl transition
 * Custom: Unique sidebar positioning, navigation styling, responsive behavior
 * 
 * Usage:
 * <?php include __DIR__ . '/sidebar.php'; ?>
 * 
 * Or styles-only mode:
 * <?php 
 * $renderComponent = false; // Just load styles
 * include __DIR__ . '/sidebar.php'; 
 * ?>
 */
?>

<style>
/* SIDEBAR COMPONENT */
.sidebar {
  /* Layout preservation */
  width: var(--sidebar-width);
  background-color: var(--color-white);
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  z-index: var(--z-fixed);
  transition: transform var(--transition-slow);
  overflow-y: auto;
  box-shadow: var(--shadow-xl);
  border-right: 1px solid var(--color-border-light);
  transform: translateX(-100%);
  scrollbar-width: thin;
  scrollbar-color: var(--color-gray-300) transparent;
  display: flex;
  flex-direction: column;
}

.sidebar::-webkit-scrollbar {
  width: 6px;
}

.sidebar::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar::-webkit-scrollbar-thumb {
  background-color: var(--color-gray-300);
  border-radius: var(--radius-full);
}

.sidebar::-webkit-scrollbar-thumb:hover {
  background-color: var(--color-gray-400);
}

@media (min-width: 900px) {
  .sidebar {
    transform: translateX(0);
    position: fixed;
  }

  .main-content-with-sidebar {
    margin-left: var(--sidebar-width);
  }
}

.sidebar.open {
  transform: translateX(0);
}

.sidebar-header {
  padding: var(--space-3) var(--space-3);
  border-bottom: 1px solid var(--color-border-light);
}

.sidebar-user {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.sidebar-avatar {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-white);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-bold);
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(59, 130, 246, 0.15);
}

.sidebar-info {
  flex: 1;
  min-width: 0;
}

.sidebar-name {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
  margin-bottom: var(--space-1);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar-details {
  font-size: var(--font-size-xs);
  color: var(--color-text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.3;
}

.sidebar-details .orchestra {
  color: var(--color-primary);
  font-weight: var(--font-weight-semibold);
}

.sidebar-details .roles {
  color: var(--color-warning-dark);
  font-weight: var(--font-weight-medium);
}

.sidebar-stats {
  padding: var(--space-4);
  background: linear-gradient(31deg, var(--color-bg-tertiary), var(--color-primary-100));
  border-bottom: 1px solid var(--color-border-light);
  border-radius: var(--radius-md);
  margin: var(--space-2);
  box-shadow: var(--shadow-sm);
}

.sidebar-stats-title {
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.sidebar-stats-refresh {
  background: none;
  border: none;
  color: var(--color-text-secondary);
  cursor: pointer;
  padding: var(--space-2);
  border-radius: var(--radius-sm);
  transition: all var(--transition-base);
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
}

.sidebar-stats-refresh:hover {
  background-color: var(--color-bg-tertiary);
  color: var(--color-text-primary);
}

.sidebar-stats-refresh:active {
  transform: scale(0.95);
}

.sidebar-stats-refresh i {
  font-size: var(--font-size-sm);
}

.sidebar-stats-bar {
  height: 4px;
  background-color: var(--color-gray-100);
  border-radius: var(--radius-full);
  overflow: hidden;
  display: flex;
  margin-bottom: var(--space-2);
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.06);
}

.sidebar-stats-segment {
  height: 100%;
  transition: all var(--transition-slow);
  width: 0%;
}

.sidebar-stats-segment.no-response {
  width: 100%;
}

.sidebar-stats-segment.attending {
  background-color: var(--color-success);
}

.sidebar-stats-segment.not-attending {
  background-color: var(--color-error);
}

.sidebar-stats-segment.no-response {
  background-color: var(--color-gray-300);
}

.sidebar-stats-legend {
  display: flex;
  justify-content: space-between;
  font-size: var(--font-size-xs);
  color: var(--color-text-primary);
  font-weight: var(--font-weight-medium);
  margin-top: var(--space-2);
}

.sidebar-stats-item {
  display: flex;
  align-items: center;
  gap: var(--space-1);
}

.sidebar-stats-dot {
  width: 8px;
  height: 8px;
  border-radius: var(--radius-full);
  box-shadow: var(--shadow-sm);
}

.sidebar-stats-dot.attending {
  background-color: var(--color-success);
}

.sidebar-stats-dot.not-attending {
  background-color: var(--color-error);
}

.sidebar-stats-dot.no-response {
  background-color: var(--color-gray-300);
}

.sidebar-stats-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--space-3);
}

.sidebar-stats-date {
  font-size: var(--font-size-xs);
  color: var(--color-text-secondary);
  font-weight: var(--font-weight-medium);
}

.sidebar-nav {
  padding: var(--space-3) var(--space-2);
  flex: 1;
  display: flex;
  flex-direction: column;
}

.sidebar-nav-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.sidebar-nav-item {
  margin: 0 0 var(--space-1) 0;
}

.sidebar-nav-link {
  display: flex;
  align-items: center;
  padding: var(--space-2) var(--space-3);
  color: var(--color-text-secondary);
  text-decoration: none;
  transition: all var(--transition-base);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  border-radius: var(--radius-base);
  position: relative;
}

.sidebar-nav-link:hover {
  background: linear-gradient(135deg, var(--color-bg-secondary), var(--color-bg-tertiary));
  color: var(--color-text-primary);
  text-decoration: none;
  transform: translateX(2px);
  box-shadow: var(--shadow-sm);
}

.sidebar-nav-link.active {
  background: linear-gradient(135deg, var(--color-primary-100), var(--color-primary-200));
  color: var(--color-primary-dark);
  font-weight: var(--font-weight-semibold);
  box-shadow: 0 2px 8px var(--color-primary-200);
}

.sidebar-nav-icon {
  margin-right: var(--space-3);
  width: 18px;
  text-align: center;
  font-size: var(--font-size-base);
}

.sidebar-nav-link.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 20px;
  background: linear-gradient(180deg, var(--color-primary), var(--color-primary-dark));
  border-radius: 0 2px 2px 0;
}

.sidebar-footer {
  margin-top: auto;
  padding: var(--space-3) var(--space-4);
  border-top: 1px solid var(--color-border);
}

.sidebar-footer-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-2);
}

.sidebar-version {
  font-size: var(--font-size-xs);
  color: var(--color-text-secondary);
  text-align: center;
  opacity: 0.7;
  line-height: 1.4;
}

.sidebar-legal-dropdown {
  position: relative;
}

.sidebar-legal-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  padding: 0;
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--color-text-secondary);
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-border-light);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all var(--transition-base);
}

.sidebar-legal-btn:hover {
  color: var(--color-text-primary);
  background: var(--color-bg-tertiary);
  border-color: var(--color-border);
}

.sidebar-legal-menu {
  position: fixed;
  min-width: 10rem;
  padding: var(--space-2) 0;
  font-size: var(--font-size-sm);
  background: var(--color-white);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  z-index: calc(var(--z-fixed) + 10);
  opacity: 0;
  pointer-events: none;
  transition: opacity 150ms ease;
}

.sidebar-legal-menu.show {
  opacity: 1;
  pointer-events: auto;
}

.sidebar-legal-menu .dropdown-item {
  display: block;
  padding: var(--space-2) var(--space-3);
  color: var(--color-text-primary);
  text-decoration: none;
  border: 0;
  background: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
  transition: background var(--transition-fast);
}

.sidebar-legal-menu .dropdown-item:hover {
  background: var(--color-bg-tertiary);
}
</style>

<?php
// Styles-only mode check
$renderComponent = $renderComponent ?? true;

if (!$renderComponent) {
    // Styles-only mode: just load the styles and exit
    return;
}

// Component rendering
?>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Load just the styles -->
 * <?php 
 * $renderComponent = false;
 * include __DIR__ . '/sidebar.php'; 
 * ?>
 * 
 * <!-- Render the full component (usually done by layout) -->
 * <?php include __DIR__ . '/sidebar.php'; ?>
 */
?>
