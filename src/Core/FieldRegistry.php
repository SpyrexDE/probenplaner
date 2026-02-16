<?php

namespace App\Core;

/**
 * Central registry of all editable field definitions.
 *
 * To add a new setting: add a migration + one array entry here. No controller/view changes needed.
 */
class FieldRegistry
{
    private static array $fields = [];
    private static bool $initialized = false;

    private static function init(): void
    {
        if (self::$initialized) return;
        self::$initialized = true;

        self::$fields = [
            'orchestra' => self::orchestraFields(),
            'user' => self::userFields(),
            'rehearsal' => self::rehearsalFields(),
        ];
    }

    // ── Public API ────────────────────────────────────────────────

    /** Get all field definitions for an entity. */
    public static function getFields(string $entity): array
    {
        self::init();
        return self::$fields[$entity] ?? [];
    }

    /** Get a single field definition. */
    public static function getField(string $entity, string $name): ?array
    {
        $fields = self::getFields($entity);
        foreach ($fields as $field) {
            if ($field['name'] === $name) return $field;
        }
        return null;
    }

    /** Get section groups for an entity (ordered). */
    public static function getGroups(string $entity): array
    {
        self::init();
        $groupDefs = [
            'orchestra' => [
                'basic'       => ['label' => 'Orchester bearbeiten', 'icon' => 'cog', 'iconBg' => 'blue'],
                'security'    => ['label' => 'Zugang & Sicherheit', 'icon' => 'shield', 'iconBg' => 'yellow'],
                'permissions' => ['label' => 'Berechtigungen', 'icon' => 'users-cog', 'iconBg' => 'green'],
                'features'    => ['label' => 'Features', 'icon' => 'flask', 'iconBg' => 'purple'],
            ],
            'user' => [
                'theme'    => ['label' => 'Design-Theme', 'icon' => 'palette', 'iconBg' => 'purple'],
                'profile'  => ['label' => 'Profil bearbeiten', 'icon' => 'edit', 'iconBg' => 'blue'],
                'password' => ['label' => 'Passwort', 'icon' => 'lock', 'iconBg' => 'yellow'],
            ],
            'rehearsal' => [
                'timing'  => ['label' => 'Datum & Uhrzeit', 'icon' => 'clock', 'iconBg' => 'blue'],
                'details' => ['label' => 'Details', 'icon' => 'info-circle', 'iconBg' => 'green'],
                'groups'  => ['label' => 'Besetzung', 'icon' => 'users', 'iconBg' => 'purple'],
            ],
        ];
        return $groupDefs[$entity] ?? [];
    }

    /** Get just the allowed field names (for Model whitelist). */
    public static function getAllowedFieldNames(string $entity): array
    {
        return array_column(self::getFields($entity), 'name');
    }

    /** Get the model class name for an entity. */
    public static function getModelClass(string $entity): ?string
    {
        $map = [
            'orchestra' => \App\Models\Orchestra::class,
            'user'      => \App\Models\User::class,
            'rehearsal' => \App\Models\Rehearsal::class,
        ];
        return $map[$entity] ?? null;
    }

    /** Get the DB table name for an entity. */
    public static function getTable(string $entity): ?string
    {
        $map = [
            'orchestra' => 'orchestras',
            'user'      => 'users',
            'rehearsal' => 'rehearsals',
        ];
        return $map[$entity] ?? null;
    }

    // ── Field Definitions ──────────────────────────────────────────

