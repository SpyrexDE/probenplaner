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

<style>
/* Professional Mobile-First Card Design */
* {
    -webkit-user-select: none;
    -moz-user-select: none;
    user-select: none;
}

input, textarea {
    -webkit-user-select: text;
    -moz-user-select: text;
    user-select: text;
}

.container-fluid {
    padding: 4px 8px 60px;
    max-width: 800px;
    margin: 0 auto;
}

/* Professional Card Design */
.rehearsal-card {
    background: white;
    border: 1px solid #e8eaed;
    border-left: 4px solid #dadce0;
    border-radius: 12px;
    margin: 8px 0;
    padding: 16px 20px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(60,64,67,0.1);
    min-height: 72px;
}

.rehearsal-card:hover {
    border-color: #dadce0;
    border-left-color: #4285f4;
    box-shadow: 0 4px 12px rgba(60,64,67,0.15);
    transform: translateY(-2px);
}

/* Status indicators */
.rehearsal-card.greenOut {
    border-left-color: #34a853;
}

.rehearsal-card.redOut {
    border-left-color: #ea4335;
}

.rehearsal-card.grayOut {
    border-left-color: #9aa0a6;
}

/* Card content */
.card-content {
    display: flex;
    align-items: center;
    width: 100%;
    gap: 16px;
}

.card-info {
    flex: 1;
    min-width: 0;
}

.info-primary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 4px;
}

.info-secondary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    opacity: 0.8;
}

.date-time {
    font-size: 15px;
    font-weight: 600;
    color: #202124;
}

.event-type {
    font-size: 14px;
    font-weight: 500;
    color: #5f6368;
    margin-left: 8px;
}

.time-detail {
    font-size: 13px;
    color: #5f6368;
}

.location {
    font-size: 13px;
    color: #5f6368;
    margin-left: 8px;
}

/* Note indicator */
.note-dot {
    width: 8px;
    height: 8px;
    background: #ea4335;
    border-radius: 50%;
    opacity: 0;
    transition: opacity 0.2s;
    margin-left: 6px;
}

.note-dot.visible {
    opacity: 1;
}

/* Professional Action Buttons */
.card-actions {
    display: flex;
    gap: 10px;
    flex-shrink: 0;
}

.action-btn {
    width: 52px;
    height: 52px;
    border: 2px solid #e8eaed;
    background: white;
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(60,64,67,0.1);
    position: relative;
}

.action-btn img {
    width: 28px;
    height: 28px;
    transition: all 0.2s ease;
    filter: brightness(0.8);
}

.action-btn:hover {
    border-color: #4285f4;
    background: #f8f9ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(66,133,244,0.15);
}

.action-btn:hover img {
    filter: brightness(1);
    transform: scale(1.1);
}

.action-btn:active {
    transform: translateY(-1px);
    transition: all 0.1s ease;
}

/* Selected state */
.action-btn:not(.deselected) {
    border-color: #34a853;
    background: #e8f5e8;
    box-shadow: 0 2px 8px rgba(52,168,83,0.2);
}

.action-btn.crossBtn:not(.deselected) {
    border-color: #ea4335;
    background: #fce8e6;
    box-shadow: 0 2px 8px rgba(234,67,53,0.2);
}

.action-btn:not(.deselected) img {
    filter: brightness(1) saturate(1.2);
}

/* Deselected state */
.action-btn.deselected {
    opacity: 0.4;
    background: #f8f9fa;
    border-color: #e8eaed;
    box-shadow: none;
}

.action-btn.deselected img {
    filter: grayscale(100%) brightness(0.7);
}

/* Responsive Button Sizes - Maintaining Touch Standards */
@media (max-width: 768px) {
    .container-fluid {
        padding: 2px 6px 60px;
    }
    
    .rehearsal-card {
        margin: 6px 0;
        padding: 12px 14px;
        gap: 12px;
    }
    
    .card-content {
        gap: 14px;
    }
    
    .card-actions {
        gap: 8px;
    }
    
    .action-btn {
        width: 48px;
        height: 48px;
        border-radius: 10px;
    }
    
    .action-btn img {
        width: 26px;
        height: 26px;
    }
    
    .date-time {
        font-size: 14px;
    }
    
    .event-type {
        font-size: 13px;
    }
    
    .time-detail,
    .location {
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .rehearsal-card {
        padding: 10px 12px;
        gap: 10px;
        margin: 5px 0;
    }
    
    .card-content {
        gap: 10px;
    }
    
    .info-primary,
    .info-secondary {
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
    }
    
    .event-type,
    .location {
        margin-left: 0;
    }
    
    .card-actions {
        gap: 6px;
    }
    
    .action-btn {
        width: 44px;
        height: 44px;
        border-radius: 8px;
    }
    
    .action-btn img {
        width: 24px;
        height: 24px;
    }
}

/* Disabled state */
.action-btn.disabled {
    opacity: 0.3;
    cursor: not-allowed;
    border-color: #f1f3f4;
    background: #f8f9fa;
    transform: none !important;
    box-shadow: none !important;
}

/* Loading indicator for buttons */
.action-btn.loading {
    position: relative;
    pointer-events: none;
}

.action-btn.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 16px;
    height: 16px;
    margin: -8px 0 0 -8px;
    border: 2px solid #4285f4;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 0.8s linear infinite;
}

