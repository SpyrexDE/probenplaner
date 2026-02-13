<?php
/**
 * Top Navigation Component
 * 
 * Usage:
 * <?php 
 * $title = 'Probenplaner';
 * $showMenuToggle = true;
 * $actions = [...]; // Navigation action buttons
 * include __DIR__ . '/top-navigation.php'; 
 * ?>
 * 
 * Or styles-only mode:
 * <?php 
 * $renderComponent = false; // Just load styles
 * include __DIR__ . '/top-navigation.php'; 
 * ?>
 */
?>

<style>
/* TOP NAVIGATION COMPONENT */
.top-nav {
  /* Layout */
  display: flex;
  align-items: center;
  justify-content: space-between;
  transition: all var(--transition-slow);
  
  /* Positioning and visual styles */
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: var(--navbar-height);
  background: rgba(255, 255, 255, 0.90);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 
    0 2px 20px rgba(0, 0, 0, 0.08),
    0 1px 3px rgba(0, 0, 0, 0.05),
    var(--shadow-md);
  z-index: var(--z-sticky);
  padding: var(--space-3) var(--space-5);
}

.top-nav *:focus {
  position: relative;
  z-index: 1;
}

.top-nav:focus-within {
  box-shadow: 
    0 2px 20px rgba(0, 0, 0, 0.08),
    0 1px 3px rgba(0, 0, 0, 0.05),
    var(--shadow-md);
}

.top-nav-left {
  display: flex;
  align-items: center;
  gap: var(--space-4);
}

.top-nav-title {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-size: 20px;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: #1a1a1a;
  background: linear-gradient(135deg, #1a1a1a 0%, #4a5568 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.top-nav-menu-toggle {
  background: none;
  border: none;
  font-size: var(--font-size-xl);
  cursor: pointer;
  padding: var(--space-3);
  border-radius: var(--radius-base);
  transition: all var(--transition-base);
  min-width: 48px;
  min-height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.top-nav-actions {
  display: flex;
  align-items: center;
  gap: var(--space-1);
}

.top-nav-icon {
  font-size: var(--font-size-xl);
  cursor: pointer;
  transition: all var(--transition-base);
  padding: var(--space-2);
  border-radius: var(--radius-base);
}

/* === PAGE CONTENT === */
.page-content {
  flex: 1;
  padding-top: var(--navbar-height);
  min-height: 100vh;
  transition: margin-left var(--transition-slow);
}

/* === SIDEBAR TOGGLE STATES === */
#wrapper.toggled .sidebar {
  transform: translateX(0);
}

#wrapper.toggled .page-content {
  margin-left: var(--sidebar-width);
}

#wrapper.toggled .sidebar-overlay {
  opacity: 1;
  pointer-events: auto;
}

.sidebar-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: var(--color-bg-overlay);
  z-index: calc(var(--z-fixed) - 1);
  opacity: 0;
  pointer-events: none;
  transition: opacity var(--transition-base);
}

@media (min-width: 900px) {
  .sidebar-overlay {
    display: none;
  }
}

/* Desktop behavior - sidebar always visible, so adjust nav position */
@media (min-width: 900px) {
  .top-nav {
    left: var(--sidebar-width) !important;
    width: calc(100% - var(--sidebar-width)) !important;
    padding-left: var(--space-5) !important;
    transition: left var(--transition-slow), width var(--transition-slow) !important;
  }
  
  /* When manually toggled OFF on desktop, return nav to full width */
  #wrapper.toggled .top-nav {
    left: 0 !important;
    width: 100% !important;
  }

  /* When toggled on desktop, hide the sidebar and reset content margin */
  #wrapper.toggled .sidebar {
    transform: translateX(-100%);
  }
  #wrapper.toggled .page-content {
    margin-left: 0;
  }
}

/* Mobile behavior - hide title when sidebar is open */
@media (max-width: 899px) {
  #wrapper.toggled .top-nav-left {
    display: none;
  }
}

/* Show actions on the right when sidebar is toggled */
#wrapper.toggled .top-nav-actions {
  display: flex;
  margin-left: auto;
}

/* Ensure title is never hidden on desktop */
@media (min-width: 900px) {
  #wrapper.toggled .top-nav-left {
    display: flex !important;
  }
}

/* Mobile responsive behavior for navigation title */
@media (max-width: 640px) {
  .top-nav {
    padding: var(--space-2) var(--space-4);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
  }
  
  .top-nav-title {
    font-size: 18px;
    font-weight: 800;
    letter-spacing: -0.01em;
  }
}

@media (max-width: 480px) {
  .top-nav {
    padding: var(--space-2) var(--space-3);
  }
  
  .top-nav-left {
    gap: var(--space-3);
  }
  
  .top-nav-title {
    font-size: 16px;
    font-weight: 800;
    letter-spacing: 0;
  }
}

/* Mobile sidebar behavior */
@media (max-width: 899px) {
  .sidebar {
    transform: translateX(-100%);
  }
  
  #wrapper.toggled .sidebar {
    transform: translateX(0);
  }
  
  #wrapper.toggled .page-content {
    margin-left: 0;
  }
  
  #wrapper.toggled .sidebar-overlay {
    opacity: 1;
    pointer-events: auto;
  }
}

.page-content-inner {
  padding: var(--space-2);
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
$title = $title ?? 'Probenplaner';
$showMenuToggle = $showMenuToggle ?? true;
$actions = $actions ?? [];
$class = $class ?? '';

// Build classes
$classes = ['top-nav'];
if ($class) $classes[] = $class;

$classString = implode(' ', $classes);
?>

<nav class="<?= $classString ?>">
    <div class="top-nav-left">
        <?php if ($showMenuToggle): ?>
            <button class="top-nav-menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
        <?php endif; ?>
        
        <div class="top-nav-title">
            <?= htmlspecialchars($title) ?>
        </div>
    </div>
    
    <div class="top-nav-actions">
        <?php foreach ($actions as $action): ?>
            <?php if (isset($action['onclick'])): ?>
                <i onclick="<?= htmlspecialchars($action['onclick']) ?>" 
                   class="<?= htmlspecialchars($action['icon'] ?? 'fas fa-question') ?> top-nav-icon"></i>
            <?php else: ?>
                <a href="<?= htmlspecialchars($action['href'] ?? '#') ?>" 
                   class="<?= htmlspecialchars($action['icon'] ?? 'fas fa-question') ?> top-nav-icon"></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</nav>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Basic navigation -->
 * <?php 
 * $title = 'My App';
 * $actions = [
 *     ['icon' => 'fas fa-user', 'href' => '/profile'],
 *     ['icon' => 'fas fa-cog', 'onclick' => 'openSettings()']
 * ];
 * include __DIR__ . '/top-navigation.php'; 
 * ?>
 * 
 * <!-- Just load styles -->
 * <?php 
 * $renderComponent = false;
 * include __DIR__ . '/top-navigation.php'; 
 * ?>
 */
?>
