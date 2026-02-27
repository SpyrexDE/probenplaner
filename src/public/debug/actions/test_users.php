<?php

/**
 * Test Users Generator Action
 * Handles both individual user generation and full test DB setup.
 */

if (!isset($_POST['action'])) {
    return ['message' => 'No action specified', 'messageType' => 'error'];
}

// ── Individual user generation ───────────────────────────────────────
if ($_POST['action'] === 'generate_users') {
    if (empty($_POST['orchestra_id']) || !is_numeric($_POST['orchestra_id'])) {
        return ['message' => 'Please select a valid orchestra', 'messageType' => 'error'];
    }
    if (empty($_POST['username_prefix'])) {
        return ['message' => 'Username prefix is required', 'messageType' => 'error'];
    }
    if (empty($_POST['max_users']) || !is_numeric($_POST['max_users']) || $_POST['max_users'] < 1) {
        return ['message' => 'Please enter a valid maximum number of users', 'messageType' => 'error'];
    }

    $orchestraId = (int)$_POST['orchestra_id'];
    $usernamePrefix = $_POST['username_prefix'];
    $maxUsers = min((int)$_POST['max_users'], 20);

    $stmt = $conn->prepare("SELECT id, name FROM orchestras WHERE id = ?");
    $stmt->bind_param('i', $orchestraId);
    $stmt->execute();
    $orchestraResult = $stmt->get_result();
    if ($orchestraResult->num_rows === 0) {
        return ['message' => 'Selected orchestra does not exist', 'messageType' => 'error'];
    }
    $orchestra = $orchestraResult->fetch_assoc();
    $stmt->close();

    $sections = [
        'Violine_1',
        'Violine_2',
        'Bratsche',
        'Cello',
        'Kontrabass',
        'Flöte',
        'Oboe',
        'Klarinette',
        'Fagott',
        'Trompete',
        'Posaune',
        'Tuba',
        'Horn',
        'Schlagwerk',
        'Andere'
    ];

    $generatedUsers = [];
    $totalGenerated = 0;
    $usernameCounter = 1;

    $conn->begin_transaction();

    try {
        foreach ($sections as $section) {
            $numUsers = rand(0, min(10, $maxUsers));

            for ($i = 0; $i < $numUsers; $i++) {
                $username = $usernamePrefix . $usernameCounter;
                $usernameCounter++;

                $checkStmt = $conn->prepare("SELECT u.id FROM users u INNER JOIN user_orchestras uo ON u.id = uo.user_id WHERE u.username = ? AND uo.orchestra_id = ? AND uo.is_active = TRUE");
                $checkStmt->bind_param('si', $username, $orchestraId);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                if ($checkResult->num_rows > 0) {
                    $checkStmt->close();
                    continue;
                }

                $hashedPassword = password_hash($username, PASSWORD_DEFAULT);

                $insertStmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $insertStmt->bind_param('ss', $username, $hashedPassword);

                if ($insertStmt->execute()) {
                    $userId = $conn->insert_id;
                    $insertStmt->close();

                    $joinedAt = date('Y-m-d H:i:s');
                    $userOrchestraStmt = $conn->prepare("INSERT INTO user_orchestras (user_id, orchestra_id, type, is_active, joined_at) VALUES (?, ?, ?, 1, ?)");
                    $userOrchestraStmt->bind_param('iiss', $userId, $orchestraId, $section, $joinedAt);

                    if ($userOrchestraStmt->execute()) {
                        $uoId = $conn->insert_id;
                        $userOrchestraStmt->close();

                        $permStmt = $conn->prepare("INSERT INTO user_ensemble_permissions (user_orchestra_id, permission_id)
                            SELECT ?, id FROM permissions WHERE name = 'can_attend_rehearsals'");
                        $permStmt->bind_param('i', $uoId);
                        $permStmt->execute();
                        $permStmt->close();

                        $generatedUsers[] = [
                            'username' => $username,
                            'password' => $username,
                            'type' => $section
                        ];
                        $totalGenerated++;
                    } else {
                        $userOrchestraStmt->close();
                    }
                } else {
                    $insertStmt->close();
                }
            }
        }

        $conn->commit();

        return [
            'message' => "Successfully generated {$totalGenerated} test users for orchestra '{$orchestra['name']}'",
            'messageType' => 'success',
            'data' => [
                'users_generated' => $totalGenerated,
                'users' => $generatedUsers,
                'orchestra' => $orchestra
            ]
        ];
    } catch (\Exception $e) {
        $conn->rollback();
        return [
            'message' => 'Error generating users: ' . $e->getMessage(),
            'messageType' => 'error'
        ];
    }
}

// ── Complete test DB setup ───────────────────────────────────────────
if ($_POST['action'] === 'generate_full_setup') {
    $conn->begin_transaction();

    try {
        $now = date('Y-m-d H:i:s');
        $counts = ['org' => 0, 'orchestras' => 0, 'users' => 0, 'rehearsals' => 0, 'promises' => 0, 'infos' => 0, 'schedules' => 0];

        // ── 1. Organization ──────────────────────────────────────────
        $conn->query("INSERT INTO organizations (name, slug, created_at, updated_at) VALUES ('Testverein Harmonia e.V.', 'harmonia', '{$now}', '{$now}')");
        $orgId = $conn->insert_id;
        $counts['org'] = 1;

        // Org-admin account
        $adminPw = password_hash('testadmin', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (username, password, is_org_admin, organization_id) VALUES ('harmonia-admin', '{$adminPw}', 1, {$orgId})");

        // ── 2. Orchestras ────────────────────────────────────────────
        $conn->query("INSERT INTO orchestras (name, slug, organization_id) VALUES ('Sinfonieorchester Harmonia', 'sinfonie-harmonia', {$orgId})");
        $sinfonieId = $conn->insert_id;

        $conn->query("INSERT INTO orchestras (name, slug, organization_id) VALUES ('Kammerensemble Harmonia', 'kammer-harmonia', {$orgId})");
        $kammerId = $conn->insert_id;
        $counts['orchestras'] = 2;

        // ── 3. Users ─────────────────────────────────────────────────
        // Roster: [username, display_name, section, orchestra_id, preset, is_active, is_small_group]
        $roster = [
            // Sinfonie — Conductor
            ['dirigent',        'Thomas Müller',        'Andere',       $sinfonieId, 'conductor',      1, 0],
            // Sinfonie — Section Leader
            ['stimmfuehrer',    'Sabine Becker',        'Violine_1',    $sinfonieId, 'section_leader', 1, 0],
            // Sinfonie — Members across sections
            ['anna.mueller',    'Anna Müller',          'Violine_1',    $sinfonieId, 'member', 1, 0],
            ['jan.schmidt',     'Jan Schmidt',          'Violine_1',    $sinfonieId, 'member', 1, 0],
            ['lisa.weber',      'Lisa Weber',           'Violine_2',    $sinfonieId, 'member', 1, 0],
            ['peter.fischer',   'Peter Fischer',        'Violine_2',    $sinfonieId, 'member', 1, 0],
            ['marie.koch',      'Marie Koch',           'Violine_2',    $sinfonieId, 'member', 1, 0],
            ['sarah.braun',     'Sarah Braun',          'Bratsche',     $sinfonieId, 'member', 1, 0],
            ['felix.wolf',      'Felix Wolf',           'Bratsche',     $sinfonieId, 'member', 1, 0],
            ['julia.richter',   'Julia Richter',        'Cello',        $sinfonieId, 'member', 1, 0],
            ['noah.klein',      'Noah Klein',           'Cello',        $sinfonieId, 'member', 1, 0],
            ['emma.schroeder',  'Emma Schröder',        'Kontrabass',   $sinfonieId, 'member', 1, 0],
            ['lukas.neumann',   'Lukas Neumann',        'Flöte',        $sinfonieId, 'member', 1, 0],
            ['hannah.schwarz',  'Hannah Schwarz',       'Oboe',         $sinfonieId, 'member', 1, 0],
            ['max.zimmermann',  'Max Zimmermann',       'Klarinette',   $sinfonieId, 'member', 1, 0],
            ['lena.hoffmann',   'Lena Hoffmann',        'Fagott',       $sinfonieId, 'member', 1, 0],
            ['david.wagner',    'David Wagner',         'Trompete',     $sinfonieId, 'member', 1, 0],
            ['sophie.bauer',    'Sophie Bauer',         'Trompete',     $sinfonieId, 'member', 1, 0],
            ['tim.schulz',      'Tim Schulz',           'Posaune',      $sinfonieId, 'member', 1, 0],
            ['laura.hartmann',  'Laura Hartmann',       'Tuba',         $sinfonieId, 'member', 1, 0],
            ['paul.krueger',    'Paul Krüger',          'Horn',         $sinfonieId, 'member', 1, 0],
            ['mia.lang',        'Mia Lang',             'Horn',         $sinfonieId, 'member', 1, 0],
            ['leon.frank',      'Leon Frank',           'Schlagwerk',   $sinfonieId, 'member', 1, 0],
            ['clara.roth',      'Clara Roth',           'Andere',       $sinfonieId, 'member', 1, 0],
            // Sinfonie — Small-group member
            ['nina.berg',       'Nina Berg',            'Violine_1',    $sinfonieId, 'member', 1, 1],
            // Sinfonie — Inactive members (left the orchestra)
            ['oldinactive1',    'Karl Ehemalig',        'Cello',        $sinfonieId, 'member', 0, 0],
            ['oldinactive2',    'Ute Vergangen',        'Flöte',        $sinfonieId, 'member', 0, 0],
            // Kammer — Conductor
            ['kammer.dirigent', 'Eva Schneider',        'Andere',       $kammerId,   'conductor',      1, 0],
            // Kammer — Members
            ['kammer.geige',    'Moritz Stein',         'Violine_1',    $kammerId,   'member', 1, 0],
            ['kammer.bratsche', 'Klara Weiß',           'Bratsche',     $kammerId,   'member', 1, 0],
            ['kammer.cello',    'Anton Frei',           'Cello',        $kammerId,   'member', 1, 0],
            ['kammer.bass',     'Greta Haus',           'Kontrabass',   $kammerId,   'member', 1, 0],
        ];

        // Permission preset → permission names (mirrors UserOrchestra::PRESETS)
        $presets = [
            'member'         => ['can_attend_rehearsals'],
            'section_leader' => ['can_attend_rehearsals', 'can_view_own_section_stats', 'can_view_all_section_stats', 'can_manage_rehearsals', 'can_view_members'],
            'conductor'      => ['can_view_own_section_stats', 'can_view_all_section_stats', 'can_view_members', 'can_manage_rehearsals', 'can_manage_members', 'can_manage_permissions', 'can_manage_ensemble'],
        ];

        // Cache permission IDs
        $permIdMap = [];
        $permResult = $conn->query("SELECT id, name FROM permissions WHERE scope = 'ensemble'");
        while ($row = $permResult->fetch_assoc()) {
            $permIdMap[$row['name']] = (int)$row['id'];
        }

        $userIdMap = []; // username → user_id (for creating promises later)
        $sinfonieUserIds = []; // active sinfonie member user IDs
        $kammerUserIds = [];

        foreach ($roster as $entry) {
            [$username, $displayName, $section, $orchId, $preset, $isActive, $isSmallGroup] = $entry;

            $pw = password_hash($username, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, display_name) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $username, $pw, $displayName);
            $stmt->execute();
            $userId = $conn->insert_id;
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO user_orchestras (user_id, orchestra_id, type, is_active, is_small_group, joined_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iisiis', $userId, $orchId, $section, $isActive, $isSmallGroup, $now);
            $stmt->execute();
            $uoId = $conn->insert_id;
            $stmt->close();

            // Sync permissions
            foreach ($presets[$preset] as $permName) {
                if (!isset($permIdMap[$permName])) continue;
                $pid = $permIdMap[$permName];
                $stmt = $conn->prepare("INSERT INTO user_ensemble_permissions (user_orchestra_id, permission_id) VALUES (?, ?)");
                $stmt->bind_param('ii', $uoId, $pid);
                $stmt->execute();
                $stmt->close();
            }

            $userIdMap[$username] = $userId;
            if ($isActive && $orchId === $sinfonieId) {
                $sinfonieUserIds[] = $userId;
            }
            if ($isActive && $orchId === $kammerId) {
                $kammerUserIds[] = $userId;
            }
            $counts['users']++;
        }

        // ── 4. Rehearsals ────────────────────────────────────────────
        $today = new DateTime();
        $rehearsalDefs = [
            // Sinfonie — past rehearsals
            ['Probe',           $sinfonieId, -42, '19:00:00', '21:00:00', 'Aula Musikschule',       0, ['tutti']],
            ['Probe',           $sinfonieId, -35, '19:00:00', '21:00:00', 'Aula Musikschule',       0, ['tutti']],
            ['Registerprobe',   $sinfonieId, -28, '18:30:00', '20:00:00', 'Raum 201',               0, ['Streicher']],
            ['Probe',           $sinfonieId, -21, '19:00:00', '21:00:00', 'Gemeindesaal St. Peter',  0, ['tutti']],
            ['Generalprobe',    $sinfonieId, -14, '18:00:00', '21:30:00', 'Stadthalle',             0, ['tutti']],
            ['Konzert',         $sinfonieId,  -7, '19:30:00', '21:30:00', 'Stadthalle',             0, ['tutti']],
            // Sinfonie — future rehearsals
            ['Probe',           $sinfonieId,   7, '19:00:00', '21:00:00', 'Aula Musikschule',       0, ['tutti']],
            ['Probe',           $sinfonieId,  14, '19:00:00', '21:00:00', 'Aula Musikschule',       1, ['tutti']], // small-group
            ['Registerprobe',   $sinfonieId,  21, '18:30:00', '20:00:00', 'Raum 201',               0, ['Holzbläser']],
            ['Konzertreise',    $sinfonieId,  35, '09:00:00', '22:00:00', 'Partnerstadt Lucca 🇮🇹', 0, ['tutti']],
            // Kammer
            ['Probe',           $kammerId,   -10, '20:00:00', '21:30:00', 'Proberaum Kammer',       0, ['tutti']],
            ['Konzert',         $kammerId,    20, '19:00:00', '21:00:00', 'Schlosskapelle',         0, ['tutti']],
        ];

        $rehearsalIdMap = []; // index → rehearsal_id

        foreach ($rehearsalDefs as $idx => $def) {
            [$type, $orchId, $dayOffset, $startTime, $endTime, $location, $isSmallGroup, $groups] = $def;

            $date = (clone $today)->modify("{$dayOffset} days");
            $startDatetime = $date->format('Y-m-d') . ' ' . $startTime;
            $endDatetime   = $date->format('Y-m-d') . ' ' . $endTime;

            $stmt = $conn->prepare("INSERT INTO rehearsals (type, start, `end`, location, orchestra_id, is_small_group, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssiiss', $type, $startDatetime, $endDatetime, $location, $orchId, $isSmallGroup, $now, $now);
            $stmt->execute();
            $rehearsalId = $conn->insert_id;
            $stmt->close();

            // Rehearsal groups
            foreach ($groups as $group) {
                $stmt = $conn->prepare("INSERT INTO rehearsal_groups (rehearsal_id, name) VALUES (?, ?)");
                $stmt->bind_param('is', $rehearsalId, $group);
                $stmt->execute();
                $stmt->close();
            }

            $rehearsalIdMap[$idx] = $rehearsalId;
            $counts['rehearsals']++;
        }

        // ── 5. Schedule items (on select rehearsals) ─────────────────
        $scheduleData = [
            4 => [ // Generalprobe
                ['18:00:00', 'Einlass & Aufbau'],
                ['18:30:00', 'Durchlauf 1. Hälfte'],
                ['19:30:00', 'Pause'],
                ['19:45:00', 'Durchlauf 2. Hälfte'],
                ['21:00:00', 'Besprechung'],
            ],
            5 => [ // Konzert
                ['19:00:00', 'Einspielen'],
                ['19:30:00', 'Konzertbeginn'],
                ['20:15:00', 'Pause'],
                ['20:30:00', '2. Hälfte'],
            ],
            9 => [ // Konzertreise
                ['09:00:00', 'Abfahrt Bus'],
                ['13:00:00', 'Ankunft & Mittagessen'],
                ['15:00:00', 'Probe im Saal'],
                ['18:00:00', 'Abendessen'],
                ['20:00:00', 'Konzert'],
            ],
        ];

        foreach ($scheduleData as $rIdx => $items) {
            $rId = $rehearsalIdMap[$rIdx];
            foreach ($items as $order => $item) {
                $time = $item[0];
                $label = $item[1];
                $stmt = $conn->prepare("INSERT INTO rehearsal_schedule_items (rehearsal_id, time, label, sort_order) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('issi', $rId, $time, $label, $order);
                $stmt->execute();
                $stmt->close();
                $counts['schedules']++;
            }
        }

        // ── 6. Info items (emoji notes on select rehearsals) ─────────
        $infoData = [
            0 => [ // First rehearsal
                ['📝', 'Bitte Noten für Beethoven 5 mitbringen'],
                ['⏰', 'Pünktlich erscheinen — Probe beginnt 19:00 sharp'],
            ],
            4 => [ // Generalprobe
                ['👔', 'Konzertkleidung tragen!'],
                ['🎵', 'Soli bitte nochmal einzeln üben'],
                ['⚠️', 'Achtung: Einlass nur über Hintereingang'],
            ],
            5 => [ // Konzert
                ['🎉', 'Saisonabschlusskonzert — Danke an alle!'],
                ['📸', 'Gruppenfoto nach dem Konzert'],
            ],
            9 => [ // Konzertreise
                ['🧳', 'Reisepass nicht vergessen!'],
                ['🇮🇹', 'Buon viaggio! 🎶'],
                ['💊', 'Reiseapotheke mitnehmen'],
                ['🔥', 'Partystimmung garantiert 🎺🥁'],
            ],
        ];

        foreach ($infoData as $rIdx => $items) {
            $rId = $rehearsalIdMap[$rIdx];
            foreach ($items as $order => $item) {
                $emoji = $item[0];
                $text  = $item[1];
                $stmt = $conn->prepare("INSERT INTO rehearsal_infos (rehearsal_id, emoji, text, sort_order) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('issi', $rId, $emoji, $text, $order);
                $stmt->execute();
                $stmt->close();
                $counts['infos']++;
            }
        }

        // ── 7. Attendances (user_promises) ───────────────────────────
        // Past sinfonie rehearsals (indices 0-5): generate varied attendance
        $notePool = [
            'Bin 10 Min später',
            'Familienfeier',
            'Urlaub 🏖️',
            'Krank 🤒',
            'Muss früher gehen',
            'Komme direkt von der Arbeit',
            'Dienstreise',
            'Klausurphase 📚',
            'Hochzeit 💒',
            '',
            '',
            '',
            '',
            '',
            '',
        ];

        for ($rIdx = 0; $rIdx <= 5; $rIdx++) {
            $rId = $rehearsalIdMap[$rIdx];

            foreach ($sinfonieUserIds as $uIdx => $userId) {
                // ~15% no response (skip entirely)
                if (rand(1, 100) <= 15) {
                    continue;
                }

                // Distribute: ~65% yes, ~20% no, ~15% maybe (no response in DB)
                $roll = rand(1, 100);
                if ($roll <= 65) {
                    $status = 'yes';
                } elseif ($roll <= 85) {
                    $status = 'no';
                } else {
                    // "gray" — leave as no response by skipping
                    continue;
                }

                $note = ($status === 'no' && rand(1, 100) <= 60) ? $notePool[array_rand($notePool)] : '';

                $stmt = $conn->prepare("INSERT INTO user_promises (user_id, rehearsal_id, status, note) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('iiss', $userId, $rId, $status, $note);
                $stmt->execute();
                $stmt->close();
                $counts['promises']++;
            }
        }

        // Future sinfonie rehearsals (indices 6-9): sparser attendance
        for ($rIdx = 6; $rIdx <= 9; $rIdx++) {
            $rId = $rehearsalIdMap[$rIdx];

            foreach ($sinfonieUserIds as $userId) {
                // ~40% no response for future
                if (rand(1, 100) <= 40) continue;

                $roll = rand(1, 100);
                if ($roll <= 55) {
                    $status = 'yes';
                } elseif ($roll <= 80) {
                    $status = 'no';
                } else {
                    continue;
                }

                $note = ($status === 'no' && rand(1, 100) <= 40) ? $notePool[array_rand($notePool)] : '';

                $stmt = $conn->prepare("INSERT INTO user_promises (user_id, rehearsal_id, status, note) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('iiss', $userId, $rId, $status, $note);
                $stmt->execute();
                $stmt->close();
                $counts['promises']++;
            }
        }

        // Kammer rehearsals (indices 10-11)
        for ($rIdx = 10; $rIdx <= 11; $rIdx++) {
            $rId = $rehearsalIdMap[$rIdx];

            foreach ($kammerUserIds as $userId) {
                if (rand(1, 100) <= 20) continue;

                $status = (rand(1, 100) <= 75) ? 'yes' : 'no';

                $stmt = $conn->prepare("INSERT INTO user_promises (user_id, rehearsal_id, status, note) VALUES (?, ?, ?, '')");
                $stmt->bind_param('iis', $userId, $rId, $status);
                $stmt->execute();
                $stmt->close();
                $counts['promises']++;
            }
        }

        // ── Done ─────────────────────────────────────────────────────
        $conn->commit();

        $summary = [];
        $summary[] = "🏛️ 1 Organization (Testverein Harmonia e.V.)";
        $summary[] = "🎻 {$counts['orchestras']} Orchestras";
        $summary[] = "👥 {$counts['users']} Users (incl. 2 inactive, 1 small-group, conductors, section leaders)";
        $summary[] = "📅 {$counts['rehearsals']} Rehearsals (past + future, 5 different types)";
        $summary[] = "📋 {$counts['schedules']} Schedule items";
        $summary[] = "📝 {$counts['infos']} Info items (with emojis)";
        $summary[] = "✅ {$counts['promises']} Attendance records";

        return [
            'message' => 'Complete test setup created successfully!',
            'messageType' => 'success',
            'data' => [
                'full_setup' => true,
                'summary' => $summary,
                'counts' => $counts,
                'credentials' => [
                    ['username' => 'harmonia-admin',  'password' => 'testadmin',        'role' => 'Org-Admin'],
                    ['username' => 'dirigent',        'password' => 'dirigent',          'role' => 'Dirigent (Sinfonie)'],
                    ['username' => 'stimmfuehrer',    'password' => 'stimmfuehrer',      'role' => 'Stimmführer (Sinfonie)'],
                    ['username' => 'anna.mueller',    'password' => 'anna.mueller',      'role' => 'Mitglied (Violine 1)'],
                    ['username' => 'kammer.dirigent', 'password' => 'kammer.dirigent',   'role' => 'Dirigent (Kammer)'],
                    ['username' => 'nina.berg',       'password' => 'nina.berg',         'role' => 'Kleingruppe (Violine 1)'],
                ],
            ]
        ];
    } catch (\Exception $e) {
        $conn->rollback();
        return [
            'message' => 'Error creating test setup: ' . $e->getMessage(),
            'messageType' => 'error'
        ];
    }
}

return ['message' => 'Unknown action', 'messageType' => 'error'];
