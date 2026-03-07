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
                'security'    => ['label' => 'Zugang & Sicherheit', 'icon' => 'shield', 'iconBg' => 'yellow'],
                'permissions' => ['label' => 'Berechtigungen', 'icon' => 'users-cog', 'iconBg' => 'green'],
                'features'    => ['label' => 'Features', 'icon' => 'flask', 'iconBg' => 'purple'],
                'structure'   => ['label' => 'Registerstruktur', 'icon' => 'sitemap', 'iconBg' => 'indigo'],
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
        return match ($entity) {
            'orchestra' => 'orchestras',
            'user' => 'users',
            'rehearsal' => 'rehearsals',
            default => throw new \InvalidArgumentException("Unknown entity: {$entity}"),
        };
    }

    /**
     * Get immediate-parent → instrument IDs map via GroupManager.
     *
     * @return array<string, string[]> Ordered parent ID → leaf instrument IDs
     */
    public static function getSections(): array
    {
        return \App\Core\GroupManager::getInstance()->getFlattenedSections();
    }

    // ── Field Definitions ──────────────────────────────────────────

    private static function orchestraFields(): array
    {
        return [
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
                'name'        => 'allow_attendance_reset',
                'type'        => 'toggle',
                'label'       => 'Rückmeldung zurücknehmen erlauben',
                'description' => 'Wenn aktiviert, können Mitglieder ihre Zu-/Absage per Gedrückthalten zurücksetzen.',
                'group'       => 'permissions',
                'default'     => true,
                'save'        => 'auto',
                'permission'  => 'conductor',
            ],
            [
                'name'        => 'allow_past_edit',
                'type'        => 'toggle',
                'label'       => 'Nachträgliche Änderung erlauben',
                'description' => 'Wenn aktiviert, können Mitglieder ihre Zu-/Absagen und Kommentare für bereits vergangene Proben noch ändern.',
                'group'       => 'permissions',
                'default'     => true,
                'save'        => 'auto',
                'permission'  => 'conductor',
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
            [
                'name'       => 'section_config',
                'type'       => 'section_config',
                'label'      => 'Registerstruktur anpassen',
                'description' => 'Passe die Sektionen, Untergruppen und Instrumente deines Ensembles an.',
                'group'      => 'structure',
                'save'       => 'manual',
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
                'name'       => 'email',
                'type'       => 'text',
                'label'      => 'E-Mail',
                'icon'       => 'envelope',
                'group'      => 'profile',
                'validation' => ['required'],
                'save'       => 'auto',
                'permission' => 'member',
            ],
            [
                'name'       => 'display_name',
                'type'       => 'text',
                'label'      => 'Anzeigename',
                'icon'       => 'user',
                'group'      => 'profile',
                'validation' => ['required', 'min:2', 'max:100'],
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
                'name'       => 'role_ids',
                'type'       => 'json',
                'label'      => 'Rollen',
                'icon'       => 'shield-alt',
                'group'      => 'orchestra',
                'validation' => [],
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
