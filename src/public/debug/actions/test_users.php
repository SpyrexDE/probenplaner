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
    if (empty($_POST['email_prefix'])) {
        return ['message' => 'Email prefix is required', 'messageType' => 'error'];
    }
    if (empty($_POST['max_users']) || !is_numeric($_POST['max_users']) || $_POST['max_users'] < 1) {
        return ['message' => 'Please enter a valid maximum number of users', 'messageType' => 'error'];
    }

    $orchestraId = (int)$_POST['orchestra_id'];
    $emailPrefix = $_POST['email_prefix'];
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

    // Read instrument list from the orchestra's section config
    $_SESSION['current_orchestra_id'] = $orchestraId;
    \App\Core\GroupManager::resetInstance($orchestraId);
    $gm = \App\Core\GroupManager::getInstance();
    $sections = array_keys($gm->getAllInstruments());

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

                    // Find all default roles for this orchestra
                    $roleStmt = $conn->prepare("SELECT id FROM roles WHERE orchestra_id = ? AND is_default = 1");
                    $roleStmt->bind_param('i', $orchestraId);
                    $roleStmt->execute();
                    $roleResult = $roleStmt->get_result();
                    $defaultRoleIds = [];
                    while ($roleRow = $roleResult->fetch_assoc()) {
                        $defaultRoleIds[] = (int)$roleRow['id'];
                    }
                    $roleStmt->close();

                    $userOrchestraStmt = $conn->prepare("INSERT INTO user_orchestras (user_id, orchestra_id, type, is_active, joined_at) VALUES (?, ?, ?, 1, ?)");
                    $userOrchestraStmt->bind_param('iiss', $userId, $orchestraId, $section, $joinedAt);

                    if ($userOrchestraStmt->execute()) {
                        $uoId = $conn->insert_id;
                        $userOrchestraStmt->close();

                        foreach ($defaultRoleIds as $defRoleId) {
                            $roleStmt2 = $conn->prepare("INSERT INTO user_orchestra_roles (user_orchestra_id, role_id) VALUES (?, ?)");
                            $roleStmt2->bind_param('ii', $uoId, $defRoleId);
                            $roleStmt2->execute();
                            $roleStmt2->close();
                        }
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
            $orchIds = "SELECT id FROM orchestras WHERE organization_id = {$eOrgId}";
            $rehIds = "SELECT id FROM rehearsals WHERE orchestra_id IN ({$orchIds})";

            // Rehearsal-related data
            $conn->query("DELETE FROM user_promises WHERE rehearsal_id IN ({$rehIds})");
            $conn->query("DELETE FROM rehearsal_schedule_items WHERE rehearsal_id IN ({$rehIds})");
            $conn->query("DELETE FROM rehearsal_infos WHERE rehearsal_id IN ({$rehIds})");
            $conn->query("DELETE FROM rehearsal_groups WHERE rehearsal_id IN ({$rehIds})");
            $conn->query("DELETE FROM rehearsal_roles WHERE rehearsal_id IN ({$rehIds})");
            $conn->query("DELETE FROM rehearsals WHERE orchestra_id IN ({$orchIds})");

            // Roles, memberships, orchestras, org
            $conn->query("DELETE FROM user_orchestra_roles WHERE user_orchestra_id IN (SELECT uo.id FROM user_orchestras uo JOIN orchestras o ON uo.orchestra_id = o.id WHERE o.organization_id = {$eOrgId})");
            $conn->query("DELETE FROM roles WHERE orchestra_id IN ({$orchIds})");
            $conn->query("DELETE uo FROM user_orchestras uo JOIN orchestras o ON uo.orchestra_id = o.id WHERE o.organization_id = {$eOrgId}");
            $conn->query("DELETE FROM orchestras WHERE organization_id = {$eOrgId}");
            $conn->query("DELETE FROM users WHERE organization_id = {$eOrgId}");
            $conn->query("DELETE FROM organizations WHERE id = {$eOrgId}");

            // Test users by email domain
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
        // Build section_config JSON from the default config
        $defaultConfig = require __DIR__ . '/../../../config/orchestra_groups.php';
        $sectionConfigJson = json_encode($defaultConfig);

        $conn->query("INSERT INTO orchestras (name, slug, organization_id, section_config) VALUES ('JSH', 'jsh', {$orgId}, '" . $conn->real_escape_string($sectionConfigJson) . "')");
        $sinfonieId = $conn->insert_id;

        $conn->query("INSERT INTO orchestras (name, slug, organization_id, section_config) VALUES ('Kammerensemble Harmonia', 'kammer-harmonia', {$orgId}, '" . $conn->real_escape_string($sectionConfigJson) . "')");
        $kammerId = $conn->insert_id;
        $counts['orchestras'] = 2;

        // ── 3. Roles ─────────────────────────────────────────────────
        $rolePresets = [
            'conductor' => [
                'name' => 'Leitung',
                'tag_color' => '#478cf4',
                'permissions' => json_encode(['can_view_own_section_stats', 'can_view_all_section_stats', 'can_view_members', 'can_manage_rehearsals', 'can_manage_members', 'can_manage_permissions', 'can_manage_ensemble']),
                'is_system' => 1,
                'is_default' => 0,
                'is_self_assignable' => 0,
                'sort_order' => 0,
            ],
            'member' => [
                'name' => 'Mitglied',
                'tag_color' => '#10b981',
                'permissions' => json_encode(['can_attend_rehearsals', 'can_view_schedule']),
                'is_system' => 0,
                'is_default' => 1,
                'is_self_assignable' => 0,
                'sort_order' => 100,
            ],
            'section_leader' => [
                'name' => 'Stimmführung',
                'tag_color' => '#f59e0b',
                'permissions' => json_encode(['can_attend_rehearsals', 'can_view_schedule', 'can_view_own_section_stats', 'can_view_members']),
                'is_system' => 0,
                'is_default' => 0,
                'is_self_assignable' => 0,
                'sort_order' => 10,
            ],
            'project' => [
                'name' => 'IYSO 2026',
                'tag_color' => '#8b5cf6',
                'permissions' => json_encode(['can_attend_rehearsals', 'can_view_schedule']),
                'is_system' => 0,
                'is_default' => 0,
                'is_self_assignable' => 1,
                'sort_order' => 90,
            ],
        ];

        $roleIdMap = [];
        foreach ([$sinfonieId, $kammerId] as $oId) {
            foreach ($rolePresets as $preset => $rp) {
                $stmt = $conn->prepare("INSERT INTO roles (orchestra_id, name, tag_color, permissions, is_system, is_default, is_self_assignable, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) throw new \Exception("Role INSERT prepare failed: " . $conn->error);
                $stmt->bind_param('isssiiii', $oId, $rp['name'], $rp['tag_color'], $rp['permissions'], $rp['is_system'], $rp['is_default'], $rp['is_self_assignable'], $rp['sort_order']);
                if (!$stmt->execute()) throw new \Exception("Role INSERT execute failed: " . $stmt->error);
                $roleIdMap[$oId][$preset] = $conn->insert_id;
                $stmt->close();
            }
        }

        // ── 4. Users ─────────────────────────────────────────────────
        // [email, display_name, section, orchestra_id, role_presets[], is_active]
        $roster = [
            // ── Sinfonie: Leitung (2) ──
            ['dirigent@test.local',            'Thomas Müller',       '',           $sinfonieId, ['conductor'],                           1],
            ['assistenz@test.local',           'Katharina Engel',     '',           $sinfonieId, ['conductor'],                           1],

            // ── Violine 1 (14) — 2× Stimmführung ──
            ['stimmfuehrer@test.local',        'Sabine Becker',       'Violine_1',  $sinfonieId, ['member', 'project', 'section_leader'], 1],
            ['anna.mueller@test.local',        'Anna Müller',         'Violine_1',  $sinfonieId, ['member', 'project', 'section_leader'], 1],
            ['jan.schmidt@test.local',         'Jan Schmidt',         'Violine_1',  $sinfonieId, ['member'],                               1],
            ['nina.berg@test.local',           'Nina Berg',           'Violine_1',  $sinfonieId, ['member', 'project'],                   1],
            ['lara.petersen@test.local',       'Lara Petersen',       'Violine_1',  $sinfonieId, ['member', 'project'],                   1],
            ['tobias.jung@test.local',         'Tobias Jung',         'Violine_1',  $sinfonieId, ['member', 'project'],                   1],
            ['elena.winter@test.local',        'Elena Winter',        'Violine_1',  $sinfonieId, ['member'],                               1],
            ['simon.kraft@test.local',         'Simon Kraft',         'Violine_1',  $sinfonieId, ['member', 'project'],                   1],
            ['amelie.horn@test.local',         'Amelie Horn',         'Violine_1',  $sinfonieId, ['member'],                               1],
            ['jonathan.vogt@test.local',       'Jonathan Vogt',       'Violine_1',  $sinfonieId, ['member', 'project'],                   1],
            ['carla.brandt@test.local',        'Carla Brandt',        'Violine_1',  $sinfonieId, ['member'],                               1],
            ['hendrik.seidel@test.local',      'Hendrik Seidel',      'Violine_1',  $sinfonieId, ['member', 'project'],                   1],
            ['miriam.koenig@test.local',       'Miriam König',        'Violine_1',  $sinfonieId, ['member'],                               1],
            ['robert.haas@test.local',         'Robert Haas',         'Violine_1',  $sinfonieId, ['member'],                               1],

            // ── Violine 2 (12) — 2× Stimmführung ──
            ['anna.weber@test.local',          'Anna Weber',          'Violine_2',  $sinfonieId, ['member', 'project', 'section_leader'], 1],
            ['peter.fischer@test.local',       'Peter Fischer',       'Violine_2',  $sinfonieId, ['member', 'project', 'section_leader'], 1],
            ['marie.koch@test.local',          'Marie Koch',          'Violine_2',  $sinfonieId, ['member', 'project'],                   1],
            ['florian.huber@test.local',       'Florian Huber',       'Violine_2',  $sinfonieId, ['member'],                               1],
            ['lisa.baumann@test.local',        'Lisa Baumann',        'Violine_2',  $sinfonieId, ['member', 'project'],                   1],
            ['moritz.schreiber@test.local',    'Moritz Schreiber',    'Violine_2',  $sinfonieId, ['member'],                               1],
            ['theresa.lorenz@test.local',      'Theresa Lorenz',      'Violine_2',  $sinfonieId, ['member', 'project'],                   1],
            ['daniel.fuchs@test.local',        'Daniel Fuchs',        'Violine_2',  $sinfonieId, ['member'],                               1],
            ['kathrin.otto@test.local',        'Kathrin Otto',        'Violine_2',  $sinfonieId, ['member', 'project'],                   1],
            ['philipp.engel@test.local',       'Philipp Engel',       'Violine_2',  $sinfonieId, ['member'],                               1],
            ['viktoria.simon@test.local',      'Viktoria Simon',      'Violine_2',  $sinfonieId, ['member'],                               1],
            ['fabian.link@test.local',         'Fabian Link',         'Violine_2',  $sinfonieId, ['member', 'project'],                   1],

            // ── Bratsche (8) — 1× Stimmführung ──
            ['sarah.mueller@test.local',       'Sarah Müller',        'Bratsche',   $sinfonieId, ['member', 'project', 'section_leader'], 1],
            ['felix.wolf@test.local',          'Felix Wolf',          'Bratsche',   $sinfonieId, ['member', 'project'],                   1],
            ['isabel.pfeiffer@test.local',     'Isabel Pfeiffer',     'Bratsche',   $sinfonieId, ['member'],                               1],
            ['matthias.ernst@test.local',      'Matthias Ernst',      'Bratsche',   $sinfonieId, ['member', 'project'],                   1],
            ['charlotte.dorn@test.local',      'Charlotte Dorn',      'Bratsche',   $sinfonieId, ['member'],                               1],
            ['niklas.hahn@test.local',         'Niklas Hahn',         'Bratsche',   $sinfonieId, ['member', 'project'],                   1],
            ['eva.riedel@test.local',          'Eva Riedel',          'Bratsche',   $sinfonieId, ['member'],                               1],
            ['johannes.blum@test.local',       'Johannes Blum',       'Bratsche',   $sinfonieId, ['member'],                               1],

            // ── Cello (8) — 1× Stimmführung ──
            ['julia.richter@test.local',       'Julia Richter',       'Cello',      $sinfonieId, ['member', 'project', 'section_leader'], 1],
            ['noah.klein@test.local',          'Noah Klein',          'Cello',      $sinfonieId, ['member', 'project'],                   1],
            ['leonie.stein@test.local',        'Leonie Stein',        'Cello',      $sinfonieId, ['member'],                               1],
            ['alexander.gross@test.local',     'Alexander Groß',      'Cello',      $sinfonieId, ['member', 'project'],                   1],
            ['hannah.berger@test.local',       'Hannah Berger',       'Cello',      $sinfonieId, ['member'],                               1],
            ['vincent.mai@test.local',         'Vincent Mai',         'Cello',      $sinfonieId, ['member', 'project'],                   1],
            ['clara.roth@test.local',          'Clara Roth',          'Cello',      $sinfonieId, ['member'],                               1],
            ['benjamin.wendt@test.local',      'Benjamin Wendt',      'Cello',      $sinfonieId, ['member'],                               1],

            // ── Kontrabass (4) — 1× Stimmführung ──
            ['emma.schroeder@test.local',      'Emma Schröder',       'Kontrabass', $sinfonieId, ['member', 'project', 'section_leader'], 1],
            ['jakob.ibach@test.local',         'Jakob Ibach',         'Kontrabass', $sinfonieId, ['member', 'project'],                   1],
            ['paula.ebert@test.local',         'Paula Ebert',         'Kontrabass', $sinfonieId, ['member'],                               1],
            ['mark.gruber@test.local',         'Mark Gruber',         'Kontrabass', $sinfonieId, ['member'],                               1],

            // ── Flöte (3) — 1× Stimmführung ──
            ['lukas.neumann@test.local',       'Lukas Neumann',       'Flöte',      $sinfonieId, ['member', 'project', 'section_leader'], 1],
            ['sophia.vogel@test.local',        'Sophia Vogel',        'Flöte',      $sinfonieId, ['member', 'project'],                   1],
            ['leonard.pohl@test.local',        'Leonard Pohl',        'Flöte',      $sinfonieId, ['member'],                               1],

            // ── Oboe (3) — 1× Stimmführung ──
            ['hannah.schwarz@test.local',      'Hannah Schwarz',      'Oboe',       $sinfonieId, ['member', 'project', 'section_leader'], 1],
            ['rafael.keller@test.local',       'Rafael Keller',       'Oboe',       $sinfonieId, ['member', 'project'],                   1],
            ['antonia.albrecht@test.local',    'Antonia Albrecht',    'Oboe',       $sinfonieId, ['member'],                               1],

            // ── Klarinette (3) — 1× Stimmführung ──
            ['max.zimmermann@test.local',      'Max Zimmermann',      'Klarinette', $sinfonieId, ['member', 'project', 'section_leader'], 1],
            ['sandra.kurz@test.local',         'Sandra Kurz',         'Klarinette', $sinfonieId, ['member', 'project'],                   1],
            ['till.winkler@test.local',        'Till Winkler',        'Klarinette', $sinfonieId, ['member'],                               1],

            // ── Fagott (3) — 1× Stimmführung ──
            ['lena.hoffmann@test.local',       'Lena Hoffmann',       'Fagott',     $sinfonieId, ['member', 'project', 'section_leader'], 1],
            ['judith.schmidt@test.local',      'Judith Schmidt',      'Fagott',     $sinfonieId, ['member', 'project'],                   1],
            ['gregor.bach@test.local',         'Gregor Bach',         'Fagott',     $sinfonieId, ['member'],                               1],

            // ── Horn (5) — 1× Stimmführung ──
            ['paul.krueger@test.local',        'Paul Krüger',         'Horn',       $sinfonieId, ['member', 'project', 'section_leader'], 1],
            ['mia.lang@test.local',            'Mia Lang',            'Horn',       $sinfonieId, ['member', 'project'],                   1],
            ['konrad.reuter@test.local',       'Konrad Reuter',       'Horn',       $sinfonieId, ['member'],                               1],
            ['celine.walter@test.local',       'Céline Walter',       'Horn',       $sinfonieId, ['member', 'project'],                   1],
            ['erik.krause@test.local',         'Erik Krause',         'Horn',       $sinfonieId, ['member'],                               1],

            // ── Trompete (4) — 1× Stimmführung ──
            ['david.wagner@test.local',        'David Wagner',        'Trompete',   $sinfonieId, ['member', 'project', 'section_leader'], 1],
            ['sophie.bauer@test.local',        'Sophie Bauer',        'Trompete',   $sinfonieId, ['member', 'project'],                   1],
            ['nico.dietrich@test.local',       'Nico Dietrich',       'Trompete',   $sinfonieId, ['member'],                               1],
            ['lea.feldmann@test.local',        'Lea Feldmann',        'Trompete',   $sinfonieId, ['member'],                               1],

            // ── Posaune (3) — 1× Stimmführung ──
            ['tim.schulz@test.local',          'Tim Schulz',          'Posaune',    $sinfonieId, ['member', 'project', 'section_leader'], 1],
            ['marcel.braun@test.local',        'Marcel Braun',        'Posaune',    $sinfonieId, ['member', 'project'],                   1],
            ['helena.meier@test.local',        'Helena Meier',        'Posaune',    $sinfonieId, ['member'],                               1],

            // ── Tuba (1) ──
            ['laura.hartmann@test.local',      'Laura Hartmann',      'Tuba',       $sinfonieId, ['member', 'project'],                   1],

            // ── Schlagwerk (4) — 1× Stimmführung ──
            ['leon.frank@test.local',          'Leon Frank',          'Schlagwerk', $sinfonieId, ['member', 'project', 'section_leader'], 1],
            ['lovis.haury@test.local',         'Lovis Haury',         'Schlagwerk', $sinfonieId, ['member', 'project'],                   1],
            ['magnus.beck@test.local',         'Magnus Beck',         'Schlagwerk', $sinfonieId, ['member'],                               1],
            ['ronja.falk@test.local',          'Ronja Falk',          'Schlagwerk', $sinfonieId, ['member'],                               1],

            // ── Harfe (1) ──
            ['magdalena.strauss@test.local',   'Magdalena Strauß',    'Harfe',      $sinfonieId, ['member', 'project'],                   1],

            // ── Inactive (3) ──
            ['oldinactive1@test.local',        'Karl Ehemalig',       'Cello',      $sinfonieId, ['member', 'project'],                   0],
            ['oldinactive2@test.local',        'Ute Vergangen',       'Flöte',      $sinfonieId, ['member'],                               0],
            ['oldinactive3@test.local',        'Franz Austritt',      'Horn',       $sinfonieId, ['member'],                               0],

            // ── Kammer (5) ──
            ['kammer.dirigent@test.local',     'Eva Schneider',       '',           $kammerId,   ['conductor'],                           1],
            ['kammer.geige@test.local',        'Moritz Stein',        'Violine_1',  $kammerId,   ['member', 'project', 'section_leader'], 1],
            ['kammer.bratsche@test.local',     'Klara Weiß',          'Bratsche',   $kammerId,   ['member', 'project'],                   1],
            ['kammer.cello@test.local',        'Anton Frei',          'Cello',      $kammerId,   ['member', 'project'],                   1],
            ['kammer.bass@test.local',         'Greta Haus',          'Kontrabass', $kammerId,   ['member', 'project'],                   1],
        ];

        $userIdMap = [];
        $sinfonieUserIds = [];
        $kammerUserIds = [];

        foreach ($roster as $entry) {
            [$email, $displayName, $section, $orchId, $presets, $isActive] = $entry;

            $pw = password_hash($email, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (email, password, display_name) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $email, $pw, $displayName);
            $stmt->execute();
            $userId = $conn->insert_id;
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO user_orchestras (user_id, orchestra_id, type, is_active, joined_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('iisis', $userId, $orchId, $section, $isActive, $now);
            $stmt->execute();
            $uoId = $conn->insert_id;
            $stmt->close();

            foreach ($presets as $preset) {
                if (!isset($roleIdMap[$orchId][$preset])) continue;
                $presetRoleId = $roleIdMap[$orchId][$preset];
                $stmt = $conn->prepare("INSERT INTO user_orchestra_roles (user_orchestra_id, role_id) VALUES (?, ?)");
                $stmt->bind_param('ii', $uoId, $presetRoleId);
                $stmt->execute();
                $stmt->close();
            }

            $userIdMap[$email] = $userId;
            if ($isActive && $orchId === $sinfonieId) $sinfonieUserIds[] = $userId;
            if ($isActive && $orchId === $kammerId) $kammerUserIds[] = $userId;
            $counts['users']++;
        }

        // ── 4. Rehearsals ────────────────────────────────────────────
        $today = new DateTime();
        // [type, orchestra_id, day_offset, start, end, location, _unused, groups, role_presets]
        // role_presets: [] = all roles (no scoping), ['project'] = IYSO only
        $rehearsalDefs = [
            // Sinfonie — past rehearsals
            ['Probe',           $sinfonieId, -42, '19:00:00', '21:00:00', 'Aula Musikschule',       0, ['tutti'],                    []],
            ['Probe',           $sinfonieId, -35, '19:00:00', '21:00:00', 'Aula Musikschule',       0, ['tutti'],                    []],
            ['Registerprobe',   $sinfonieId, -28, '18:30:00', '20:00:00', 'Raum 201',               0, ['Streicher'],                []],
            ['Probe',           $sinfonieId, -21, '19:00:00', '21:00:00', 'Gemeindesaal St. Peter',  0, ['tutti'],                    []],
            ['Generalprobe',    $sinfonieId, -14, '18:00:00', '21:30:00', 'Stadthalle',             0, ['tutti'],                    []],
            ['Konzert',         $sinfonieId,  -7, '19:30:00', '21:30:00', 'Stadthalle',             0, ['tutti'],                    []],
            // Sinfonie — near-future rehearsals
            ['Probe',           $sinfonieId,   7, '19:00:00', '21:00:00', 'Aula Musikschule',       0, ['tutti'],                    []],
            ['Probe',           $sinfonieId,  14, '19:00:00', '21:00:00', 'Aula Musikschule',       1, ['tutti'],                    []],
            ['Registerprobe',   $sinfonieId,  21, '18:30:00', '20:00:00', 'Raum 201',               0, ['Holzbläser'],               []],
            ['Konzertreise',    $sinfonieId,  35, '09:00:00', '22:00:00', 'Partnerstadt Lucca 🇮🇹', 0, ['tutti'],                    []],
            // IYSO 2026 project rehearsals (scoped to project role)
            ['Probe',           $sinfonieId, -30, '17:00:00', '19:00:00', 'Aula Musikschule',       0, ['tutti'],                    ['project']],
            ['Probe',           $sinfonieId,  10, '17:00:00', '19:00:00', 'Aula Musikschule',       0, ['tutti'],                    ['project']],
            ['Generalprobe',    $sinfonieId,  28, '14:00:00', '18:00:00', 'Konzerthaus',            0, ['tutti'],                    ['project']],
            // Kammer
            ['Probe',           $kammerId,   -10, '20:00:00', '21:30:00', 'Proberaum Kammer',       0, ['tutti'],                    []],
            ['Konzert',         $kammerId,    20, '19:00:00', '21:00:00', 'Schlosskapelle',         0, ['tutti'],                    []],

            // ── Far-future rehearsals (10) ──────────────────────────────
            ['Probe',           $sinfonieId,  60, '19:00:00', '21:00:00', 'Neubau Konzertsaal (NBK)',       0, ['tutti'],             []],
            ['Registerprobe',   $sinfonieId,  67, '18:30:00', '20:00:00', 'Raum 201',                      0, ['Streicher'],          []],
            ['Registerprobe',   $sinfonieId,  74, '18:30:00', '20:00:00', 'Sanderuni SR 227',               0, ['Blechbläser', 'Schlagwerk'], []],
            ['Probe',           $sinfonieId,  81, '19:00:00', '21:30:00', 'Neubau Konzertsaal (NBK)',       0, ['tutti'],             []],
            ['Probe',           $sinfonieId,  88, '18:00:00', '19:30:00', 'Sanderuni SR 227',               0, ['tutti'],             ['section_leader']],
            ['Probe',           $sinfonieId,  95, '17:00:00', '19:00:00', 'Aula Musikschule',               0, ['tutti'],             ['project']],
            ['Probe',           $sinfonieId, 102, '17:00:00', '19:00:00', 'Aula Musikschule',               0, ['tutti'],             ['project']],
            ['Probe',           $sinfonieId, 120, '09:00:00', '17:00:00', 'Heiligenhof Bad Kissingen',      0, ['tutti'],             []],
            ['Generalprobe',    $sinfonieId, 150, '14:00:00', '18:00:00', 'Hochschule für Musik (HfM)',     0, ['tutti'],             []],
            ['Konzert',         $sinfonieId, 152, '15:00:00', '18:00:00', 'HfM Großer Saal',                0, ['tutti'],             []],
        ];

        $rehearsalIdMap = [];

        foreach ($rehearsalDefs as $idx => $def) {
            [$type, $orchId, $dayOffset, $startTime, $endTime, $location, $_unused, $groups, $rolePresets] = $def;

            $date = (clone $today)->modify("{$dayOffset} days");
            $startDatetime = $date->format('Y-m-d') . ' ' . $startTime;
            $endDatetime   = $date->format('Y-m-d') . ' ' . $endTime;

            $stmt = $conn->prepare("INSERT INTO rehearsals (type, start, `end`, location, orchestra_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssiss', $type, $startDatetime, $endDatetime, $location, $orchId, $now, $now);
            $stmt->execute();
            $rehearsalId = $conn->insert_id;
            $stmt->close();

            foreach ($groups as $group) {
                $stmt = $conn->prepare("INSERT INTO rehearsal_groups (rehearsal_id, name) VALUES (?, ?)");
                $stmt->bind_param('is', $rehearsalId, $group);
                $stmt->execute();
                $stmt->close();
            }

            // Scope rehearsal to specific roles
            foreach ($rolePresets as $preset) {
                if (!isset($roleIdMap[$orchId][$preset])) continue;
                $scopeRoleId = $roleIdMap[$orchId][$preset];
                $stmt = $conn->prepare("INSERT INTO rehearsal_roles (rehearsal_id, role_id) VALUES (?, ?)");
                $stmt->bind_param('ii', $rehearsalId, $scopeRoleId);
                $stmt->execute();
                $stmt->close();
            }

            $rehearsalIdMap[$idx] = $rehearsalId;
            $counts['rehearsals']++;
        }

        // ── 5. Schedule items (on select rehearsals) ─────────────────
        $scheduleData = [
            4 => [ // Generalprobe (past)
                ['18:00:00', 'Einlass & Aufbau'],
                ['18:30:00', 'Durchlauf 1. Hälfte'],
                ['19:30:00', 'Pause'],
                ['19:45:00', 'Durchlauf 2. Hälfte'],
                ['21:00:00', 'Besprechung'],
            ],
            5 => [ // Konzert (past)
                ['19:00:00', 'Einspielen'],
                ['19:30:00', 'Konzertbeginn'],
                ['20:15:00', 'Pause'],
                ['20:30:00', '2. Hälfte'],
            ],
            9 => [ // Konzertreise Lucca
                ['09:00:00', 'Abfahrt Bus'],
                ['13:00:00', 'Ankunft & Mittagessen'],
                ['15:00:00', 'Probe im Saal'],
                ['18:00:00', 'Abendessen'],
                ['20:00:00', 'Konzert'],
            ],
            15 => [ // Tutti-Probe NBK
                ['19:00:00', 'Aufbau (Aufbaugruppe: Holz + Blech)'],
                ['19:15:00', 'Einspielzeit Stimmen'],
                ['19:30:00', 'Price, Symphonie No. 4 — 1. & 2. Satz'],
                ['20:15:00', 'Pause (5 Min)'],
                ['20:20:00', 'Mahler, Totenfeier'],
            ],
            18 => [ // Tutti-Probe NBK (2nd)
                ['19:00:00', 'Aufbau (Aufbaugruppe: Streicher)'],
                ['19:15:00', 'Einspielzeit Stimmen'],
                ['19:30:00', 'Armstrong/Ricketts, Satchmo! (Kurzversion)'],
                ['20:15:00', 'Pause'],
                ['20:20:00', 'Hoche, Marimbakonzert mit Solistin'],
                ['21:15:00', 'Gemeinsamer Abbau'],
            ],
            19 => [ // Stimmführungs-Besprechung
                ['18:00:00', 'Begrüßung & Tagesordnung'],
                ['18:15:00', 'Rückblick: Registerproben-Ergebnisse'],
                ['18:45:00', 'Besetzungsfragen Universitätskonzert'],
                ['19:00:00', 'Stimmproben-Planung & Aufgabenverteilung'],
                ['19:15:00', 'Verschiedenes & Feedback'],
            ],
            22 => [ // Probenwochenende Heiligenhof
                ['09:00:00', 'Frühstück & Aufbau'],
                ['09:45:00', 'Einspielzeit Stimmen'],
                ['10:00:00', 'Price, Symphonie No. 4 — Durchlauf komplett'],
                ['11:30:00', 'Registerproben (parallel: Str., Holz, Blech)'],
                ['12:30:00', 'Mittagessen'],
                ['13:30:00', 'Mahler, Totenfeier — Durchlauf'],
                ['14:30:00', 'Pause'],
                ['14:45:00', 'Hoche, Marimbakonzert — mit Solistin'],
                ['15:30:00', 'Armstrong/Ricketts, Satchmo!'],
                ['16:15:00', 'Schlussbesprechung & gemeinsamer Abbau'],
            ],
            23 => [ // Generalprobe HfM
                ['14:00:00', 'Aufbau & Einlass'],
                ['14:30:00', 'Anspielprobe 1. Hälfte'],
                ['15:30:00', 'Pause'],
                ['15:45:00', 'Anspielprobe 2. Hälfte'],
                ['16:45:00', 'Korrekturen & Detailarbeit'],
                ['17:30:00', 'Schluss-Besprechung'],
            ],
            24 => [ // Konzert HfM
                ['15:00:00', 'Anspielprobe'],
                ['15:30:00', 'Konzertbeginn — 1. Hälfte'],
                ['16:15:00', 'Pause'],
                ['16:30:00', '2. Hälfte'],
                ['17:30:00', 'Zugabe & Applaus'],
                ['17:45:00', 'Gemeinsamer Abbau, After-Concert-Party'],
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
            0 => [
                ['📝', 'Bitte Noten für Beethoven 5 mitbringen'],
                ['⏰', 'Pünktlich erscheinen — Probe beginnt 19:00 sharp'],
            ],
            4 => [
                ['👔', 'Konzertkleidung tragen!'],
                ['🎵', 'Soli bitte nochmal einzeln üben'],
                ['⚠️', 'Achtung: Einlass nur über Hintereingang'],
            ],
            5 => [
                ['🎉', 'Saisonabschlusskonzert — Danke an alle!'],
                ['📸', 'Gruppenfoto nach dem Konzert'],
            ],
            9 => [
                ['🧳', 'Reisepass nicht vergessen!'],
                ['🇮🇹', 'Buon viaggio! 🎶'],
                ['💊', 'Reiseapotheke mitnehmen'],
                ['🔥', 'Partystimmung garantiert 🎺🥁'],
            ],
            15 => [
                ['📝', 'Noten für Price und Mahler vorbereiten'],
                ['🪑', 'Aufbaugruppe: Holz + Blech — bitte 20 Min vor Probenbeginn da sein'],
            ],
            16 => [
                ['🎻', 'Nur Streicher — Notenständer bitte mitbringen!'],
            ],
            17 => [
                ['🥁', 'Blech + Schlagwerk — Dämpfer nicht vergessen'],
            ],
            19 => [
                ['📋', 'Tagesordnung wird per E-Mail verschickt'],
                ['☕', 'Kaffee & Kekse vorhanden'],
                ['⏱️', 'Bitte pünktlich — straffer Zeitplan'],
            ],
            20 => [
                ['🌍', 'IYSO-Probe — nur für angemeldete Teilnehmer'],
                ['📝', 'Programmhefte zum Korrekturlesen mitbringen'],
            ],
            22 => [
                ['🏡', 'Probenwochenende im Heiligenhof Bad Kissingen'],
                ['🧳', 'Bitte eigene Bettwäsche / Schlafsack mitbringen'],
                ['🍕', 'Mittagessen wird gestellt — Allergien bitte vorher melden'],
                ['📝', 'Notenständer mitbringen!'],
                ['🎶', 'Intensive Probenphase — alle Stimmen vorbereitet haben'],
            ],
            23 => [
                ['👔', 'Konzertkleidung zur GP mitbringen!'],
                ['⚠️', 'HfM: Eingang über den Innenhof'],
                ['🅿️', 'Parken: Tiefgarage Residenzplatz (5 Min Fußweg)'],
            ],
            24 => [
                ['🎉', 'Universitätskonzert — Konzertbeginn 15:00 Uhr'],
                ['📸', 'Gruppenfoto nach dem Konzert auf der Treppe'],
                ['🍾', 'After-Concert-Party im Foyer — alle eingeladen!'],
                ['📋', 'ECTS-Scheine werden nach dem Konzert verteilt'],
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
            'Arzttermin',
            'Orchesterfahrt Schulorchester',
            '',
            '',
            '',
            '',
            '',
            '',
        ];

        // Past sinfonie rehearsals (indices 0-5): high response rate
        for ($rIdx = 0; $rIdx <= 5; $rIdx++) {
            $rId = $rehearsalIdMap[$rIdx];

            foreach ($sinfonieUserIds as $uIdx => $userId) {
                if (rand(1, 100) <= 15) continue;

                $roll = rand(1, 100);
                if ($roll <= 65) {
                    $status = 'yes';
                } elseif ($roll <= 85) {
                    $status = 'no';
                } else {
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

        // Near-future sinfonie rehearsals (indices 6-9): sparser
        for ($rIdx = 6; $rIdx <= 9; $rIdx++) {
            $rId = $rehearsalIdMap[$rIdx];

            foreach ($sinfonieUserIds as $userId) {
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

        // Kammer rehearsals (indices 13-14)
        for ($rIdx = 13; $rIdx <= 14; $rIdx++) {
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

        // Far-future tutti rehearsals (indices 15, 16, 17, 18, 22, 23, 24)
        foreach ([15, 16, 17, 18, 22, 23, 24] as $rIdx) {
            $rId = $rehearsalIdMap[$rIdx];

            foreach ($sinfonieUserIds as $userId) {
                if (rand(1, 100) <= 55) continue;

                $roll = rand(1, 100);
                if ($roll <= 50) {
                    $status = 'yes';
                } elseif ($roll <= 80) {
                    $status = 'no';
                } else {
                    continue;
                }

                $note = ($status === 'no' && rand(1, 100) <= 30) ? $notePool[array_rand($notePool)] : '';

                $stmt = $conn->prepare("INSERT INTO user_promises (user_id, rehearsal_id, status, note) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('iiss', $userId, $rId, $status, $note);
                $stmt->execute();
                $stmt->close();
                $counts['promises']++;
            }
        }

        // Stimmführungs-Besprechung (index 19): only section_leader users
        $sfRId = $rehearsalIdMap[19];
        foreach ($sinfonieUserIds as $userId) {
            $hasSfRole = $conn->query(
                "SELECT 1 FROM user_orchestra_roles uor
                 JOIN roles r ON uor.role_id = r.id
                 WHERE uor.user_orchestra_id IN (
                     SELECT id FROM user_orchestras WHERE user_id = {$userId} AND orchestra_id = {$sinfonieId}
                 ) AND r.id = {$roleIdMap[$sinfonieId]['section_leader']} LIMIT 1"
            );
            if (!$hasSfRole || $hasSfRole->num_rows === 0) continue;

            if (rand(1, 100) <= 30) continue;
            $status = (rand(1, 100) <= 70) ? 'yes' : 'no';

            $stmt = $conn->prepare("INSERT INTO user_promises (user_id, rehearsal_id, status, note) VALUES (?, ?, ?, '')");
            $stmt->bind_param('iis', $userId, $sfRId, $status);
            $stmt->execute();
            $stmt->close();
            $counts['promises']++;
        }

        // IYSO far-future rehearsals (indices 20, 21): only project-role users
        foreach ([10, 11, 12, 20, 21] as $rIdx) {
            $rId = $rehearsalIdMap[$rIdx];
            foreach ($sinfonieUserIds as $userId) {
                $hasProjectRole = $conn->query(
                    "SELECT 1 FROM user_orchestra_roles uor
                     JOIN roles r ON uor.role_id = r.id
                     WHERE uor.user_orchestra_id IN (
                         SELECT id FROM user_orchestras WHERE user_id = {$userId} AND orchestra_id = {$sinfonieId}
                     ) AND r.id = {$roleIdMap[$sinfonieId]['project']} LIMIT 1"
                );
                if (!$hasProjectRole || $hasProjectRole->num_rows === 0) continue;

                if (rand(1, 100) <= 45) continue;
                $roll = rand(1, 100);
                if ($roll <= 55) {
                    $status = 'yes';
                } elseif ($roll <= 85) {
                    $status = 'no';
                } else {
                    continue;
                }

                $note = ($status === 'no' && rand(1, 100) <= 30) ? $notePool[array_rand($notePool)] : '';

                $stmt = $conn->prepare("INSERT INTO user_promises (user_id, rehearsal_id, status, note) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('iiss', $userId, $rId, $status, $note);
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
        $summary[] = "🎭 4 Roles: Leitung, Mitglied★, Stimmführung, IYSO 2026";
        $summary[] = "👥 {$counts['users']} Users (full symphony orchestra, 3 inactive)";
        $summary[] = "📅 {$counts['rehearsals']} Rehearsals (past + future, incl. IYSO & Stimmführungs-Besprechung)";
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
                    ['email' => 'harmonia-admin@probenplaner.local', 'password' => 'testadmin',                'role' => 'Org-Admin'],
                    ['email' => 'dirigent@test.local',               'password' => 'dirigent@test.local',       'role' => 'Leitung (Sinfonie)'],
                    ['email' => 'stimmfuehrer@test.local',           'password' => 'stimmfuehrer@test.local',   'role' => 'Stimmführung (V1)'],
                    ['email' => 'jan.schmidt@test.local',            'password' => 'jan.schmidt@test.local',    'role' => 'Mitglied only'],
                    ['email' => 'nina.berg@test.local',              'password' => 'nina.berg@test.local',      'role' => 'Mitglied + IYSO 2026'],
                    ['email' => 'clara.roth@test.local',             'password' => 'clara.roth@test.local',     'role' => 'Mitglied (Cello)'],
                    ['email' => 'kammer.dirigent@test.local',        'password' => 'kammer.dirigent@test.local', 'role' => 'Leitung (Kammer)'],
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
