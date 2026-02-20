<?php $this->layout('layouts/default', ['title' => 'Einladungslink einlösen', 'currentPage' => 'invite_redeem']) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';
?>

<?php ob_start(); ?>

<form method="POST" action="/orchestras/redeem" class="login-form">
    <?php include __DIR__ . '/../components/csrf-input.php'; ?>

    <?php include __DIR__ . '/../components/logo.php'; ?>

    <input class="login-input"
        type="text"
        name="link"
        autofocus required
        placeholder="Einladungslink hier einfügen..."
        autocomplete="off">

    <button class="login-button" type="submit">
        Beitreten
    </button>
</form>

<?php
$content = ob_get_clean();
$backLink = ['url' => '/orchestras/select', 'text' => 'Zurück zur Auswahl'];
include __DIR__ . '/../components/centered-card.php';
?>