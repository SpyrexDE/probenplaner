<?php

namespace App\Core;

/**
 * Smart Group Display Class
 * 
 * Generates intelligent, natural language descriptions of rehearsal groups
 * based on any hierarchical ensemble configuration using a mathematically
 * optimal recursive Dynamic Programming (DP) string length algorithm.
 */
class SmartGroupDisplay
{
    private GroupManager $groupManager;
    private array $language;
    
    // DP caching array
    private array $dpCache = [];

    // Store intermediate candidate lists for debugging
    private array $debugCache = [];
    private bool $debugMode = false;

    public function __construct(?array $language = null, ?GroupManager $groupManager = null)
    {
        $this->groupManager = $groupManager ?? GroupManager::getInstance();
        $this->language = $language ?? [
            'and' => 'und',
            'without' => 'ohne',
            'but' => 'aber' // Potentially unused now
        ];
    }
    
    /**
     * Enable debug mode to trace tree traversal
     */
    public function setDebugMode(bool $enabled): void
    {
        $this->debugMode = $enabled;
    }
    
    /**
     * Get debug data from last generation
     */
    public function getDebugData(): array
    {
        return $this->debugCache;
    }

    /**
     * Entry point: Generate optimal description from an array of group IDs
     */
    public function generateDescription(array $selectedGroups): string
    {
        if (empty($selectedGroups)) {
            return '';
        }

        // 1. Resolve selected groups to a flat list of actual instrument leaves
        $selectedLeaves = [];
        foreach ($selectedGroups as $groupId) {
            $selectedLeaves = array_merge($selectedLeaves, $this->getAllInstrumentsInGroup($groupId));
        }
        $selectedLeaves = array_unique($selectedLeaves);

        if (empty($selectedLeaves)) {
            return '';
        }

        // 2. Fetch all configuration root nodes
        $roots = $this->groupManager->getRootNodes();
        $rootIds = array_keys($roots);
        
        // Ensure rootIds exist
        if (empty($rootIds)) {
            return $this->generateSimpleListFromSelectedLeaves($selectedLeaves);
        }

        $this->dpCache = [];
        $this->debugCache = [];

        // 3. Initiate recursive solving using the root level
        $optimalParts = $this->solve($rootIds, $selectedLeaves, true);

        if ($optimalParts !== null) {
            return $this->formatListWithUnd($optimalParts, true);
        }

        // 4. Ultimate fallback (should mathematically never be reached if config exists)
        return $this->generateSimpleListFromSelectedLeaves($selectedLeaves);
    }
    
    /**
     * Secondary fallback to format leaves if config is broken
     */
    private function generateSimpleListFromSelectedLeaves(array $leaves): string
    {
        $names = array_map(fn($l) => $this->groupManager->getDisplayName($l), $leaves);
        return $this->formatListWithUnd($names, false);
    }

    /**
     * Core DP Recursion Solver
     * 
     * @param string[] $nodeIds Nodes available to partition the selected leaves
     * @param string[] $selectedLeaves Target leaves that must be described
     * @param bool $allowExclusions Whether we can use subtractive definitions (e.g. "ohne X")
     * @return string[]|null Array of string components, or null if mathematically impossible
     */
    private function solve(array $nodeIds, array $selectedLeaves, bool $allowExclusions): ?array
    {
        $nodeIds = array_values(array_unique($nodeIds));
        
        // Sort nodes by their original order in the config tree (flatMapping keys)
        $allGroups = array_keys($this->groupManager->getAllGroups());
        usort($nodeIds, function($a, $b) use ($allGroups) {
            $posA = array_search($a, $allGroups);
            $posB = array_search($b, $allGroups);
            return $posA <=> $posB;
        });
        
        // Filter nodes to ones that actively intersect with our target leaves
        $activeNodes = [];
        $activeIntersectAssoc = [];
        $allInstrumentsInActiveNodesAssoc = [];
        
        foreach ($nodeIds as $n) {
            $insts = $this->getAllInstrumentsInGroup($n);
            $intersect = array_intersect($selectedLeaves, $insts);
            if (!empty($intersect)) {
                $activeNodes[] = $n;
                foreach ($intersect as $l) $activeIntersectAssoc[$l] = true;
                foreach ($insts as $l) $allInstrumentsInActiveNodesAssoc[$l] = true;
            }
        }
        
        if (empty($activeNodes)) {
            return null;
        }

        $activeIntersect = array_keys($activeIntersectAssoc);
        $allInstrumentsInActiveNodes = array_keys($allInstrumentsInActiveNodesAssoc);
        sort($activeIntersect);
        sort($allInstrumentsInActiveNodes);

        // Prepare memoization key
        $cacheKey = implode('|', $activeNodes) . '::' . implode('|', $activeIntersect) . '::' . ($allowExclusions ? '1' : '0');
        if (isset($this->dpCache[$cacheKey])) {
            return $this->dpCache[$cacheKey];
        }

        $candidates = [];

        // CASE 1: Exact Match
        // Target leaves completely match the leaves of all active nodes
        $missingFromActive = array_diff($allInstrumentsInActiveNodes, $activeIntersect);
        if (empty($missingFromActive)) {
            $names = array_map(fn($n) => $this->groupManager->getDisplayName($n), $activeNodes);
            $this->dpCache[$cacheKey] = $names;
            return $names;
        }

        // CASE 2: Subtractive (Active Nodes 'ohne' Missing)
        if ($allowExclusions) {
            // Find positive additive description for the missing leaves using the SAME active nodes
            // Prevent nested exclusions by passing $allowExclusions = false
            $missingParts = $this->solve($activeNodes, $missingFromActive, false);
            if ($missingParts !== null) {
                // Missing parts are formatted without brackets
                $missingString = $this->formatListWithUnd($missingParts, false); 
                
                $positiveNames = array_map(fn($n) => $this->groupManager->getDisplayName($n), $activeNodes);
                $positiveString = $this->formatListWithUnd($positiveNames, false);
                
                $candidates[] = [$positiveString . ' ' . $this->language['without'] . ' ' . $missingString];
            }
        }

        // CASE 3: Additive (Decompose active nodes)
        if (count($activeNodes) > 1) {
            // Solve each node perfectly independently, then collect components
            $parts = [];
            $valid = true;
            foreach ($activeNodes as $n) {
                // The subset solver will organically filter out leaves not belonging to this node
                $subParts = $this->solve([$n], $activeIntersect, $allowExclusions);
                if ($subParts !== null) {
                    $parts = array_merge($parts, $subParts);
                } else {
                    $valid = false;
                    break;
                }
            }
            if ($valid) {
                $candidates[] = $parts;
            }
        } else {
            // Only 1 active node - decompose it strictly to its children
            $n = $activeNodes[0];
            $children = $this->groupManager->getChildren($n);
            if (!empty($children)) {
                $childIds = array_map(fn($c) => $c['id'], $children);
                $childParts = $this->solve($childIds, $activeIntersect, $allowExclusions);
                if ($childParts !== null) {
                    $candidates[] = $childParts;
                }
            }
        }

        // Log to debug array before resolving
        if ($this->debugMode) {
            $this->debugCache[$cacheKey] = $candidates;
        }

        // Mathematics dictates we simply pick the shortest representation
        $best = $this->pickBestCandidate($candidates);
        $this->dpCache[$cacheKey] = $best;
        return $best;
    }

