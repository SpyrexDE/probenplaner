<?php
/**
 * RECURRING REHEARSAL CARD
 *
 * A proper rehearsal-card ie-card ie-expanded with no data-api-url.
 * Reuses IEM.editType / editLocation / editColor directly.
 * Emulates the exact HTML structure of rehearsal-card.php for dates, times, schedule, infos, and tags.
 */
?>

<?php if (!defined('RECURRING_CARD_STYLES_LOADED')): define('RECURRING_CARD_STYLES_LOADED', true); ?>
<style>
    .recurring-card-wrapper { display: none; margin-bottom: var(--space-3); }
    .recurring-card-wrapper.open { display: block; animation: ie-fade-in 0.3s ease; }

    .rc-section-row {
        display: flex;
        gap: var(--space-2);
        align-items: center;
        flex-wrap: wrap;
    }
    .rc-day {
        padding: 6px 12px;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        background: var(--color-bg-primary);
        color: var(--color-text-secondary);
        font-size: 12px;
        font-weight: var(--font-weight-semibold);
        cursor: pointer;
        transition: all 0.15s ease;
        min-width: 42px;
        text-align: center;
        min-height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .rc-day:hover { border-color: var(--color-primary-200); background: var(--color-bg-secondary); }
    .rc-day.active { background: var(--color-primary); border-color: var(--color-primary); color: #fff; }

    .rc-toggle {
        display: inline-flex;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        overflow: hidden;
    }
    .rc-toggle-opt {
        padding: 6px 14px;
        border: none;
        background: var(--color-bg-primary);
        color: var(--color-text-secondary);
        font-size: 12px;
        cursor: pointer;
        transition: all 0.15s ease;
        min-height: 32px;
    }
    .rc-toggle-opt:not(:last-child) { border-right: 1px solid var(--color-border); }
    .rc-toggle-opt.active { background: var(--color-primary); color: #fff; }

    .rc-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: var(--space-3);
        padding-top: var(--space-2);
        border-top: 1px solid var(--color-border);
    }
    .rc-preview { font-size: var(--font-size-sm); color: var(--color-text-muted); }
    .rc-preview strong { color: var(--color-primary); font-weight: var(--font-weight-semibold); }
    .rc-submit {
        padding: var(--space-2) var(--space-5);
        background: var(--color-primary);
        color: #fff;
        border: none;
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-semibold);
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .rc-submit:hover { box-shadow: var(--shadow-md); }
    .rc-submit:disabled { opacity: 0.5; cursor: not-allowed; }

    /* Fix z-index for popovers inside recurring card */
    .recurring-card-wrapper .ie-card { overflow: visible !important; }
</style>
<?php endif; ?>

<div class="recurring-card-wrapper" id="recurringCard">
    <div class="rehearsal-card ie-card ie-expanded border-l-4 border rounded-lg"
         data-rehearsal-id="recurring"
         data-type=""
         data-location="Probenraum"
         data-color="#e5e7eb"
         data-start="<?= date('Y-m-d 18:00:00', strtotime('next monday')) ?>"
         data-end="<?= date('Y-m-d 20:00:00', strtotime('next monday + 3 months')) ?>"
         style="border-radius: var(--radius-lg); padding: var(--space-4) var(--space-5); border-left-color: var(--color-primary);">

        <div class="flex items-start w-full gap-3">
            <div class="flex-1 min-w-0 flex flex-col">

                <!-- Row 1: Header (same layout as rehearsal cards) -->
                <div class="flex items-center gap-2 flex-wrap" style="margin-bottom: 8px;">
                    <div class="rehearsal-weekday"><i class="fas fa-layer-group"></i></div>
                    <div class="flex flex-col gap-0" style="padding: 2px 6px;">
                        <div class="flex items-center gap-2 mb-1">
                            <span style="font-size: 10px; font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-text-secondary); width: 30px;">Von</span>
                            <span style="display:inline-block;position:relative;cursor:pointer;text-decoration:underline dashed var(--color-primary-200);text-underline-offset:2px;text-decoration-thickness:2px; font-size: var(--font-size-sm); font-weight: var(--font-weight-bold); color: var(--color-text-primary);" id="recurringStartSpan">
                                <?= date('Y-m-d', strtotime('next monday')) ?>
                                <input type="date" id="recurringStart" value="<?= date('Y-m-d', strtotime('next monday')) ?>" style="display:block;position:absolute;inset:0;width:100%;height:100%;opacity:0.01;cursor:pointer;z-index:1;border:0;padding:0;margin:0;-webkit-appearance:none;background:transparent;color:transparent;font-size:16px;" onchange="document.getElementById('recurringStartSpan').firstChild.textContent = this.value; window.updatePreview?.()" onclick="try { this.showPicker(); } catch(e) {}">
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span style="font-size: 10px; font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-text-secondary); width: 30px;">Bis</span>
                            <span style="display:inline-block;position:relative;cursor:pointer;text-decoration:underline dashed var(--color-primary-200);text-underline-offset:2px;text-decoration-thickness:2px; font-size: var(--font-size-sm); font-weight: var(--font-weight-bold); color: var(--color-text-primary);" id="recurringEndSpan">
                                <?= date('Y-m-d', strtotime('next monday + 3 months')) ?>
                                <input type="date" id="recurringEnd" value="<?= date('Y-m-d', strtotime('next monday + 3 months')) ?>" style="display:block;position:absolute;inset:0;width:100%;height:100%;opacity:0.01;cursor:pointer;z-index:1;border:0;padding:0;margin:0;-webkit-appearance:none;background:transparent;color:transparent;font-size:16px;" onchange="document.getElementById('recurringEndSpan').firstChild.textContent = this.value; document.getElementById('recurringDuration').value = 'custom'; window.updatePreview?.()" onclick="try { this.showPicker(); } catch(e) {}">
                            </span>
                            
                            <select id="recurringDuration" style="display:inline-block; cursor:pointer; font-size: 10px; font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.3px; padding: 2px 20px 2px 6px; border-radius: var(--radius-sm); border: 1px dashed var(--color-primary-200); color: var(--color-primary); background: rgba(71, 140, 244, 0.05); margin-left: 8px; appearance:none; -webkit-appearance:none; outline:none; background-image: url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'8\' height=\'8\' viewBox=\'0 0 12 12\'><path d=\'M3 5l3 3 3-3\' fill=\'none\' stroke=\'%23478cf4\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/></svg>'); background-repeat: no-repeat; background-position: right 4px center;" onchange="window.updateEndDateFromDuration?.(this)">
                                <option value="custom" style="display:none;">Individuell</option>
                                <option value="1w">1 Woche</option>
                                <option value="2w">2 Wochen</option>
                                <option value="3w">3 Wochen</option>
                                <option value="4w">4 Wochen</option>
                                <option value="1m">1 Monat</option>
                                <option value="2m">2 Monate</option>
                                <option value="3m" selected>3 Monate</option>
                                <option value="6m">6 Monate</option>
                                <option value="1y">1 Jahr</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" class="ie-edit-toggle" id="recurringClose"
                            style="margin-left: auto; color: var(--color-primary); background: rgba(71, 140, 244, 0.12);" title="Schließen">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Row 2: Badges (identical to rehearsal-card expanded badges) -->
                <div class="rehearsal-badges flex items-center gap-1 flex-wrap">
                    <div class="rehearsal-type-badge ie-editable" data-ie-type data-ie-field="type"
                         onclick="window.IEM && window.IEM.editType(this)"
                         style="font-size: 10px; font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.3px; padding: 2px 6px; border-radius: var(--radius-sm); display: inline-block; width: fit-content; margin-right: var(--space-1); opacity: 0.4; border-style: dashed;">
                        Typ…
                    </div>

                    <span class="ie-editable" data-ie-location data-ie-field="location"
                          onclick="window.IEM && window.IEM.editLocation(this)"
                          style="font-size: 10px; font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.3px; padding: 2px 6px; border-radius: var(--radius-sm); display: inline-block; width: fit-content; margin-right: var(--space-1); color: var(--color-text-secondary);">
                        📍 Probenraum
                    </span>

                    <span class="ie-editable" data-ie-field="color" data-ie-color
                          onclick="window.IEM && window.IEM.editColor(this)"
                          style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 6px; border-radius: var(--radius-sm); cursor: pointer; position: relative; color: var(--color-text-muted); font-size: 10px;">
                        <span data-ie-color-dot style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: #e5e7eb; border: 1px solid var(--color-border);"></span>
                        Farbe
                    </span>
                </div>

                <!-- Series-specific settings (Wochentage & Intervall) -->
                <div class="ie-section" style="display: block; margin-top: var(--space-4);">
                    <div class="rc-section-row" style="gap: var(--space-4);">
                        <div style="flex: 1;">
                            <span class="rc-label" style="font-size: 10px; font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-text-muted); display: block; margin-bottom: var(--space-2);">Wochentage</span>
                            <div class="rc-section-row" id="recurringDays">
                                <button type="button" class="rc-day" data-day="1">Mo</button>
                                <button type="button" class="rc-day" data-day="2">Di</button>
                                <button type="button" class="rc-day" data-day="3">Mi</button>
                                <button type="button" class="rc-day" data-day="4">Do</button>
                                <button type="button" class="rc-day" data-day="5">Fr</button>
                                <button type="button" class="rc-day" data-day="6">Sa</button>
                                <button type="button" class="rc-day" data-day="0">So</button>
                            </div>
                        </div>
                        <div style="flex: 0 0 auto;">
                            <span class="rc-label" style="font-size: 10px; font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-text-muted); display: block; margin-bottom: var(--space-2);">Intervall</span>
                            <div class="rc-toggle" id="recurringInterval">
                                <button type="button" class="rc-toggle-opt active" data-weeks="1">Jede Woche</button>
                                <button type="button" class="rc-toggle-opt" data-weeks="2">Alle 2 Wochen</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ablauf (Schedule Editor) -->
                <div class="ie-section" style="display: block; margin-top: var(--space-3); padding-top: var(--space-3); border-top: 1px solid var(--color-border);">
                    <?php
                    $formData = ['schedule_items' => []];
                    $autoSave = false;
                    $editorId = 'schedule-editor-recurring';
                    include __DIR__ . '/schedule-editor.php';
                    ?>
                </div>

                <!-- Hinweise (Infobox Editor) -->
                <div class="ie-section" style="display: block; margin-top: var(--space-3); padding-top: var(--space-3); border-top: 1px solid var(--color-border);">
                    <?php
                    $formData = ['infos' => []];
                    $autoSave = false;
                    $editorId = 'infobox-editor-recurring';
                    include __DIR__ . '/infobox-editor.php';
                    ?>
                </div>

                <div class="ie-footer" style="display: flex; border-top: none; padding-top: 0; margin-top: var(--space-3);">
                    <div class="ie-tags" data-ie-tags id="recurringTagsContainer">
                        <!-- Tags rendered here by JS -->
                        <button type="button" class="ie-tag-add"
                            onclick="if(!window.IEM?._guard(event))return; window.IEM.addTagInput(this)"
                            title="Tag hinzufügen">+ Tag</button>
                    </div>
                </div>

                <!-- Footer (Preview & Submit) -->
                <div class="rc-footer">
                    <span class="rc-preview" id="recurringPreview">Wähle mindestens einen Tag</span>
                    <button type="button" class="rc-submit" id="recurringSubmit" disabled>Erstellen</button>
                </div>
            </div>
        </div>
    </div>
</div>
