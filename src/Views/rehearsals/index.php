<?php $this->layout('layouts/default', ['title' => 'Termine', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<div class="container-app pb-20">
    
    <?php if (empty($rehearsals)): ?>
        <?php 
            $title = 'Keine Termine gefunden';
            $message = 'Lege einen neuen Termin an, um hier Proben zu sehen.';
            $actionHref = '/rehearsals/create';
            $actionLabel = 'Termin hinzufügen';
            include __DIR__ . '/../components/empty-state.php';
        ?>
    <?php else: ?>
        <?php 
        // Separate current/future and past rehearsals
        $currentRehearsals = [];
        $pastRehearsals = [];
        $today = date('Y-m-d');
        
        foreach ($rehearsals as $rehearsal) {
            if ($rehearsal['date'] >= $today) {
                $currentRehearsals[] = $rehearsal;
            } else {
                $pastRehearsals[] = $rehearsal;
            }
        }
        ?>
        
        <!-- Past Rehearsals (only shown if showOld is true) -->
        <?php if ($showOld && !empty($pastRehearsals)): ?>
            <div class="past-rehearsals-section" id="pastRehearsalsSection">
                <?php foreach ($pastRehearsals as $rehearsal): ?>
                    <?php 
                    // Set options for the rehearsal card component
                    $context = 'rehearsals';
                    $options = [
                        'showButtons' => true
                    ];
                    include __DIR__ . '/../components/rehearsal-card.php';
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Date Separator and Load Past Button -->
        <?php if (!empty($currentRehearsals) || !empty($pastRehearsals)): ?>
            <?php include __DIR__ . '/../components/date-separator.php'; ?>
        <?php endif; ?>
        
        <!-- Current/Future Rehearsals -->
        <?php foreach ($currentRehearsals as $rehearsal): ?>
            <?php 
            // Set options for the rehearsal card component
            $context = 'rehearsals';
            $options = [
                'showButtons' => true
            ];
            include __DIR__ . '/../components/rehearsal-card.php';
            ?>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php 
    // FAB for adding new rehearsal
    $icon = 'plus';
    $href = '/rehearsals/create';
    $title = 'Neue Probe hinzufügen';
    include __DIR__ . '/../components/fab.php';
    ?>
</div>

<script>
// Delete rehearsal with AJAX and Sweetalert2 (using event delegation)
document.addEventListener('click', function(event) {
    if (event.target.closest('.delete-btn')) {
        const deleteBtn = event.target.closest('.delete-btn');
        const id = deleteBtn.id;
        
        Swal.fire({
            title: 'Willst du diesen Termin wirklich löschen?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#478cf4',
            cancelButtonText: 'Abbrechen',
            confirmButtonText: 'Löschen'
        }).then((result) => {
            if (result.isConfirmed) {
                <?php $orchestraId = $_SESSION['current_orchestra_id'] ?? 1; ?>
                fetch('/<?= $orchestraId ?>/rehearsals/delete/' + id, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (window.notifySuccess) {
                            window.notifySuccess('Termin gelöscht');
                            setTimeout(function(){ location.reload(); }, 600);
                        } else {
                            location.reload();
                        }
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
    }
});

// Edit rehearsal redirect (using event delegation)
document.addEventListener('click', function(event) {
    if (event.target.closest('.edit-btn')) {
        const editBtn = event.target.closest('.edit-btn');
        const buttonId = editBtn.id;
        window.location.href = '/rehearsals/edit/' + buttonId;
    }
});
</script> 