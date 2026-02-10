<?php $this->layout('layouts/default', ['title' => 'Meine Proben-Rückmeldungen', 'currentPage' => $currentPage ?? 'promises']) ?>

<?php 
/**
 * Promises (attendance) view - Professional Mobile-First Design
 */

// Helper function to sort groups by importance
function sortGroups($groups) {
    $groupArray = array_keys($groups);
    $tm = \App\Core\RehearsalTypeManager::class;

    usort($groupArray, function($a, $b) use ($tm) {
        $userType = $_SESSION['current_type'] ?? '';

        if ($b == $tm::TYPE_CONCERT_TOUR) {
            return 1;
        } elseif ($b == $tm::TYPE_CONCERT && $a != $tm::TYPE_CONCERT_TOUR) {
            return 1;
        } elseif ($b == $tm::TYPE_DRESS_REHEARSAL && $a != $tm::TYPE_CONCERT) {
            return 1;
        } elseif ($b == $tm::TYPE_SECTIONAL && $a != $tm::TYPE_DRESS_REHEARSAL && $a != $tm::TYPE_CONCERT) {
            return 1;
        } elseif ($b == $userType && $a != $tm::TYPE_SECTIONAL && $a != $tm::TYPE_DRESS_REHEARSAL && $a != $tm::TYPE_CONCERT) {
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
        
        <!-- Past Rehearsals section (dynamically populated via AJAX) -->
        <?php if ($showOld && !empty($pastRehearsals)): ?>
            <div class="past-rehearsals-section" id="pastRehearsalsSection">
                <?php foreach ($pastRehearsals as $rehearsal): ?>
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
                    
                    // Generate smart display text with integrated Kleingruppe handling
                    $smartDisplay = new \App\Core\SmartGroupDisplay();
                    $groupsText = $smartDisplay->generateDescription(
                        $groupArray, 
                        $rehearsal, 
                        false // Not admin view
                    );
                    
                    // Set options for the rehearsal card component
                    $context = 'promises';
                    $options = [
                        'status' => $status,
                        'note' => $note,
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
            // Determine status for this rehearsal
            $status = 'pending';
            $note = '';
            
            if (isset($promises[$rehearsal['id']])) {
                $status = $promises[$rehearsal['id']]['attending'] ? 'attending' : 'not_attending';
                $note = $promises[$rehearsal['id']]['note'];
            }
            
            // Get group information
            $groupArray = $rehearsal['groups'] ?? [];
            
            // Generate smart display text with integrated Kleingruppe handling
            $smartDisplay = new \App\Core\SmartGroupDisplay();
            $groupsText = $smartDisplay->generateDescription(
                $groupArray, 
                $rehearsal, 
                false // Not admin view
            );
            
            // Set options for the rehearsal card component
            $context = 'promises';
            $options = [
                'status' => $status,
                'note' => $note,
                'showButtons' => true
            ];
            include __DIR__ . '/../components/rehearsal-card.php';
            ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Save indicator -->
<?php 
// Ensure component renders the indicator
$renderComponent = true;
$message = 'Speichern...';
$show = false;
$type = 'default';
$icon = 'spinner';
include __DIR__ . '/../components/save-indicator.php';
?>

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
    
    // Initialize note displays for existing notes on page load
    $('.rehearsal-card input[id^="note"]').each(function() {
        var noteInput = $(this);
        var note = noteInput.val();
        var rehearsalId = noteInput.attr('id').replace('note', '');
        var container = $('.rehearsal-card').has('#note' + rehearsalId);
        
        if (note && note.trim() !== '') {
            // Add note tag if not already present and card is declined  
            if (container.hasClass('status-not_attending') && container.find('.rehearsal-note-tag').length === 0) {
                var noteHtml = '<div class="rehearsal-note-tag">' +
                               '<i class="fa-solid fa-quote-left rehearsal-note-icon"></i>' +
                               '<span class="rehearsal-note-text">' + $('<div>').text(note).html() + '</span></div>';
                container.append(noteHtml);
            }
        }
    });
    
    // Handle attend/not attend button clicks (using event delegation for dynamic content)
    $(document).on('click', '.checkBtn', function() {
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
        
        // Remove note tag
        container.find('.rehearsal-note-tag').remove();
        
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
    
    $(document).on('click', '.crossBtn', function() {
        if ($(this).hasClass('disabled')) {
            return;
        }
        
        var id = $(this).attr('id');
        var container = $(this).closest('.rehearsal-card');
        
        // Check if already declined - if so, open note dialog for editing
        if ($(this).hasClass('selected') && container.hasClass('status-not_attending')) {
            var existingNote = $('#note' + id).val() || '';
            showNoteDialog(id, existingNote);
            return;
        }
        
        // Disable buttons for this rehearsal
        disableRehearsalButtons(id);
        
        // Toggle selection
        $(this).removeClass('deselected').addClass('selected');
        $(this).siblings('.checkBtn').removeClass('selected').addClass('deselected');
        
        // Update UI
        
        // Get existing note
        var existingNote = $('#note' + id).val() || '';
        
        // Always show note dialog for new decline
        showNoteDialog(id, existingNote);
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
            allowOutsideClick: true,
            allowEscapeKey: true,
            inputValidator: (value) => {
                if (value && value.length > 500) {
                    return 'Notiz ist zu lang (max. 500 Zeichen)';
                }
            }
        }).then((result) => {
            var note = currentNote; // Keep existing note by default
            var container = $('.rehearsal-card').has('#' + id);
            var shouldUpdate = false;
            
            if (result.isConfirmed) {
                // User clicked "Speichern" - save the note (even if empty)
                note = result.value || '';
                shouldUpdate = true;
                
                // Update or create note tag
                var existingNoteTag = container.find('.rehearsal-note-tag');
                if (note) {
                    if (existingNoteTag.length) {
                        existingNoteTag.find('.rehearsal-note-text').text(note);
                    } else {
                        var noteHtml = '<div class="rehearsal-note-tag">' +
                                       '<i class="fa-solid fa-quote-left rehearsal-note-icon"></i>' +
                                       '<span class="rehearsal-note-text">' + $('<div>').text(note).html() + '</span></div>';
                        container.append(noteHtml);
                    }
                } else {
                    // Note was saved as empty
                    existingNoteTag.remove();
                }
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // User clicked "Ohne Grund" - clear the note completely
                note = '';
                shouldUpdate = true;
                container.find('.rehearsal-note-tag').remove();
            }
            // For any other dismissal (clicking outside, ESC), do nothing - keep existing note
            
            if (shouldUpdate) {
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
            }
        });
    }
    
    // Double click to edit note (using event delegation for dynamic content)
    $(document).on('dblclick', '.rehearsal-card', function() {
        var card = $(this);
        if (card.hasClass('status-not_attending')) {
            var id = card.find('.crossBtn').attr('id');
            var currentNote = $('#note' + id).val() || '';
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
            window.spinnerShowTime = Date.now(); // Track when spinner was shown
        }
        
        var update = window.updateQueue.shift();
        
        if (update.type === "promise") {
            updatePromise(update.id, update.attending, update.note);
        }
    }
    
    function updatePromise(id, attending, note) {
        <?php $orchestraId = $_SESSION['current_orchestra_id'] ?? 1; ?>
        $.ajax({
            url: '/<?= $orchestraId ?>/promises/update',
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
                    hideSaveIndicatorWithDelay();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                notifyError('Verbindungsfehler beim Speichern der Zusage');
                
                // Re-enable buttons
                enableRehearsalButtons(id);
                
                window.isProcessingQueue = false;
                hideSaveIndicatorWithDelay();
            }
        });
    }
    
    function hideSaveIndicatorWithDelay() {
        var minDisplayTime = 500; // 500ms minimum display time
        var elapsed = Date.now() - (window.spinnerShowTime || 0);
        var remainingTime = Math.max(0, minDisplayTime - elapsed);
        
        setTimeout(function() {
            window.promiseBeingUpdated = false;
            $('#save-indicator').removeClass('show');
        }, remainingTime);
    }
    
    function disableRehearsalButtons(id) {
        $('.rehearsal-card').has('#' + id).find('.action-btn').addClass('disabled loading');
    }
    
    function enableRehearsalButtons(id) {
        $('.rehearsal-card').has('#' + id).find('.action-btn').removeClass('disabled loading');
    }
});

// Stretch text functionality for time display
function stretchTexts() {
    document.querySelectorAll('.stretch-text').forEach(el => {
        // Get the date element from the same datetime block
        const datetimeBlock = el.closest('.rehearsal-datetime-block');
        const dateElement = datetimeBlock.querySelector('.rehearsal-date');
        
        if (dateElement) {
            // Set time container width to match date width
            const dateWidth = dateElement.offsetWidth;
            el.parentElement.style.width = dateWidth + 'px';
            
            // Apply stretching
            el.style.transform = 'translateY(-50%)';
            const scale = el.parentElement.offsetWidth / el.offsetWidth;
            el.style.transform = `translateY(-50%) scaleX(${scale})`;
        }
    });
}

// Run on load and resize
window.addEventListener('load', stretchTexts);
window.addEventListener('resize', stretchTexts);
</script>