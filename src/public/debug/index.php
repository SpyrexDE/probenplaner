<?php
/**
 * Debug Redirect
 * 
 * This file redirects old /debug requests to the new comprehensive dashboard
 */

// Redirect to the dashboard
header('Location: dashboard.php');
exit;
?> 