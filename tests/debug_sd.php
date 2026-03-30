<?php
namespace App\Models {
    class Orchestra {
        public function findById($id) { return null; }
    }
}

namespace {
    define('APP_ROOT', __DIR__ . '/../src');
    require_once APP_ROOT . '/Core/SmartGroupDisplay.php';
    require_once APP_ROOT . '/Core/GroupManager.php';

    use App\Core\SmartGroupDisplay;

    session_start();

    $display = new SmartGroupDisplay();

    /**
     * Orchestra hierarchy:
     * Tutti
     *   ├── Streicher: Violine 1, Violine 2, Bratsche, Cello, Kontrabass
     *   ├── Bläser
     *   │     ├── Holzbläser: Flöte, Oboe, Klarinette, Fagott
     *   │     └── Blechbläser: Horn, Trompete, Posaune, Tuba
     *   ├── Schlagwerk
     *   └── Harfe
     */

    $cases = [

        // === Vollständige Gruppen ===
        'Tutti'       => ['tutti'],
        'Streicher'   => ['Streicher'],
        'Bläser'      => ['Bläser'],
        'Holzbläser'  => ['Holzbläser'],
        'Blechbläser' => ['Blechbläser'],
        'Schlagwerk'  => ['Schlagwerk'],
        'Harfe'       => ['Harfe'],

        // === Tutti ohne eine Hauptgruppe ===
        'Tutti ohne Streicher'   => ['Bläser', 'Schlagwerk', 'Harfe'],
        'Tutti ohne Bläser'      => ['Streicher', 'Schlagwerk', 'Harfe'],
        'Tutti ohne Schlagwerk'  => ['Streicher', 'Bläser', 'Harfe'],
        'Tutti ohne Harfe'       => ['Streicher', 'Bläser', 'Schlagwerk'],

        // === Einfache Zwei-Gruppen-Kombis (kein Abstraktionsgewinn durch Tutti) ===
        'Streicher und Bläser'       => ['Streicher', 'Bläser'],
        'Streicher und Schlagwerk'   => ['Streicher', 'Schlagwerk'],
        'Streicher und Harfe'        => ['Streicher', 'Harfe'],
        'Bläser und Schlagwerk'      => ['Bläser', 'Schlagwerk'],
        'Bläser und Harfe'           => ['Bläser', 'Harfe'],
        'Schlagwerk und Harfe'       => ['Schlagwerk', 'Harfe'],
        'Streicher und Holzbläser'   => ['Streicher', 'Holzbläser'],
        'Streicher und Blechbläser'  => ['Streicher', 'Blechbläser'],
        'Schlagwerk und Blechbläser' => ['Schlagwerk', 'Blechbläser'],
        'Schlagwerk und Bläser'      => ['Schlagwerk', 'Bläser'],
        'Holzbläser und Schlagwerk'  => ['Holzbläser', 'Schlagwerk'],

        // === Streicher ohne X ===
        'Streicher ohne Violine 1'                => ['Violine_2', 'Bratsche', 'Cello', 'Kontrabass'],
        'Streicher ohne Violine 2'                => ['Violine_1', 'Bratsche', 'Cello', 'Kontrabass'],
        'Streicher ohne Bratsche'                 => ['Violine_1', 'Violine_2', 'Cello', 'Kontrabass'],
        'Streicher ohne Cello'                    => ['Violine_1', 'Violine_2', 'Bratsche', 'Kontrabass'],
        'Streicher ohne Kontrabass'               => ['Violine_1', 'Violine_2', 'Bratsche', 'Cello'],
        'Streicher ohne Violine 1 und Violine 2'  => ['Bratsche', 'Cello', 'Kontrabass'],
        'Streicher ohne Violine 1 und Kontrabass' => ['Violine_2', 'Bratsche', 'Cello'],
        'Streicher ohne Bratsche und Kontrabass'  => ['Violine_1', 'Violine_2', 'Cello'],
        'Streicher ohne Cello und Kontrabass'     => ['Violine_1', 'Violine_2', 'Bratsche'],

        // === Holzbläser ohne X ===
        'Holzbläser ohne Flöte'             => ['Oboe', 'Klarinette', 'Fagott'],
        'Holzbläser ohne Oboe'              => ['Flöte', 'Klarinette', 'Fagott'],
        'Holzbläser ohne Klarinette'        => ['Flöte', 'Oboe', 'Fagott'],
        'Holzbläser ohne Fagott'            => ['Flöte', 'Oboe', 'Klarinette'],
        'Holzbläser ohne Flöte und Oboe'    => ['Klarinette', 'Fagott'],
        'Holzbläser ohne Flöte und Fagott'  => ['Oboe', 'Klarinette'],
        'Holzbläser ohne Oboe und Fagott'   => ['Flöte', 'Klarinette'],
        'Holzbläser ohne Oboe und Klarinette'    => ['Flöte', 'Fagott'],
        'Holzbläser ohne Klarinette und Fagott'  => ['Flöte', 'Oboe'],
        'Holzbläser ohne Flöte und Klarinette'   => ['Oboe', 'Fagott'],

        // === Blechbläser ohne X ===
        'Blechbläser ohne Horn'              => ['Trompete', 'Posaune', 'Tuba'],
        'Blechbläser ohne Trompete'          => ['Horn', 'Posaune', 'Tuba'],
        'Blechbläser ohne Posaune'           => ['Horn', 'Trompete', 'Tuba'],
        'Blechbläser ohne Tuba'              => ['Horn', 'Trompete', 'Posaune'],
        'Blechbläser ohne Horn und Trompete' => ['Posaune', 'Tuba'],
        'Blechbläser ohne Horn und Posaune'  => ['Trompete', 'Tuba'],
        'Blechbläser ohne Horn und Tuba'     => ['Trompete', 'Posaune'],
        'Blechbläser ohne Trompete und Posaune' => ['Horn', 'Tuba'],
        'Blechbläser ohne Trompete und Tuba' => ['Horn', 'Posaune'],
        'Blechbläser ohne Posaune und Tuba'  => ['Horn', 'Trompete'],

        // === Bläser ohne X (Kombination beider Untergruppen – die Original-Bugs) ===
        'Bläser ohne Flöte'          => ['Oboe', 'Klarinette', 'Fagott', 'Blechbläser'],
        'Bläser ohne Oboe'           => ['Flöte', 'Klarinette', 'Fagott', 'Blechbläser'],
        'Bläser ohne Klarinette'     => ['Flöte', 'Oboe', 'Fagott', 'Blechbläser'],
        'Bläser ohne Fagott'         => ['Flöte', 'Oboe', 'Klarinette', 'Blechbläser'],
        'Bläser ohne Horn'           => ['Holzbläser', 'Trompete', 'Posaune', 'Tuba'],
        'Bläser ohne Trompete'       => ['Holzbläser', 'Horn', 'Posaune', 'Tuba'],
        'Bläser ohne Posaune'        => ['Holzbläser', 'Horn', 'Trompete', 'Tuba'],
        'Bläser ohne Tuba'           => ['Holzbläser', 'Horn', 'Trompete', 'Posaune'],
        'Bläser ohne Posaune und Tuba'       => ['Holzbläser', 'Horn', 'Trompete'],
        'Bläser ohne Horn und Tuba'          => ['Holzbläser', 'Trompete', 'Posaune'],
        'Bläser ohne Horn und Trompete'      => ['Holzbläser', 'Posaune', 'Tuba'],
        'Bläser ohne Trompete und Tuba'      => ['Holzbläser', 'Horn', 'Posaune'],
        'Bläser ohne Horn und Posaune'       => ['Holzbläser', 'Trompete', 'Tuba'],
        'Bläser ohne Trompete und Posaune'   => ['Holzbläser', 'Horn', 'Tuba'],
        'Bläser ohne Flöte und Tuba'         => ['Oboe', 'Klarinette', 'Fagott', 'Horn', 'Trompete', 'Posaune'],
        'Bläser ohne Flöte und Oboe'         => ['Klarinette', 'Fagott', 'Blechbläser'],

        // === Tutti ohne tief verschachtelte Einzelinstrumente ===
        'Tutti ohne Oboe'             => ['Streicher', 'Flöte', 'Klarinette', 'Fagott', 'Blechbläser', 'Schlagwerk', 'Harfe'],
        'Tutti ohne Flöte'            => ['Streicher', 'Oboe', 'Klarinette', 'Fagott', 'Blechbläser', 'Schlagwerk', 'Harfe'],
        'Tutti ohne Tuba'             => ['Streicher', 'Holzbläser', 'Horn', 'Trompete', 'Posaune', 'Schlagwerk', 'Harfe'],
        'Tutti ohne Posaune'          => ['Streicher', 'Holzbläser', 'Horn', 'Trompete', 'Tuba', 'Schlagwerk', 'Harfe'],
        'Tutti ohne Violine 1'        => ['Violine_2', 'Bratsche', 'Cello', 'Kontrabass', 'Bläser', 'Schlagwerk', 'Harfe'],
        'Tutti ohne Flöte und Oboe'   => ['Klarinette', 'Fagott', 'Blechbläser', 'Streicher', 'Schlagwerk', 'Harfe'],
        'Tutti ohne Flöte und Tuba'   => ['Streicher', 'Oboe', 'Klarinette', 'Fagott', 'Horn', 'Trompete', 'Posaune', 'Schlagwerk', 'Harfe'],
        'Tutti ohne Posaune und Tuba' => ['Streicher', 'Holzbläser', 'Horn', 'Trompete', 'Schlagwerk', 'Harfe'],
    ];

    $passed = 0;
    $failed = 0;

    echo str_pad('', 70, '=') . PHP_EOL;
    printf("%-52s %s\n", 'Expected', 'Got');
    echo str_pad('', 70, '=') . PHP_EOL;

    foreach ($cases as $expected => $groups) {
        $result  = $display->generateBaseDescription($groups);
        $correct = ($result === $expected);

        if ($correct) {
            $passed++;
            printf("✓ %-50s\n", $expected);
        } else {
            $failed++;
            printf("✗ %-50s  GOT: \"%s\"\n", $expected, $result);
            printf("  Input: [%s]\n", implode(', ', $groups));
        }
    }

    echo str_pad('', 70, '=') . PHP_EOL;
    echo sprintf(
        "Result: %d/%d passed%s\n",
        $passed,
        $passed + $failed,
        $failed > 0 ? " ({$failed} FAILED)" : ' – alle Tests bestanden 🎉'
    );
}
