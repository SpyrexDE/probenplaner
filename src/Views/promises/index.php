<?php $this->layout('layouts/default', ['title' => 'My Rehearsal Responses', 'currentPage' => $currentPage ?? 'promises']) ?>

<?php 
/**
 * Promises (attendance) view
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
        } elseif ($b == "Stimmprobe" && $a != "Generalprobe" && $a != "Konzert") {
            return 1;
        } elseif ($b == $userType && $a != "Stimmprobe" && $a != "Generalprobe" && $a != "Konzert") {
            return 1;
        } else {
            return -1;
        }
    });
    
    return $groupArray;
}
?>

<!-- Custom styling -->
<style>
/* Make elements unselectable */
* {
    -webkit-touch-callout: none; /* iOS Safari */
    -webkit-user-select: none;   /* Safari */
    -khtml-user-select: none;    /* Konqueror HTML */
    -moz-user-select: none;      /* Firefox */
    -ms-user-select: none;       /* Internet Explorer/Edge */
    user-select: none;           /* Non-prefixed version, currently supported by Chrome and Opera */
}

/* Allow selection only in input/textarea elements */
input, textarea {
    -webkit-touch-callout: text;
    -webkit-user-select: text;
    -khtml-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
    user-select: text;
}

/* Fix colors for check and cross buttons */
.checkBtn:not(.deselected) {
    filter: brightness(1.1) hue-rotate(5deg); /* Adjust green shade */
}

.crossBtn:not(.deselected) {
    filter: brightness(1.1) hue-rotate(-5deg); /* Adjust red shade */
}

/* Set exact colors for the border colors */
.greenOut {
    border-color: #50dc36 !important; /* Original green color */
}

.redOut {
    border-color: #dc3836 !important; /* Original red color */
}

/* Add gray border for unpromised rehearsals */
.grayOut {
    border-color: #aaaaaa !important; /* Gray color for unpromised rehearsals */
}

