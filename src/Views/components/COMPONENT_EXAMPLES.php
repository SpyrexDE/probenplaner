<?php
/**
 * Component Examples - Demonstrating the New Tailwind + Minimal CSS Approach
 * 
 * This file shows how to use the new component system that replaces
 * the old 934-line theme files with component-colocated styling.
 */
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Component Examples - Probenplaner</title>
    
    <!-- Load the minimal theme CSS (only ~257 lines now!) -->
    <link rel="stylesheet" href="/assets/css/themes/theme-default.css">
    
    <!-- Tailwind for utility classes -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <div class="max-w-4xl mx-auto p-8 space-y-8">
        <h1 class="text-3xl font-bold mb-8" style="color: var(--color-text-primary);">
            🎯 New Component System Examples
        </h1>
        
        <!-- Button Examples -->
        <section>
            <h2 class="text-2xl font-semibold mb-4" style="color: var(--color-text-primary);">Buttons</h2>
            <div class="flex flex-wrap gap-4 mb-4">
                <?php 
                // Primary button with sophisticated gradient
                $type = 'primary'; $text = 'Primary Button'; $size = 'md';
                include __DIR__ . '/button.php'; 
                ?>
                
                <?php 
                // Success button with icon
                $type = 'success'; $text = 'Save Changes'; $icon = 'fas fa-save'; $size = 'md';
                include __DIR__ . '/button.php'; 
                ?>
                
                <?php 
                // Outline button
                $type = 'outline'; $text = 'Outline Style'; $size = 'md';
                include __DIR__ . '/button.php'; 
                ?>
                
                <?php 
                // Small danger button
                $type = 'danger'; $text = 'Delete'; $size = 'sm';
                include __DIR__ . '/button.php'; 
                ?>
            </div>
            
            <div class="flex gap-4">
                <?php 
                // Icon-only buttons
                $iconOnly = true; $icon = 'fas fa-edit'; $type = 'ghost'; $size = 'sm';
                include __DIR__ . '/button.php';
                unset($iconOnly);
                ?>
                
                <?php 
                $iconOnly = true; $icon = 'fas fa-trash'; $type = 'danger'; $size = 'sm';
                include __DIR__ . '/button.php';
                unset($iconOnly);
                ?>
            </div>
        </section>

        <!-- Form Examples -->
        <section>
            <h2 class="text-2xl font-semibold mb-4" style="color: var(--color-text-primary);">Forms</h2>
            
            <?php 
            // CRITICAL: These preserve .form-input-modern and .form-group-modern for JavaScript!
            ob_start();
            ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <?php 
                    $type = 'text'; $name = 'username'; $label = 'Username'; 
                    $required = true; $value = 'john_doe';
                    include __DIR__ . '/form-input.php'; 
                    ?>
                    
                    <?php 
                    $type = 'email'; $name = 'email'; $label = 'Email Address'; 
                    $required = true; $value = 'john@example.com';
                    include __DIR__ . '/form-input.php'; 
                    ?>
                </div>
                
                <div>
                    <?php 
                    $type = 'select'; $name = 'instrument'; $label = 'Instrument';
                    $options = [
                        'violin' => 'Violin',
                        'viola' => 'Viola', 
                        'cello' => 'Cello',
                        'bass' => 'Double Bass'
                    ];
                    $value = 'violin';
                    include __DIR__ . '/form-input.php'; 
                    ?>
                    
                    <?php 
                    $type = 'textarea'; $name = 'notes'; $label = 'Notes'; 
                    $value = 'Some sample notes...';
                    include __DIR__ . '/form-input.php'; 
                    ?>
                </div>
            </div>
            
            <?php 
            $formContent = ob_get_clean();
            ?>
        </section>

        <!-- Card Examples -->
        <section>
            <h2 class="text-2xl font-semibold mb-4" style="color: var(--color-text-primary);">Cards</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php 
                // Simple card
                $header = 'User Profile';
                $content = '<p class="mb-4">This is a card with header and sophisticated hover effects.</p>' . $formContent;
                $hover = true;
                include __DIR__ . '/card.php'; 
                ?>
                
                <?php 
                // Interactive card with footer
                $header = 'Rehearsal Details';
                $content = '<p class="mb-2"><strong>Date:</strong> 2024-01-15</p>
                          <p class="mb-2"><strong>Time:</strong> 19:00 - 21:30</p>
                          <p><strong>Location:</strong> Main Hall</p>';
                $footer = 'Click to view full details';
                $interactive = true;
                $hover = true;
                $onclick = 'alert("Card clicked!")';
                include __DIR__ . '/card.php'; 
                ?>
            </div>
        </section>

        <!-- Dropdown Examples -->
        <section>
            <h2 class="text-2xl font-semibold mb-4" style="color: var(--color-text-primary);">Dropdowns</h2>
            <div class="flex gap-4">
                <?php 
                // CRITICAL: Preserves .dropdown-menu and .dropdown-item for dropdown.js!
                $triggerText = 'User Options';
                $items = [
                    ['text' => 'View Profile', 'onclick' => 'alert("View Profile")'],
                    ['text' => 'Edit Settings', 'onclick' => 'alert("Edit Settings")'],
                    'divider',
                    ['text' => 'Logout', 'onclick' => 'alert("Logout")', 'class' => 'text-red-600']
                ];
                include __DIR__ . '/dropdown.php'; 
                ?>
                
                <?php 
                $triggerText = 'Actions';
                $triggerIcon = 'fas fa-cog';
                $items = [
                    ['text' => 'Create New', 'onclick' => 'alert("Create New")'],
                    ['text' => 'Import Data', 'onclick' => 'alert("Import")'],
                    ['text' => 'Export Data', 'onclick' => 'alert("Export")']
                ];
                include __DIR__ . '/dropdown.php'; 
                ?>
            </div>
        </section>

        <!-- Collapse Examples -->
        <section>
            <h2 class="text-2xl font-semibold mb-4" style="color: var(--color-text-primary);">Collapsible Content</h2>
            
            <?php 
            // CRITICAL: Preserves .collapse class for collapse.js!
            $triggerText = 'Advanced Settings';
            $variant = 'card';
            $content = '
                <div class="space-y-4">
                    <p>These are advanced configuration options.</p>
                    <div class="grid grid-cols-2 gap-4">
                        <button class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border transition-colors duration-200" 
                                style="color: var(--color-text-primary); background-color: var(--color-bg-primary); border-color: var(--color-border);">
                            Option A
                        </button>
                        <button class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border transition-colors duration-200" 
                                style="color: var(--color-text-primary); background-color: var(--color-bg-primary); border-color: var(--color-border);">
                            Option B  
                        </button>
                    </div>
                </div>
            ';
            include __DIR__ . '/collapse.php'; 
            ?>
        </section>

        <!-- Theme Demo -->
        <section>
            <h2 class="text-2xl font-semibold mb-4" style="color: var(--color-text-primary);">Theme Variables Demo</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-4 rounded-lg text-center text-white font-semibold" 
                     style="background-color: var(--color-primary);">
                    Primary
                </div>
                <div class="p-4 rounded-lg text-center text-white font-semibold" 
                     style="background-color: var(--color-secondary);">
                    Secondary
                </div>
                <div class="p-4 rounded-lg text-center text-white font-semibold" 
                     style="background-color: var(--color-success);">
                    Success
                </div>
                <div class="p-4 rounded-lg text-center text-white font-semibold" 
                     style="background-color: var(--color-error);">
                    Error
                </div>
            </div>
            <p class="mt-4 text-sm" style="color: var(--color-text-secondary);">
                💡 <strong>These colors automatically change when switching themes!</strong> 
                The theme system now uses only ~257 lines instead of 934 lines per theme.
            </p>
        </section>

        <!-- Migration Guide -->
        <section class="mt-12">
            <h2 class="text-2xl font-semibold mb-4" style="color: var(--color-text-primary);">🔄 Migration Guide</h2>
            
            <?php 
            $header = 'Old vs New Approach';
            $content = '
                <div class="space-y-6">
                    <div>
                        <h4 class="font-semibold mb-2 text-red-600">❌ OLD WAY (Before Refactoring):</h4>
                        <pre class="bg-gray-100 p-3 rounded text-sm overflow-x-auto"><code>&lt;button class="btn-base btn-primary"&gt;Save&lt;/button&gt;
