# How to Use Existing Components

## rehearsal-card.php - Context-Aware Rehearsal Display

This is a sophisticated component that adapts based on context and user role. **DO NOT create manual rehearsal HTML - always use this component.**

### Context: 'promises' (User Promise View)
Shows rehearsal from the perspective of a user viewing/editing their promises.

```php
<?php 
$context = 'promises';
$options = [
    'status' => $userPromise['status'] ?? 'pending',
    'note' => $userPromise['note'] ?? '',
    'showButtons' => true
];
include __DIR__ . '/../components/rehearsal-card.php';
?>
```

### Context: 'rehearsals' (Admin Rehearsal Management)
Shows rehearsal from administrator management perspective.

```php
<?php 
$context = 'rehearsals';
$options = ['showButtons' => true];
include __DIR__ . '/../components/rehearsal-card.php';
?>
```

### Advanced Features
- **Smart Group Display**: Uses SmartGroupDisplay service for intelligent group formatting
- **Rehearsal Type Badges**: Automatically applies correct badges via RehearsalTypeManager
- **Conditional UI**: Shows different buttons/actions based on user role and context
- **Advanced Date/Time**: Handles complex date/time formatting and display

## compact-color-picker.php - Color Selection Component

```php
<?php 
$selectedColor = $selectedColor ?? '#ffffff';
include APP_ROOT . '/Views/components/compact-color-picker.php';
?>
```

**JavaScript Dependency**: Uses `compact-color-picker.js` - DO NOT change CSS classes `.compact-color-picker` or `.compact-color-option`.

## dynamic-group-selector.php - Orchestra Group Selector

```php
<?php
$groups = $orchestraGroups; // from orchestra_groups.php
include APP_ROOT . '/Views/components/dynamic-group-selector.php';
?>
```

## empty-state.php - Consistent Empty States

```php
<?php
$emptyTitle = 'No Data Found';
$emptyMessage = 'Try adjusting your filters';
include APP_ROOT . '/Views/components/empty-state.php';
?>
```

## Form Pattern - Always Use These Classes

**DO NOT create custom form HTML**. Always use this exact structure:

```html
<div class="form-group-modern">
    <label class="form-label-modern">Label Text</label>
    <input class="form-input-modern" type="text" name="field" />
</div>
```

**JavaScript Dependency**: Form validation and state management depends on these exact class names.

## Button Pattern - Use Existing Classes

**DO NOT create new button classes**. Always combine these:

```html
<button class="btn-base btn-primary">Primary Action</button>
<button class="btn-base btn-secondary btn-sm">Small Secondary</button>
<button class="btn-base btn-danger">Delete Action</button>
<button class="btn-base btn-outline">Outlined Button</button>
<button class="btn-base btn-icon">🎵</button>
```

Available classes:
- **Base**: `.btn-base` (always required)
- **Variants**: `.btn-primary`, `.btn-secondary`, `.btn-success`, `.btn-danger`, `.btn-outline`
- **Sizes**: `.btn-sm`, `.btn-lg`
- **Special**: `.btn-icon`

## Theme System Usage

```php
<?php
$themeManager = new App\Core\ThemeManager();
$currentTheme = $themeManager->getUserTheme($user);
echo $themeManager->getThemeCssLink($currentTheme);
?>
```

## Notification System - Never Use alert()

```javascript
// DO use these
window.notifySuccess('Success message');
window.notifyError('Error message'); 
window.notifyInfo('Info message');

// DON'T use these
alert('message');
console.log('message');
```

## API Call Pattern - Standardized Approach

```javascript
fetch(url, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify(data)
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        window.notifySuccess(data.message);
    } else {
        window.notifyError(data.message || 'An error occurred');
    }
})
.catch(error => {
    window.notifyError('Network error occurred');
});
```

## CSS Variables - Never Hardcode Colors

```css
/* DO use theme variables */
color: var(--color-primary);
background: var(--color-bg-primary);
border-color: var(--color-border);

/* DON'T hardcode colors */
color: #478cf4;
background: white;
border-color: #ccc;
```
