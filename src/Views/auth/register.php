<?php $this->layout('layouts/default', ['title' => 'Registrierung', 'currentPage' => $currentPage]) ?>

<style>
    .login-clean {
        width: 100%;
        padding-bottom: 20vh;
        padding-top: 5vh;
        height: 100%;
        min-height: 440px;
    }
    .fas, .far {
        font-size: 21px;
    }
    /* Ensure mobile responsiveness */
    @media (max-width: 767px) {
        .login-clean form {
            width: 90%;
            margin: 0 auto;
        }
        /* Avoid form being cut off on smaller screens */
        .login-clean {
            padding-bottom: 10vh;
        }
    }
</style>

<div class="login-clean">
    <form method="post" action="/register">
        <h2 class="sr-only">Registration Form</h2>
        <div class="illustration">
            <img src="/assets/img/Logo.png" style="transform: scale(0.85); transform-origin: 0 0;"/>
        </div>
        <div class="form-group">
            <input class="form-control" type="text" id="username" name="username" placeholder="Nutzername" style="font-family: Roboto, sans-serif;" required minlength="2" maxlength="20">
        </div>
        <div class="form-group">
            <input class="form-control" type="password" id="password" name="password" placeholder="Passwort" style="font-family: Roboto, sans-serif;" required minlength="4" maxlength="20">
        </div>
        <div class="form-group">
            <input class="form-control" type="password" id="password_confirm" name="password_confirm" placeholder="Passwort bestätigen" style="font-family: Roboto, sans-serif;" required minlength="4" maxlength="20">
        </div>
        <div class="form-group">
            <input class="form-control" type="text" id="token" name="token" placeholder="Orchester-Token" style="font-family: Roboto, sans-serif;" required>
            <small class="form-text text-muted">Der Token identifiziert dein Orchester</small>
        </div>
        <div class="form-group">
            <select class="form-control" id="type" name="type" style="font-family: Roboto, sans-serif;" required>
                <option value="" disabled selected>Instrument / Stimmgruppe</option>
                <?php foreach ($typeStructure as $group => $instruments): ?>
                    <option value="" disabled style="font-weight: bold; background-color: #e1e1e1;"><?= $group ?></option>
                    <?php foreach ($instruments as $instrument): ?>
                        <option value="<?= $instrument ?>">&nbsp;&nbsp;<?= str_replace('_', ' ', $instrument) ?></option>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <button class="btn btn-primary btn-block" type="submit" style="background-color: rgb(71,140,244); font-family: Roboto, sans-serif;">Registrieren</button>
        </div>
        <a href="/login" style="display: block; text-align: center; font-size: 12px; color: gray;">
            Bereits registriert? Hier <font color="#5772b4">einloggen</font>!
        </a>
        <a href="/orchestras/create" style="display: block; text-align: center; font-size: 12px; color: gray; margin-top: 10px;">
            Neues Orchester erstellen
        </a>
    </form>
</div>