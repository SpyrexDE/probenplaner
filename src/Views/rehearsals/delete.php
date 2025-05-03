<?php $this->layout('layouts/default', ['title' => 'Delete Rehearsal', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Delete Rehearsal</h4>
                </div>
                <div class="card-body">
                    <p class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Are you sure you want to delete this rehearsal? This action cannot be undone.
                    </p>
                    
                    <div class="mb-4">
                        <h5>Rehearsal Details:</h5>
                        <ul class="list-group">
                            <li class="list-group-item"><strong>Date:</strong> <?= htmlspecialchars($rehearsal['date']) ?></li>
                            <li class="list-group-item"><strong>Time:</strong> <?= htmlspecialchars($rehearsal['time']) ?></li>
                            <li class="list-group-item"><strong>Location:</strong> <?= htmlspecialchars($rehearsal['location']) ?></li>
                            <?php if (!empty($rehearsal['description'])): ?>
                                <li class="list-group-item"><strong>Notes:</strong> <?= htmlspecialchars($rehearsal['description']) ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <form method="post" action="/rehearsals/delete/<?= $rehearsal['id'] ?>">
                        <div class="d-flex justify-content-between">
                            <a href="/rehearsals" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash-alt"></i> Yes, Delete Rehearsal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div> 