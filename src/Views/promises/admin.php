<?php $this->layout('layouts/default', ['title' => 'Rückmeldungen Dashboard', 'currentPage' => $currentPage ?? 'admin']) ?>


<?php include __DIR__ . '/../components/promises-resources.php'; ?>

<?php 
// Set admin context for dashboard
$isAdmin = true;
include __DIR__ . '/../components/promises-dashboard-wrapper.php'; 
?>