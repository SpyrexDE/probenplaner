<?php

namespace App\Core;

/**
 * Rehearsal Group Processor
 * 
 * Handles the processing and validation of group data from rehearsal forms.
 * Ensures clean, deduplicated group arrays for the smart display system.
 */
class RehearsalGroupProcessor
{
    /**
     * Process groups from form submission
     * Stores the specific groups selected by the user.
     * 
     * @param array $postData POST data from form
     * @return array Cleaned array of group IDs
     */
    public static function processGroups(array $postData): array
    {
        $groupsSelected = $postData['groups'] ?? [];
        $rehearsalType = $postData['rehearsal_type'] ?? '';

        if (empty($groupsSelected)) {
            return [];
        }

        $groupManager = new GroupManager();

        // Store exactly what was checked (cleaned)
        $cleanedGroups = [];

        foreach ($groupsSelected as $group) {
            if (empty($group)) continue;

            $group = trim($group);


            // Find correct group ID from config (case-insensitive)
            $foundGroup = self::findGroupId($group, $groupManager);
            if ($foundGroup && $foundGroup !== $rehearsalType) {
                $cleanedGroups[] = $foundGroup;
            }
        }

        // Remove duplicates, preserve order
        $cleanedGroups = array_values(array_unique($cleanedGroups));

        // Check if any selected group is a root node — if so, collapse to [rootId]
        $rootNodes = $groupManager->getRootNodes();
        $rootIds = array_map(fn($r) => $r['id'], $rootNodes);
        $selectedRoots = array_intersect($cleanedGroups, $rootIds);

        // If all root nodes are selected, return all root IDs
        if (count($selectedRoots) === count($rootIds)) {
            return $rootIds;
        }

        // Remove redundant parent-child selections
        $cleanedGroups = self::removeRedundantSelections($cleanedGroups, $groupManager);

        return $cleanedGroups;
    }

    /**
     * Find the correct group ID from the configuration
     */
    private static function findGroupId(string $searchGroup, GroupManager $groupManager): ?string
    {
        foreach ($groupManager->getAllGroups() as $groupId => $groupData) {
            if (
                strtolower($groupId) === strtolower($searchGroup) ||
                strtolower($groupData['display_name'] ?? '') === strtolower($searchGroup)
            ) {
                return $groupId;
            }
        }
        return null;
    }



    /**
     * Validate that groups are not empty
     * 
     * @param array $groups Processed groups array
     * @return array Array of validation errors (empty if valid)
     */
    public static function validateGroups(array $groups): array
    {
        $errors = [];

        if (empty($groups)) {
            $errors[] = 'Es muss mindestens eine Gruppe ausgewählt werden.';
        }

        return $errors;
    }

    /**
     * Generate form data for edit forms
     * 
     * @param array $rehearsalGroups Groups from database
     * @return array Form data array
     */
    public static function generateFormData(array $rehearsalGroups): array
    {
        if (empty($rehearsalGroups)) {
            return ['groups' => []];
        }

        // Return exactly what was stored
        return [
            'groups' => $rehearsalGroups
        ];
    }

    /**
     * Get all individual checkboxes that should be checked for a root
     * Returns sections and instruments, primarily dealing with individual instruments
     */
    private static function getAllGroupsForRoot(string $rootId, GroupManager $groupManager): array
    {
        $result = [];
        $rootNodes = $groupManager->getRootNodes();
        $rootIds = array_map(fn($r) => $r['id'], $rootNodes);

        if (in_array($rootId, $rootIds)) {
            // Root node: include all sections and instruments
            foreach ($groupManager->getAllSections() as $sectionId => $sectionData) {
                $result[] = $sectionId;
            }
            foreach ($groupManager->getAllInstruments() as $instrumentId => $instrumentData) {
                $result[] = $instrumentId;
            }
            $result[] = $rootId;
        } else {
            foreach ($groupManager->getDescendants($rootId) as $descendant) {
                $result[] = $descendant['id'];
            }
        }

        return array_unique($result);
    }



    /**
     * Remove redundant parent-child selections
     * If both a parent and all its children are selected, keep only the parent
     * BUT preserve individual instruments for smart display algorithm
     */
    private static function removeRedundantSelections(array $groups, GroupManager $groupManager): array
    {
        $filtered = [];

        foreach ($groups as $group) {
            $shouldInclude = true;

            // Check if this group is a child of any other selected group
            foreach ($groups as $otherGroup) {
                if ($group === $otherGroup) continue;

                // Get children of the other group
                $children = $groupManager->getChildren($otherGroup);
                $childIds = array_map(fn($child) => $child['id'], $children);

                // If this group is a child of the other group, don't include it
                // BUT preserve individual instruments (type: 'instrument') for smart display
                if (in_array($group, $childIds)) {
                    $groupData = $groupManager->getGroup($group);
                    $groupType = $groupData['type'] ?? '';

                    // Preserve individual instruments - they're needed for smart display algorithm
                    if ($groupType === 'instrument') {
                        $shouldInclude = true;
                        break;
                    } else {
                        $shouldInclude = false;
                        break;
                    }
                }
            }

            if ($shouldInclude) {
                $filtered[] = $group;
            }
        }

        return $filtered;
    }
}
