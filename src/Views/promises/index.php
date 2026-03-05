<?php $this->layout('layouts/default', ['title' => 'Meine Proben-Rückmeldungen', 'currentPage' => $currentPage ?? 'promises']) ?>

<?php
/**
 * Promises (attendance) view - Professional Mobile-First Design
 */

// Helper function to sort groups by importance
function sortGroups($groups)
{
    $groupArray = array_keys($groups);
    $tm = \App\Core\RehearsalTypeManager::class;

    usort($groupArray, function ($a, $b) use ($tm) {
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

        if (!$showOld && ($hasPastRehearsals ?? false)) {
            $actionHref = '?showOld=1';
            $actionLabel = 'Vergangene Proben anzeigen';
        }

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
        // Configuration
        const FORCE_DECLINE_REASON = <?= json_encode($forceDeclineReason ?? false) ?>;
        const ALLOW_ATTENDANCE_RESET = <?= json_encode($allowAttendanceReset ?? true) ?>;

        // Store pending updates to prevent async issues
        window.updateQueue = [];
        window.isProcessingQueue = false;

        // Prevent page refreshes while updates are in progress
        $(window).on('beforeunload', function() {
            if (window.promiseBeingUpdated || window.updateQueue.length > 0) {
                return "Änderungen werden noch gespeichert. Bitte warten Sie einen Moment.";
            }
        });

        // Initialize note tags
        $('.rehearsal-card input[id^="note"]').each(function() {
            var noteInput = $(this);
            var note = noteInput.val();
            var rehearsalId = noteInput.attr('id').replace('note', '');
            var container = $('.rehearsal-card').has('#note' + rehearsalId);

            if (note && note.trim() !== '') {
                if (container.hasClass('status-not_attending') && container.find('.rehearsal-note-tag').length === 0) {
                    var noteHtml = '<div class="rehearsal-note-tag">' +
                        '<i class="fa-solid fa-quote-left rehearsal-note-icon"></i>' +
                        '<span class="rehearsal-note-text">' + $('<div>').text(note).html() + '</span></div>';
                    container.append(noteHtml);
                }
            }
        });

        let longPressTimer;
        const longPressDuration = 500; // ms

        function handleLongPressStart(element) {
            if (!ALLOW_ATTENDANCE_RESET) return;
            if ($(element).hasClass('disabled')) return;

            longPressTimer = setTimeout(function() {
                // Long press detected
                var id = $(element).attr('id');
                var container = $(element).closest('.rehearsal-card');

                if (container.find('.action-btn.selected').length === 0) {
                    // Already reset/no record -> do nothing
                    return;
                }

                disableRehearsalButtons(id);

                container.find('.checkBtn, .crossBtn').removeClass('selected').addClass('deselected');

                container.removeClass('status-attending status-not_attending');

                container.find('.rehearsal-note-tag').remove();

                $('#note' + id).val('');

                // Queue reset update
                queueUpdate("promise", id, 'reset', '');

                // Show feedback
                if (typeof window.notifySuccess === 'function') {
                    window.notifySuccess("Status zurückgesetzt", {
                        timer: 1500
                    });
                }

                // Update stats
                if (typeof window.loadUserStats === 'function') {
                    setTimeout(function() {
                        window.loadUserStats();
                    }, 100);
                }

                // Prevent the click event that might follow
                $(element).data('longPressed', true);

            }, longPressDuration);
        }

        // Helper to handle long press end
        function handleLongPressEnd(element) {
            clearTimeout(longPressTimer);
        }

        // Attach long press events to buttons
        $(document).on('mousedown touchstart', '.checkBtn, .crossBtn', function(e) {
            // Reset longPressed flag
            $(this).data('longPressed', false);
            handleLongPressStart(this);
        });

        $(document).on('mouseup touchend mouseleave', '.checkBtn, .crossBtn', function(e) {
            handleLongPressEnd(this);
        });

        // Handle attend/not attend button clicks (using event delegation for dynamic content)
        $(document).on('click', '.checkBtn', function(e) {
            // If it was a long press, ignore the click
            if ($(this).data('longPressed')) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }

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
            container.removeClass('status-not_attending').addClass('status-attending');

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

        $(document).on('click', '.crossBtn', function(e) {
            // If it was a long press, ignore the click
            if ($(this).data('longPressed')) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }

            if ($(this).hasClass('disabled')) {
                return;
            }

            var id = $(this).attr('id');
            var container = $(this).closest('.rehearsal-card');
            var existingNote = $('#note' + id).val() || '';

            // Check if already declined - if so, open note dialog for editing
            if ($(this).hasClass('selected') && container.hasClass('status-not_attending')) {
                showNoteDialog(id, existingNote);
                return;
            }

            // Check forced reason logic
            if (FORCE_DECLINE_REASON && !existingNote) {
                // Force user to enter a reason BEFORE processing the decline
                showNoteDialog(id, existingNote, true); // true = force mode
                return;
            }

            // Proceed with standard decline (if reason not forced or reason already exists)
            processDecline(id, existingNote);
        });

        function processDecline(id, note) {
            // Disable buttons for this rehearsal
            disableRehearsalButtons(id);

            var btn = $('#' + id + '.crossBtn');
            var container = btn.closest('.rehearsal-card');

            // Toggle selection
            btn.removeClass('deselected').addClass('selected');
            btn.siblings('.checkBtn').removeClass('selected').addClass('deselected');

            // Update UI status class
            container.removeClass('status-attending').addClass('status-not_attending');

            // Get or update note
            var currentNote = note !== undefined ? note : ($('#note' + id).val() || '');

            // Update note input hidden field
            $('#note' + id).val(currentNote);

            // Add to queue
            queueUpdate("promise", id, false, currentNote);

            // Update sidebar stats
            if (typeof window.loadUserStats === 'function') {
                setTimeout(function() {
                    window.loadUserStats();
                }, 100);
            }

            // If not forced mode and note is empty, we act optimistic and don't show dialog immediately
            // unless specific logic requires it. Original logic showed dialog always.
            // If we are here, we already passed forced check or have a note.
            // If we want to allow editing note after decline, we can show dialog here, 
            // but for "Forced" mode we effectively just came from the dialog.

            if (!FORCE_DECLINE_REASON && !currentNote) {
                showNoteDialog(id, currentNote);
            }
        }

        // Note dialog function
        function showNoteDialog(id, currentNote, isForced = false) {
            var cancelButtonText = isForced ? 'Abbrechen' : 'Ohne Grund';
            var showCancelButton = true;

            Swal.fire({
                title: isForced ? 'Begründung erforderlich' : 'Grund für Absage (optional)',
                input: 'textarea',
                inputValue: currentNote,
                inputPlaceholder: 'Warum können Sie nicht teilnehmen?',
                showCancelButton: showCancelButton,
                confirmButtonText: 'Speichern',
                cancelButtonText: cancelButtonText,
                confirmButtonColor: '#478cf4',
                cancelButtonColor: '#6c757d',
                allowOutsideClick: !isForced,
                allowEscapeKey: !isForced,
                inputValidator: (value) => {
                    if (isForced && (!value || !value.trim())) {
                        return 'Bitte geben Sie einen Grund an.';
                    }
                    if (value && value.length > 500) {
                        return 'Notiz ist zu lang (max. 500 Zeichen)';
                    }
                }
            }).then((result) => {
                var note = currentNote;
                var container = $('.rehearsal-card').has('#' + id);

                if (result.isConfirmed) {
                    // User entered note and clicked Confirm
                    note = result.value || '';

                    // If forced mode, this confirms the decline action
                    if (isForced) {
                        processDecline(id, note);

                        // Render note tag
                        renderNoteTag(container, note);
                    } else {
                        // Just updating note on existing decline
                        $('#note' + id).val(note);
                        renderNoteTag(container, note);
                        queueUpdate("promise", id, false, note);
                    }
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    if (isForced) {
                        // User cancelled the forced decline -> Do nothing (abort decline)
                        return;
                    } else {
                        // User clicked "Ohne Grund" -> clear note
                        note = '';
                        $('#note' + id).val('');
                        renderNoteTag(container, '');
                        queueUpdate("promise", id, false, '');
                    }
                }
            });
        }

        function renderNoteTag(container, note) {
            var existingNoteTag = container.find('.rehearsal-note-tag');
            if (note && note.trim() !== '') {
                if (existingNoteTag.length) {
                    existingNoteTag.find('.rehearsal-note-text').text(note);
                } else {
                    var noteHtml = '<div class="rehearsal-note-tag">' +
                        '<i class="fa-solid fa-quote-left rehearsal-note-icon"></i>' +
                        '<span class="rehearsal-note-text">' + $('<div>').text(note).html() + '</span></div>';
                    container.append(noteHtml);
                }
            } else {
                existingNoteTag.remove();
            }
        }

        // Double click to edit note
        $(document).on('dblclick', '.rehearsal-card', function() {
            var card = $(this);
            if (card.hasClass('status-not_attending')) {
                var id = card.find('.crossBtn').attr('id');
                var currentNote = $('#note' + id).val() || '';
                showNoteDialog(id, currentNote, FORCE_DECLINE_REASON && !currentNote);
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
            <?php $orchestraBase = ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? ''); ?>

            // Prepare data
            var data = {
                id: id,
                note: note,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            };

            if (attending === 'reset') {
                data.status = 'reset';
            } else {
                data.status = attending ? 1 : 0;
            }

            $.ajax({
                url: '/<?= $orchestraBase ?>/promises/update',
                type: 'POST',
                data: data,
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
                        const technicalDetails = response.debug_message || response.error || JSON.stringify(response, null, 2);
                        window.notifyErrorWithDetails('Fehler beim Speichern', technicalDetails);
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
                    const technicalDetails = 'Status: ' + status + '\nError: ' + error + '\nResponse: ' + xhr.responseText;
                    window.notifyErrorWithDetails('Verbindungsfehler beim Speichern der Zusage', technicalDetails);

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