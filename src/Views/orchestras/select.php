<?php $this->layout('layouts/default', ['title' => 'Orchester auswählen', 'currentPage' => 'orchestra_select']) ?>

<?php 
// Component styles
$renderComponent = false;
include __DIR__ . '/../components/form-input.php'; 
include __DIR__ . '/../components/user-badge.php'; 
?>

<style>
/* Additional styles for orchestra selection */
.orchestra-card {
    display: block;
    width: 100%;
    text-align: left;
    padding: 1rem;
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-lg);
    background: white;
    transition: all var(--transition-base);
    text-decoration: none;
    color: inherit;
}

.orchestra-card:hover {
    border-color: var(--color-primary);
    background-color: var(--color-gray-50);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.orchestra-card h3 {
    font-weight: 600;
    color: var(--color-text-primary);
    margin-bottom: 0.25rem;
    display: inline-flex;
    align-items: center;
    line-height: 1.3;
}

.orchestra-card:hover h3 {
    color: var(--color-primary);
}

.orchestra-meta {
    font-size: var(--font-size-sm);
    color: var(--color-text-secondary);
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
}

.orchestra-date {
    font-size: var(--font-size-xs);
    color: var(--color-gray-500);
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--color-gray-500);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    display: block;
}

.join-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 0.75rem 1rem;
    background: var(--color-primary);
    color: white;
    text-decoration: none;
    border-radius: var(--radius-md);
    font-size: var(--font-size-sm);
    font-weight: 500;
    transition: background-color var(--transition-base);
}

.join-button:hover {
    background: var(--color-primary-600);
    color: white;
}

.join-button-outline {
    background: transparent;
    color: var(--color-primary);
    border: 1px solid var(--color-primary);
}

.join-button-outline:hover {
    background: var(--color-primary);
    color: white;
}

.divider {
    border-top: 1px solid var(--color-gray-200);
    margin: 1rem 0;
    padding-top: 1rem;
}

/* Badge integration */
.orchestra-card .user-badge {
    margin-left: 0.5rem;
    vertical-align: middle;
}

/* Empty state card - no hover effects */
.orchestra-card[style*="cursor: default"]:hover {
    border-color: var(--color-gray-300);
    background-color: white;
    transform: none;
    box-shadow: none;
}
</style>

<div class="login-container">
    <div class="admin-verify-back">
        <a href="/logout" class="back-link">
            <i class="fas fa-sign-out-alt"></i>
            Abmelden
        </a>
    </div>
    
    <div class="login-form">
        <div class="login-logo">
            <img src="/assets/img/Logo.png" alt="Logo"/>
        </div>


        <?php if (!empty($orchestras)): ?>
            <div style="margin-bottom: 1.5rem;">
                <?php foreach ($orchestras as $orchestra): ?>
                    <form method="POST" action="/orchestras/set-current" style="margin-bottom: 0.75rem;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <input type="hidden" name="orchestra_id" value="<?= htmlspecialchars($orchestra['orchestra_id']) ?>">
                        
                        <button type="submit" class="orchestra-card">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h3>
                                        <?= htmlspecialchars($orchestra['orchestra_name']) ?>
                                    </h3>
                                    <div class="orchestra-meta">
                                        <?php 
                                        // Display info
                                        $displayInfo = \App\Core\Utilities::getUserDisplayInfo($orchestra['type'], $orchestra['role']);
                                        ?>
                                        <?php if ($displayInfo['type']): ?>
                                        <span>
                                            <?= htmlspecialchars($displayInfo['type']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <span>
                                            <?= htmlspecialchars($displayInfo['role']) ?>
                                        </span>
                                        <?php 
                                        // Role badges
                                        $userData = [
                                            'role' => $orchestra['role'],
                                            'is_small_group' => false // This will be handled in new system
                                        ];
                                        $badges = \App\Core\Utilities::generateUserBadges($userData);
                                        echo $badges;
                                        ?>
                                    </div>
                                </div>
                                <div style="color: var(--color-primary);">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </div>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
            
            <a href="/orchestras/join" class="join-button join-button-outline">
                <i class="fas fa-plus" style="margin-right: 0.5rem;"></i>
                Beitreten
            </a>
        <?php else: ?>
            <div style="margin-bottom: 1.5rem;">
                <div class="orchestra-card" style="cursor: default; border-style: dashed; border-color: var(--color-gray-300);">
                    <div style="text-align: center; color: var(--color-text-secondary);">
                        <div style="font-weight: 500; margin-bottom: 0.25rem;">
                            Keinem Orchester beigetreten
                        </div>
                        <div style="font-size: var(--font-size-sm); color: var(--color-gray-500);">
                            Treten Sie einem Orchester bei, um zu beginnen
                        </div>
                    </div>
                </div>
            </div>
            
            <a href="/orchestras/join" class="join-button join-button-outline">
                <i class="fas fa-plus" style="margin-right: 0.5rem;"></i>
                Beitreten
            </a>
        <?php endif; ?>
        
        <div class="auth-links">
            <a href="/orchestras/create" class="auth-link auth-link-secondary">
                Neues Orchester erstellen
            </a>
        </div>
    </div>
</div>
