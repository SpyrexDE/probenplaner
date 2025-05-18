<?php $this->layout('layouts/default', ['title' => 'Termine', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<div class="container-fluid mt-4">
    <?php if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])): ?>
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        
        <?php foreach ($_SESSION['flash_messages'] as $key => $message): ?>
            Toast.fire({
                icon: '<?= $message['type'] === 'error' ? 'error' : ($message['type'] === 'success' ? 'success' : 'info') ?>',
                title: '<?= htmlspecialchars($message['message']) ?>'
            });
        <?php unset($_SESSION['flash_messages'][$key]); endforeach; ?>
    </script>
    <?php endif; ?>

    <?php if (empty($rehearsals)): ?>
    <script>
        Swal.fire({
            title: 'Information',
            text: 'Keine Termine gefunden.',
            icon: 'info',
            confirmButtonColor: '#478cf4'
        });
    </script>
    <?php else: ?>
        <?php foreach ($rehearsals as $rehearsal): ?>
            <?php 
                $rehearsalId = $rehearsal['id'];
                $date = $rehearsal['date'];
                $start_time = isset($rehearsal['start_time']) ? substr($rehearsal['start_time'], 0, 5) : '??:??';
                $end_time = isset($rehearsal['end_time']) ? substr($rehearsal['end_time'], 0, 5) : '??:??';
                $time_display = $start_time . ' - ' . $end_time;
                $location = $rehearsal['location'] ?? 'TBA';
                
                // Determine rehearsal groups
                $groupKeys = $rehearsal['groups'] ?? [];
                
                // Check if it's a small group
                $isSmallGroup = isset($rehearsal['is_small_group']) && $rehearsal['is_small_group'] == 1;
                
                // Add * suffix to group names if it's a small group
                if ($isSmallGroup) {
                    foreach ($groupKeys as &$group) {
                        $group .= '*';
                    }
                }
                
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
                                <label class="col-form-label text-break" style="margin-bottom: 0; margin-left: 20px; font-size: 20px; font-weight: 600; width: 100%; overflow: auto; max-height: 40px;"><?= htmlspecialchars($time_display) ?><br></label>
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