# CSS Classes with JavaScript Dependencies - NEVER CHANGE THESE

## Form Classes
- `.form-input-modern` - JavaScript adds/removes 'focused', 'filled', 'error' states
- `.form-group-modern` - JavaScript targets parent container

## UI Component Classes  
- `.dropdown-menu` - dropdown.js looks for this exact class
- `.dropdown-item` - dropdown.js handles clicks on this class
- `.compact-color-picker` - color picker JavaScript depends on this
- `.compact-color-option` - color picker clicks depend on this
- `.collapse` - collapse.js depends on this
- `.tree` - tree-view-clickable.js depends on this
- `.tree-item-span` - tree-view JavaScript depends on this

## Critical Notes
- **These class names are hardcoded in JavaScript files**
- **Changing them will break functionality**
- **Always check this list before refactoring CSS**
- **When adding new interactive components, update this list**

## Files That Use These Classes
- `src/public/assets/js/dropdown.js` - Uses `.dropdown-menu`, `.dropdown-item`
- `src/public/assets/js/collapse.js` - Uses `.collapse`
- `src/public/assets/js/tree-view-clickable.js` - Uses `.tree`, `.tree-item-span`
- `src/public/assets/js/compact-color-picker.js` - Uses `.compact-color-picker`, `.compact-color-option`
- `src/public/assets/js/promises-shared.js` - Uses form classes for validation
