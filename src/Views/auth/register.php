<?php $this->layout('layouts/default', ['title' => 'Registrierung', 'currentPage' => $currentPage, 'isFluid' => true]) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';

ob_start();
?>

<form method="post" action="/register" class="login-form">
    <?php include __DIR__ . '/../components/csrf-input.php'; ?>

    <?php include __DIR__ . '/../components/logo.php'; ?>

    <input class="login-input"
        type="email"
        name="email"
        placeholder="E-Mail-Adresse"
        required
        minlength="3"
        maxlength="254"
        autocomplete="email">

    <input class="login-input"
        type="text"
        name="display_name"
        placeholder="Anzeigename"
        required
        minlength="2"
        maxlength="100"
        autocomplete="name">

    <input class="login-input"
        type="password"
        name="password"
        placeholder="Passwort"
        required
        minlength="4"
        maxlength="128"
        autocomplete="new-password">

    <input class="login-input"
        type="password"
        name="password_confirm"
        placeholder="Passwort bestätigen"
        required
        minlength="4"
        maxlength="128"
        autocomplete="new-password">

    <div class="form-text">Nach der Registrierung können Sie Orchestern beitreten</div>

    <button class="login-button" type="submit">
        Registrieren
    </button>

    <?php
    $links = [
        ['url' => '/login', 'text' => 'Bereits registriert? ', 'primary' => 'Einloggen']
    ];
    include __DIR__ . '/../components/auth-footer.php';
    ?>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../components/centered-card.php';
?>