    /**
     * Evaluates all candidates and returns the one mapping to the shortest physical string.
     */
    private function pickBestCandidate(array $candidates): ?array
    {
        if (empty($candidates)) {
            return null;
        }
        
        usort($candidates, function ($a, $b) {
            // Simulate the final output string exactly as the user will see it
            $strA = $this->formatListWithUnd($a, true);
            $strB = $this->formatListWithUnd($b, true);
            
            // For mathematical fairness, we do not penalize brackets that are added for visual flavor
            $cleanA = str_replace(['(', ')'], '', $strA);
            $cleanB = str_replace(['(', ')'], '', $strB);
            
            // Primary metric: Raw string length
            $scoreA = strlen($cleanA);
            $scoreB = strlen($cleanB);
            
            // Minor tie-breakers for aesthetics (only matters if length is identical)
            // 1. Penalize commas to favor cleaner structure
            $scoreA += substr_count($cleanA, ',') * 1;
            $scoreB += substr_count($cleanB, ',') * 1;
            // 2. Penalize 'ohne' to favor additive definitions where length is identical
            $scoreA += substr_count($cleanA, $this->language['without']) * 1;
            $scoreB += substr_count($cleanB, $this->language['without']) * 1;

            return $scoreA <=> $scoreB;
        });
        
        return $candidates[0];
    }
    
    /**
     * Takes an array of components, optionally formats brackets around exclusions, 
     * and joins them with comma + "und".
     */
    private function formatListWithUnd(array $parts, bool $applyBrackets = false): string
    {
        if (empty($parts)) {
            return '';
        }
        
        if (count($parts) === 1) {
            // If only 1 part, do not inject brackets for visual reasons (e.g., "Tutti ohne Oboe" directly is fine)
            return reset($parts);
        }

        $formatted = [];
        $withoutWord = ' ' . $this->language['without'] . ' ';
        
        foreach ($parts as $part) {
            if ($applyBrackets && strpos($part, $withoutWord) !== false) {
                // "Holzbläser ohne Oboe" -> "Holzbläser (ohne Oboe)"
                $exploded = explode($withoutWord, $part, 2);
                $formatted[] = $exploded[0] . ' (' . trim($withoutWord . $exploded[1]) . ')';
            } else {
                $formatted[] = $part;
            }
        }

        $last = array_pop($formatted);
        
        if (empty($formatted)) {
            return $last;
        }
        
        return implode(', ', $formatted) . ' ' . $this->language['and'] . ' ' . $last;
    }

    /**
     * Recursively fetch flat leaf instruments (identical to original logic)
     */
    public function getAllInstrumentsInGroup(string $groupId): array
    {
        $group = $this->groupManager->getGroup($groupId);
        if (!$group) {
            return [];
        }

        if (($group['type'] ?? '') === 'instrument') {
            return [$groupId];
        }

        $children = $this->groupManager->getChildren($groupId);
        
        // If a section has no children, it acts as its own leaf instrument
        if (empty($children)) {
            return [$groupId];
        }

        $instruments = [];
        foreach ($children as $child) {
            $instruments = array_merge($instruments, $this->getAllInstrumentsInGroup($child['id']));
        }
        
        return array_unique($instruments);
    }
    
    /**
     * Fallback formatter for arrays (simulating identical functionality to old `generateSimpleList`)
     */
    public function generateSimpleList(array $groupIds): string
    {
        $names = array_map(fn($id) => $this->groupManager->getDisplayName($id), $groupIds);
        return $this->formatListWithUnd($names, false);
    }
}