    private static function orchestraFields(): array
    {
        return [
            [
                'name'       => 'name',
                'type'       => 'text',
                'label'      => 'Orchestername',
                'icon'       => 'music',
                'group'      => 'basic',
                'validation' => ['required', 'min:1', 'max:100'],
                'save'       => 'auto',
                'permission' => 'conductor',
            ],
            [
                'name'       => 'token',
                'type'       => 'secret',
                'label'      => 'Orchester-Token',
                'description' => 'Für Mitglieder-Registrierung',
                'icon'       => 'key',
                'group'      => 'security',
                'validation' => ['required', 'pattern:/^[a-zA-Z0-9_-]+$/'],
                'save'       => 'auto',
                'permission' => 'conductor',
            ],
            [
                'name'       => 'leader_pw',
                'type'       => 'secret',
                'label'      => 'Stimmführer-Passwort',
                'description' => 'Für Stimmführer-Berechtigungen',
                'icon'       => 'shield',
                'group'      => 'security',
                'validation' => ['required', 'min:4'],
                'save'       => 'auto',
                'permission' => 'conductor',
            ],
            [
                'name'       => 'leaders_can_view_all_sections',
                'type'       => 'toggle',
                'label'      => 'Stimmführer dürfen alle Register sehen',
                'description' => 'Stimmführer können alle Register in der Rückmeldungsübersicht einsehen. Das Proben-Insights-Feature ist davon ausgenommen.',
                'group'      => 'permissions',
                'default'    => false,
                'save'       => 'auto',
                'permission' => 'conductor',
            ],
            [
                'name'       => 'force_decline_reason',
                'type'       => 'toggle',
                'label'      => 'Begründung bei Absage erzwingen',
                'description' => 'Wenn aktiviert, müssen Mitglieder einen Grund angeben, wenn sie eine Probe absagen.',
                'group'      => 'permissions',
                'default'    => false,
                'save'       => 'auto',
                'permission' => 'conductor',
            ],
            [
                'name'       => 'show_rehearsal_insights',
                'type'       => 'toggle',
                'label'      => 'Proben-Insights anzeigen (Beta)',
                'description' => 'Aktiviert erweiterte Analyse-Features für Proben-Rückmeldungen. Hilfreich für effektivere Proben-Planung.',
                'group'      => 'features',
                'default'    => false,
                'save'       => 'auto',
                'permission' => 'conductor',
            ],
        ];
    }

    private static function userFields(): array
    {
        return [
            [
                'name'       => 'theme',
                'type'       => 'theme',
                'label'      => 'Design-Theme',
                'group'      => 'theme',
                'save'       => 'auto',
                'permission' => 'member',
            ],
            [
                'name'       => 'username',
                'type'       => 'text',
                'label'      => 'Nutzername',
                'icon'       => 'user',
                'group'      => 'profile',
                'validation' => ['required', 'min:3', 'max:20'],
                'save'       => 'auto',
                'permission' => 'member',
            ],
            [
                'name'       => 'group_type',
                'type'       => 'select',
                'label'      => 'Instrument / Stimmgruppe',
                'icon'       => 'music',
                'group'      => 'orchestra',
                'validation' => ['required'],
                'save'       => 'auto',
                'permission' => 'member',
            ],
            [
                'name'       => 'small_group',
                'type'       => 'toggle',
                'label'      => 'Kleine Besetzung',
                'group'      => 'orchestra',
                'save'       => 'auto',
                'permission' => 'member',
            ],
            [
                'name'       => 'group_leader',
                'type'       => 'toggle',
                'label'      => 'Stimmführung',
                'group'      => 'orchestra',
                'save'       => 'auto',
                'permission' => 'member',
            ],
        ];
    }

    private static function rehearsalFields(): array
    {
        return [
            [
                'name'       => 'start',
                'type'       => 'datetime',
                'label'      => 'Startzeit',
                'icon'       => 'clock',
                'group'      => 'timing',
                'validation' => ['required'],
                'save'       => 'auto',
                'permission' => 'conductor',
            ],
            [
                'name'       => 'end',
                'type'       => 'datetime',
                'label'      => 'Endzeit',
                'icon'       => 'clock',
                'group'      => 'timing',
                'validation' => ['required'],
                'save'       => 'auto',
                'permission' => 'conductor',
            ],
            [
                'name'       => 'location',
                'type'       => 'text',
                'label'      => 'Ort',
                'icon'       => 'map-marker-alt',
                'group'      => 'details',
                'save'       => 'auto',
                'permission' => 'conductor',
            ],
            [
                'name'       => 'color',
                'type'       => 'color',
                'label'      => 'Farbe',
                'icon'       => 'palette',
                'group'      => 'details',
                'save'       => 'auto',
                'permission' => 'conductor',
            ],
            [
                'name'       => 'type',
                'type'       => 'text',
                'label'      => 'Probentyp',
                'icon'       => 'tag',
                'group'      => 'details',
                'save'       => 'auto',
                'permission' => 'conductor',
            ],
            [
                'name'       => 'is_small_group',
                'type'       => 'toggle',
                'label'      => 'Kleingruppe',
                'description' => 'Ist dies eine Kleingruppen-Probe?',
                'group'      => 'details',
                'default'    => false,
                'save'       => 'auto',
                'permission' => 'conductor',
            ],
            [
                'name'       => 'infos',
                'type'       => 'json',
                'label'      => 'Infos',
                'group'      => 'details',
                'save'       => 'manual',
                'permission' => 'conductor',
            ],

        ];
    }
}
