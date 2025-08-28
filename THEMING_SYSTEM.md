# Professional Theming System - Probenplaner

## Overview

This document outlines the comprehensive theming system implemented for the Probenplaner application. The system eliminates inline styles, magic numbers, and inconsistent styling in favor of a maintainable, professional theming architecture.

## Architecture

### 1. CSS Custom Properties (`src/public/assets/css/theme.css`)

All design tokens are defined as CSS custom properties (variables) in the `:root` selector:

- **Colors**: Primary, secondary, status colors, and neutral scales
- **Spacing**: Consistent spacing scale (4px base)
- **Typography**: Font families, sizes, weights, and line heights
- **Borders**: Radius and width scales
- **Shadows**: Box shadow definitions
- **Transitions**: Consistent animation timing
- **Z-index**: Layering scale for UI elements

### 2. Component Classes (`src/public/assets/css/components.css`)

Reusable component classes built using the theme system:

- **Layout Components**: Cards, containers, pages
- **Navigation**: Sidebar, top navigation, menu items
- **Form Elements**: Inputs, buttons, selects
- **Status Elements**: Success, error, warning states
- **Interactive Elements**: Buttons, actions, hover states

### 3. Tailwind Configuration (`tailwind.config.js`)

Extended Tailwind CSS configuration that:

- Matches our design tokens exactly
- Provides utility classes for common patterns
- Includes custom component plugins
- Maintains consistency with our theme system

## Key Features

### ✅ Design Token System
- All colors, spacing, typography defined in one place
- Easy to maintain and update themes
- No more magic numbers scattered throughout code

### ✅ Component-Based Architecture
- Reusable styled components
- Consistent behavior across the application
- Easy to extend and customize

### ✅ Responsive Design
- Mobile-first approach
- Consistent breakpoints
- Responsive utilities

### ✅ Accessibility
- Proper color contrast
- Focus states
- Screen reader support

### ✅ Performance
- CSS custom properties for dynamic theming
- Minimal CSS footprint
- Optimized for production

## Usage Examples

### Basic Components

```php
<!-- Card Component -->
<div class="card-base card-hover">
    <div class="p-6">
        <h3>Card Title</h3>
        <p>Card content</p>
    </div>
</div>

<!-- Button Components -->
<button class="btn btn-primary">Primary Button</button>
<button class="btn btn-outline">Outline Button</button>
<button class="btn btn-sm">Small Button</button>

<!-- Form Input -->
<input type="text" class="form-input" placeholder="Enter text...">
```

### Layout Components

```php
<!-- Page Container -->
<div class="container-app">
    <!-- Content goes here -->
</div>

<!-- Rehearsal Card -->
<div class="rehearsal-card status-attending">
    <div class="rehearsal-card-content">
        <div class="rehearsal-card-info">
            <div class="rehearsal-card-primary">
                <span class="rehearsal-date-time">March 15, 2024</span>
                <span class="rehearsal-type">Full Orchestra</span>
            </div>
        </div>
    </div>
</div>
```

### Status Classes

```php
<!-- Status indicators -->
<div class="status-attending">Attending</div>
<div class="status-not-attending">Not Attending</div>
<div class="status-pending">Pending Response</div>
```

## Theming Variables

### Colors

```css
/* Primary brand colors */
--color-primary: #478cf4;
--color-primary-light: #6ba3f6;
--color-primary-dark: #3a7bd5;

/* Status colors */
--color-success: #10b981;
--color-error: #ef4444;
--color-warning: #f59e0b;

/* Neutral colors */
--color-gray-100: #f3f4f6;
--color-gray-500: #6b7280;
--color-gray-900: #111827;
```

### Spacing

```css
/* Consistent spacing scale */
--space-1: 0.25rem;   /* 4px */
--space-2: 0.5rem;    /* 8px */
--space-4: 1rem;      /* 16px */
--space-6: 1.5rem;    /* 24px */
--space-8: 2rem;      /* 32px */
```

### Typography

```css
/* Font system */
--font-family-sans: 'Roboto', sans-serif;
--font-family-brand: 'Fugaz One', cursive;

--font-size-sm: 0.875rem;   /* 14px */
--font-size-base: 1rem;     /* 16px */
--font-size-lg: 1.125rem;   /* 18px */

--font-weight-normal: 400;
--font-weight-medium: 500;
--font-weight-semibold: 600;
```

## Component Reference

### Buttons

| Class | Description |
|-------|-------------|
| `.btn` | Base button styles |
| `.btn-primary` | Primary action button |
| `.btn-secondary` | Secondary action button |
| `.btn-success` | Success button |
| `.btn-danger` | Danger/delete button |
| `.btn-outline` | Outlined button |
| `.btn-ghost` | Minimal button |
| `.btn-sm` | Small button |
| `.btn-lg` | Large button |
| `.btn-icon` | Icon-only button |
| `.btn-round` | Rounded button |

### Cards

| Class | Description |
|-------|-------------|
| `.card-base` | Base card component |
| `.card-hover` | Card with hover effects |
| `.rehearsal-card` | Specialized rehearsal card |
| `.rehearsal-card-content` | Card content wrapper |
| `.rehearsal-card-info` | Card information section |

### Form Elements

| Class | Description |
|-------|-------------|
| `.form-input` | Styled input field |
| `.form-textarea` | Styled textarea |
| `.form-select` | Styled select dropdown |

### Status Classes

| Class | Description |
|-------|-------------|
| `.status-attending` | Attending status styling |
| `.status-not-attending` | Not attending status styling |
| `.status-pending` | Pending status styling |

### Layout Classes

| Class | Description |
|-------|-------------|
| `.container-app` | Main app container |
| `.page-content` | Page content wrapper |
| `.empty-state` | Empty state component |
| `.fab` | Floating action button |

## Migration Guide

### From Inline Styles

**Before:**
```php
<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
```

**After:**
```php
<div class="card-base">
    <div class="p-5">
```

### From Magic Numbers

**Before:**
```css
margin: 16px 20px 24px;
font-size: 14px;
color: #6b7280;
```

**After:**
```css
margin: var(--space-4) var(--space-5) var(--space-6);
font-size: var(--font-size-sm);
color: var(--color-text-secondary);
```

### From Bootstrap Classes

**Before:**
```php
<div class="container-fluid">
<button class="btn btn-primary" style="background-color: #478cf4;">
```

**After:**
```php
<div class="container-app">
<button class="btn btn-primary">
```

## Benefits

1. **Maintainability**: One place to change colors, spacing, or typography
2. **Consistency**: All components use the same design tokens
3. **Flexibility**: Easy to add themes or customize appearance
4. **Performance**: Optimized CSS with minimal redundancy
5. **Developer Experience**: Clear component library with predictable behavior
6. **Accessibility**: Built-in focus states and proper contrast ratios

## Future Enhancements

1. **Dark Mode**: The theming system is ready for dark mode implementation
2. **Multiple Themes**: Easy to add orchestra-specific themes
3. **Component Variants**: Additional button and card variants as needed
4. **Animation Library**: Consistent animations and transitions

## File Structure

```
src/public/assets/css/
├── theme.css          # CSS custom properties and base styles
├── components.css     # Component library
tailwind.config.js     # Tailwind configuration
```

## Best Practices

1. **Always use theme variables** instead of hardcoded values
2. **Use component classes** for common UI patterns
3. **Leverage Tailwind utilities** for unique styling needs
4. **Test responsive behavior** on different screen sizes
5. **Maintain accessibility** with proper contrast and focus states

This theming system provides a solid foundation for maintaining and scaling the Probenplaner application while ensuring a consistent, professional user experience across all components and pages.
