<?php $this->layout('layouts/default', ['title' => 'Delete Rehearsal', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<div class="container-app">
    
    <div class="form-container">
        <div class="confirmation-card">
            <div class="confirmation-header">
                <i class=" text-warning"><?= icon('exclamation-triangle', 'text-gray-600') ?>></i>
                <h3>Termin löschen</h3>
            </div>
            
            <div class="confirmation-content">
                <p class="confirmation-message">
                    <strong>Achtung:</strong> Diese Aktion kann nicht rückgängig gemacht werden.
                </p>
                <p class="confirmation-details">
                    Alle zugehörigen Daten, einschließlich Teilnehmerantworten und Notizen, werden dauerhaft gelöscht.
                </p>
                
                <div class="rehearsal-details">
                    <h4>Termin-Details:</h4>
                    <div class="detail-item">
                        <span class="detail-label">Datum:</span>
                        <span class="detail-value"><?= htmlspecialchars($rehearsal['date_formatted'] ?? $rehearsal['date']) ?></span>
                    </div>
                    <?php 
                        $start_time_del = isset($rehearsal['start_time']) ? substr($rehearsal['start_time'], 0, 5) : '??:??';
                        $end_time_del = isset($rehearsal['end_time']) ? substr($rehearsal['end_time'], 0, 5) : '??:??';
                        $time_display_del = $start_time_del . ' - ' . $end_time_del;
                    ?>
                    <div class="detail-item">
                        <span class="detail-label">Zeit:</span>
                        <span class="detail-value"><?= htmlspecialchars($time_display_del) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Ort:</span>
                        <span class="detail-value"><?= htmlspecialchars($rehearsal['location']) ?></span>
                    </div>
                    <?php if (!empty($rehearsal['description'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Notizen:</span>
                            <span class="detail-value"><?= htmlspecialchars($rehearsal['description']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <form action="/<?= $_SESSION['current_orchestra_id'] ?>/rehearsals/delete" method="post" class="confirmation-form">
                <input type="hidden" name="id" value="<?= htmlspecialchars($rehearsal['id']) ?>">
                <div class="form-actions">
                    <button type="submit" class="btn-danger" onclick="return confirmDelete(event)">
                        <i class=" -alt"><?= icon('trash', 'text-white') ?></i>
                        Termin löschen
                    </button>
                                         <a href="/<?= $_SESSION['current_orchestra_id'] ?>/rehearsals" class="btn-outline">Abbrechen</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(event) {
    event.preventDefault();
    Swal.fire({
        title: 'Termin löschen?',
        html: '<div class="text-left"><p><i class=" text-warning"><?= icon('exclamation-triangle', 'text-gray-600') ?>></i> <strong>Achtung:</strong> Diese Aktion kann nicht rückgängig gemacht werden.</p><p>Alle zugehörigen Daten, einschließlich Teilnehmerantworten und Notizen, werden dauerhaft gelöscht.</p></div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Löschen',
        cancelButtonText: 'Abbrechen',
        confirmButtonColor: '#ef4444',
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            event.target.closest('form').submit();
        }
    });
    return false;
}
</script> 