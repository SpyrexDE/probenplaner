<?php $this->layout('layouts/default', ['title' => 'Rückmeldungen Dashboard', 'currentPage' => $currentPage ?? 'admin']) ?>


<?php include __DIR__ . '/../components/promises-resources.php'; ?>

<?php 
// Dashboard context
$isAdmin = true;
include __DIR__ . '/../components/promises-dashboard-wrapper.php'; 
?>