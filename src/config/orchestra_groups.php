<?php

/**
 * Orchestra Groups Configuration
 * 
 * Defines the hierarchical structure of orchestra sections and instruments.
 * This configuration supports any tree structure and can be customized per orchestra.
 * 
 * Structure:
 * - Each group can have 'display_name', 'children', and other properties
 * - 'type' is inferred automatically: 'section' (has children) or 'instrument' (leaf)
 * - 'id' is used for database storage and HTML elements
 * - 'display_name' is used for user-facing labels
 * - 'children' contains nested groups
 */

return [
    'strings' => [
        'id' => 'Streicher',
        'display_name' => 'Streicher',
        'type' => 'section',
        'emoji' => '🎻',
        'bg' => 'background: var(--color-blue-100);',
        'tc' => 'color: var(--color-primary);',
        'children' => [
            'violin1' => [
                'id' => 'Violine_1',
                'display_name' => 'Violine 1',
                'type' => 'instrument'
            ],
            'violin2' => [
                'id' => 'Violine_2',
                'display_name' => 'Violine 2',
                'type' => 'instrument'
            ],
            'viola' => [
                'id' => 'Bratsche',
                'display_name' => 'Bratsche',
                'aliases' => ['Viola'],
                'type' => 'instrument'
            ],
            'cello' => [
                'id' => 'Cello',
                'display_name' => 'Cello',
                'aliases' => ['Violoncello'],
                'type' => 'instrument'
            ],
            'doublebass' => [
                'id' => 'Kontrabass',
                'display_name' => 'Kontrabass',
                'type' => 'instrument'
            ]
        ]
    ],
    'winds' => [
        'id' => 'Bläser',
        'display_name' => 'Bläser',
        'type' => 'section',
        'emoji' => '🎺',
        'bg' => 'background: rgba(124, 58, 237, 0.1);',
        'tc' => 'color: #7c3aed;',
        'children' => [
            'woodwinds' => [
                'id' => 'Holzbläser',
                'display_name' => 'Holzbläser',
                'type' => 'section',
                'emoji' => '🪈',
                'children' => [
                    'flute' => [
                        'id' => 'Flöte',
                        'display_name' => 'Flöte',
                        'plural' => 'Flöten',
                        'type' => 'instrument'
                    ],
                    'oboe' => [
                        'id' => 'Oboe',
                        'display_name' => 'Oboe',
                        'plural' => 'Oboen',
                        'type' => 'instrument'
                    ],
                    'clarinet' => [
                        'id' => 'Klarinette',
                        'display_name' => 'Klarinette',
                        'plural' => 'Klarinetten',
                        'type' => 'instrument'
                    ],
                    'bassoon' => [
                        'id' => 'Fagott',
                        'display_name' => 'Fagott',
                        'plural' => 'Fagotte',
                        'type' => 'instrument'
                    ]
                ]
            ],
            'brass' => [
                'id' => 'Blechbläser',
                'display_name' => 'Blechbläser',
                'type' => 'section',
                'emoji' => '🎺',
                'children' => [
                    'horn' => [
                        'id' => 'Horn',
                        'display_name' => 'Horn',
                        'plural' => 'Hörner',
                        'type' => 'instrument'
                    ],
                    'trumpet' => [
                        'id' => 'Trompete',
                        'display_name' => 'Trompete',
                        'plural' => 'Trompeten',
                        'type' => 'instrument'
                    ],
                    'trombone' => [
                        'id' => 'Posaune',
                        'display_name' => 'Posaune',
                        'plural' => 'Posaunen',
                        'type' => 'instrument'
                    ],
                    'tuba' => [
                        'id' => 'Tuba',
                        'display_name' => 'Tuba',
                        'plural' => 'Tuben',
                        'type' => 'instrument'
                    ]
                ]
            ]
        ]
    ],
    'percussion' => [
        'id' => 'Schlagwerk',
        'display_name' => 'Schlagwerk',
        'type' => 'section',
        'emoji' => '🥁',
        'bg' => 'background: var(--color-warning-100);',
        'tc' => 'color: var(--color-warning-dark);',
    ],
    'harp' => [
        'id' => 'Harfe',
        'display_name' => 'Harfe',
        'plural' => 'Harfen',
        'type' => 'section',
        'emoji' => '🎵',
        'bg' => 'background: var(--color-success-100);',
        'tc' => 'color: var(--color-success);',
    ]
];
