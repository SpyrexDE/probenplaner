<?php $this->layout('layouts/default', ['title' => 'Registrierung', 'currentPage' => $currentPage]) ?>

<div class="login-container">
    <form method="post" action="/register" class="login-form">
        <?php if (isset($csrf_token)): ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        
        <div class="login-logo">
            <img src="/assets/img/Logo.png" alt="Logo"/>
        </div>

        <input class="login-input" 
               type="text" 
               name="username" 
               placeholder="Nutzername" 
               required 
               minlength="2" 
               maxlength="20"
               autocomplete="username">

        <input class="login-input" 
               type="password" 
               name="password" 
               placeholder="Passwort" 
               required 
               minlength="4" 
               maxlength="20"
               autocomplete="new-password">

        <input class="login-input" 
               type="password" 
               name="password_confirm" 
               placeholder="Passwort bestätigen" 
               required 
               minlength="4" 
               maxlength="20"
               autocomplete="new-password">

        <input class="login-input" 
               type="text" 
               name="token" 
               placeholder="Orchester-Token" 
               required>
        
        <div class="form-text">Der Token identifiziert dein Orchester</div>

        <select class="login-input" name="type" required>
            <option value="" disabled selected>Instrument / Stimmgruppe</option>
            <?php foreach ($typeStructure as $group => $instruments): ?>
                <option value="" disabled style="font-weight: bold; color: #6b7280;"><?= $group ?></option>
                <?php foreach ($instruments as $instrument): ?>
                    <option value="<?= $instrument ?>">&nbsp;&nbsp;<?= str_replace('_', ' ', $instrument) ?></option>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </select>

        <button class="login-button" type="submit">
            Registrieren
        </button>

        <div class="auth-links">
            <a href="/login" class="auth-link">
                Bereits registriert? <span class="auth-link-primary">Einloggen</span>
            </a>
            <a href="/orchestras/create" class="auth-link auth-link-secondary">
                Neues Orchester erstellen
            </a>
        </div>
    </form>
</div>