<?php $this->layout('layouts/default', ['title' => 'Termine', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<div class="container-app">
    <div class="page-header">
        <h1 class="page-title">Termine</h1>
        <p class="page-subtitle">Verwalte alle Proben und Konzerte</p>
    </div>

    <?php if (empty($rehearsals)): ?>
        <?php 
            $title = 'Keine Termine gefunden';
            $message = 'Lege einen neuen Termin an, um hier Proben zu sehen.';
            $actionHref = '/rehearsals/create';
            $actionLabel = 'Termin hinzufügen';
            include __DIR__ . '/../components/empty-state.php';
        ?>
    <?php else: ?>
        <div class="rehearsal-grid">
            <?php foreach ($rehearsals as $rehearsal): ?>
                <?php 
                    $rehearsalId = $rehearsal['id'];
                    $date = $rehearsal['date_formatted'] ?? $rehearsal['date'];
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
                    
                    // Convert group keys to smart display
                    $smartDisplay = new \App\Core\SmartGroupDisplay();
                    $groupsText = $smartDisplay->generateDescription($groupKeys);
                    $groupsDisplay = str_replace(", ", "<br>", $groupsText);
                ?>
                
                <div class="rehearsal-card" style="<?= !empty($rehearsal['color']) ? 'border-left-color: ' . $rehearsal['color'] . ';' : '' ?>">
                    <div class="rehearsal-card-content">
                        <div class="rehearsal-card-info">
                            <div class="rehearsal-card-primary">
                                <span class="rehearsal-date-time"><?= htmlspecialchars($rehearsal['date_formatted'] ?? $date) ?></span>
                                <span class="rehearsal-type"><?= $groupsDisplay ?></span>
                            </div>
                            <div class="rehearsal-card-secondary">
                                <span class="rehearsal-time"><?= htmlspecialchars($time_display) ?></span>
                                <span class="rehearsal-location"><?= htmlspecialchars($location) ?></span>
                            </div>
                        </div>
                        <div class="rehearsal-actions">
                            <button type="button" id="<?= $rehearsalId ?>" class="btn-icon btn-outline edit-btn">
                                <i ><?= icon('edit', 'text-gray-600') ?></i>
                            </button>
                            <button type="button" id="<?= $rehearsalId ?>" class="btn-icon btn-danger delete-btn">
                                <i ><?= icon('trash', 'text-white') ?></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
         <a href="/rehearsals/create" class="fixed bottom-5 right-5 bg-primary text-white rounded-full w-14 h-14 flex items-center justify-center shadow-lg hover:scale-110 transition-transform">
         <i ><?= icon('plus', 'text-white') ?></i>
     </a>
</div>

<script>
// Delete rehearsal with AJAX and Sweetalert2
document.querySelectorAll('.delete-btn').forEach(function(element) {
    element.addEventListener('click', function(event) {
        const id = event.currentTarget.id;
        
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
    });
});

// Edit rehearsal redirect
document.querySelectorAll('.edit-btn').forEach(function(element) {
    element.addEventListener('click', function(event) {
        const buttonId = event.currentTarget.id;
        window.location.href = '/rehearsals/edit/' + buttonId;
    });
});
</script> 