.action-btn.loading img {
    opacity: 0;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Save indicator */
.save-indicator {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #333;
    color: white;
    padding: 8px 16px;
    border-radius: 24px;
    z-index: 1000;
    display: none;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.3);
}

.save-indicator.show {
    display: flex;
}

.save-indicator i {
    font-size: 14px;
}
</style>

<div class="container-fluid">
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
        
        $groupsText = str_replace("_", " ", implode(", ", $groupArray));
        
        // Prepare time display
        $start_time_prom = isset($rehearsal['start_time']) ? substr($rehearsal['start_time'], 0, 5) : '??:??';
        $end_time_prom = isset($rehearsal['end_time']) ? substr($rehearsal['end_time'], 0, 5) : '??:??';
        $time_display_prom = $start_time_prom . ' - ' . $end_time_prom;
        
        // Determine color class
        $colorClass = 'grayOut'; // Default for pending
        
        if ($status === 'attending') {
            $colorClass = 'greenOut';
        } else if ($status === 'not_attending') {
            $colorClass = 'redOut';
        }
        ?>
        
        <div class="rehearsal-card <?= $colorClass ?>" style="<?= !empty($rehearsal['color']) ? 'background-color: ' . $rehearsal['color'] . ';' : '' ?>">
            <div class="card-content">
                <div class="card-info">
                    <div class="info-primary">
                        <span class="date-time"><?= htmlspecialchars($rehearsal['date_formatted'] ?? $rehearsal['date']) ?></span>
                        <span class="event-type"><?= $groupsText ?>
                            <span class="note-dot <?= !empty($note) ? 'visible' : '' ?>"></span>
                        </span>
                    </div>
                    <div class="info-secondary">
                        <span class="time-detail"><?= htmlspecialchars($time_display_prom) ?></span>
                        <span class="location"><?= htmlspecialchars($rehearsal['location']) ?></span>
                    </div>
                </div>
                <div class="card-actions">
                    <button type="button" id="<?= $rehearsal['id'] ?>" class="checkBtn action-btn <?= $status !== 'attending' ? 'deselected' : '' ?>">
                        <img src="/assets/img/icons8_checked_checkbox_48px_2.png" alt="Zusagen">
                    </button>
                    <button type="button" id="<?= $rehearsal['id'] ?>" class="crossBtn action-btn <?= $status !== 'not_attending' ? 'deselected' : '' ?>">
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
        $(this).removeClass('deselected');
        $(this).siblings('.crossBtn').addClass('deselected');
        
        // Update UI
        var container = $(this).closest('.rehearsal-card');
        container.removeClass('redOut grayOut').addClass('greenOut');
        
        // Hide note dot
        container.find('.note-dot').removeClass('visible');
        
        // Clear any existing note
        $('#note' + id).val('');
        
        // Add to queue
        queueUpdate("promise", id, true, '');
        
        // Update sidebar stats immediately for better UX
        if (typeof window.loadUserStats === 'function') {
            console.log('Updating sidebar stats after attending change...');
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
        $(this).removeClass('deselected');
        $(this).siblings('.checkBtn').addClass('deselected');
        
        // Update UI
        var container = $(this).closest('.rehearsal-card');
        container.removeClass('grayOut greenOut').addClass('redOut');
        
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
                console.log('Updating sidebar stats after direct decline...');
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
                console.log('Updating sidebar stats after note change...');
                setTimeout(function() {
                    window.loadUserStats();
                }, 100);
            }
        });
    }
    
    // Double click to edit note
    $('.rehearsal-card').on('dblclick', function() {
        var card = $(this);
        if (card.hasClass('redOut')) {
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
                        console.log('Updating sidebar stats after promise change...');
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