<?php
/**
 * Modular Rehearsal Card Component
 * 
 * This component can be used in different contexts (promises, rehearsals)
 * with different button configurations and behaviors.
 * 
 * @param array $rehearsal - The rehearsal data
 * @param string $context - Context: 'promises' or 'rehearsals'
 * @param array $options - Additional options for customization
 *   - status: string - For promises context (attending/not_attending/pending)
 *   - note: string - Note text for promises
 *   - showButtons: bool - Whether to show action buttons (default: true)
 *   - buttons: array - Custom button configuration
 */

// Set default values
$context = $context ?? 'rehearsals';
$options = $options ?? [];
$status = $options['status'] ?? 'pending';
$note = $options['note'] ?? '';
$showButtons = $options['showButtons'] ?? true;
$buttons = $options['buttons'] ?? [];

// Get rehearsal data
$rehearsalId = $rehearsal['id'];
$groupArray = $rehearsal['groups'] ?? [];

// Generate smart display text with integrated Kleingruppe handling
$smartDisplay = new \App\Core\SmartGroupDisplay();
$groupsText = $smartDisplay->generateDescription(
    $groupArray, 
    $rehearsal, 
    false // Not admin view
);

// Prepare time display
$start_time = isset($rehearsal['start_time']) ? substr($rehearsal['start_time'], 0, 5) : '??:??';
$end_time = isset($rehearsal['end_time']) ? substr($rehearsal['end_time'], 0, 5) : '??:??';
$time_display = $start_time . ' - ' . $end_time;

// Get German weekday abbreviations
$germanWeekdays = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
$dayOfWeek = date('w', strtotime($rehearsal['date']));
$weekdayShort = $germanWeekdays[$dayOfWeek];

// Get rehearsal type using modern manager
$rehearsalType = \App\Core\RehearsalTypeManager::getRehearsalType($rehearsal);

// Check if we should show the rehearsal type badge
$showRehearsalType = \App\Core\RehearsalTypeManager::shouldDisplayType($rehearsalType);

// Check if location is different from normal rehearsal
$normalLocation = 'Probenraum'; // Default normal rehearsal location
$showLocation = !empty($rehearsal['location']) && 
               $rehearsal['location'] !== $normalLocation &&
               strtolower($rehearsal['location']) !== strtolower($normalLocation);

// Determine CSS classes based on context
$cardClasses = 'rehearsal-card';
if ($context === 'promises') {
    $cardClasses .= ' status-' . $status;
}
?>

<div class="<?= $cardClasses ?>" style="<?= !empty($rehearsal['color']) ? 'border-left-color: ' . $rehearsal['color'] . ';' : '' ?>">
    <div class="rehearsal-card-content">
        <div class="rehearsal-card-info">
            <div class="rehearsal-card-header">
                <div class="rehearsal-main-info">
                    <!-- Use consistent row layout for both contexts -->
                    <div class="rehearsal-content-row">
                        <div class="rehearsal-weekday"><?= strtoupper($weekdayShort) ?></div>
                        <div class="rehearsal-datetime-block">
                            <div class="rehearsal-date">
                                <?= htmlspecialchars($rehearsal['date_formatted'] ?? $rehearsal['date']) ?>
                            </div>
                            <div class="rehearsal-time-container">
                                <div class="rehearsal-time stretch-text"><?= htmlspecialchars($time_display) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="rehearsal-badges">
                        <?php if ($showRehearsalType): ?>
                            <div class="rehearsal-type-badge"><?= htmlspecialchars($rehearsalType) ?></div>
                        <?php endif; ?>
                        <div class="rehearsal-section-badge"><?= $groupsText ?></div>
                        <?php if ($showLocation): ?>
                            <div class="rehearsal-location-badge"><?= htmlspecialchars($rehearsal['location']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($context === 'promises' && !empty($note)): ?>
                    <div class="rehearsal-note-tag">
                        <?= icon('quote-left', 'rehearsal-note-icon') ?>
                        <span class="rehearsal-note-text"><?= htmlspecialchars($note) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($showButtons): ?>
            <div class="rehearsal-actions">
                <?php if ($context === 'promises'): ?>
                    <!-- Promises buttons: Attend/Not Attend -->
                    <button type="button" id="<?= $rehearsalId ?>" class="checkBtn action-btn <?= $status !== 'attending' ? 'deselected' : 'selected' ?>">
                        <img src="/assets/img/icons8_checked_checkbox_48px_2.png" alt="Zusagen">
                    </button>
                    <button type="button" id="<?= $rehearsalId ?>" class="crossBtn action-btn cross-btn <?= $status !== 'not_attending' ? 'deselected' : 'selected' ?>">
                        <img src="/assets/img/icons8_close_window_48px_1.png" alt="Absagen">
                    </button>
                <?php elseif ($context === 'rehearsals'): ?>
                    <!-- Rehearsals buttons: Edit/Delete -->
                    <button type="button" id="<?= $rehearsalId ?>" class="btn-icon btn-outline edit-btn">
                        <i ><?= icon('edit', 'text-gray-600') ?></i>
                    </button>
                    <button type="button" id="<?= $rehearsalId ?>" class="btn-icon btn-danger delete-btn">
                        <i ><?= icon('trash', 'text-white') ?></i>
                    </button>
                <?php else: ?>
                    <!-- Custom buttons -->
                    <?php foreach ($buttons as $button): ?>
                        <button 
                            type="button" 
                            id="<?= $rehearsalId ?>" 
                            class="<?= $button['class'] ?? 'btn-primary' ?>"
                            <?= !empty($button['data_attrs']) ? implode(' ', array_map(function($k, $v) { return 'data-' . $k . '="' . htmlspecialchars($v) . '"'; }, array_keys($button['data_attrs']), $button['data_attrs'])) : '' ?>
                        >
                            <?= $button['content'] ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    </div>
    
    <?php if ($context === 'promises'): ?>
        <input type="hidden" id="note<?= $rehearsalId ?>" value="<?= htmlspecialchars($note) ?>">
    <?php endif; ?>
</div>
