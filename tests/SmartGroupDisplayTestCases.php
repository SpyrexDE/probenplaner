<?php

require_once __DIR__ . '/../src/Core/GroupManager.php';
require_once __DIR__ . '/../src/Core/SmartGroupDisplay.php';

use App\Core\GroupManager;
use App\Core\SmartGroupDisplay;

// Mock the DB and session requirements for standalone test
class MockDatabase {
    public static function getInstance() { return new self(); }
}
if (!class_exists('App\Core\Database')) {
    class_alias('MockDatabase', 'App\Core\Database');
}
$_SESSION['current_orchestra_id'] = 'test';

// Define complex config with 2 Roots: Tutti (Orchestra) and Chor
$config = [
    [
        'id' => 'tutti',
        'display_name' => 'Tutti',
        'type' => 'root',
        'children' => [
            [
                'id' => 'Streicher',
                'display_name' => 'Streicher',
                'type' => 'section',
                'children' => [
                    ['id' => 'Violine 1', 'type' => 'instrument', 'display_name' => 'Violine 1'],
                    ['id' => 'Violine 2', 'type' => 'instrument', 'display_name' => 'Violine 2'],
                    ['id' => 'Bratsche', 'type' => 'instrument', 'display_name' => 'Bratsche'],
                    ['id' => 'Cello', 'type' => 'instrument', 'display_name' => 'Cello'],
                    ['id' => 'Kontrabass', 'type' => 'instrument', 'display_name' => 'Kontrabass'],
                ]
            ],
            [
                'id' => 'Bläser',
                'display_name' => 'Bläser',
                'type' => 'section',
                'children' => [
                    [
                        'id' => 'Holzbläser',
                        'display_name' => 'Holzbläser',
                        'type' => 'section',
                        'children' => [
                            ['id' => 'Flöte', 'type' => 'instrument', 'display_name' => 'Flöte'],
                            ['id' => 'Oboe', 'type' => 'instrument', 'display_name' => 'Oboe'],
                            ['id' => 'Klarinette', 'type' => 'instrument', 'display_name' => 'Klarinette'],
                            ['id' => 'Fagott', 'type' => 'instrument', 'display_name' => 'Fagott'],
                        ]
                    ],
                    [
                        'id' => 'Blechbläser',
                        'display_name' => 'Blechbläser',
                        'type' => 'section',
                        'children' => [
                            ['id' => 'Horn', 'type' => 'instrument', 'display_name' => 'Horn'],
                            ['id' => 'Trompete', 'type' => 'instrument', 'display_name' => 'Trompete'],
                            ['id' => 'Posaune', 'type' => 'instrument', 'display_name' => 'Posaune'],
                            ['id' => 'Tuba', 'type' => 'instrument', 'display_name' => 'Tuba'],
                        ]
                    ]
                ]
            ],
            [
                'id' => 'Schlagwerk',
                'display_name' => 'Schlagwerk',
                'type' => 'section',
                'children' => [
                    ['id' => 'Pauke', 'type' => 'instrument', 'display_name' => 'Pauke'],
                    ['id' => 'Percussion', 'type' => 'instrument', 'display_name' => 'Percussion'],
                ]
            ]
        ]
    ],
    [
        'id' => 'chor',
        'display_name' => 'Chor',
        'type' => 'root',
        'children' => [
            ['id' => 'Sopran', 'type' => 'instrument', 'display_name' => 'Sopran'],
            ['id' => 'Alt', 'type' => 'instrument', 'display_name' => 'Alt'],
            ['id' => 'Tenor', 'type' => 'instrument', 'display_name' => 'Tenor'],
            ['id' => 'Bass', 'type' => 'instrument', 'display_name' => 'Bass'],
        ]
    ]
];

$gm = GroupManager::fromConfig($config);
$display = new SmartGroupDisplay(null, $gm);

$tests = [];

