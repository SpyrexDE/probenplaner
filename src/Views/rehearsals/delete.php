<?php $this->layout('layouts/default', ['title' => 'Delete Rehearsal', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Delete Rehearsal</h4>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h5>Rehearsal Details:</h5>
                        <ul class="list-group">
                            <li class="list-group-item"><strong>Date:</strong> <?= htmlspecialchars($rehearsal['date']) ?></li>
                            <?php 
                                $start_time_del = isset($rehearsal['start_time']) ? substr($rehearsal['start_time'], 0, 5) : '??:??';
                                $end_time_del = isset($rehearsal['end_time']) ? substr($rehearsal['end_time'], 0, 5) : '??:??';
                                $time_display_del = $start_time_del . ' - ' . $end_time_del;
                            ?>
                            <li class="list-group-item"><strong>Time:</strong> <?= htmlspecialchars($time_display_del) ?></li>
                            <li class="list-group-item"><strong>Location:</strong> <?= htmlspecialchars($rehearsal['location']) ?></li>
                            <?php if (!empty($rehearsal['description'])): ?>
                                <li class="list-group-item"><strong>Notes:</strong> <?= htmlspecialchars($rehearsal['description']) ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <form action="/rehearsals/delete" method="post">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($rehearsal['id']) ?>">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger" onclick="return confirmDelete(event)">
                                <i class="fas fa-trash-alt me-2"></i>Delete Rehearsal
                            </button>
                            <a href="/rehearsals" class="btn btn-secondary">Cancel</a>
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
        title: 'Delete Rehearsal?',
        html: '<div class="text-left"><p><i class="fas fa-exclamation-triangle text-warning"></i> <strong>Warning:</strong> This action cannot be undone.</p><p>All associated data, including member responses and notes, will be permanently deleted.</p></div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
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