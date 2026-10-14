<?php

namespace App\Core;

/**
 * Picks a member's section (Stimme/Register) for the free-text instrument from JOinJMD.
 *
 * Only leaves of the orchestra's section config count, because user_orchestras.type must be
 * a leaf (otherwise the member sees no rehearsals). A leaf matches a name if its id,
 * display_name, plural or one of its aliases is the same after normalize().
 */
class SectionMatcher
{
    /**
     * Words that name an instrument but no section of the default config. Not aliases in
     * orchestra_groups.php: an alias resolves to exactly one group (GroupManager::resolveAlias),
     * "Geige" means both violin sections.
     */
    private const SYNONYMS = ['geige' => 'violine', 'geigen' => 'violine'];

    /**
     * GroupManager for an orchestra row: its own section_config, otherwise the default config.
     */
    public static function groupsFor(array $orchestra): GroupManager
    {
        $custom = $orchestra['section_config'] ?? null;
        if (is_string($custom)) {
            $custom = json_decode($custom, true);
        }

        return GroupManager::fromConfig(is_array($custom) && !empty($custom) ? $custom : GroupManager::getDefaultConfig());
    }

    /**
     * @return array{0: ?string, 1: bool} [leaf id, exact]. Exact if exactly one leaf matches the
     *         instrument, or else exactly one matches instrument2. Otherwise the best guess: the
     *         first of several matching leaves, the most similar leaf, or (no input) the first
     *         leaf. The leaf id is null only if the config has no leaves.
     */
    public static function match(GroupManager $groups, ?string $instrument, ?string $instrument2 = null): array
    {
        // Leaf id => normalized names, in config order
        $leaves = [];
        foreach (self::leafIds($groups) as $leafId) {
            $group = $groups->getGroup($leafId) ?? [];
            $names = [$leafId, $group['display_name'] ?? '', $group['plural'] ?? ''];
            if (!empty($group['aliases']) && is_array($group['aliases'])) {
                $names = array_merge($names, $group['aliases']);
            }
            $names = array_map(fn($name) => is_scalar($name) ? self::normalize((string)$name) : '', $names);
            $leaves[$leafId] = array_values(array_unique(array_filter($names, 'strlen')));
        }
        if (empty($leaves)) {
            return [null, false];
        }

        $candidates = [];
        foreach ([$instrument, $instrument2] as $value) {
            $candidate = self::normalize($value);
            if ($candidate !== '') {
                $candidates[] = self::hits($leaves, $candidate) ? $candidate : self::replaceSynonyms($candidate);
            }
        }

        $guess = null;
        foreach ($candidates as $candidate) {
            $hits = self::hits($leaves, $candidate);
            if (count($hits) === 1) {
                return [$hits[0], true];
            }
            $guess = $guess ?? ($hits[0] ?? null);
        }
        if ($guess !== null) {
            return [$guess, false];
        }

        $best = array_key_first($leaves);
        $bestScore = -1.0;
        foreach ($candidates as $candidate) {
            foreach ($leaves as $leafId => $names) {
                foreach ($names as $name) {
                    $score = self::similarity($candidate, $name);
                    if ($score > $bestScore) {
                        $best = $leafId;
                        $bestScore = $score;
                    }
                }
            }
        }

        return [$best, false];
    }

    /**
     * @return string[] The sections a member can have (leaves of the config), in config order
     */
    public static function leafIds(GroupManager $groups): array
    {
        $leafIds = [];
        foreach ($groups->getFlattenedSections() as $ids) {
            foreach ($ids as $id) {
                $leafIds[] = (string)$id;
            }
        }
        return $leafIds;
    }

    /**
     * Lowercase, '_' and '-' as spaces, whitespace collapsed and trimmed.
     */
    public static function normalize(?string $name): string
    {
        $name = str_replace(['_', '-'], ' ', mb_strtolower((string)$name, 'UTF-8'));
        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    /**
     * @return string[] Ids of the leaves with this normalized name, in config order
     */
    private static function hits(array $leaves, string $candidate): array
    {
        return array_keys(array_filter($leaves, fn($names) => in_array($candidate, $names, true)));
    }

    private static function replaceSynonyms(string $candidate): string
    {
        return implode(' ', array_map(fn($word) => self::SYNONYMS[$word] ?? $word, explode(' ', $candidate)));
    }

    /**
     * One name containing the other ("violine" / "violine 1") beats any similar_text() percentage.
     */
    private static function similarity(string $a, string $b): float
    {
        if (strpos($b, $a) !== false || strpos($a, $b) !== false) {
            return 100 + min(strlen($a), strlen($b)) / max(strlen($a), strlen($b));
        }
        similar_text($a, $b, $percent);
        return $percent;
    }
}
