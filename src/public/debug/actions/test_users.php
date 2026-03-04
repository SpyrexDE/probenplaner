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
        return ['message' => 'Email prefix is required', 'messageType' => 'error'];
    }
    if (empty($_POST['max_users']) || !is_numeric($_POST['max_users']) || $_POST['max_users'] < 1) {
        return ['message' => 'Please enter a valid maximum number of users', 'messageType' => 'error'];
    }

    $orchestraId = (int)$_POST['orchestra_id'];
    $emailPrefix = $_POST['username_prefix'];
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
    $emailCounter = 1;

    $conn->begin_transaction();

    try {
        foreach ($sections as $section) {
            $numUsers = rand(0, min(10, $maxUsers));

            for ($i = 0; $i < $numUsers; $i++) {
                $email = $emailPrefix . $emailCounter . '@test.local';
                $displayName = $emailPrefix . $emailCounter;
                $emailCounter++;

                $checkStmt = $conn->prepare("SELECT u.id FROM users u INNER JOIN user_orchestras uo ON u.id = uo.user_id WHERE u.email = ? AND uo.orchestra_id = ? AND uo.is_active = TRUE");
                $checkStmt->bind_param('si', $email, $orchestraId);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                if ($checkResult->num_rows > 0) {
                    $checkStmt->close();
                    continue;
                }

                $hashedPassword = password_hash($displayName, PASSWORD_DEFAULT);

                $insertStmt = $conn->prepare("INSERT INTO users (email, display_name, password) VALUES (?, ?, ?)");
                $insertStmt->bind_param('sss', $email, $displayName, $hashedPassword);

                if ($insertStmt->execute()) {
                    $userId = $conn->insert_id;
                    $insertStmt->close();

                    $joinedAt = date('Y-m-d H:i:s');

                    // Find or create default member role for this orchestra
                    $roleStmt = $conn->prepare("SELECT id FROM roles WHERE orchestra_id = ? AND is_default = 1 LIMIT 1");
                    $roleStmt->bind_param('i', $orchestraId);
                    $roleStmt->execute();
                    $roleResult = $roleStmt->get_result();
                    $defaultRoleId = null;
                    if ($roleRow = $roleResult->fetch_assoc()) {
                        $defaultRoleId = (int)$roleRow['id'];
                    }
                    $roleStmt->close();

                    $userOrchestraStmt = $conn->prepare("INSERT INTO user_orchestras (user_id, orchestra_id, type, is_active, role_id, joined_at) VALUES (?, ?, ?, 1, ?, ?)");
                    $userOrchestraStmt->bind_param('iisis', $userId, $orchestraId, $section, $defaultRoleId, $joinedAt);

                    if ($userOrchestraStmt->execute()) {
                        $userOrchestraStmt->close();
                        $generatedUsers[] = [
                            'email' => $email,
                            'password' => $displayName,
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

        // Clean up any leftover data from a previous run
        $existingOrg = $conn->query("SELECT id FROM organizations WHERE slug = 'harmonia'")->fetch_assoc();
        if ($existingOrg) {
            $eOrgId = (int)$existingOrg['id'];
            $conn->query("DELETE FROM roles WHERE orchestra_id IN (SELECT id FROM orchestras WHERE organization_id = {$eOrgId})");
            $conn->query("DELETE uo FROM user_orchestras uo JOIN orchestras o ON uo.orchestra_id = o.id WHERE o.organization_id = {$eOrgId}");
            $conn->query("DELETE FROM orchestras WHERE organization_id = {$eOrgId}");
            $conn->query("DELETE FROM users WHERE organization_id = {$eOrgId}");
            $conn->query("DELETE FROM organizations WHERE id = {$eOrgId}");
            // Remove test users created by this function (by email domain)
            $conn->query("DELETE FROM user_orchestras WHERE user_id IN (SELECT id FROM users WHERE email LIKE '%@test.local')");
            $conn->query("DELETE FROM users WHERE email LIKE '%@test.local'");
            $conn->query("DELETE FROM users WHERE email = 'harmonia-admin@probenplaner.local'");
        }

        // ── 1. Organization ──────────────────────────────────────────
        $conn->query("INSERT INTO organizations (name, slug, created_at, updated_at) VALUES ('Testverein Harmonia e.V.', 'harmonia', '{$now}', '{$now}')");
        $orgId = $conn->insert_id;
        $counts['org'] = 1;

        // Org-admin account
        $adminPw = password_hash('testadmin', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (email, display_name, password, is_org_admin, organization_id) VALUES ('harmonia-admin@probenplaner.local', 'Harmonia Admin', '{$adminPw}', 1, {$orgId})");

        // ── 2. Orchestras ────────────────────────────────────────────
        $conn->query("INSERT INTO orchestras (name, slug, organization_id) VALUES ('Sinfonieorchester Harmonia', 'sinfonie-harmonia', {$orgId})");
        $sinfonieId = $conn->insert_id;

        $conn->query("INSERT INTO orchestras (name, slug, organization_id) VALUES ('Kammerensemble Harmonia', 'kammer-harmonia', {$orgId})");
        $kammerId = $conn->insert_id;
        $counts['orchestras'] = 2;

        // ── 3. Users ─────────────────────────────────────────────────
        // Roster: [email, display_name, section, orchestra_id, preset, is_active, is_small_group]
        $roster = [
            // Sinfonie — Conductor (empty type = no instrument section)
            ['dirigent@test.local',        'Thomas Müller',        '',             $sinfonieId, 'conductor',      1, 0],
            ['stimmfuehrer@test.local',    'Sabine Becker',        'Violine_1',    $sinfonieId, 'section_leader', 1, 0],
            ['anna.mueller@test.local',    'Anna Müller',          'Violine_1',    $sinfonieId, 'member', 1, 0],
            ['jan.schmidt@test.local',     'Jan Schmidt',          'Violine_1',    $sinfonieId, 'member', 1, 0],
            ['lisa.weber@test.local',      'Lisa Weber',           'Violine_2',    $sinfonieId, 'member', 1, 0],
            ['peter.fischer@test.local',   'Peter Fischer',        'Violine_2',    $sinfonieId, 'member', 1, 0],
            ['marie.koch@test.local',      'Marie Koch',           'Violine_2',    $sinfonieId, 'member', 1, 0],
            ['sarah.braun@test.local',     'Sarah Braun',          'Bratsche',     $sinfonieId, 'member', 1, 0],
            ['felix.wolf@test.local',      'Felix Wolf',           'Bratsche',     $sinfonieId, 'member', 1, 0],
            ['julia.richter@test.local',   'Julia Richter',        'Cello',        $sinfonieId, 'member', 1, 0],
            ['noah.klein@test.local',      'Noah Klein',           'Cello',        $sinfonieId, 'member', 1, 0],
            ['emma.schroeder@test.local',  'Emma Schröder',        'Kontrabass',   $sinfonieId, 'member', 1, 0],
            ['lukas.neumann@test.local',   'Lukas Neumann',        'Flöte',        $sinfonieId, 'member', 1, 0],
            ['hannah.schwarz@test.local',  'Hannah Schwarz',       'Oboe',         $sinfonieId, 'member', 1, 0],
            ['max.zimmermann@test.local',  'Max Zimmermann',       'Klarinette',   $sinfonieId, 'member', 1, 0],
            ['lena.hoffmann@test.local',   'Lena Hoffmann',        'Fagott',       $sinfonieId, 'member', 1, 0],
            ['david.wagner@test.local',    'David Wagner',         'Trompete',     $sinfonieId, 'member', 1, 0],
            ['sophie.bauer@test.local',    'Sophie Bauer',         'Trompete',     $sinfonieId, 'member', 1, 0],
            ['tim.schulz@test.local',      'Tim Schulz',           'Posaune',      $sinfonieId, 'member', 1, 0],
            ['laura.hartmann@test.local',  'Laura Hartmann',       'Tuba',         $sinfonieId, 'member', 1, 0],
            ['paul.krueger@test.local',    'Paul Krüger',          'Horn',         $sinfonieId, 'member', 1, 0],
            ['mia.lang@test.local',        'Mia Lang',             'Horn',         $sinfonieId, 'member', 1, 0],
            ['leon.frank@test.local',      'Leon Frank',           'Schlagwerk',   $sinfonieId, 'member', 1, 0],
            ['clara.roth@test.local',      'Clara Roth',           'Andere',       $sinfonieId, 'member', 1, 0],
            ['nina.berg@test.local',       'Nina Berg',            'Violine_1',    $sinfonieId, 'member', 1, 1],
            ['oldinactive1@test.local',    'Karl Ehemalig',        'Cello',        $sinfonieId, 'member', 0, 0],
            ['oldinactive2@test.local',    'Ute Vergangen',        'Flöte',        $sinfonieId, 'member', 0, 0],
            ['kammer.dirigent@test.local', 'Eva Schneider',        '',             $kammerId,   'conductor',      1, 0],
            ['kammer.geige@test.local',    'Moritz Stein',         'Violine_1',    $kammerId,   'member', 1, 0],
            ['kammer.bratsche@test.local', 'Klara Weiß',           'Bratsche',     $kammerId,   'member', 1, 0],
            ['kammer.cello@test.local',    'Anton Frei',           'Cello',        $kammerId,   'member', 1, 0],
            ['kammer.bass@test.local',     'Greta Haus',           'Kontrabass',   $kammerId,   'member', 1, 0],
        ];

        // ── 3a. Create system roles per orchestra ──────────────────────
        $rolePresets = [
            'conductor' => [
                'name' => 'Leitung',
                'tag_color' => '#478cf4',
                'permissions' => json_encode(['can_view_own_section_stats', 'can_view_all_section_stats', 'can_view_members', 'can_manage_rehearsals', 'can_manage_members', 'can_manage_permissions', 'can_manage_ensemble']),
                'is_system' => 1,
                'is_default' => 0,
                'sort_order' => 0,
            ],
            'section_leader' => [
                'name' => 'Stimmführer',
                'tag_color' => '#f59e0b',
                'permissions' => json_encode(['can_attend_rehearsals', 'can_view_own_section_stats', 'can_view_all_section_stats', 'can_manage_rehearsals', 'can_view_members']),
                'is_system' => 1,
                'is_default' => 0,
                'sort_order' => 10,
            ],
            'member' => [
                'name' => 'Mitglied',
                'tag_color' => '#10b981',
                'permissions' => json_encode(['can_attend_rehearsals']),
                'is_system' => 1,
                'is_default' => 1,
                'sort_order' => 100,
            ],
        ];

        // role_id cache: orchestraId → preset → role_id
        $roleIdMap = [];
        foreach ([$sinfonieId, $kammerId] as $oId) {
            foreach ($rolePresets as $preset => $rp) {
                $stmt = $conn->prepare("INSERT INTO roles (orchestra_id, name, tag_color, permissions, is_system, is_default, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new \Exception("Role INSERT prepare failed: " . $conn->error);
                }
                $stmt->bind_param('isssiii', $oId, $rp['name'], $rp['tag_color'], $rp['permissions'], $rp['is_system'], $rp['is_default'], $rp['sort_order']);
                if (!$stmt->execute()) {
                    throw new \Exception("Role INSERT execute failed: " . $stmt->error);
                }
                $roleIdMap[$oId][$preset] = $conn->insert_id;
                $stmt->close();
            }
        }

        $userIdMap = [];
        $sinfonieUserIds = [];
        $kammerUserIds = [];

        foreach ($roster as $entry) {
            [$email, $displayName, $section, $orchId, $preset, $isActive, $isSmallGroup] = $entry;

            $pw = password_hash($email, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (email, password, display_name) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $email, $pw, $displayName);
            $stmt->execute();
            $userId = $conn->insert_id;
            $stmt->close();

            $roleId = $roleIdMap[$orchId][$preset];
            $stmt = $conn->prepare("INSERT INTO user_orchestras (user_id, orchestra_id, type, is_active, is_small_group, role_id, joined_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iisiiis', $userId, $orchId, $section, $isActive, $isSmallGroup, $roleId, $now);
            $stmt->execute();
            $stmt->close();

            $userIdMap[$email] = $userId;
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
                    ['email' => 'harmonia-admin@probenplaner.local', 'password' => 'testadmin',           'role' => 'Org-Admin'],
                    ['email' => 'dirigent@test.local',               'password' => 'dirigent@test.local',  'role' => 'Dirigent (Sinfonie)'],
                    ['email' => 'stimmfuehrer@test.local',           'password' => 'stimmfuehrer@test.local', 'role' => 'Stimmführer (Sinfonie)'],
                    ['email' => 'anna.mueller@test.local',           'password' => 'anna.mueller@test.local', 'role' => 'Mitglied (Violine 1)'],
                    ['email' => 'kammer.dirigent@test.local',        'password' => 'kammer.dirigent@test.local', 'role' => 'Dirigent (Kammer)'],
                    ['email' => 'nina.berg@test.local',              'password' => 'nina.berg@test.local',  'role' => 'Kleingruppe (Violine 1)'],
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
