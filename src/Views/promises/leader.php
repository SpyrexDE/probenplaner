<?php $this->layout('layouts/default', ['title' => 'Rückmeldungen', 'currentPage' => $currentPage ?? 'leader']) ?>

<!-- Custom styling for leader view -->
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

/* Style the user spans to look more clickable */
.userSpan {
    cursor: pointer;
    padding: 2px 0;
}

.userSpan:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

/* Fix the icon colors to match exactly */
.fa-check-circle {
    color: #50dc36 !important;
}

.fa-times-circle {
    color: #dc3836 !important;
}

/* Adjust tree styling for better visibility */
.tree {
    margin-bottom: 20px;
}

.tree ul {
    list-style-type: none;
}

.tree-item-span {
    display: block;
    padding: 3px 0;
}

.tree-item-span:hover {
    background-color: rgba(0, 0, 0, 0.03);
}
</style>

<div class="container-fluid mt-4">

    <?php if (empty($rehearsals)): ?>
        <div class="alert alert-info">
            Keine Termine für deine Gruppe gefunden.
        </div>
    <?php else: ?>
        <?php foreach ($rehearsals as $rehearsal): ?>
            <?php 
                $rehearsalId = $rehearsal['id'];
                $date = $rehearsal['date'];
                $start_time_ldr = isset($rehearsal['start_time']) ? substr($rehearsal['start_time'], 0, 5) : '??:??';
                $end_time_ldr = isset($rehearsal['end_time']) ? substr($rehearsal['end_time'], 0, 5) : '??:??';
                $time_display_ldr = $start_time_ldr . ' - ' . $end_time_ldr;
                $location = $rehearsal['location'] ?? 'TBA';

                // Determine rehearsal type
                $groupKeys = $rehearsal['groups'] ?? [];
                $rehearsalType = '';
                
                // Add * suffix to group names if it's a small group
                $isSmallGroup = isset($rehearsal['is_small_group']) && $rehearsal['is_small_group'] == 1;
                if ($isSmallGroup) {
                    foreach ($groupKeys as &$group) {
                        $group .= '*';
                    }
                }
                
                if (in_array('Registerprobe', $groupKeys)) {
                    $rehearsalType = 'Registerprobe';
                } elseif (in_array('Konzert', $groupKeys)) {
                    $rehearsalType = 'Konzert';
                } elseif (in_array('Generalprobe', $groupKeys)) {
                    $rehearsalType = 'Generalprobe';
                } elseif (in_array('Konzertreise', $groupKeys)) {
                    $rehearsalType = 'Konzertreise';
                }
                
                if ($isSmallGroup) {
                    $rehearsalType .= ' (Kleingruppe)';
                }

                $attendingCount = count($memberPromises[$rehearsalId]['attending'] ?? []);
                $notAttendingCount = count($memberPromises[$rehearsalId]['not_attending'] ?? []);
                $noResponseCount = count($memberPromises[$rehearsalId]['no_response'] ?? []);
            ?>
            
            <div class="tree">
                <ul style="padding-left: 5px;">
                    <li>
                        <span class="tree-item-span">
                            <a style="color:#000; text-decoration:none; background-color: <?= !empty($rehearsal['color']) ? $rehearsal['color'] : 'white' ?>;" data-toggle="collapse" href="#Orchester<?= $rehearsalId ?>" aria-expanded="false" aria-controls="Orchester<?= $rehearsalId ?>">
                                <i class="collapsed"><i class="fas fa-folder"></i></i>
                                <i class="expanded"><i class="far fa-folder-open"></i></i> 
                                <?= htmlspecialchars($date) ?> - <?= htmlspecialchars($time_display_ldr) ?>
                                <?php if (!empty($rehearsalType)): ?>
                                    - <?= htmlspecialchars($rehearsalType) ?>
                                <?php endif; ?>
                            </a>
                            <a class="rightfloatet"><?= $notAttendingCount ?></a>
                            <i class="fas fa-times-circle treeIcon rightfloatet"></i>
                            <a class="rightfloatet"><?= $attendingCount ?></a>
                            <i class="fas fa-check-circle treeIcon rightfloatet"></i>
                            <a class="rightfloatet"><?= $noResponseCount ?></a>
                            <i class="fas fa-question-circle treeIcon rightfloatet"></i>
                        </span>
                        
                        <div id="Orchester<?= $rehearsalId ?>" class="collapse">
                            <ul>
                                <?php if (!empty($memberPromises[$rehearsalId]['not_attending'])): ?>
                                    <?php foreach($memberPromises[$rehearsalId]['not_attending'] as $member): ?>
                                        <li>
                                            <span class="userSpan">
                                                <i class="fas fa-user" style="zoom: 0.8; margin-right: 5px;"></i> 
                                                <?= htmlspecialchars($member['username']) ?>
                                                <?php if (!empty($member['note'])): ?>
                                                    - <?= htmlspecialchars($member['note']) ?>
                                                <?php endif; ?>
                                                <i class="fas fa-times-circle smallTreeIcon rightfloatet" style="color: red;"></i>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($memberPromises[$rehearsalId]['attending'])): ?>
                                    <?php foreach($memberPromises[$rehearsalId]['attending'] as $member): ?>
                                        <li>
                                            <span class="userSpan">
                                                <i class="fas fa-user" style="zoom: 0.8; margin-right: 5px;"></i> 
                                                <?= htmlspecialchars($member['username']) ?>
                                                <?php if (!empty($member['note'])): ?>
                                                    - <?= htmlspecialchars($member['note']) ?>
                                                <?php endif; ?>
                                                <i class="fas fa-check-circle smallTreeIcon rightfloatet" style="color: green;"></i>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($memberPromises[$rehearsalId]['no_response'])): ?>
                                    <?php foreach($memberPromises[$rehearsalId]['no_response'] as $member): ?>
                                        <li>
                                            <span class="userSpan">
                                                <i class="fas fa-user" style="zoom: 0.8; margin-right: 5px;"></i> 
                                                <?= htmlspecialchars($member['username']) ?>
                                                <i class="fas fa-question-circle smallTreeIcon rightfloatet" style="color: gray;"></i>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// Remove showOldRehearsals event handler as it's now handled by the history icon

