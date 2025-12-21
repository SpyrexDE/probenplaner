<?php
/**
 * Orchestra Groups Configuration
 * 
 * Defines the hierarchical structure of orchestra sections and instruments.
 * This configuration supports any tree structure and can be customized per orchestra.
 * 
 * Structure:
 * - Each group can have 'name', 'display_name', 'children', and other properties
 * - The 'type' property indicates if this is a leaf node (instrument) or branch (section)
 * - 'id' is used for database storage and HTML elements
 * - 'display_name' is used for user-facing labels
 * - 'children' contains nested groups
 * - 'special_rules' can define custom behavior (like 'tutti' affecting everyone)
 */

return [
    'tutti' => [
        'id' => 'tutti',
        'display_name' => 'Tutti',
        'type' => 'special',
        'special_rules' => ['affects_all' => true],
        'children' => [
            'strings' => [
                'id' => 'Streicher',
                'display_name' => 'Streicher',
                'type' => 'section',
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
                'children' => [
                    'woodwinds' => [
                        'id' => 'Holzbläser',
                        'display_name' => 'Holzbläser',
                        'type' => 'section',
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
                'type' => 'section'
            ],
            'harp' => [
                'id' => 'Harfe',
                'display_name' => 'Harfe',
                'plural' => 'Harfen',
                'type' => 'section'
            ],
            'other' => [
                'id' => 'Andere',
                'display_name' => 'Andere',
                'type' => 'section'
            ]
        ]
    ],
    // Special groups that can be used independently
    'special_groups' => [
        'concert' => [
            'id' => 'Konzert',
            'display_name' => 'Konzert', 
            'type' => 'special',
            'special_rules' => ['affects_all' => true]
        ],
        'concert_tour' => [
            'id' => 'Konzertreise',
            'display_name' => 'Konzertreise',
            'type' => 'special', 
            'special_rules' => ['affects_all' => true]
        ],
        'dress_rehearsal' => [
            'id' => 'Generalprobe',
            'display_name' => 'Generalprobe',
            'type' => 'special',
            'special_rules' => ['affects_all' => true]
        ]
    ]
];