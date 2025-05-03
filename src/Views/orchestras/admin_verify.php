<?php $this->layout('layouts/default', ['title' => 'Admin Verifizierung', 'currentPage' => $currentPage]) ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Admin Verifizierung</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Um ein neues Orchester anlegen zu können, benötigen Sie das Admin-Passwort.</p>
                    
                    <form action="/orchestras/create" method="post">
                        <div class="mb-3">
                            <label for="admin_password" class="form-label">Admin-Passwort</label>
                            <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Verifizieren</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div> 