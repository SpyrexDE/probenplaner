<?php $this->layout('layouts/default', ['title' => 'Termine', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<div class="container-app pb-20">
    <div class="text-center mb-6">
        <h1 class="page-title">Termine</h1>
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
            <?php foreach ($rehearsals as $rehearsal): ?>
                
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