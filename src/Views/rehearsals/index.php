<?php $this->layout('layouts/default', ['title' => 'Termine', 'currentPage' => $currentPage ?? 'rehearsals']) ?>

<div class="container-app pb-20">

    <?php if (empty($rehearsals)): ?>
        <?php
        if (!$showOld && ($hasPastRehearsals ?? false)) {
            $title = 'Keine aktuellen Termine';
            $message = 'Es stehen keine kommenden Proben an.';
            $actionHref = '?showOld=1';
            $actionLabel = 'Vergangene Termine anzeigen';
        } else {
            $title = 'Keine Termine gefunden';
            $message = 'Lege einen neuen Termin an, um hier Proben zu sehen.';
            $actionHref = '/' . $_SESSION['current_orchestra_id'] . '/rehearsals/create';
            $actionLabel = 'Termin hinzufügen';
        }

        include __DIR__ . '/../components/empty-state.php';
        ?>
    <?php else: ?>
        <?php
        // Separate rehearsals
        $currentRehearsals = [];
        $pastRehearsals = [];
        $today = date('Y-m-d');

        foreach ($rehearsals as $rehearsal) {
            $rehearsalDate = $rehearsal['date'];
            if ($rehearsalDate >= $today) {
                $currentRehearsals[] = $rehearsal;
            } else {
                $pastRehearsals[] = $rehearsal;
            }
        }
        ?>

        <!-- Past Rehearsals -->
        <?php if ($showOld && !empty($pastRehearsals)): ?>
            <div class="past-rehearsals-section" id="pastRehearsalsSection">
                <?php foreach ($pastRehearsals as $rehearsal): ?>
                    <?php
                    // Card options
                    $context = 'rehearsals';
                    $options = [
                        'showButtons' => true
                    ];
                    include __DIR__ . '/../components/rehearsal-card.php';
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Separator and Past Button -->
        <?php if (!empty($currentRehearsals) || !empty($pastRehearsals)): ?>
            <?php include __DIR__ . '/../components/date-separator.php'; ?>
        <?php endif; ?>

        <!-- Current/Future Rehearsals -->
        <?php foreach ($currentRehearsals as $rehearsal): ?>
            <?php
            // Card options
            $context = 'rehearsals';
            $options = [
                'showButtons' => true
            ];
            include __DIR__ . '/../components/rehearsal-card.php';
            ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php
    // Add Button
    $icon = 'plus';
    $href = '/' . $_SESSION['current_orchestra_id'] . '/rehearsals/create';
    $title = 'Neue Probe hinzufügen';
    include __DIR__ . '/../components/fab.php';
    ?>
</div>

<script>
    // Delete handler
    document.addEventListener('click', function(event) {
        if (event.target.closest('.delete-btn')) {
            const deleteBtn = event.target.closest('.delete-btn');
            const id = deleteBtn.id;

            Swal.fire({
                title: 'Willst du diesen Termin wirklich löschen?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#478cf4',
                cancelButtonText: 'Abbrechen',
                confirmButtonText: 'Löschen'
            }).then((result) => {
                if (result.isConfirmed) {
                    <?php $orchestraId = $_SESSION['current_orchestra_id'] ?? 1; ?>
                    fetch('/<?= $orchestraId ?>/rehearsals/delete/' + id, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'Accept': 'application/json'
                            },
                            body: 'id=' + id
                        })
                        .then(async (response) => {
                            const contentType = response.headers.get('content-type') || '';
                            const text = await response.text().catch(() => '');
                            const isJson = contentType.includes('application/json');
                            const parseJson = () => {
                                try {
                                    return JSON.parse(text);
                                } catch (e) {
                                    return null;
                                }
                            };
                            if (!response.ok) {
                                const data = isJson ? parseJson() : null;
                                const message = (data && (data.message || data.error)) || text || `HTTP ${response.status}`;
                                throw new Error(message);
                            }
                            return isJson ? (parseJson() || {
                                success: false
                            }) : {
                                success: false
                            };
                        })
                        .then(data => {
                            if (data.success) {
                                if (window.notifySuccess) {
                                    window.notifySuccess('Termin gelöscht');
                                    setTimeout(function() {
                                        location.reload();
                                    }, 600);
                                } else {
                                    location.reload();
                                }
                            } else {
                                // Use custom error with details
                                const technicalDetails = data.debug_message || data.error || JSON.stringify(data, null, 2);
                                if (window.notifyErrorWithDetails) {
                                    window.notifyErrorWithDetails(
                                        'Der Termin konnte nicht gelöscht werden.',
                                        technicalDetails
                                    );
                                } else {
                                    // Fallback if helper not available (should not happen)
                                    Swal.fire({
                                        title: 'Fehler',
                                        text: data.message || 'Unbekannter Fehler beim Löschen des Termins', // Use data.message for user-friendly text, fallback to generic
                                        icon: 'error',
                                        confirmButtonColor: '#478cf4'
                                    });
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            if (window.notifyErrorWithDetails) {
                                window.notifyErrorWithDetails(
                                    'Es ist ein Fehler aufgetreten.',
                                    error.message || error.toString()
                                );
                            } else {
                                Swal.fire({
                                    title: 'Fehler',
                                    text: 'Verbindungsfehler beim Löschen des Termins',
                                    icon: 'error',
                                    confirmButtonColor: '#478cf4'
                                });
                            }
                        });
                }
            });
        }
    });

    // Edit handler
    document.addEventListener('click', function(event) {
        if (event.target.closest('.edit-btn')) {
            const editBtn = event.target.closest('.edit-btn');
            const buttonId = editBtn.id;
            window.location.href = '/<?= $_SESSION['current_orchestra_id'] ?>/rehearsals/edit/' + buttonId;
        }
    });
</script>