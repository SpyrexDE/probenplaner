<?php $this->layout('layouts/default', ['title' => 'Orchester löschen', 'currentPage' => $currentPage]) ?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Orchester löschen</h5>
                </div>
                <div class="card-body">
                    <form action="/orchestras/delete" method="post">
                        <input type="hidden" name="confirm_delete" value="yes">
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger" onclick="return confirmDelete(event)">
                                <i class="fas fa-trash-alt me-2"></i>Ja, Orchester unwiderruflich löschen
                            </button>
                            <a href="/orchestras/settings" class="btn btn-secondary">Abbrechen</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(event) {
    event.preventDefault();
    Swal.fire({
        title: 'Orchester löschen?',
        html: `<div class="text-left">
            <p><i class="fas fa-exclamation-triangle text-warning"></i> <strong>Warnung:</strong> Sie sind dabei, das Orchester <strong><?= $this->e($orchestra['name']) ?></strong> zu löschen.</p>
            <p>Diese Aktion kann nicht rückgängig gemacht werden.</p>
            <p><strong>Folgende Daten werden gelöscht:</strong></p>
            <ul class="text-left" style="list-style-type: disc; padding-left: 20px;">
                <li>Alle Mitglieder und deren Accounts</li>
                <li>Alle Proben und Konzerte</li>
                <li>Alle Zusagen der Mitglieder</li>
                <li>Alle Orchestereinstellungen</li>
            </ul>
        </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ja, löschen',
        cancelButtonText: 'Abbrechen',
        confirmButtonColor: '#dc3545',
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            event.target.closest('form').submit();
        }
    });
    return false;
}
</script> 