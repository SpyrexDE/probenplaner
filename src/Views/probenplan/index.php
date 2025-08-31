<div class="max-w-7xl mx-auto px-4 mt-4">
    <div class="w-full">
        <div class="w-full text-center">
            <h1>Probenplan</h1>
            <h5>Stand: <?= date("d.m.Y") ?></h5>
            
            <div class="flex flex-wrap gap-2 mb-6 justify-center">
                <button id="filterToggle" class="btn-base btn-outline btn-sm" onclick="togglePersonalizedView()">
                    <i class="fas fa-filter mr-2"></i><?= $personalized ? 'Personalisierte Ansicht' : 'Alle Proben' ?>
                </button>
                <a href="<?= $showOld ? '/probenplan' . ($personalized ? '?personalized=1' : '') : '/probenplan' . ($personalized ? '?personalized=1&showOld=1' : '?showOld=1') ?>" class="btn-base btn-ghost btn-sm">
                    <i class="fas fa-history mr-2"></i><?= $showOld ? 'Nur aktuelle Proben' : 'Alle Proben (inkl. vergangene)' ?>
                </a>
            </div>
        </div>
    </div>

    <div class="w-full mt-4">
        <div class="w-full">
            <div class="table-responsive">
                <table class="table-themed">
                    <thead>
                        <tr>
                            <th>Tag</th>
                            <th>Datum</th>
                            <th>Zeit</th>
                            <th>Ort</th>
                            <th>Stimmen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rehearsals)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Keine Proben gefunden</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rehearsals as $i => $rehearsal): ?>
                                <?php
                                    $start_time_pp = isset($rehearsal['start_time']) ? substr($rehearsal['start_time'], 0, 5) : '??:??';
                                    $end_time_pp = isset($rehearsal['end_time']) ? substr($rehearsal['end_time'], 0, 5) : '??:??';
                                    $time_display_pp = $start_time_pp . ' - ' . $end_time_pp;
                                ?>
                                <tr class="<?= !empty($rehearsal['color']) ? '' : '' ?>" style="<?= !empty($rehearsal['color']) ? 'border-left: 4px solid ' . $rehearsal['color'] . ';' : '' ?>">
                                    <td><?= isset($days[$i]) ? $days[$i] : '' ?></td>
                                    <td><?= $rehearsal['date_formatted'] ?? $rehearsal['date'] ?></td>
                                    <td><?= htmlspecialchars($time_display_pp) ?></td>
                                    <td><?= $rehearsal['location'] ?></td>
                                    <td>
                                        <?php 
                                        if (isset($rehearsal['groups']) && is_array($rehearsal['groups'])) {
                                            echo implode(', ', array_map(function($group) {
                                                return str_replace('_', ' ', $group);
                                            }, $rehearsal['groups']));
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <button class="fixed bottom-5 right-5 bg-primary text-white rounded-full w-14 h-14 flex items-center justify-center shadow-lg hover:scale-110 transition-transform print:hidden" onclick="window.print()" id="print-btn">
        <i class="fas fa-print text-xl"></i>
    </button>
</div>

<style>
@media print {
    /* Hide navigation and UI elements */
    .top-nav, nav, header, .sidebar, #sidebar-wrapper, #wrapper > nav,
    .btn, button, .fab, #print-btn, .print\\:hidden {
        display: none !important;
    }

    /* Reset page layout */
    body, html {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
        font-size: 12pt !important;
        line-height: 1.4 !important;
    }

    /* Reset main containers */
    #wrapper, .page-content-inner, .max-w-7xl {
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
    }

    /* Center and style headers */
    h1 {
        text-align: center !important;
        font-size: 18pt !important;
        margin: 0.5em 0 !important;
    }

    h5 {
        text-align: center !important;
        font-size: 12pt !important;
        color: #666 !important;
        margin: 0.5em 0 2em 0 !important;
    }

    /* Table styling */
    .table-responsive {
        overflow: visible !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin: 0 !important;
        font-size: 11pt !important;
    }

    th, td {
        border: 1px solid #333 !important;
        padding: 6px 8px !important;
        text-align: left !important;
    }

    th {
        background-color: #f5f5f5 !important;
        font-weight: bold !important;
    }

    /* Preserve row colors */
    tr[style*="background-color"] {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Page settings */
    @page {
        margin: 1.5cm !important;
        size: A4 !important;
    }
}

@media screen and (max-width: 768px) {
    .table-themed {
        font-size: 0.875rem;
    }
    
    th, td {
        padding: 0.5rem 0.25rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButton = document.getElementById('filterToggle');
    if (filterButton) {
        tippy(filterButton, {
            content: '<?= $personalized ? "Zur vollständigen Ansicht wechseln" : "Zur personalisierten Ansicht wechseln" ?>',
            placement: 'top'
        });
    }
});

function togglePersonalizedView() {
    <?php if ($personalized): ?>
        Swal.fire({
            title: 'Zur vollständigen Ansicht wechseln?',
            text: 'In der vollständigen Ansicht werden alle Proben angezeigt.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Vollständige Ansicht',
            cancelButtonText: 'Abbrechen',
            confirmButtonColor: '#478cf4'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '/probenplan<?= $showOld ? "?showOld=1" : "" ?>';
            }
        });
    <?php else: ?>
        Swal.fire({
            title: 'Zur personalisierten Ansicht wechseln?',
            text: 'In der personalisierten Ansicht werden nur für dich relevante Proben angezeigt.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Personalisierte Ansicht',
            cancelButtonText: 'Abbrechen',
            confirmButtonColor: '#478cf4'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '/probenplan?personalized=1<?= $showOld ? "&showOld=1" : "" ?>';
            }
        });
    <?php endif; ?>
}
</script> 