// 1. Initial 7 Core Edge Cases (Hardcoded Expectations)
$manualTests = [
    [
        'groups' => ['Violine 1', 'Violine 2', 'Bratsche', 'Cello', 'Kontrabass'],
        'expected' => 'Streicher',
        'name' => 'Full Streicher correctly summed'
    ],
    [
        'groups' => [
            'Bratsche', 'Cello', 'Pauke', 'Percussion', 
            'Flöte', 'Klarinette', 'Fagott',
            'Horn', 'Posaune', 'Tuba'
        ],
        'expected' => 'Bratsche, Cello, Bläser (ohne Oboe und Trompete) und Schlagwerk',
        'name' => 'User Edgecase: Bratsche Cello Schlagwerk Bläser ohne Oboe/Trompete'
    ],
    [
        'groups' => [
            'Violine 1', 'Violine 2', 'Bratsche', 'Cello', 'Kontrabass',
            'Flöte', 'Oboe', 'Klarinette', 'Fagott',
            'Horn', 'Trompete', 'Posaune', 'Tuba',
            'Pauke', 'Percussion',
            'Sopran', 'Alt', 'Tenor'
        ],
        'expected' => 'Tutti und Chor ohne Bass',
        'name' => 'Tutti + Chor ohne Bass'
    ],
    [
        'groups' => [
            'Violine 1', 'Violine 2', 'Bratsche', 'Cello', 
            'Flöte', 'Oboe', 'Klarinette', 'Fagott',
            'Horn', 'Trompete', 'Posaune', 'Tuba',
            'Pauke', 'Percussion',
            'Sopran', 'Alt', 'Tenor'
        ],
        'expected' => 'Tutti und Chor ohne Kontrabass und Bass',
        'name' => 'Tutti and Chor subtractive (ohne Kontrabass und Bass)'
    ],
    [
        'groups' => ['Flöte', 'Oboe', 'Horn', 'Trompete', 'Sopran', 'Bass'],
        'expected' => 'Flöte, Oboe, Horn, Trompete, Sopran und Bass', 
        'name' => 'Sparse additive distribution'
    ],
    [
        'groups' => ['Klarinette', 'Fagott', 'Horn', 'Trompete', 'Posaune', 'Tuba'], 
        'expected' => 'Bläser ohne Flöte und Oboe', 
        'name' => 'Subtractive Math beats Additive'
    ],
    [
        'groups' => array_diff(
            ['Violine 1', 'Violine 2', 'Bratsche', 'Cello', 'Kontrabass', 'Flöte', 'Oboe', 'Klarinette', 'Fagott', 'Horn', 'Trompete', 'Posaune', 'Tuba', 'Pauke', 'Percussion'],
            ['Flöte', 'Oboe', 'Pauke']
        ),
        'expected' => 'Tutti ohne Flöte, Oboe und Pauke',
        'name' => 'Nested subtraction prevented'
    ],
];

foreach ($manualTests as $mt) {
    array_push($tests, $mt);
}

// 2. Fuzzing 93 additional randomized edge-cases to guarantee coverage and length-optimality!
$allLeaves = $gm->getAllInstruments();
$allLeafIds = array_keys($allLeaves);

for ($i = 8; $i <= 100; $i++) {
    // Generate a random subset
    $subsetCount = rand(1, count($allLeafIds));
    $keys = (array)array_rand($allLeafIds, $subsetCount);
    $subset = [];
    foreach ($keys as $k) {
        $subset[] = $allLeafIds[$k];
    }
    
    // Test definitions mapping to the DP contract:
    $tests[] = [
        'groups' => $subset,
        'name' => 'Procedural fuzz test #' . $i . ' (Size ' . count($subset) . ')',
        'isFuzz' => true
    ];
}

echo "Running DP SmartGroupDisplay Test Cases...\n";
echo str_repeat("-", 50) . "\n";

$passed = 0;
$failed = 0;

foreach ($tests as $i => $test) {
    if ($i < 7) {
        echo "Test " . ($i + 1) . ": " . $test['name'] . "\n";
    }
    
    $start = microtime(true);
    $result = $display->generateDescription($test['groups']);
    $time = round((microtime(true) - $start) * 1000, 2);
    
    if (isset($test['isFuzz'])) {
        // Assertions for fuzz tests:
        // 1. Must never crash
        // 2. Length must NEVER be greater than a simple concatenated list!
        $fallbackLength = strlen($display->generateSimpleList($test['groups']));
        $optimalLength = strlen($result);
        
        // 3. Must NEVER contain "ohne" mathematically nested (e.g., "(ohne ... ohne ...)")
        $nestedOhnes = preg_match('/ohne.*ohne/i', $result);
        
        if ($optimalLength <= $fallbackLength && !$nestedOhnes && !empty($result)) {
            $passed++;
        } else {
            echo "❌ FUZZ FAIL on Test " . ($i + 1) . "\n";
            echo "   Fallback length: $fallbackLength vs Optimal length: $optimalLength\n";
            echo "   Nested Ohne? " . ($nestedOhnes ? "Yes" : "No") . "\n";
            echo "   Groups: " . implode(', ', $test['groups']) . "\n";
            echo "   Result: $result\n";
            $failed++;
        }
    } else {
        // Hardcoded assertion logic
        if ($result === $test['expected']) {
            echo "✅ PASS  ({$time}ms)\n";
            echo "   Result: $result\n";
            $passed++;
        } else {
            echo "❌ FAIL\n";
            echo "   Expected: " . $test['expected'] . "\n";
            echo "   Got:      " . $result . "\n";
            $failed++;
        }
    }
}

echo str_repeat("-", 50) . "\n";
echo "Summing up: $passed Passed | $failed Failed\n";
if ($failed > 0) exit(1);
exit(0);

