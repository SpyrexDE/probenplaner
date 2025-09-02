<?php
namespace App\Core;

/**
 * Rehearsal Group Processor
 * 
 * Handles the processing and validation of group data from rehearsal forms.
 * Fixes issues with duplicate tutti handling, case sensitivity, and ensures
 * clean group arrays for the smart display system.
 */
class RehearsalGroupProcessor
{
    /**
     * Process groups from form submission - SIMPLE VERSION
     * Store exactly what the user selected, no "optimization"
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
            
            // Ignore legacy exclusion markers from old forms
            if (strpos($group, '!EXCLUDED!') === 0 || strpos($group, '!') === 0) {
                continue;
            }
            
            // Find correct group ID from config (case-insensitive)
            $foundGroup = self::findGroupId($group, $groupManager);
            if ($foundGroup && $foundGroup !== $rehearsalType) {
                $cleanedGroups[] = $foundGroup;
            }
        }
        
        // Remove duplicates, preserve order
        $cleanedGroups = array_values(array_unique($cleanedGroups));
        
        // If 'tutti' is selected, just return ['tutti'] (ignore all the child selections)
        if (in_array('tutti', $cleanedGroups)) {
            return ['tutti'];
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
            if (strtolower($groupId) === strtolower($searchGroup) || 
                strtolower($groupData['display_name'] ?? '') === strtolower($searchGroup)) {
                return $groupId;
            }
        }
        return null;
    }
    
    // Removed legacy exclusion-root optimization; we only return positive selections now
    
    // Removed optimization - we store exactly what user selects
    
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
            $errors[] = 'At least one group must be selected';
        }
        
        return $errors;
    }
    
    /**
     * Generate form data for edit forms - SIMPLE VERSION
     * Return exactly what was stored, no expansion
     * 
     * @param array $rehearsalGroups Groups from database
     * @return array Form data array
     */
    public static function generateFormData(array $rehearsalGroups): array
    {
        if (empty($rehearsalGroups)) {
            return ['groups' => []];
        }
        
        // Handle legacy format with exclusions (expand to positive selections)
        if (count($rehearsalGroups) > 1 && strpos($rehearsalGroups[0], '!') !== 0) {
            $rootGroup = $rehearsalGroups[0];
            $excludedGroups = [];
            
            foreach (array_slice($rehearsalGroups, 1) as $group) {
                if (strpos($group, '!') === 0) {
                    $excludedGroups[] = substr($group, 1);
                }
            }
            
            if (!empty($excludedGroups)) {
                // Legacy format: expand to individual selections
                $groupManager = new GroupManager();
                $allRootGroups = self::getAllGroupsForRoot($rootGroup, $groupManager);
                $formGroups = array_values(array_diff($allRootGroups, array_merge($excludedGroups, [$rootGroup])));
                
                return [
                    'groups' => $formGroups
                ];
            }
        }
        
        // Otherwise, return exactly what was stored
        return [
            'groups' => $rehearsalGroups
        ];
    }
    
    /**
     * Get all individual checkboxes that should be checked for a root
     * Returns sections and instruments, but prioritizes individual instruments for precise control
     */
    private static function getAllGroupsForRoot(string $rootId, GroupManager $groupManager): array
    {
        $allGroups = [];
        
        if ($rootId === 'tutti') {
            // For tutti, include all sections and instruments
            $allSections = $groupManager->getAllSections();
            foreach ($allSections as $sectionId => $sectionData) {
                $allGroups[] = $sectionId;
            }
            
            // Also include all instruments
            $allInstruments = $groupManager->getAllInstruments();
            foreach ($allInstruments as $instrumentId => $instrumentData) {
                $allGroups[] = $instrumentId;
            }
            
            // Include the tutti checkbox itself
            $allGroups[] = 'tutti';
        } else {
            // For other roots, get the immediate children only (sections and instruments)
            $descendants = $groupManager->getDescendants($rootId);
            
            foreach ($descendants as $descendant) {
                $allGroups[] = $descendant['id'];
            }
        }
        
        return array_unique($allGroups);
    }
    
    /**
     * Handle explicit tutti selection
     */
    private static function handleExplicitTutti(array $groups, GroupManager $groupManager): array
    {
        // Deprecated: explicit tutti handling is simplified via isFullTuttiCoverage
        if (count($groups) === 1 && $groups[0] === 'tutti') {
            return ['tutti'];
        }
        return $groups;
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
