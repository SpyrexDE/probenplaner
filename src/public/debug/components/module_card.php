<?php
/**
 * Reusable Module Card Component
 * 
 * Usage:
 * include_once __DIR__ . '/components/module_card.php';
 * 
 * echo moduleCard([
 *     'title' => 'Card Title',
 *     'content' => 'Card content here',
 *     'id' => 'unique-id',           // Optional: for closable cards
 *     'is_closable' => true,         // Optional: defaults to false
 *     'class' => 'extra-class'       // Optional: additional CSS classes
 * ]);
 */

function moduleCard($options) {
    $id = $options['id'] ?? 'card-' . uniqid();
    $title = $options['title'] ?? '';
    $content = $options['content'] ?? '';
    $isClosable = $options['is_closable'] ?? false;
    $class = $options['class'] ?? '';
    
    ob_start();
    ?>
    <div class="module-card <?= htmlspecialchars($class) ?>" id="<?= htmlspecialchars($id) ?>">
        <div class="module-card-header">
            <h3><?= htmlspecialchars($title) ?></h3>
            <?php if ($isClosable): ?>
                <button type="button" class="close-button" onclick="document.getElementById('<?= htmlspecialchars($id) ?>').remove()">×</button>
            <?php endif; ?>
        </div>
        <div class="module-card-content">
            <?= $content ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?> 