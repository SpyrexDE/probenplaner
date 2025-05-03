<?php $this->layout('layouts/default', ['title' => 'Termine', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<div class="container-fluid mt-4">
    <?php if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])): ?>
        <?php foreach ($_SESSION['flash_messages'] as $key => $message): ?>
            <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show" role="alert">
                <?= $message['message'] ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['flash_messages'][$key]); ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (empty($rehearsals)): ?>
        <div class="alert alert-info">
            Keine Termine gefunden.
        </div>
    <?php else: ?>
        <?php foreach ($rehearsals as $rehearsal): ?>
            <?php 
                $rehearsalId = $rehearsal['id'];
                $date = $rehearsal['date'];
                $time = $rehearsal['time'];
                $location = $rehearsal['location'] ?? 'TBA';
                
                // Determine rehearsal groups
                $groups = json_decode($rehearsal['groups_data'] ?? '{}', true);
                $groupKeys = array_keys($groups);
                
                // Convert group keys to formatted display
                $groupsDisplay = str_replace("_", " ", implode("<br>", $groupKeys));
            ?>
            
            <div style="display: block; border-radius: 10px; height: 111px; margin-right: 20px; margin-left: 20px; box-shadow: 0px 0px 30px rgba(128,128,128,0.4); margin-top: 30px; text-align: left; min-width: 300px; zoom: 0.8; border-width: 4px; border-style: solid; border-color: rgb(179,179,179); background-color: <?= !empty($rehearsal['color']) ? $rehearsal['color'] : 'white' ?>;">
                <div class="row" style="width: 100%;">
                    <div class="col col-8" style="margin-top: -7px;">
                        <div class="row">
                            <div class="col col-6">
                                <label class="col-form-label text-break" style="margin-bottom: 0; margin-top: 15px; margin-left: 20px; font-size: 20px; font-weight: 600; width: 100%; overflow: auto; max-height: 40px;"><?= htmlspecialchars($date) ?><br></label>
                            </div>
                            <div class="col">
                                <label class="col-form-label text-break" style="margin-bottom: 0; margin-top: 15px; font-size: 20px; font-weight: 600; width: 100%; overflow: auto; max-height: 40px;"><?= $groupsDisplay ?>&nbsp;<br></label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col col-6">
                                <label class="col-form-label text-break" style="margin-bottom: 0; margin-left: 20px; font-size: 20px; font-weight: 600; width: 100%; overflow: auto; max-height: 40px;"><?= htmlspecialchars($time) ?><br></label>
                            </div>
                            <div class="col">
                                <label class="col-form-label text-break" style="margin-bottom: 0; font-size: 20px; font-weight: 600; width: 100%; overflow: auto; max-height: 40px;"><?= htmlspecialchars($location) ?><br></label>
                            </div>
                        </div>
                    </div>
                    <div style="height: 50%; width: 24%; display: inline-block; white-space: nowrap; float: right; padding-top: 15px; transform: scale(1.4); transform-origin: 0 0;">
                        <img id="<?= $rehearsalId ?>" class="edit" src="/assets/img/icons8_edit_file_48px.png" style="cursor: pointer; transform: scale(0.9); transform-origin: 0 -210px;">
                        <img id="<?= $rehearsalId ?>" class="delete" src="/assets/img/icons8_delete_bin_96px.png" style="transform: scale(0.5); transform-origin: 0 0; cursor: pointer;">
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <div class="mt-4 mb-4" style="height: 100px;">
        <a href="/rehearsals/create">
            <img src="/assets/img/icons8_add_96px.png" style="position: fixed; bottom: 10px; right: 10px; z-index: 9999; -webkit-filter: drop-shadow(5px 5px 5px #222); filter: drop-shadow(5px 5px 5px #222);" />
        </a>
    </div>
</div>

<script>
// Delete rehearsal with AJAX and Sweetalert2
document.querySelectorAll('.delete').forEach(function(element) {
    element.addEventListener('click', function(event) {
        const id = event.target.id;
        
        Swal.fire({
            title: 'Willst du diesen Termin wirklich löschen?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#478cf4',
            cancelButtonText: 'Abbrechen',
            confirmButtonText: 'Löschen'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('/rehearsals/delete/' + id, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        Swal.fire({
                            title: 'Fehler',
                            text: data.message || 'Unbekannter Fehler beim Löschen des Termins',
                            icon: 'error',
                            confirmButtonColor: '#478cf4'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Fehler',
                        text: 'Verbindungsfehler beim Löschen des Termins',
                        icon: 'error',
                        confirmButtonColor: '#478cf4'
                    });
                });
            }
        });
    });
});

// Edit rehearsal redirect
document.querySelectorAll('.edit').forEach(function(element) {
    element.addEventListener('click', function(event) {
        window.location.href = '/rehearsals/edit/' + event.target.id;
    });
});
</script> 