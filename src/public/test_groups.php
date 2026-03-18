<?php
require_once __DIR__ . '/../bootstrap.php';
$config = \App\Core\GroupManager::getDefaultConfig();
$gm = \App\Core\GroupManager::fromConfig($config);
$groups = $gm->getAllGroups();
foreach ($groups as $id => $g) {
    if (isset($g['type']) && $g['type'] === 'section' || empty($g['type'])) {
        echo $id . " => " . ($g['display_name'] ?? 'N/A') . "\n";
    }
}
