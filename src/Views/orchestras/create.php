<?php $this->layout('layouts/default', ['title' => 'Neues Orchester erstellen', 'currentPage' => $currentPage]) ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Neues Orchester erstellen</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($admin_verified) && $admin_verified): ?>
                    <p class="card-text">Füllen Sie bitte alle Felder aus, um ein neues Orchester zu erstellen.</p>
                    
                    <form action="/orchestras/store" method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Orchestername</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="form-text">Der vollständige Name des Orchesters.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="token" class="form-label">Token</label>
                            <input type="text" class="form-control" id="token" name="token" required>
                            <div class="form-text">Ein kurzer Code für die Registrierung neuer Mitglieder.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="leader_pw" class="form-label">Stimmführer-Passwort</label>
                            <input type="text" class="form-control" id="leader_pw" name="leader_pw" required>
                            <div class="form-text">Passwort für Stimmführer-Berechtigungen bei der Registrierung.</div>
                        </div>
                        
                        <hr>
                        <h5>Dirigenten-Account</h5>
                        
                        <div class="mb-3">
                            <label for="conductor_username" class="form-label">Benutzername</label>
                            <input type="text" class="form-control" id="conductor_username" name="conductor_username" required>
                            <div class="form-text">Benutzername für den Dirigenten-Account.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="conductor_password" class="form-label">Passwort</label>
                            <input type="password" class="form-control" id="conductor_password" name="conductor_password" required>
                            <div class="form-text">Passwort für den Dirigenten-Account.</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Orchester erstellen</button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <p>Sie müssen sich zuerst als Administrator verifizieren.</p>
                        <a href="/orchestras/create" class="btn btn-primary">Zurück zur Verifizierung</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div> 