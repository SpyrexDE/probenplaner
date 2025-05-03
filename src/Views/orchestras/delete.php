<?php $this->layout('layouts/default', ['title' => 'Orchester löschen', 'currentPage' => $currentPage]) ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Orchester löschen</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h5 class="alert-heading">Sind Sie sicher?</h5>
                        <p>
                            Sie sind dabei, das Orchester <strong><?= $this->e($orchestra['name']) ?></strong> zu löschen. 
                            Diese Aktion kann nicht rückgängig gemacht werden.
                        </p>
                        <p>
                            <strong>Folgende Daten werden gelöscht:</strong>
                        </p>
                        <ul>
                            <li>Alle Mitglieder und deren Accounts</li>
                            <li>Alle Proben und Konzerte</li>
                            <li>Alle Zusagen der Mitglieder</li>
                            <li>Alle Orchestereinstellungen</li>
                        </ul>
                    </div>
                    
                    <form action="/orchestras/delete" method="post">
                        <input type="hidden" name="confirm_delete" value="yes">
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger">Ja, Orchester unwiderruflich löschen</button>
                            <a href="/orchestras/settings" class="btn btn-secondary">Abbrechen</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div> 