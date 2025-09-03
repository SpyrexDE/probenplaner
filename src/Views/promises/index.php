<?php $this->layout('layouts/default', ['title' => 'My Rehearsal Responses', 'currentPage' => $currentPage ?? 'promises']) ?>

<?php 
/**
 * Promises (attendance) view - Professional Mobile-First Design
 */

// Helper function to sort groups by importance
function sortGroups($groups) {
    $groupArray = array_keys($groups);
    
    usort($groupArray, function($a, $b) {
        $userType = $_SESSION['type'];
        
        if ($b == "Konzertreise") {
            return 1;
        } elseif ($b == "Konzert" && $a != "Konzertreise") {
            return 1;
        } elseif ($b == "Generalprobe" && $a != "Konzert") {
            return 1;
        } elseif ($b == "Registerprobe" && $a != "Generalprobe" && $a != "Konzert") {
            return 1;
        } elseif ($b == $userType && $a != "Registerprobe" && $a != "Generalprobe" && $a != "Konzert") {
            return 1;
        } else {
            return -1;
        }
    });
    
    return $groupArray;
}
?>











<div class="container-app">
    <?php if (empty($rehearsals)): ?>
        <?php 
            $title = 'Keine Proben gefunden';
            $message = 'Aktuell sind keine Proben für dich eingetragen.';
            include __DIR__ . '/../components/empty-state.php';
        ?>
    <?php else: ?>
    <?php foreach ($rehearsals as $rehearsal): ?>
        <?php 
        // Determine status for this rehearsal
        $status = 'pending';
        $note = '';
        
        if (isset($promises[$rehearsal['id']])) {
            $status = $promises[$rehearsal['id']]['attending'] ? 'attending' : 'not_attending';
            $note = $promises[$rehearsal['id']]['note'];
        }
        
        // Get group information
        $groupArray = $rehearsal['groups'] ?? [];
        
        // Check if it's a small group
        $isSmallGroup = isset($rehearsal['is_small_group']) && $rehearsal['is_small_group'] == 1;
        
        // Add * suffix to group names if it's a small group
        if ($isSmallGroup) {
            foreach ($groupArray as &$group) {
                $group .= '*';
            }
        }
        
        $smartDisplay = new \App\Core\SmartGroupDisplay();
        $groupsText = $smartDisplay->generateDescription($groupArray);
        
        // Prepare time display
        $start_time_prom = isset($rehearsal['start_time']) ? substr($rehearsal['start_time'], 0, 5) : '??:??';
        $end_time_prom = isset($rehearsal['end_time']) ? substr($rehearsal['end_time'], 0, 5) : '??:??';
        $time_display_prom = $start_time_prom . ' - ' . $end_time_prom;
        
        // Determine color class
        $colorClass = 'status-pending'; // Default for pending
        
        if ($status === 'attending') {
            $colorClass = 'status-attending';
        } else if ($status === 'not_attending') {
            $colorClass = 'status-not_attending';
        }
        ?>
        
        <div class="rehearsal-card status-<?= $status ?>" style="<?= !empty($rehearsal['color']) ? 'border-left-color: ' . $rehearsal['color'] . ';' : '' ?>">
            <div class="rehearsal-card-content">
                <div class="rehearsal-card-info">
                    <div class="rehearsal-card-primary">
                        <span class="rehearsal-date-time"><?= htmlspecialchars($rehearsal['date_formatted'] ?? $rehearsal['date']) ?></span>
                        <span class="rehearsal-type"><?= $groupsText ?>
                            <span class="rehearsal-note-dot <?= !empty($note) ? 'visible' : '' ?>"></span>
                        </span>
                    </div>
                    <div class="rehearsal-card-secondary">
                        <span class="rehearsal-time"><?= htmlspecialchars($time_display_prom) ?></span>
                        <span class="rehearsal-location"><?= htmlspecialchars($rehearsal['location']) ?></span>
                    </div>
                </div>
                <div class="rehearsal-actions">
                    <button type="button" id="<?= $rehearsal['id'] ?>" class="checkBtn action-btn <?= $status !== 'attending' ? 'deselected' : 'selected' ?>">
                        <img src="/assets/img/icons8_checked_checkbox_48px_2.png" alt="Zusagen">
                    </button>
                    <button type="button" id="<?= $rehearsal['id'] ?>" class="crossBtn action-btn cross-btn <?= $status !== 'not_attending' ? 'deselected' : 'selected' ?>">
                        <img src="/assets/img/icons8_close_window_48px_1.png" alt="Absagen">
                    </button>
                </div>
            </div>
            <input type="hidden" id="note<?= $rehearsal['id'] ?>" value="<?= htmlspecialchars($note) ?>">
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Save indicator -->
<div id="save-indicator" class="save-indicator">
    <i class="fa fa-spinner fa-spin"></i>
    <span>Speichern...</span>
</div>

