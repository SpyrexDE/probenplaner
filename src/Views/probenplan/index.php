<div class="container mt-4">
    <div class="row">
        <div class="col-12 text-center">
            <h1>Probenplan</h1>
            <h5>Stand: <?= date("d.m.Y") ?></h5>
            
            <div class="btn-group btn-group-sm mb-3">
                <button id="filterToggle" class="btn btn-outline-primary" onclick="togglePersonalizedView()">
                    <i class="fas fa-filter"></i> <?= $personalized ? 'Personalisierte Ansicht' : 'Alle Proben' ?>
                </button>
                <a href="<?= $showOld ? '/probenplan' . ($personalized ? '?personalized=1' : '') : '/probenplan' . ($personalized ? '?personalized=1&showOld=1' : '?showOld=1') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-history"></i> <?= $showOld ? 'Nur aktuelle Proben' : 'Alle Proben (inkl. vergangene)' ?>
                </a>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-striped">
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
                                <tr style="background-color: <?= !empty($rehearsal['color']) ? $rehearsal['color'] : 'transparent' ?>">
                                    <td><?= isset($days[$i]) ? $days[$i] : '' ?></td>
                                    <td><?= $rehearsal['date'] ?></td>
                                    <td><?= $rehearsal['time'] ?></td>
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

    <div class="row mt-3 mb-5">
        <div class="col-12 text-right">
            <button class="btn btn-primary print-btn" onclick="window.print()">
                <i class="fas fa-print text-white"></i>
            </button>
        </div>
    </div>
</div>

<style>
@media print {
    /* Hide UI elements not for print */
    .print-btn, #filterToggle, .btn-group, /* Probenplan specific controls */
    #sidebar-wrapper, /* Main sidebar */
    .topBar, /* All instances of topBar, including the one above contentPage */
    .navbar, /* Generic bootstrap navbar class */
    nav, /* Generic nav HTML tags, if any */
    header, /* Generic header HTML tags, if any */
    #menu-toggle, /* Sidebar toggle */
    .history-link, .help-link, /* Specific header icons */
    .sidebar-nav, /* Content of sidebar */
    .page-content-wrapper > .col.topBar /* The topBar directly under page-content-wrapper */
    {
        display: none !important;
    }

    /* Ensure the main content and its wrappers are visible and take full space */
    body, html {
        width: 100% !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        background: white !important; /* Prevent unwanted backgrounds */
        overflow: visible !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important; /* For Firefox */
        color-adjust: exact !important; /* Standard */
    }

    #wrapper, 
    .page-content-wrapper, /* This is the main wrapper around contentPage when logged in */
    #contentPage /* This is the direct container for $content when logged in */
    {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important; /* Allow content to flow */
        display: block !important; /* Ensure they are rendered as blocks */
        background-color: transparent !important; /* Avoid overriding content background */
        box-shadow: none !important; /* Remove shadows */
        border: none !important; /* Remove any borders */
    }

    /* Style the actual content container from probenplan/index.php (which is this view itself) */
    /* This rule targets the <div class="container mt-4"> that starts this view file */
    body > .container.mt-4, /* For non-logged in case */
    #contentPage > .container.mt-4 /* For logged in case */
    {
        width: 100% !important;
        max-width: none !important;
        padding: 0 !important; /* Remove padding like mt-4 for print */
        margin: 0 auto !important; /* Center if there\'s still some page margin from @page */
        border: none !important;
        box-shadow: none !important;
    }

    /* Show h1, h5 titles from probenplan/index.php, matching old style */
    h1, h5 {
        display: block !important;
        text-align: center !important;
        margin-top: 0.5em;
        margin-bottom: 0.5em;
    }
    h5 {
        color: gray !important;
        margin-bottom: 5em !important; /* As per user's original example */
    }

    .table-responsive {
        overflow-x: visible !important; /* Ensure full table is printed, not scrolled */
        border: none !important;
        box-shadow: none !important;
    }

    .table {
        width: 100% !important; /* Ensure table takes full width */
        border-collapse: collapse !important;
        margin: 0 !important; /* Remove any margins on table */
    }

    .table th, .table td {
        border: 1px solid #ddd !important;
        padding: 8px !important;
        background-color: transparent !important; /* Ensure row colors are not overridden by general rules */
    }
    
    /* Preserve explicit background colors on table rows if set by style attribute */
    tr[style*="background-color"] {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }

    tr {
        page-break-inside: avoid !important;
    }

    @page {
        margin: 1cm; /* Standard page margin */
    }
}

@media screen {
    .print-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        transition: transform 0.2s;
    }
    
    .print-btn:hover {
        transform: scale(1.1);
    }
    
    .print-btn:active {
        transform: scale(0.9);
    }
}

@media only screen and (max-width: 600px) {
    .table {
        font-size: 0.9rem;
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