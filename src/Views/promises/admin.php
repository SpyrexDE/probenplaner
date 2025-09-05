<?php $this->layout('layouts/default', ['title' => 'Rückmeldungen Dashboard', 'currentPage' => $currentPage ?? 'admin']) ?>

<div class="text-center mb-6">
    <h1 class="page-title">Rückmeldungen</h1>
</div>

<?php include __DIR__ . '/../components/promises-resources.php'; ?>

<?php 
// Set admin context for dashboard
$isAdmin = true;
include __DIR__ . '/../components/promises-dashboard-wrapper.php'; 
?>