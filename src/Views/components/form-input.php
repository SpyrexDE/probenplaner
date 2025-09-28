<?php
/**
 * Form Input Component - Tailwind utility classes + minimal custom styles
 * CRITICAL: Preserves .form-input-modern and .form-group-modern class names for JavaScript
 * 
 * Usage examples:
 * <?php 
 * $type = 'text'; $name = 'username'; $label = 'Username'; $required = true;
 * include __DIR__ . '/form-input.php'; 
 * ?>
 * 
 * <?php 
 * $type = 'email'; $name = 'email'; $label = 'Email Address'; $value = $user['email']; 
 * include __DIR__ . '/form-input.php'; 
 * ?>
 */
?>

<style>
/* FORM INPUT COMPONENT - All form-related styles colocated */

/* === FORM CONTAINERS === */
.form-container {
    max-width: 42rem;
    margin: 0 auto;
    padding: 0 var(--space-4);
}

.form {
    background-color: var(--color-bg-primary);
    border-radius: var(--radius-xl);
    padding: var(--space-8);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--color-border);
}

.form {
    box-shadow: var(--shadow-lg) !important;
}

/* === FORM GROUPS AND LAYOUT === */
.form-group {
    margin-bottom: var(--space-6);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-4);
    margin-bottom: var(--space-6);
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: var(--space-3);
    }
}

/* === FORM LABELS === */
.form-label {
    display: block;
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-primary);
    margin-bottom: var(--space-2);
    font-size: var(--font-size-base);
}

/* === FORM INPUTS === */
.form-input {
    width: 100%;
    padding: var(--space-3) var(--space-4);
    border: 2px solid var(--color-border);
    border-radius: var(--radius-base);
    font-size: var(--font-size-base);
    color: var(--color-text-primary);
    background: var(--color-white);
    transition: all var(--transition-fast);
}

.form-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px var(--color-primary-100);
}

.form-input::placeholder {
    color: var(--color-text-muted);
}

/* === FORM SECTIONS === */
.form-section {
    margin-bottom: var(--space-8);
    padding: var(--space-6);
    background-color: var(--color-bg-secondary);
    border-radius: var(--radius-lg);
    border: 1px solid var(--color-border-light);
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.form-section-header {
    margin-bottom: var(--space-3);
}

.form-section-title {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-primary);
    margin-bottom: var(--space-4);
    padding-bottom: var(--space-2);
    border-bottom: 1px solid var(--color-border);
}

.form-section-description {
    font-size: var(--font-size-sm);
    color: var(--color-text-secondary);
    margin: 0;
}

/* === FORM ACTIONS === */
.form-actions {
    display: flex;
    gap: var(--space-4);
    justify-content: flex-end;
    margin-top: var(--space-8);
    padding-top: var(--space-6);
    border-top: 1px solid var(--color-border);
}

/* === MODERN FORM COMPONENTS (for advanced forms) === */
.form-group-modern {
    margin-bottom: var(--space-3);
    position: relative;
}

.form-label-modern {
    display: flex;
    align-items: center;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    color: var(--color-text-primary);
    margin-bottom: var(--space-2);
    transition: color var(--transition-base);
}

.form-label-icon {
    width: 16px;
    height: 16px;
    margin-right: var(--space-2);
    color: var(--color-text-secondary);
    transition: color var(--transition-base);
}

.form-group-modern.focused .form-label-modern,
.form-group-modern.focused .form-label-icon {
    color: var(--color-primary);
}

.form-input-modern {
    width: 100%;
    padding: var(--space-4) var(--space-5);
    font-size: var(--font-size-base);
    line-height: var(--line-height-normal);
    color: var(--color-text-primary);
    background: var(--color-white);
    border: 2px solid var(--color-border);
    border-radius: var(--radius-lg);
    transition: all var(--transition-base);
}

.form-input-modern:hover {
    border-color: var(--color-primary-200);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.form-input-modern:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(71, 140, 244, 0.1), 0 2px 6px rgba(0, 0, 0, 0.08);
    transform: translateY(-1px);
}

.form-input-modern.error {
    border-color: var(--color-error);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1), 0 2px 6px rgba(239, 68, 68, 0.08);
}

.form-group-modern.focused .form-input-modern {
    box-shadow: 0 4px 16px rgba(71, 140, 244, 0.15);
    border-color: var(--color-primary);
}

.form-group-modern.filled .form-input-modern {
    background-color: var(--color-bg-secondary);
}

.form-group-modern.error .form-input-modern {
    border-color: var(--color-error);
    box-shadow: 0 4px 16px rgba(239, 68, 68, 0.15);
}

/* Special handling for login forms to prevent card shadow interference */
.login-form .form-input-modern:focus {
    box-shadow: 0 0 0 3px rgba(71, 140, 244, 0.1), 0 4px 16px rgba(71, 140, 244, 0.15);
}

