<?php $this->layout('layouts/default', ['title' => 'Ungültiger Link', 'currentPage' => 'invite_invalid']) ?>

<?php
ob_start();
?>

<?php
$content = '<div style="background:white;border-radius:var(--radius-xl);box-shadow:var(--shadow-lg);padding:var(--space-8);width:100%;max-width:400px;text-align:center">';
$content .= '<div style="font-size:3rem;margin-bottom:var(--space-3)">🔗</div>';
$content .= '<div style="font-size:var(--font-size-xl);font-weight:600;margin-bottom:var(--space-2)">Ungültiger Link</div>';
$content .= '<div style="font-size:var(--font-size-sm);color:var(--color-gray-500);margin-bottom:var(--space-5)">Dieser Einladungslink ist ungültig oder abgelaufen.</div>';
$content .= '<a href="/orchestras/select" style="display:block;padding:var(--space-2) var(--space-4);background:var(--color-primary);color:white;border-radius:var(--radius-lg);text-decoration:none;font-weight:500;transition:all var(--transition-base)">Zur Ensemble-Auswahl</a>';
$content .= '</div>';

$headerContent = '<img src="/assets/img/Logo.png" alt="Probenplaner" style="height:64px">';
include __DIR__ . '/../components/centered-card.php';
?>
