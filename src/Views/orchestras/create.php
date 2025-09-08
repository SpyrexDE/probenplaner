<?php $this->layout('layouts/default', ['title' => 'Neues Orchester erstellen', 'currentPage' => $currentPage]) ?>

<div class="login-container">
    <?php if (isset($admin_verified) && $admin_verified): ?>
    <div class="admin-verify-back">
        <a href="/" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Zurück
        </a>
    </div>
    
    <form action="/orchestras/store" method="post" class="login-form">
        <?php if (isset($csrf_token)): ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        
        <div class="login-logo">
            <img src="/assets/img/Logo.png" alt="Logo"/>
        </div>

        <h2 class="verify-title">Neues Orchester erstellen</h2>
        <p class="verify-subtitle">Füllen Sie bitte alle Felder aus, um ein neues Orchester zu erstellen.</p>
        
        <input type="text" 
               class="login-input" 
               name="name" 
               placeholder="Orchestername" 
               value="<?= isset($formData['name']) ? htmlspecialchars($formData['name']) : '' ?>" 
               required>
        <div class="form-text">Der vollständige Name des Orchesters.</div>
        
        <input type="text" 
               class="login-input" 
               name="token" 
               placeholder="Token" 
               value="<?= isset($formData['token']) ? htmlspecialchars($formData['token']) : '' ?>" 
               required>
        <div class="form-text">Ein kurzer Code für die Registrierung neuer Mitglieder.</div>
        
        <input type="text" 
               class="login-input" 
               name="leader_password" 
               placeholder="Stimmführer-Passwort" 
               value="<?= isset($formData['leader_password']) ? htmlspecialchars($formData['leader_password']) : '' ?>" 
               required>
        <div class="form-text">Passwort für Stimmführer-Berechtigungen bei der Registrierung.</div>
        
        <h3 class="verify-subtitle" style="margin-top: 2rem;">Dirigenten-Account</h3>
        
        <input type="text" 
               class="login-input" 
               name="conductor_username" 
               placeholder="Benutzername (Dirigent)" 
               value="<?= isset($formData['conductor_username']) ? htmlspecialchars($formData['conductor_username']) : '' ?>" 
               required>
        <div class="form-text">Benutzername für den Dirigenten-Account.</div>
        
        <input type="password" 
               class="login-input" 
               name="conductor_password" 
               placeholder="Passwort (Dirigent)" 
               required>
        <div class="form-text">Passwort für den Dirigenten-Account.</div>
        
        <button type="submit" class="login-button">
            Orchester erstellen
        </button>
    </form>
    <?php else: ?>
    <form method="post" action="/orchestras/create" class="login-form">
        <?php if (isset($csrf_token)): ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        
        <div class="login-logo">
            <img src="/assets/img/Logo.png" alt="Logo"/>
        </div>

        <h2 class="verify-title">Admin Verifizierung</h2>
        <p class="verify-subtitle">Um ein neues Orchester anlegen zu können, benötigen Sie das Admin-Passwort</p>

        <input class="login-input" 
               type="password" 
               name="admin_password" 
               placeholder="Admin-Passwort" 
               required>

        <button class="login-button" type="submit">
            Verifizieren
        </button>
    </form>
    <?php endif; ?>
</div> 