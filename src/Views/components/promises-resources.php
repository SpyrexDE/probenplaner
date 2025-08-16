<?php
/**
 * Include shared resources for promise views
 * This component includes the shared CSS and JavaScript files
 */
?>

<!-- Shared CSS for promise views -->
<link rel="stylesheet" href="/assets/css/promises-shared.css">

<!-- Shared styles that need to be inline for immediate rendering -->
<style>
/* Any view-specific styles can be added here by the including file */
</style>

<?php
/**
 * Include this at the end of the body to include shared JavaScript
 */
function includePromisesScript() {
    echo '<script src="/assets/js/promises-shared.js"></script>';
}
?>