/* === FORM HELPERS === */
.form-help-text {
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
    margin-top: var(--space-1);
}

/* === FORM CHECKBOX GROUPS === */
.form-checkbox-group {
    margin-left: 0;
}

.form-checkbox-group .custom-checkbox {
    margin-bottom: var(--space-1);
}

.form-checkbox-group.main-group .custom-checkbox label {
    font-weight: var(--font-weight-semibold);
    font-size: var(--text-lg);
}

.form-checkbox-group.tutti .custom-checkbox label {
    font-weight: var(--font-weight-bold);
    font-size: var(--text-lg);
    color: var(--color-primary);
}

/* === SOPHISTICATED MODERN CARDS === */
.modern-card {
    background: var(--color-bg-primary);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-xl);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 10px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    position: relative;
}


.modern-card:focus-within {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 10px 15px rgba(0, 0, 0, 0.1) !important;
    border-color: var(--color-primary-200);
}

.modern-card-header {
    background: linear-gradient(135deg, var(--color-gray-50) 0%, var(--color-white) 100%);
    border-bottom: 1px solid var(--color-border);
    padding: var(--space-5);
    position: relative;
}

.modern-card-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, var(--color-border) 50%, transparent 100%);
}

.modern-card-body {
    padding: var(--space-5);
}

.modern-card-footer {
    padding: var(--space-4) var(--space-5);
    background: var(--color-bg-secondary);
    border-top: 1px solid var(--color-border);
}

.modern-card-danger {
    border-color: var(--color-error-200);
    box-shadow: 0 4px 6px rgba(239, 68, 68, 0.05), 0 10px 15px rgba(239, 68, 68, 0.1);
}


.modern-card-danger .modern-card-header {
    background: linear-gradient(135deg, var(--color-error-50) 0%, #fef2f2 100%);
    border-bottom-color: var(--color-error-200);
}

/* === SOPHISTICATED LOGIN STYLES === */
.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: var(--space-4);
    position: relative;
}

/* Logo styling */
.login-logo {
    text-align: center;
    margin-bottom: var(--space-8);
    position: relative;
    z-index: 2;
}

.login-logo img {
    width: 80px;
    height: 80px;
    object-fit: contain;
    margin: 0 auto;
    display: block;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
}

/* Form text/helper text */
.form-text {
    font-size: var(--font-size-xs);
    color: var(--color-gray-600);
    margin-top: var(--space-1);
    margin-bottom: var(--space-4);
    text-align: center;
}

.login-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 20% 20%, rgba(71, 140, 244, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(244, 71, 107, 0.1) 0%, transparent 50%);
    pointer-events: none;
}

.login-form {
    width: 100%;
    max-width: 340px;
    background: var(--color-white);
    padding: var(--space-8);
    border-radius: var(--radius-xl);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    position: relative;
    z-index: 1;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.login-form::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
    border-radius: var(--radius-xl);
    pointer-events: none;
}

.login-input {
    width: 100%;
    padding: var(--space-3) var(--space-4);
    border: 2px solid var(--color-border);
    border-radius: var(--radius-lg);
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-normal);
    color: var(--color-text-primary);
    background: var(--color-white);
    transition: all var(--transition-base);
    margin-bottom: var(--space-4);
    min-height: 44px;
    position: relative;
}

.login-input:hover {
    border-color: var(--color-primary-200);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-1px);
}

.login-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(71, 140, 244, 0.1), 0 8px 20px rgba(71, 140, 244, 0.15);
    transform: translateY(-2px);
}

.login-button {
    width: 100%;
    height: 44px;
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
    color: var(--color-white);
    border: none;
    border-radius: var(--radius-lg);
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    cursor: pointer;
    transition: all var(--transition-base);
    margin: var(--space-4) 0;
    box-shadow: 0 4px 14px rgba(71, 140, 244, 0.4);
    position: relative;
    overflow: hidden;
}

.login-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.login-button:hover::before {
    left: 100%;
}

.login-button:hover {
    background: linear-gradient(135deg, var(--color-primary-dark) 0%, #1e40af 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(71, 140, 244, 0.5);
}

.login-button:active {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(71, 140, 244, 0.4);
}

/* Auth links styling */
.auth-links {
    text-align: center;
    border-top: 1px solid var(--color-gray-200);
    padding-top: var(--space-2);
    margin-top: var(--space-4);
    position: relative;
    z-index: 2;
}

.auth-link {
    color: var(--color-text-secondary);
    text-decoration: none;
    transition: color var(--transition-base);
}

.auth-link:hover {
    color: var(--color-text-primary);
}

.auth-link-primary {
    color: var(--color-primary);
    font-weight: var(--font-weight-semibold);
}

.auth-link-secondary {
    display: block;
    margin-top: var(--space-2);
    font-size: var(--font-size-xs);
}

/* === LAYOUT COMPONENTS === */
.container-fluid-themed {
    width: 100%;
    padding: var(--space-2) var(--space-2) var(--space-16);
    max-width: 800px;
    margin: 0 auto;
}

.page-header {
    padding: var(--space-6) 0 var(--space-4);
    text-align: center;
    position: relative;
}

.page-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 2px;
    background: linear-gradient(90deg, var(--color-primary), var(--color-secondary));
    border-radius: var(--radius-full);
}

