<?php
// Component styles
$renderComponent = false;
include __DIR__ . '/../components/table.php';
?>

<div class="">
    <div class="w-full">
        <!-- Print Header -->
        <div class="print-header print-only">
            <div class="print-header-main">
                <h1 class="print-title">Probenplan</h1>
                <div class="print-subtitle">
                    <?= $_SESSION['current_orchestra_name'] ?? 'Orchester' ?>
                    <?php if ($personalized): ?>
                        <?php
                        $isConductor = !empty($_SESSION['current_permissions']['can_manage_ensemble']);
                        $gm = \App\Core\GroupManager::getInstance();
                        $typeLabel = $isConductor ? 'Leitung' : $gm->getDisplayName($_SESSION['current_type'] ?? '');
                        ?>
                        · Personalisierte Ansicht (<?= $typeLabel ?>)
                    <?php else: ?>
                        · Alle Proben
                    <?php endif; ?>
                </div>
            </div>
            <div class="print-info">
                <div class="print-date">Stand: <?= date("d.m.Y, H:i") ?> Uhr</div>
                <?php if (!empty($rehearsals)): ?>
                    <div class="print-range">
                        <?php
                        $firstDate = reset($rehearsals)['date_formatted'];
                        $lastDate = end($rehearsals)['date_formatted'];
                        if ($firstDate === $lastDate) {
                            echo $firstDate;
                        } else {
                            echo $firstDate . ' – ' . $lastDate;
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="w-full text-center mb-6">
            <h5>Stand: <?= date("d.m.Y") ?></h5>

            <div class="filter-controls">
                <?php if (!$canManageRehearsals): ?>
                    <div class="filter-toggle-container">
                        <span class="filter-label">Personalisierte Ansicht</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="personalizedToggle" <?= $personalized ? 'checked' : '' ?> />
                            <span class="toggle-slider"></span>
                            <span class="toggle-dot"></span>
                        </label>
                    </div>
                <?php endif; ?>

                <div class="filter-toggle-container">
                    <span class="filter-label">Vergangene Proben anzeigen</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="showOldToggle" <?= $showOld ? 'checked' : '' ?> />
                        <span class="toggle-slider"></span>
                        <span class="toggle-dot"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="w-full mt-4">
        <?php if (empty($rehearsals)): ?>
            <?php
            $title = 'Keine Proben gefunden';
            $message = 'Aktuell sind keine öffentlichen Proben eingetragen.';

            if (!$showOld && ($hasPastRehearsals ?? false)) {
                $buttonParams = $personalized ? '&personalized=1' : '';
                $actionHref = '?showOld=1' . $buttonParams;
                $actionLabel = 'Vergangene Proben anzeigen';
            }

            include __DIR__ . '/../components/empty-state.php';
            ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-themed table-striped">
                    <thead>
                        <tr>
                            <th>Tag</th>
                            <th>Datum</th>
                            <th>Zeit</th>
                            <th>Ort</th>
                            <th>Art</th>
                            <th>Stimmen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rehearsals as $i => $rehearsal): ?>
                            <?php
                            $start_time_pp = substr($rehearsal['start_time'], 0, 5);
                            $end_time_pp = substr($rehearsal['end_time'], 0, 5);
                            $time_display_pp = $start_time_pp . ' - ' . $end_time_pp;
                            ?>
                            <tr class="<?= !empty($rehearsal['color']) ? '' : '' ?>">
                                <td style="<?= !empty($rehearsal['color']) ? 'border-left: 4px solid ' . $rehearsal['color'] . ';' : '' ?>"><?= isset($days[$i]) ? $days[$i] : '' ?></td>
                                <td><?= $rehearsal['date_formatted'] ?></td>
                                <td><?= htmlspecialchars($time_display_pp) ?></td>
                                <td><?= $rehearsal['location'] ?></td>
                                <td>
                                    <?php
                                    $rehearsalType = \App\Core\RehearsalTypeManager::getRehearsalType($rehearsal);
                                    if (\App\Core\RehearsalTypeManager::shouldDisplayType($rehearsalType)) {
                                        echo htmlspecialchars($rehearsalType);
                                    } elseif ($rehearsalType === \App\Core\RehearsalTypeManager::TYPE_REHEARSAL) {
                                        echo \App\Core\RehearsalTypeManager::TYPE_REHEARSAL;
                                    } else {
                                        echo '';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if (isset($rehearsal['groups']) && is_array($rehearsal['groups'])) {
                                        $smartDisplay = new \App\Core\SmartGroupDisplay();
                                        echo htmlspecialchars($smartDisplay->generateDescription($rehearsal['groups'], $rehearsal, false));
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php
    // Print Button
    $renderComponent = true;
    $icon = 'print';
    $onclick = 'window.print()';
    $title = 'Drucken';
    $id = 'print-btn';
    include __DIR__ . '/../components/fab.php';
    ?>
</div>

<style>
    /* Filter Controls Styling */
    .filter-controls {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-6);
        justify-content: center;
        align-items: center;
        margin-bottom: var(--space-6);
        padding: var(--space-4);
        background: var(--color-bg-primary);
        border-radius: var(--radius-lg);
        border: 1px solid var(--color-border);
        box-shadow: var(--shadow-sm);
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .filter-toggle-container {
        display: flex;
        align-items: center;
        gap: var(--space-3);
    }

    .filter-label {
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-medium);
        color: var(--color-text-secondary);
        white-space: nowrap;
    }

    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
        cursor: pointer;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: var(--color-gray-300);
        border-radius: 24px;
        transition: var(--transition-base);
    }

    .toggle-dot {
        position: absolute;
        content: '';
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: var(--color-white);
        border-radius: 50%;
        transition: var(--transition-base);
        box-shadow: var(--shadow-sm);
    }

    .toggle-switch input:checked+.toggle-slider {
        background-color: var(--color-primary);
    }

    .toggle-switch input:checked+.toggle-slider+.toggle-dot {
        transform: translateX(26px);
    }

    .toggle-switch:hover .toggle-dot {
        box-shadow: var(--shadow-md);
    }

    @media (max-width: 640px) {
        .filter-controls {
            flex-direction: column;
            gap: var(--space-4);
            padding: var(--space-3);
        }

        .filter-toggle-container {
            width: 100%;
            justify-content: space-between;
            padding: var(--space-2) 0;
        }
    }

    /* Print-only elements */
    .print-only {
        display: none;
    }

    @media print {

        /* Define CSS variables for print context */
        :root {
            --radius-base: 8px;
            --color-border: #e5e7eb;
        }

        /* Show print-only elements */
        .print-only {
            display: block !important;
        }

        /* Hide specific UI elements for print */
        .top-nav,
        nav,
        header,
        .sidebar,
        #sidebar-wrapper,
        .sidebar-overlay,
        .btn,
        button,
        .fab,
        .filter-controls,
        .toggle-switch,
        .filter-toggle-container,
        .filter-label,
        .toggle-slider,
        .toggle-dot,
        .page-header,
        .breadcrumbs,
        .alert,
        .toast,
        .modal,
        .dropdown,
        /* Hide the screen header with date */
        .w-full.text-center.mb-6 {
            display: none !important;
        }

        /* Reset page layout for clean printing */
        * {
            box-shadow: none !important;
            text-shadow: none !important;
        }

        body,
        html {
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif !important;
            font-size: 10pt !important;
            line-height: 1.3 !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Reset main containers for print */
        body,
        html {
            background: white !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Force main content to be visible and properly positioned */
        #wrapper {
            display: block !important;
            position: static !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        #page-content-wrapper {
            display: block !important;
            position: static !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .page-content-inner {
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .max-w-7xl {
            max-width: none !important;
            margin: 0 auto !important;
            padding: 0 20px !important;
            display: block !important;
        }

        /* Ensure content divs are visible */
        .max-w-7xl>.w-full {
            display: block !important;
        }

        .w-full.mt-4 {
            display: block !important;
            margin-top: 0 !important;
        }

        /* Print Header Styling */
        .print-header {
            margin-bottom: 24px !important;
            padding-bottom: 16px !important;
            border-bottom: 2px solid #e5e7eb !important;
        }

        .print-header-main {
            text-align: center !important;
            margin-bottom: 12px !important;
        }

        .print-title {
            font-size: 20pt !important;
            font-weight: bold !important;
            margin: 0 0 6px 0 !important;
            color: #111827 !important;
        }

        .print-subtitle {
            font-size: 12pt !important;
            color: #6b7280 !important;
            font-weight: normal !important;
            margin: 0 !important;
        }

        .print-info {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            font-size: 9pt !important;
            color: #9ca3af !important;
        }

        .print-date,
        .print-range {
            margin: 0 !important;
        }

        /* Table styling */
        .table-responsive {
            overflow: visible !important;
            border-radius: var(--radius-base) !important;
            border: 1px solid var(--color-border) !important;
            box-shadow: none !important;
            background: white !important;
        }

        table.table-themed {
            width: 100% !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            margin: 0 !important;
            font-size: 11pt !important;
            background: white !important;
        }

        table.table-themed th {
            background: #f3f4f6 !important;
            font-weight: 600 !important;
            color: #374151 !important;
            padding: 12px 16px !important;
            text-align: left !important;
            border-bottom: 2px solid #e5e7eb !important;
            font-size: 11pt !important;
        }

        table.table-themed td {
            padding: 12px 16px !important;
            text-align: left !important;
            border-bottom: 1px solid #e5e7eb !important;
            color: #111827 !important;
            font-size: 11pt !important;
            vertical-align: top !important;
        }

        /* Match UI striped rows exactly */
        table.table-themed.table-striped tbody tr:nth-child(odd) {
            background: #ffffff !important;
        }

        table.table-themed.table-striped tbody tr:nth-child(even) {
            background: #f3f4f6 !important;
        }

        /* Round header corners like UI */
        table.table-themed thead th:first-child {
            border-top-left-radius: var(--radius-base) !important;
        }

        table.table-themed thead th:last-child {
            border-top-right-radius: var(--radius-base) !important;
        }

        /* Color borders */
        table.table-themed td[style*="border-left"] {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Natural column widths for print like UI */

        /* Page settings for optimal printing */
        @page {
            margin: 1.2cm !important;
            size: A4 portrait !important;
        }

        /* Prevent table from breaking across pages poorly */
        table.table-themed {
            page-break-inside: avoid !important;
        }

        table.table-themed tr {
            page-break-inside: avoid !important;
            page-break-after: auto !important;
        }

        table.table-themed thead {
            display: table-header-group !important;
        }

        table.table-themed tfoot {
            display: table-footer-group !important;
        }
    }

    @media screen and (max-width: 768px) {
        .table-themed {
            font-size: 0.875rem;
        }

        th,
        td {
            padding: 0.5rem 0.25rem;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const personalizedToggle = document.getElementById('personalizedToggle');
        const showOldToggle = document.getElementById('showOldToggle');

        // Initialize
        initializeToggleStates();

        // Listeners
        if (personalizedToggle) {
            personalizedToggle.addEventListener('change', handlePersonalizedToggle);
        }

        if (showOldToggle) {
            showOldToggle.addEventListener('change', handleShowOldToggle);
        }

        function initializeToggleStates() {
            const currentlyPersonalized = <?= json_encode($personalized ?? false) ?>;
            const currentlyShowingOld = <?= json_encode($showOld ?? false) ?>;

            if (personalizedToggle) {
                updateToggleVisuals(personalizedToggle, currentlyPersonalized);
            }

            if (showOldToggle) {
                updateToggleVisuals(showOldToggle, currentlyShowingOld);
            }
        }

        function updateToggleVisuals(toggle, isActive) {
            const slider = toggle.nextElementSibling;
            const dot = slider.nextElementSibling;

            if (isActive) {
                slider.style.backgroundColor = 'var(--color-primary)';
                dot.style.transform = 'translateX(26px)';
            } else {
                slider.style.backgroundColor = 'var(--color-gray-300)';
                dot.style.transform = 'translateX(0px)';
            }
        }

        function handlePersonalizedToggle() {
            const url = new URL(window.location);
            const slider = this.nextElementSibling;
            const dot = slider.nextElementSibling;

            if (this.checked) {
                url.searchParams.set('personalized', '1');
                slider.style.backgroundColor = 'var(--color-primary)';
                dot.style.transform = 'translateX(26px)';
            } else {
                url.searchParams.delete('personalized');
                slider.style.backgroundColor = 'var(--color-gray-300)';
                dot.style.transform = 'translateX(0px)';
            }

            // Preserve parameter
            if (<?= json_encode($showOld ?? false) ?>) {
                url.searchParams.set('showOld', '1');
            }

            window.location.href = url.toString();
        }

        function handleShowOldToggle() {
            const url = new URL(window.location);
            const slider = this.nextElementSibling;
            const dot = slider.nextElementSibling;

            if (this.checked) {
                url.searchParams.set('showOld', '1');
                slider.style.backgroundColor = 'var(--color-primary)';
                dot.style.transform = 'translateX(26px)';
            } else {
                url.searchParams.delete('showOld');
                slider.style.backgroundColor = 'var(--color-gray-300)';
                dot.style.transform = 'translateX(0px)';
            }

            // Preserve parameter
            if (<?= json_encode($personalized ?? false) ?>) {
                url.searchParams.set('personalized', '1');
            }

            window.location.href = url.toString();
        }
    });
</script>