// Initialize collapse controls
document.addEventListener('DOMContentLoaded', function() {
    // Expand/collapse behavior for folder icons
    const folderIcons = document.querySelectorAll('.tree a[data-toggle="collapse"]');
    folderIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            const expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !expanded);
        });
    });
    
    // Add click handler for userSpan elements
    const userSpans = document.querySelectorAll('.userSpan');
    userSpans.forEach(span => {
        span.style.cursor = 'pointer';
        
        span.addEventListener('click', function(e) {
            // Prevent click from affecting parent elements
            e.stopPropagation();
            
            // Extract user information
            const username = this.innerText.split('-')[0].trim();
            
            // Get user attendance statistics
            const getUserStats = () => {
                // Find all instances of this username in the document
                const userSpans = Array.from(document.querySelectorAll('.userSpan')).filter(span => 
                    span.textContent.includes(username)
                );
                
                // Count each status type
                let attending = 0;
                let notAttending = 0;
                let noResponse = 0;
                
                userSpans.forEach(span => {
                    if (span.querySelector('.fa-check-circle')) {
                        attending++;
                    } else if (span.querySelector('.fa-times-circle')) {
                        notAttending++;
                    } else if (span.querySelector('.fa-question-circle')) {
                        noResponse++;
                    }
                });
                
                return { attending, notAttending, noResponse };
            };
            
            const stats = getUserStats();
            
            // Show SweetAlert with user statistics
            Swal.fire({
                title: username,
                html: `
                    <div style="text-align: center; margin-bottom: 15px;">
                        <div style="display: inline-block; margin: 0 10px;">
                            <i class="fas fa-check-circle" style="color: #50dc36; font-size: 24px;"></i>
                            <div><strong>${stats.attending}</strong></div>
                        </div>
                        <div style="display: inline-block; margin: 0 10px;">
                            <i class="fas fa-times-circle" style="color: #dc3836; font-size: 24px;"></i>
                            <div><strong>${stats.notAttending}</strong></div>
                        </div>
                        <div style="display: inline-block; margin: 0 10px;">
                            <i class="fas fa-question-circle" style="color: gray; font-size: 24px;"></i>
                            <div><strong>${stats.noResponse}</strong></div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'Passwort zurücksetzen',
                confirmButtonColor: '#3085d6',
                denyButtonText: 'Account löschen',
                denyButtonColor: '#d33',
                cancelButtonText: 'Abbrechen',
            }).then((result) => {
                if (result.isDenied) {
                    deleteAcc(username);
                } else if (result.isConfirmed) {
                    resetPW(username);
                }
            });
        });
    });
    
    // Helper function to delete an account
    function deleteAcc(username) {
        Swal.fire({
            title: "Account Löschen",
            html: "Willst du den Account von " + username + " wirklich löschen?<br>Wir können keine Daten wiederherstellen!",
            showCancelButton: true,
            confirmButtonText: "Löschen",
            confirmButtonColor: '#d33', // Red button for deletion
            cancelButtonText: "Abbrechen",
            icon: 'warning'
        }).then((result) => {
            if (result.isConfirmed) {
                // Use the MVC controller endpoint
                fetch('/user/deleteUser?username=' + encodeURIComponent(username))
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw new Error(data.error || 'Server returned ' + response.status);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Show success message
                        // Use toast notification for success
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true,
                            didClose: () => {
                                // Reload the page to reflect the account deletion
                                window.location.reload();
                            }
                        });
                        
                        Toast.fire({
                            icon: "success",
                            title: data.message
                        });
                    })
                    .catch(error => {
                        console.error('Error deleting account:', error);
                        // Use toast notification for error
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                        
                        Toast.fire({
                            icon: "error",
                            title: error.message || "Die Anfrage konnte nicht verarbeitet werden."
                        });
                    });
            }
        });
    }
    
    // Helper function to reset a password
    function resetPW(username) {
        Swal.fire({
            title: "Passwort zurücksetzen",
            text: "Willst du das Passwort von " + username + " wirklich zurücksetzen?\nWir können keine Daten wiederherstellen!",
            showCancelButton: true,
            confirmButtonText: "Zurücksetzen",
            confirmButtonColor: '#3085d6',
            cancelButtonText: "Abbrechen",
        }).then((result) => {
            if (result.isConfirmed) {
                // Use the MVC controller endpoint
                fetch('/user/resetPassword?username=' + encodeURIComponent(username))
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw new Error(data.error || 'Server returned ' + response.status);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Use toast notification for success
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: false,
                            timer: 10000,
                            timerProgressBar: true
                        });
                        
                        Toast.fire({
                            icon: "success",
                            title: data.message
                        });
                    })
                    .catch(error => {
                        console.error('Error resetting password:', error);
                        // Use toast notification for error
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: false,
                            timer: 10000,
                            timerProgressBar: true
                        });
                        
                        Toast.fire({
                            icon: "error",
                            title: error.message || "Die Anfrage konnte nicht verarbeitet werden."
                        });
                    });
            }
        });
    }
});
</script> 