.page-subtitle {
    font-size: var(--font-size-lg);
    color: var(--color-text-secondary);
    margin: 0;
    font-weight: var(--font-weight-normal);
    line-height: 1.4;
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
$type = $type ?? 'text';
$name = $name ?? '';
$id = $id ?? $name;
$label = $label ?? '';
$value = $value ?? '';
$placeholder = $placeholder ?? '';
$required = $required ?? false;
$disabled = $disabled ?? false;
$error = $error ?? '';
$helperText = $helperText ?? '';
$size = $size ?? 'md';

// Size classes
$sizeClasses = [
    'sm' => 'h-10 px-3 py-2 text-sm',
    'md' => 'h-12 px-5 py-4 text-base',
    'lg' => 'h-14 px-6 py-5 text-lg'
];

$labelSizeClasses = [
    'sm' => 'text-xs',
    'md' => 'text-sm', 
    'lg' => 'text-base'
];

// Build attributes
$attributes = '';
if ($required) $attributes .= ' required';
if ($disabled) $attributes .= ' disabled';
if ($placeholder) $attributes .= ' placeholder="' . htmlspecialchars($placeholder) . '"';
if (isset($autocomplete)) $attributes .= ' autocomplete="' . htmlspecialchars($autocomplete) . '"';
if (isset($readonly) && $readonly) $attributes .= ' readonly';

// Tailwind classes for form inputs - CRITICAL: Must include .form-input-modern for JavaScript
$inputClasses = "form-input-base form-input-modern w-full leading-normal border-2 rounded-md transition-all duration-200";
$inputClasses .= " focus:outline-none focus:ring-0 " . $sizeClasses[$size];
$inputClasses .= $disabled ? " opacity-50 cursor-not-allowed" : "";

// Use CSS variables for colors that change with themes
$inputStyle = "color: var(--color-text-primary); background: var(--color-bg-primary); border-color: var(--color-border);";

// Container classes - CRITICAL: Must include .form-group-modern for JavaScript  
$containerClasses = "form-group form-group-modern mb-6 relative";
if ($error) $containerClasses .= " error";
?>

<div class="<?= $containerClasses ?>">
    <?php if ($label): ?>
        <label for="<?= htmlspecialchars($id) ?>" 
               class="block mb-2 font-semibold transition-colors duration-200 <?= $labelSizeClasses[$size] ?>"
               style="color: var(--color-text-primary);">
            <?= htmlspecialchars($label) ?>
            <?php if ($required): ?>
                <span class="text-red-500 ml-1">*</span>
            <?php endif; ?>
        </label>
    <?php endif; ?>
    
    <?php if ($type === 'textarea'): ?>
        <textarea 
            id="<?= htmlspecialchars($id) ?>"
            name="<?= htmlspecialchars($name) ?>"
            class="<?= $inputClasses ?> min-h-[120px] resize-vertical"
            style="<?= $inputStyle ?>"
            <?= $attributes ?>
        ><?= htmlspecialchars($value) ?></textarea>
    <?php elseif ($type === 'select' && isset($options)): ?>
        <select 
            id="<?= htmlspecialchars($id) ?>"
            name="<?= htmlspecialchars($name) ?>"
            class="<?= $inputClasses ?>"
            style="<?= $inputStyle ?>"
            <?= $attributes ?>
        >
            <?php if ($placeholder): ?>
                <option value=""><?= htmlspecialchars($placeholder) ?></option>
            <?php endif; ?>
            <?php foreach ($options as $optionValue => $optionLabel): ?>
                <option value="<?= htmlspecialchars($optionValue) ?>" 
                        <?= $value == $optionValue ? 'selected' : '' ?>>
                    <?= htmlspecialchars($optionLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php else: ?>
        <input 
            type="<?= htmlspecialchars($type) ?>"
            id="<?= htmlspecialchars($id) ?>"
            name="<?= htmlspecialchars($name) ?>"
            value="<?= htmlspecialchars($value) ?>"
            class="<?= $inputClasses ?>"
            style="<?= $inputStyle ?>"
            <?= $attributes ?>
        />
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="mt-2 text-sm" style="color: var(--color-error);">
            <i class="fas fa-exclamation-circle mr-1"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($helperText && !$error): ?>
        <div class="mt-2 text-sm" style="color: var(--color-text-secondary);">
            <?= htmlspecialchars($helperText) ?>
        </div>
    <?php endif; ?>
</div>