&lt;div class="form-group-modern"&gt;
    &lt;input class="form-input-modern" type="text"&gt;
&lt;/div&gt;</code></pre>
                        <p class="text-sm mt-2 text-red-600">Problems: 934 lines per theme, massive redundancy</p>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold mb-2 text-green-600">✅ NEW WAY (After Refactoring):</h4>
                        <pre class="bg-gray-100 p-3 rounded text-sm overflow-x-auto"><code>&lt;?php 
$type = \'primary\'; $text = \'Save\'; 
include __DIR__ . \'/components/button.php\'; 
?&gt;

&lt;?php 
$name = \'username\'; $label = \'Username\'; 
include __DIR__ . \'/components/form-input.php\'; 
?&gt;</code></pre>
                        <p class="text-sm mt-2 text-green-600">Benefits: ~257 lines per theme, zero redundancy, Tailwind utilities</p>
                    </div>
                </div>
            ';
            include __DIR__ . '/card.php'; 
            ?>
        </section>
    </div>

    <script>
        // Demo the form interaction that depends on preserved class names
        document.addEventListener('DOMContentLoaded', function() {
            // This JavaScript depends on the preserved .form-input-modern and .form-group-modern classes!
            document.querySelectorAll('.form-input-modern').forEach(input => {
                input.addEventListener('focus', function() {
                    this.closest('.form-group-modern').classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.closest('.form-group-modern').classList.remove('focused');
                    if (this.value) {
                        this.closest('.form-group-modern').classList.add('filled');
                    } else {
                        this.closest('.form-group-modern').classList.remove('filled');
                    }
                });
                
                // Set initial state
                if (input.value) {
                    input.closest('.form-group-modern').classList.add('filled');
                }
            });
        });
    </script>
</body>
</html>
