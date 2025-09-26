<?php 
// Load table styles (table component used for styles only)
$renderComponent = false; // Just load styles, don't render component
include __DIR__ . '/../components/table.php'; 
?>

<div class="max-w-7xl mx-auto px-4 mt-4">
    <div class="w-full">
        <div class="w-full text-center mb-6">
            <h5>Stand: <?= date("d.m.Y") ?></h5>
            
            <div class="filter-controls">
                <?php if ($userRole !== 'conductor'): ?>
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
        <div class="table-responsive">
            <table class="table-themed table-striped">
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
                                <tr class="<?= !empty($rehearsal['color']) ? '' : '' ?>">
                                    <td style="<?= !empty($rehearsal['color']) ? 'border-left: 4px solid ' . $rehearsal['color'] . ';' : '' ?>"><?= isset($days[$i]) ? $days[$i] : '' ?></td>
                                    <td><?= $rehearsal['date_formatted'] ?? $rehearsal['date'] ?></td>
                                    <td><?= htmlspecialchars($time_display_pp) ?></td>
                                    <td><?= $rehearsal['location'] ?></td>
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
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
    </div>

    <button class="fixed bottom-5 right-5 bg-primary text-white rounded-full w-14 h-14 flex items-center justify-center shadow-lg hover:scale-110 transition-transform print:hidden" onclick="window.print()" id="print-btn">
        <i class="fas fa-print text-xl"></i>
    </button>
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

.toggle-switch input:checked + .toggle-slider {
    background-color: var(--color-primary);
}

.toggle-switch input:checked + .toggle-slider + .toggle-dot {
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

@media print {
    /* Hide navigation and UI elements */
    .top-nav, nav, header, .sidebar, #sidebar-wrapper, #wrapper > nav,
    .btn, button, .fab, #print-btn, .print\\:hidden, .filter-controls {
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
    // Get toggle elements
    const personalizedToggle = document.getElementById('personalizedToggle');
    const showOldToggle = document.getElementById('showOldToggle');
    
    // Initialize toggle states visually
    initializeToggleStates();
    
    // Add event listeners for immediate toggling
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
        
        // Preserve showOld parameter if it exists
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
        
        // Preserve personalized parameter if it exists
        if (<?= json_encode($personalized ?? false) ?>) {
            url.searchParams.set('personalized', '1');
        }
        
        window.location.href = url.toString();
    }
});
</script> 