<script>
$(document).ready(function() {
    // Store pending updates to prevent async issues
    window.updateQueue = [];
    window.isProcessingQueue = false;
    
    // Prevent page refreshes while updates are in progress
    $(window).on('beforeunload', function() {
        if (window.promiseBeingUpdated || window.updateQueue.length > 0) {
            return "Änderungen werden noch gespeichert. Bitte warten Sie einen Moment.";
        }
    });
    
    // Handle attend/not attend button clicks
    $('.checkBtn').click(function() {
        if ($(this).hasClass('disabled')) {
            return;
        }
        
        var id = $(this).attr('id');
        
        // Disable buttons for this rehearsal
        disableRehearsalButtons(id);
        
        // Toggle selection
        $(this).removeClass('deselected').addClass('selected');
        $(this).siblings('.crossBtn').removeClass('selected').addClass('deselected');
        
        // Update UI
        var container = $(this).closest('.rehearsal-card');
        container.removeClass('status-not_attending status-pending').addClass('status-attending');
        
        // Hide note dot
        container.find('.note-dot').removeClass('visible');
        
        // Clear any existing note
        $('#note' + id).val('');
        
        // Add to queue
        queueUpdate("promise", id, true, '');
        
        // Update sidebar stats immediately for better UX
        if (typeof window.loadUserStats === 'function') {
            setTimeout(function() {
                window.loadUserStats();
            }, 100);
        }
    });
    
    $('.crossBtn').click(function() {
        if ($(this).hasClass('disabled')) {
            return;
        }
        
        var id = $(this).attr('id');
        
        // Disable buttons for this rehearsal
        disableRehearsalButtons(id);
        
        // Toggle selection
        $(this).removeClass('deselected').addClass('selected');
        $(this).siblings('.checkBtn').removeClass('selected').addClass('deselected');
        
        // Update UI
        var container = $(this).closest('.rehearsal-card');
        container.removeClass('status-pending status-attending').addClass('status-not_attending');
        
        // Get existing note
        var existingNote = $('#note' + id).val();
        
        // Show note prompt if no note exists
        if (!existingNote) {
            showNoteDialog(id, '');
        } else {
            // Show existing note dot
            container.find('.note-dot').addClass('visible');
            queueUpdate("promise", id, false, existingNote);
            
            // Update sidebar stats immediately for better UX
            if (typeof window.loadUserStats === 'function') {
                setTimeout(function() {
                    window.loadUserStats();
                }, 100);
            }
        }
    });
    
    // Note dialog function
    function showNoteDialog(id, currentNote) {
        Swal.fire({
            title: 'Grund für Absage (optional)',
            input: 'textarea',
            inputValue: currentNote,
            inputPlaceholder: 'Warum können Sie nicht teilnehmen?',
            showCancelButton: true,
            confirmButtonText: 'Speichern',
            cancelButtonText: 'Ohne Grund',
            confirmButtonColor: '#478cf4',
            cancelButtonColor: '#6c757d',
            inputValidator: (value) => {
                if (value && value.length > 500) {
                    return 'Notiz ist zu lang (max. 500 Zeichen)';
                }
            }
        }).then((result) => {
            var note = '';
            if (result.isConfirmed && result.value) {
                note = result.value;
                // Show note dot
                $('.rehearsal-card').has('#' + id).find('.note-dot').addClass('visible');
            }
            
            // Update hidden field
            $('#note' + id).val(note);
            
            // Queue the update
            queueUpdate("promise", id, false, note);
            
            // Update sidebar stats after note change
            if (typeof window.loadUserStats === 'function') {
                setTimeout(function() {
                    window.loadUserStats();
                }, 100);
            }
        });
    }
    
    // Double click to edit note
    $('.rehearsal-card').on('dblclick', function() {
        var card = $(this);
        if (card.hasClass('status-not_attending')) {
            var id = card.find('.crossBtn').attr('id');
            var currentNote = $('#note' + id).val();
            showNoteDialog(id, currentNote);
        }
    });
    
    function queueUpdate(type, id, attending, note) {
        window.updateQueue.push({
            type: type,
            id: id,
            attending: attending,
            note: note
        });
        
        processQueue();
    }
    
    function processQueue() {
        if (window.isProcessingQueue || window.updateQueue.length === 0) {
            return;
        }
        
        window.isProcessingQueue = true;
        
        // Show save indicator
        if (!window.promiseBeingUpdated) {
            $('#save-indicator').addClass('show');
            window.promiseBeingUpdated = true;
        }
        
        var update = window.updateQueue.shift();
        
        if (update.type === "promise") {
            updatePromise(update.id, update.attending, update.note);
        }
    }
    
    function updatePromise(id, attending, note) {
        $.ajax({
            url: '/promises/update',
            type: 'POST',
            data: {
                id: id,
                status: attending ? 1 : 0,
                note: note,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Success handled in UI already
                    
                    // Update sidebar stats after successful promise update
                    if (typeof window.loadUserStats === 'function') {
                        setTimeout(function() {
                            window.loadUserStats();
                        }, 100); // Small delay to ensure UI is updated
                    }
                } else {
                    console.error('Server returned error:', response.message);
                    notifyError('Fehler beim Speichern: ' + (response.message || 'Unbekannter Fehler'));
                }
                
                // Re-enable buttons
                enableRehearsalButtons(id);
                
                // Continue processing queue
                window.isProcessingQueue = false;
                
                if (window.updateQueue.length > 0) {
                    processQueue();
                } else {
                    window.promiseBeingUpdated = false;
                    $('#save-indicator').removeClass('show');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                notifyError('Verbindungsfehler beim Speichern der Zusage');
                
                // Re-enable buttons
                enableRehearsalButtons(id);
                
                window.isProcessingQueue = false;
                window.promiseBeingUpdated = false;
                $('#save-indicator').removeClass('show');
            }
        });
    }
    
    function disableRehearsalButtons(id) {
        $('.rehearsal-card').has('#' + id).find('.action-btn').addClass('disabled loading');
    }
    
    function enableRehearsalButtons(id) {
        $('.rehearsal-card').has('#' + id).find('.action-btn').removeClass('disabled loading');
    }
});
</script>