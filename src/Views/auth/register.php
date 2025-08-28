<?php $this->layout('layouts/default', ['title' => 'Registrierung', 'currentPage' => $currentPage]) ?>





<div class="login-container">
    <form method="post" action="/register" class="login-form">
        <?php if (isset($csrf_token)): ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        <h2 class="sr-only">Registration Form</h2>
        <div class="login-logo">
            <img src="/assets/img/Logo.png" alt="Logo"/>
        </div>
        <div class="form-group">
            <input class="form-control login-input" type="text" id="username" name="username" placeholder="Nutzername" required minlength="2" maxlength="20">
        </div>
        <div class="form-group">
            <input class="form-control login-input" type="password" id="password" name="password" placeholder="Passwort" required minlength="4" maxlength="20">
        </div>
        <div class="form-group">
            <input class="form-control login-input" type="password" id="password_confirm" name="password_confirm" placeholder="Passwort bestätigen" required minlength="4" maxlength="20">
        </div>
        <div class="form-group">
            <input class="form-control login-input" type="text" id="token" name="token" placeholder="Orchester-Token" required>
            <small class="form-text text-muted">Der Token identifiziert dein Orchester</small>
        </div>
        <div class="form-group">
            <select class="form-control login-input" id="type" name="type" required>
                <option value="" disabled selected>Instrument / Stimmgruppe</option>
                <?php foreach ($typeStructure as $group => $instruments): ?>
                    <option value="" disabled class="font-bold text-gray-600"><?= $group ?></option>
                    <?php foreach ($instruments as $instrument): ?>
                        <option value="<?= $instrument ?>">&nbsp;&nbsp;<?= str_replace('_', ' ', $instrument) ?></option>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <button class="btn btn-primary btn-block login-button" type="submit">Registrieren</button>
        </div>
        <a href="/login" class="login-link">
            Bereits registriert? Hier <span class="text-primary">einloggen</span>!
        </a>
        <a href="/orchestras/create" class="login-link block mt-2">
            Neues Orchester erstellen
        </a>
    </form>
</div>