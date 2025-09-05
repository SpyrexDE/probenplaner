<?php $this->layout('layouts/default', ['title' => 'Registerführer Dashboard', 'currentPage' => $currentPage ?? 'leader']) ?>

<?php include __DIR__ . '/../components/promises-resources.php'; ?>

<?php if (!empty($rehearsals) && isset($memberPromises) && !empty($_SESSION['role']) && $_SESSION['role'] === 'leader'): ?>
<div style="margin-bottom: var(--space-6); text-align: right;">
    <div style="display: inline-flex; align-items: center; gap: var(--space-3); padding: var(--space-3) var(--space-4); background: var(--color-white); border-radius: var(--radius-lg); border: 1px solid var(--color-border); box-shadow: var(--shadow-sm);" title="<?php echo empty($leadersCanViewAllSections) ? 'Nicht verfügbar: vom Dirigenten deaktiviert' : ''; ?>">
        <span style="font-size: var(--font-size-sm); font-weight: var(--font-weight-medium); color: var(--color-text-secondary);">Alle Register anzeigen</span>
        <label style="position: relative; display: inline-block; width: 50px; height: 24px;">
            <input type="checkbox" id="viewToggle" <?php echo empty($leadersCanViewAllSections) ? 'disabled' : ''; ?> 
                   style="opacity: 0; width: 0; height: 0;" />
            <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--color-gray-300); border-radius: 24px; transition: .3s;"></span>
            <span style="position: absolute; content: ''; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: .3s;"></span>
        </label>
    </div>
</div>
<?php endif; ?>

<?php 
// Set leader context for dashboard
$isAdmin = false;
$isLeader = true;
include __DIR__ . '/../components/promises-dashboard-wrapper.php'; 
?>

<script>
// Leader-specific toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('viewToggle');
    if (toggle) {
        toggle.addEventListener('change', function() {
            const slider = this.nextElementSibling;
            const dot = slider.nextElementSibling;
            
            if (this.checked) {
                slider.style.backgroundColor = 'var(--color-primary)';
                dot.style.transform = 'translateX(26px)';
                showAllSections();
            } else {
                slider.style.backgroundColor = 'var(--color-gray-300)';
                dot.style.transform = 'translateX(0px)';
                showLeaderSectionsOnly();
            }
        });
        
        // Initialize to show only leader's section by default
        // Add a small delay to ensure DOM is fully loaded
        setTimeout(() => {
            console.log('Initializing leader view...');
            console.log('Leader section:', '<?= $leaderSection ?? '' ?>');
            console.log('Leader display name:', '<?= $leaderSectionDisplayName ?? '' ?>');
            console.log('All leader section names:', <?= json_encode($leaderSectionNames ?? []) ?>);
            showLeaderSectionsOnly();
        }, 100);
    }
});

function showAllSections() {
    // Show all section cards
    document.querySelectorAll('.section-card').forEach(card => {
        card.style.display = 'block';
    });
    
    // Show all tree nodes (sections in tree view)
    document.querySelectorAll('.tree-node').forEach(node => {
        node.style.display = 'block';
        // Remove aria-hidden to ensure accessibility
        node.removeAttribute('aria-hidden');
    });
    
    // Show all overview bars (rehearsal stats)
    document.querySelectorAll('.rehearsal-stats-container').forEach(container => {
        container.style.display = 'block';
    });
}

function showLeaderSectionsOnly() {
    // Remove focus from any focused elements in tree nodes before hiding
    document.querySelectorAll('.tree-node button:focus').forEach(button => {
        button.blur();
    });
    
    // Hide all section cards first
    document.querySelectorAll('.section-card').forEach(card => {
        card.style.display = 'none';
    });
    
    // Hide all tree nodes first (including root)
    document.querySelectorAll('.tree-node').forEach(node => {
        node.style.display = 'none';
        // Set aria-hidden to ensure accessibility compliance
        node.setAttribute('aria-hidden', 'true');
    });
    
    // Hide all overview bars first
    document.querySelectorAll('.rehearsal-stats-container').forEach(container => {
        container.style.display = 'none';
    });
    
    // Show only the leader's section
    const leaderSection = '<?= $leaderSection ?? '' ?>';
    const leaderSectionDisplayName = '<?= $leaderSectionDisplayName ?? '' ?>';
    const leaderSectionNames = <?= json_encode($leaderSectionNames ?? []) ?>;
    
    if (leaderSection || leaderSectionDisplayName || leaderSectionNames.length > 0) {
        // Find section cards that match the leader's section
        document.querySelectorAll('.section-card').forEach(card => {
            const sectionName = card.querySelector('.section-name');
            if (sectionName) {
                const nameText = sectionName.textContent.toLowerCase();
                let shouldShow = false;
                
                // Check against all possible leader section names
                for (const leaderName of leaderSectionNames) {
                    const leaderNameLower = leaderName.toLowerCase();
                    if (nameText.includes(leaderNameLower) || 
                        leaderNameLower.includes(nameText) ||
                        nameText === leaderNameLower) {
                        shouldShow = true;
                        break;
                    }
                }
                
                if (shouldShow) {
                    card.style.display = 'block';
                }
            }
        });
        
        // Find tree nodes that match the leader's section (depth 1 and 2)
        document.querySelectorAll('.tree-node').forEach(node => {
            const titleText = node.querySelector('.tree-node-title-text');
            if (titleText) {
                const nameText = titleText.textContent.toLowerCase();
                let shouldShow = false;
                
                // Check against all possible leader section names
                for (const leaderName of leaderSectionNames) {
                    const leaderNameLower = leaderName.toLowerCase();
                    if (nameText.includes(leaderNameLower) || 
                        leaderNameLower.includes(nameText) ||
                        nameText === leaderNameLower) {
                        shouldShow = true;
                        break;
                    }
                }
                
                if (shouldShow) {
                    node.style.display = 'block';
                    // Remove aria-hidden to ensure accessibility
                    node.removeAttribute('aria-hidden');
                }
            }
        });
        
        // Find overview bars that match the leader's section
        document.querySelectorAll('.rehearsal-stats-container').forEach(container => {
            // For overview bars, we need to check if this rehearsal has members from the leader's section
            const rehearsalCard = container.closest('.rehearsal-compact');
            if (rehearsalCard) {
                // Check if any section cards in this rehearsal are visible (match leader's section)
                const sectionCards = rehearsalCard.querySelectorAll('.section-card');
                let hasVisibleSections = false;
                
                sectionCards.forEach(card => {
                    const sectionName = card.querySelector('.section-name');
                    if (sectionName) {
                        const nameText = sectionName.textContent.toLowerCase();
                        for (const leaderName of leaderSectionNames) {
                            const leaderNameLower = leaderName.toLowerCase();
                            if (nameText.includes(leaderNameLower) || 
                                leaderNameLower.includes(nameText) ||
                                nameText === leaderNameLower) {
                                hasVisibleSections = true;
                                break;
                            }
                        }
                    }
                });
                
                if (hasVisibleSections) {
                    container.style.display = 'block';
                }
            }
        });
    }
}
</script>