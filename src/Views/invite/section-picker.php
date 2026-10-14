<?php $this->layout('layouts/default', ['title' => 'Beitreten', 'currentPage' => 'invite_section_picker']) ?>

<?php
ob_start();

$pickerAction      = '/invite/join';
$pickerHeading     = $orchestra['name'] . ' beitreten';
$pickerButton      = 'Beitreten';
$pickerHidden      = ['csrf_token' => $csrf_token, 'token' => $token];
$pickerSelected    = '';
$pickerOrchestraId = (int) $orchestra['id'];
$pickerLinks       = [['url' => '/orchestras/select', 'icon' => 'fa-chevron-left', 'text' => 'Zurück']];
include __DIR__ . '/../components/section-picker-form.php';

$content = ob_get_clean();
include __DIR__ . '/../components/centered-card.php';
?>
