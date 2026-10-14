<?php $this->layout('layouts/default', ['title' => 'Deine Stimme', 'currentPage' => 'section_confirm']) ?>

<?php
// Section guessed from the JOinJMD instrument (user_orchestras.section_confirmed = 0), shown
// instead of the requested page until the member confirms it; then back to that page.
ob_start();

$pickerAction      = '/' . rawurlencode($_SESSION['current_org_slug'] ?? '') . '/' . rawurlencode($_SESSION['current_orchestra_slug'] ?? '') . '/section/confirm';
$pickerHeading     = 'Deine Stimme in ' . ($_SESSION['current_orchestra_name'] ?? '');
$pickerButton      = 'Weiter';
$pickerHidden      = ['csrf_token' => \App\Core\CSRF::getToken(), 'return' => $returnUrl];
$pickerSelected    = $guessedType;
$pickerOrchestraId = (int) $_SESSION['current_orchestra_id'];
$pickerLinks       = [];
include __DIR__ . '/../components/section-picker-form.php';

$content = ob_get_clean();
include __DIR__ . '/../components/centered-card.php';
?>