/* Add hover effect to rehearsal cards */
.rehearsal-card {
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.rehearsal-card:hover {
    transform: scale(1.01);
    box-shadow: 0px 0px 35px rgba(100,100,100,0.5);
    cursor: pointer;
}

/* Style for disabled buttons */
.checkBtn.disabled, .crossBtn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

<div class="container-fluid mt-4">
<?php if (empty($rehearsals)): ?>
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div style="background-color: #e8f7fc; padding: 40px 20px; border-radius: 10px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);">
                <h2 style="color: #006064; text-align: center; font-size: 2.5rem; font-weight: 500; margin-bottom: 20px;">Keine Proben gefunden</h2>
                <p style="text-align: center; font-size: 1.2rem; margin-bottom: 30px;">Aktuell sind keine Proben für dich eingetragen.</p>
            </div>
        </div>
    </div>
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
        $groups = json_decode($rehearsal['groups_data'] ?? '{}', true);
        $groupArray = sortGroups($groups);
        $groupsText = str_replace("_", " ", implode("<br>", $groupArray));
        
        // Determine color class
        $colorClass = 'grayOut'; // Default for pending (unpromised)
        
        if ($status === 'attending') {
            $colorClass = 'greenOut';
        } else if ($status === 'not_attending') {
            $colorClass = 'redOut';
        }
        ?>
        
        <div class="rehearsal-card <?= $colorClass ?>" style="display: block; border-radius: 10px; height: 111px; margin-right: 20px; margin-left: 20px; box-shadow: 0px 0px 30px rgba(128,128,128,0.4); margin-top: 30px; text-align: left; min-width: 300px; zoom: 0.8; border-width: 4px; border-style: solid; <?php if ($status === 'not_attending'): ?>border-color: #dc3836;<?php elseif ($status === 'attending'): ?>border-color: #50dc36;<?php else: ?>border-color: #aaaaaa;<?php endif; ?> position: relative; <?= !empty($rehearsal['color']) ? 'background-color: ' . $rehearsal['color'] . ';' : 'background-color: white;' ?>">
            <div class="row" style="width: 100%;">
                <div class="col col-8" style="margin-top: -7px;">
                    <div class="row">
                        <div class="col col-6">
                            <label class="col-form-label text-break user-data" style="margin-bottom: 0; margin-top: 15px; margin-left: 20px; font-size: 20px; font-weight: 600; width: 100%; overflow: auto; max-height: 40px;" data-rehearsal-id="<?= $rehearsal['id'] ?>" data-rehearsal-date="<?= htmlspecialchars($rehearsal['date']) ?>" data-rehearsal-time="<?= htmlspecialchars($rehearsal['time']) ?>" data-rehearsal-location="<?= htmlspecialchars($rehearsal['location']) ?>" data-rehearsal-description="<?= htmlspecialchars($rehearsal['description'] ?? '') ?>">
                                <?= htmlspecialchars($rehearsal['date']) ?>
                            </label>
                        </div>
                        <div class="col">
                            <label class="col-form-label text-break" style="margin-bottom: 0; margin-top: 15px; font-size: 20px; font-weight: 600; width: 100%; overflow: auto; max-height: 40px;">
                                <?= $groupsText ?>
                            </label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col col-6">
                            <label class="col-form-label text-break" style="margin-bottom: 0; margin-left: 20px; font-size: 20px; font-weight: 600; width: 100%; overflow: auto; max-height: 40px;">
                                <?= htmlspecialchars($rehearsal['time']) ?>
                            </label>
                        </div>
                        <div class="col">
                            <label class="col-form-label text-break" style="margin-bottom: 0; font-size: 20px; font-weight: 600; width: 100%; overflow: auto; max-height: 40px;">
                                <?= htmlspecialchars($rehearsal['location']) ?>
                            </label>
                        </div>
                    </div>
                </div>
                <div style="height: 50%; width: 24%; display: inline-block; white-space: nowrap; float: right; padding-top: 15px; transform: scale(1.4); transform-origin: 0 0;">
                    <img id="<?= $rehearsal['id'] ?>" class="checkBtn <?= $status !== 'attending' ? 'deselected' : '' ?>" src="/assets/img/icons8_checked_checkbox_48px_2.png" style="cursor: pointer;">
                    <img id="<?= $rehearsal['id'] ?>" class="crossBtn <?= $status !== 'not_attending' ? 'deselected' : '' ?>" src="/assets/img/icons8_close_window_48px_1.png" style="cursor: pointer;">
                </div>
            </div>
            <i id="icon<?= $rehearsal['id'] ?>" class="<?= $status === 'not_attending' ? 'showIcon' : 'hideIcon' ?> fa <?= empty($note) ? 'fa-plus-square' : 'fa-pen-square' ?> addNoteBtn" style="transform-origin: 0; top: 6px; right: 25px; position: absolute; <?= empty($note) ? 'color: lightgrey;' : '' ?> <?= $status !== 'not_attending' ? 'visibility: hidden;' : '' ?> cursor: pointer;"></i>
            <input type="hidden" id="note<?= $rehearsal['id'] ?>" value="<?= htmlspecialchars($note) ?>">
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="mt-4 mb-4" style="height: 100px;"></div>
</div>

<!-- Save indicator -->
<div id="save-indicator" style="display: none; position: fixed; bottom: 20px; right: 20px; background-color: rgba(0,0,0,0.7); color: white; padding: 10px 20px; border-radius: 5px; z-index: 1000;">
    <i class="fa fa-spinner fa-spin" style="margin-right: 10px;"></i> Speichern...
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
            return; // Prevent actions on disabled buttons
        }
        
        var id = $(this).attr('id');
        
        // Disable buttons for this rehearsal to prevent multiple rapid clicks
        disableRehearsalButtons(id);
        
        // Toggle selection
        $(this).removeClass('deselected');
        $(this).siblings('.crossBtn').addClass('deselected');
        
        // Update UI
        var container = $(this).closest('.rehearsal-card');
        container.removeClass('redOut grayOut').addClass('greenOut');
        container.css('border-color', '#50dc36');
        
        // Hide note icon
        $('#icon' + id).removeClass('showIcon').addClass('hideIcon').css('visibility', 'hidden');
        
        // Clear any existing note
        $('#note' + id).val('');
        
        // Add to queue instead of executing immediately
        queueUpdate("promise", id, true, '');
    });
    
    $('.crossBtn').click(function() {
        if ($(this).hasClass('disabled')) {
            return; // Prevent actions on disabled buttons
        }
        
        var id = $(this).attr('id');
        
        // Disable buttons for this rehearsal to prevent multiple rapid clicks
        disableRehearsalButtons(id);
        
        // Toggle selection
        $(this).removeClass('deselected');
        $(this).siblings('.checkBtn').addClass('deselected');
        
        // Update UI
        var container = $(this).closest('.rehearsal-card');
        container.removeClass('grayOut greenOut').addClass('redOut');
        container.css('border-color', '#dc3836');
        
        // Get existing note
        var existingNote = $('#note' + id).val();
        var iconClass = (existingNote && existingNote.trim() !== '') ? 'fa-pen-square' : 'fa-plus-square';
        var iconColor = (existingNote && existingNote.trim() !== '') ? '' : 'lightgrey';
        
        // Show note icon with appropriate class and color
        $('#icon' + id).removeClass('hideIcon').addClass('showIcon')
                       .removeClass('fa-plus-square fa-pen-square').addClass(iconClass)
                       .css({
                           'visibility': 'visible',
                           'color': iconColor
                       });
        
        // Add to queue instead of executing immediately
        queueUpdate("promise", id, false, existingNote);
    });
    
    // Handle note button clicks
    $('.addNoteBtn').click(function() {
        var id = $(this).attr('id').replace('icon', '');
        if ($('.checkBtn[id="' + id + '"]').hasClass('disabled')) {
            return; // Don't show dialog if buttons are disabled
        }
        showNoteDialog(id);
    });
    
    // Function to disable rehearsal buttons during updates
    function disableRehearsalButtons(id) {
        $('.checkBtn[id="' + id + '"], .crossBtn[id="' + id + '"]').addClass('disabled').css('opacity', '0.5');
    }
    
    // Function to enable rehearsal buttons after updates
    function enableRehearsalButtons(id) {
        $('.checkBtn[id="' + id + '"], .crossBtn[id="' + id + '"]').removeClass('disabled').css('opacity', '1');
    }
    
    // Function to add update to queue
    function queueUpdate(type, id, status, note) {
        // Add to queue
        window.updateQueue.push({
            type: type,
            id: id,
            status: status,
            note: note
        });
        
        // Show save indicator if it's not already visible
        if (!window.promiseBeingUpdated) {
            $('#save-indicator').fadeIn(200);
            window.promiseBeingUpdated = true;
        }
        
        // Start processing if not already in progress
        if (!window.isProcessingQueue) {
            processNextUpdate();
        }
    }
    
    // Function to process the next update in the queue
    function processNextUpdate() {
        if (window.updateQueue.length === 0) {
            window.isProcessingQueue = false;
            window.promiseBeingUpdated = false;
            $('#save-indicator').fadeOut(200);
            return;
        }
        
        window.isProcessingQueue = true;
        const update = window.updateQueue.shift();
        
        if (update.type === "promise") {
            updatePromise(update.id, update.status, update.note);
        } else if (update.type === "note") {
            updateNote(update.id, update.note);
        }
    }
    
    // Function to show note dialog
    function showNoteDialog(id) {
        // Get existing note if any
        var existingNote = '';
        
        // Try to get the note from a hidden field if it exists
        if ($('#note' + id).length > 0) {
            existingNote = $('#note' + id).val();
        }
        
        // Disable buttons for this rehearsal to prevent multiple rapid clicks
        disableRehearsalButtons(id);
        
        // Show note modal with SweetAlert2
        Swal.fire({
            title: 'Anmerkung hinzufügen',
            input: 'textarea',
            inputLabel: 'Deine Anmerkung zur Zu- oder Absage',
            inputPlaceholder: 'Gib hier deine Anmerkung ein...',
            inputValue: existingNote,
            showCancelButton: true,
            cancelButtonText: 'Abbrechen',
            confirmButtonText: 'Speichern'
        }).then((result) => {
            if (result.isConfirmed) {
                // Get the current status
                const attending = !$('.checkBtn[id="' + id + '"]').hasClass('deselected');
                
                // Update note in database
                queueUpdate("note", id, attending, result.value);
                
                // Update hidden field
                if ($('#note' + id).length > 0) {
                    $('#note' + id).val(result.value);
                }
                
                // Update icon
                if (result.value.trim() !== '') {
                    $('#icon' + id).removeClass('fa-plus-square').addClass('fa-pen-square').css('color', '');
                } else {
                    $('#icon' + id).removeClass('fa-pen-square').addClass('fa-plus-square').css('color', 'lightgrey');
                }
            } else {
                // Re-enable the buttons if dialog is canceled
                enableRehearsalButtons(id);
            }
        });
    }
    
    // Function to update promise
    function updatePromise(id, attending, note) {
        // Show save indicator
        $('#save-indicator').fadeIn(200);
        
        $.ajax({
            url: '/promises/update',
            type: 'POST',
            data: {
                id: id,
                status: attending ? 1 : 0
            },
            success: function(response) {
                console.log('Promise update response:', response);
                if (response.success) {
                    // If server returned updated promises, update UI accordingly
                    if (response.promises) {
                        // Update all promise UI elements based on returned promises string
                        updatePromisesUI(response.promises);
                    }
                    
                    // Also update this specific UI element
                    const container = $('.checkBtn[id="' + id + '"]').closest('.rehearsal-card');
                    if (attending) {
                        container.removeClass('redOut grayOut').addClass('greenOut');
                        // Update border color
                        container.css('border-color', '#50dc36');
                    } else {
                        container.removeClass('grayOut greenOut').addClass('redOut');
                        // Update border color
                        container.css('border-color', '#dc3836');
                    }
                    
                    // If we need to update the note as well
                    if (note !== '') {
                        // Add note update to the queue
                        window.updateQueue.push({
                            type: "note",
                            id: id,
                            status: attending,
                            note: note
                        });
                    }
                    
                    // Process the next update in the queue
                    setTimeout(function() {
                        processNextUpdate();
                    }, 300);
                } else {
                    // Show error to user with details
                    Swal.fire({
                        title: response.message || 'Fehler beim Speichern',
                        html: response.details ? `${response.message}<br><br><button id="showDetailsBtn" class="swal2-styled">Details anzeigen</button><div id="errorDetails" style="display:none; margin-top:10px; text-align:left; font-size:12px; color:#a94442; background:#f9f2f4; border:1px solid #ebccd1; padding:10px; border-radius:4px; white-space:pre-wrap;">${response.details}</div>` : response.message,
                        icon: 'error',
                        didOpen: () => {
                            const btn = document.getElementById('showDetailsBtn');
                            if (btn) {
                                btn.onclick = function() {
                                    const details = document.getElementById('errorDetails');
                                    if (details.style.display === 'none') {
                                        details.style.display = 'block';
                                        btn.textContent = 'Details ausblenden';
                                    } else {
                                        details.style.display = 'none';
                                        btn.textContent = 'Details anzeigen';
                                    }
                                };
                            }
                        }
                    });
                }
                
                // Hide save indicator
                $('#save-indicator').fadeOut(200);
                
                // Re-enable the buttons
                enableRehearsalButtons(id);
            },
            error: function(xhr, status, error) {
                console.error('Error updating promise:', error);
                
                // Try to parse the error response
                let errorMessage = 'Fehler beim Speichern der Antwort';
                let errorDetails = 'Ein unerwarteter Fehler ist aufgetreten.';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                    if (response.details) {
                        errorDetails = response.details;
                    }
                } catch (e) {
                    console.error('Error parsing error response:', e);
                }
                
                // Show error to user with details
                Swal.fire({
                    title: errorMessage,
                    html: `${errorMessage}<br><br><button id="showDetailsBtn" class="swal2-styled">Details anzeigen</button><div id="errorDetails" style="display:none; margin-top:10px; text-align:left; font-size:12px; color:#a94442; background:#f9f2f4; border:1px solid #ebccd1; padding:10px; border-radius:4px; white-space:pre-wrap;">${errorDetails}</div>`,
                    icon: 'error',
                    didOpen: () => {
                        const btn = document.getElementById('showDetailsBtn');
                        if (btn) {
                            btn.onclick = function() {
                                const details = document.getElementById('errorDetails');
                                if (details.style.display === 'none') {
                                    details.style.display = 'block';
                                    btn.textContent = 'Details ausblenden';
                                } else {
                                    details.style.display = 'none';
                                    btn.textContent = 'Details anzeigen';
                                }
                            };
                        }
                    }
                });
                
                // Hide save indicator
                $('#save-indicator').fadeOut(200);
                
                // Re-enable the buttons
                enableRehearsalButtons(id);
            }
        });
    }
    
    // Function to update note
    function updateNote(id, note) {
        // Show save indicator
        $('#save-indicator').fadeIn(200);
        
        $.ajax({
            url: '/promises/note',
            type: 'POST',
            data: {
                id: id,
                note: note
            },
            success: function(response) {
                console.log('Note update response:', response);
                if (response.success) {
                    // If server returned updated promises, update UI accordingly
                    if (response.promises) {
                        // Update all promise UI elements based on returned promises string
                        updatePromisesUI(response.promises);
                    }
                }
                
                // Re-enable the buttons after a short delay
                setTimeout(function() {
                    enableRehearsalButtons(id);
                }, 200);
                
                // Show brief success message
                if (window.updateQueue.length === 0) {
                    // Use toast notification instead of modal for success
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'bottom-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    
                    Toast.fire({
                        icon: 'success',
                        title: 'Anmerkung gespeichert'
                    });
                }
                
                // Process the next update in the queue
                setTimeout(function() {
                    processNextUpdate();
                }, 300);
            },
            error: function(xhr, status, error) {
                console.error('Error updating note:', error);
                
                // Try to parse the error response
                let errorMessage = 'Fehler beim Speichern der Anmerkung';
                let errorDetails = 'Ein unerwarteter Fehler ist aufgetreten.';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                    if (response.details) {
                        errorDetails = response.details;
                    }
                } catch (e) {
                    console.error('Error parsing error response:', e);
                }
                
                // Show error to user with details
                Swal.fire({
                    title: errorMessage,
                    html: `${errorMessage}<br><br><button id="showDetailsBtn" class="swal2-styled">Details anzeigen</button><div id="errorDetails" style="display:none; margin-top:10px; text-align:left; font-size:12px; color:#a94442; background:#f9f2f4; border:1px solid #ebccd1; padding:10px; border-radius:4px; white-space:pre-wrap;">${errorDetails}</div>`,
                    icon: 'error',
                    didOpen: () => {
                        const btn = document.getElementById('showDetailsBtn');
                        if (btn) {
                            btn.onclick = function() {
                                const details = document.getElementById('errorDetails');
                                if (details.style.display === 'none') {
                                    details.style.display = 'block';
                                    btn.textContent = 'Details ausblenden';
                                } else {
                                    details.style.display = 'none';
                                    btn.textContent = 'Details anzeigen';
                                }
                            };
                        }
                    }
                });
                
                // Hide save indicator
                $('#save-indicator').fadeOut(200);
                
                // Re-enable the buttons
                enableRehearsalButtons(id);
            }
        });
    }
    
    // Function to update UI based on promises string
    function updatePromisesUI(promisesStr) {
        if (!promisesStr) return;
        
        const promises = parsePromisesString(promisesStr);
        console.log('Parsed promises:', promises);
        
        // First set all containers to unpromised (gray)
        $('.rehearsal-card').each(function() {
            const container = $(this);
            // Extract rehearsal ID from the check button inside this container
            const rehearsalId = container.find('.checkBtn').attr('id');
            
            // If this rehearsal ID is not in the promises, set it to unpromised
            if (!promises[rehearsalId]) {
                container.removeClass('redOut greenOut').addClass('grayOut');
                container.css('border-color', '#aaaaaa');
                
                // Set both check and cross buttons to deselected
                container.find('.checkBtn, .crossBtn').addClass('deselected');
                
                // Hide note icon
                $('#icon' + rehearsalId).removeClass('showIcon').addClass('hideIcon').css('visibility', 'hidden');
            }
        });
        
        // Then update each rehearsal card based on parsed promises
        Object.keys(promises).forEach(rehearsalId => {
            const promise = promises[rehearsalId];
            const attending = promise.attending;
            const note = promise.note;
            
            // Update check/cross buttons
            if (attending) {
                $('.checkBtn[id="' + rehearsalId + '"]').removeClass('deselected');
                $('.crossBtn[id="' + rehearsalId + '"]').addClass('deselected');
                
                // Update container styling
                const container = $('.checkBtn[id="' + rehearsalId + '"]').closest('.rehearsal-card');
                container.removeClass('redOut grayOut').addClass('greenOut');
                container.css('border-color', '#50dc36');
                
                // Hide note icon
                $('#icon' + rehearsalId).removeClass('showIcon').addClass('hideIcon').css('visibility', 'hidden');
            } else {
                $('.crossBtn[id="' + rehearsalId + '"]').removeClass('deselected');
                $('.checkBtn[id="' + rehearsalId + '"]').addClass('deselected');
                
                // Update container styling
                const container = $('.crossBtn[id="' + rehearsalId + '"]').closest('.rehearsal-card');
                container.removeClass('grayOut greenOut').addClass('redOut');
                container.css('border-color', '#dc3836');
                
                // Show note icon with appropriate styling
                const iconClass = note ? 'fa-pen-square' : 'fa-plus-square';
                const iconColor = note ? '' : 'lightgrey';
                
                $('#icon' + rehearsalId)
                    .removeClass('hideIcon fa-plus-square fa-pen-square')
                    .addClass('showIcon ' + iconClass)
                    .css({
                        'visibility': 'visible',
                        'color': iconColor
                    });
            }
            
            // Update note hidden field
            $('#note' + rehearsalId).val(note);
        });
    }
    
    // Function to parse promises string into an object
    function parsePromisesString(promisesStr) {
        if (!promisesStr) return {};
        
        const promises = {};
        const promiseItems = promisesStr.split('|');
        
        promiseItems.forEach(item => {
            if (!item) return;
            
            let attending = true;
            let rehearsalId = item;
            let note = '';
            
            // Check if not attending
            if (item.startsWith('!')) {
                attending = false;
                rehearsalId = item.substring(1);
            }
            
            // Extract note if exists
            const noteMatch = rehearsalId.match(/\((.*?)\)/);
            if (noteMatch) {
                note = noteMatch[1];
                rehearsalId = rehearsalId.replace(/\(.*?\)/, '');
            }
            
            // Store promise data
            promises[rehearsalId] = {
                attending: attending,
                note: note
            };
        });
        
        return promises;
    }
    
    // Handle clicking on rehearsal data to show details
    $('.user-data').click(function() {
        const rehearsalId = $(this).data('rehearsal-id');
        const rehearsalDate = $(this).data('rehearsal-date');
        const rehearsalTime = $(this).data('rehearsal-time');
        const rehearsalLocation = $(this).data('rehearsal-location');
        const rehearsalDescription = $(this).data('rehearsal-description') || '';
        
        // Get status and note
        const status = $('.checkBtn[id="' + rehearsalId + '"]').hasClass('deselected') ? 'Nicht dabei' : 'Dabei';
        const note = $('#note' + rehearsalId).val() || '';
        
        // Create content for modal
        let content = `
            <div style="text-align: left;">
                <p><strong>Datum:</strong> ${rehearsalDate}</p>
                <p><strong>Zeit:</strong> ${rehearsalTime}</p>
                <p><strong>Ort:</strong> ${rehearsalLocation}</p>
                ${rehearsalDescription ? '<p><strong>Beschreibung:</strong> ' + rehearsalDescription + '</p>' : ''}
                <p><strong>Status:</strong> <span style="color: ${status === 'Dabei' ? '#50dc36' : '#dc3836'}">${status}</span></p>
                ${note ? '<p><strong>Notiz:</strong> ' + note + '</p>' : ''}
            </div>
        `;
        
        // Show SweetAlert with rehearsal details
        Swal.fire({
            title: 'Proben Details',
            html: content,
            confirmButtonText: 'Schließen',
            showCancelButton: false,
            customClass: {
                confirmButton: 'btn btn-primary'
            }
        });
    });
});
</script> 