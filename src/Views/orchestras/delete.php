<?php $this->layout('layouts/default', ['title' => 'Orchester löschen', 'currentPage' => $currentPage]) ?>

<div class="container-app py-8">
    <div class="max-w-lg mx-auto">
        <div class="card-base">
            <div class="p-6 bg-error text-white rounded-t-lg">
                <h5 class="text-xl font-bold mb-0">Orchester löschen</h5>
            </div>
            <div class="p-6">
                    <form action="/<?= $_SESSION['current_orchestra_id'] ?>/orchestras/delete" method="post">
                        <input type="hidden" name="confirm_delete" value="yes">
                        
                        <div class="space-y-3">
                            <button type="submit" class="btn-base btn-danger w-full" onclick="return confirmDelete(event)">
                                <i class=" -alt mr-2"><?= icon('trash', 'text-white') ?></i>Ja, Orchester unwiderruflich löschen
                            </button>
                            <a href="/<?= $_SESSION['current_orchestra_id'] ?>/orchestras/settings" class="btn-base btn-outline w-full text-center block">Abbrechen</a>
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
            <p><i class=" text-warning"><?= icon('exclamation-triangle', 'text-gray-600') ?>></i> <strong>Warnung:</strong> Sie sind dabei, das Orchester <strong><?= $this->e($orchestra['name']) ?></strong> zu löschen.</p>
            <p>Diese Aktion kann nicht rückgängig gemacht werden.</p>
            <p><strong>Folgende Daten werden gelöscht:</strong></p>
            <ul class="text-left list-disc pl-5">
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