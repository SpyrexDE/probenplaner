<?php $this->layout('layouts/default', ['title' => 'Orchester-Einstellungen', 'currentPage' => $currentPage]) ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Orchester-Einstellungen</h5>
                </div>
                <div class="card-body">
                    <form action="/orchestras/update" method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Orchestername</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= $this->e($orchestra['name']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="token" class="form-label">Token</label>
                            <input type="text" class="form-control" id="token" name="token" value="<?= $this->e($orchestra['token']) ?>" required>
                            <div class="form-text">Dieser Token wird für die Registrierung neuer Mitglieder verwendet.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="leader_pw" class="form-label">Stimmführer-Passwort</label>
                            <input type="text" class="form-control" id="leader_pw" name="leader_pw" value="<?= $this->e($orchestra['leader_pw']) ?>" required>
                            <div class="form-text">Dieses Passwort ermöglicht Stimmführer-Berechtigungen bei der Registrierung.</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Orchester löschen</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        <strong>Achtung:</strong> Das Löschen eines Orchesters kann nicht rückgängig gemacht werden. 
                        Alle Daten, einschließlich Proben, Nutzer und Zusagen werden unwiderruflich gelöscht.
                    </p>
                    <div class="d-grid">
                        <a href="/orchestras/delete-confirm" class="btn btn-danger">Orchester löschen</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>