<?php
namespace App\Core;

/**
 * Smart Group Display Class
 * 
 * Generates intelligent, natural language descriptions of rehearsal groups
 * based on the hierarchical orchestra configuration. Works dynamically with
 * any group hierarchy and can generate descriptions like:
 * - "Tutti"
 * - "Tutti ohne Schlagwerk" 
 * - "Streicher und Blechbläser"
 * - "Tutti ohne Streicher aber Violine 1"
 * - "Holzbläser ohne Fagott"
 */
class SmartGroupDisplay
{
    private GroupManager $groupManager;
    private array $language;
    
    public function __construct(?array $language = null)
    {
        $this->groupManager = new GroupManager();
        $this->language = $language ?? [
            'and' => 'und',
            'without' => 'ohne',
            'but' => 'aber'
        ];
    }
    
    /**
     * Generate smart description for selected groups
     * 
     * @param array $selectedGroups Array of selected group IDs
     * @return string Natural language description
     */
    public function generateDescription(array $selectedGroups): string
    {
        if (empty($selectedGroups)) {
            return '';
        }
        
        // Handle special root-with-exclusions format (generic for any root)
        if (count($selectedGroups) > 1 && strpos($selectedGroups[0], '!') !== 0) {
            $rootGroup = $selectedGroups[0];
            $excludedGroups = [];
            $regularGroups = [];
            
            foreach (array_slice($selectedGroups, 1) as $group) {
                if (strpos($group, '!') === 0) {
                    // This is an excluded group (missing from root)
                    $excludedGroups[] = substr($group, 1); // Remove the '!' prefix
                } else {
                    $regularGroups[] = $group;
                }
            }
            
            // If we have exclusions, generate "Root ohne X"
            if (!empty($excludedGroups)) {
                $rootDisplayName = $this->groupManager->getDisplayName($rootGroup);
                $description = $rootDisplayName;
                
                if (!empty($excludedGroups)) {
                    $description .= ' ' . $this->language['without'] . ' ';
                    $description .= $this->generateSimpleList($excludedGroups);
                }
                if (!empty($regularGroups)) {
                    $description .= ' ' . $this->language['but'] . ' ';
                    $description .= $this->generateSimpleList($regularGroups);
                }
                return $description;
            }
        }
        
        // Handle single root group
        if (count($selectedGroups) === 1) {
            $singleGroup = $selectedGroups[0];
            $displayName = $this->groupManager->getDisplayName($singleGroup);
            return $displayName;
        }
        
        // Normalize input - remove duplicates and sort
        $selectedGroups = array_unique($selectedGroups);
        sort($selectedGroups);
        
        // NEW: Find the most concise representation using tree compression
        $compressedDescription = $this->compressTreeRepresentation($selectedGroups);
        if ($compressedDescription) {
            return $compressedDescription;
        }
        
        // Fallback to simple list if no compression was found
        return $this->generateSimpleList($selectedGroups);
    }
    
    /**
     * Find possible roots that could be used to describe the selection
     */
    private function findPossibleRoots(array $selectedGroups): array
    {
        $roots = [];
        $config = $this->groupManager->getConfig();
        
        // Check all groups in config as potential roots
        $this->findRootsRecursive($config, $selectedGroups, $roots);
        
        // Sort roots by hierarchy level (deeper first, then by coverage)
        usort($roots, function($a, $b) use ($selectedGroups) {
            $coverageA = $this->calculateCoverage($selectedGroups, $a);
            $coverageB = $this->calculateCoverage($selectedGroups, $b);
            
            // Prefer higher coverage, then prefer deeper in hierarchy
            if ($coverageA !== $coverageB) {
                return $coverageB <=> $coverageA; // Higher coverage first
            }
            
            $levelA = count($this->groupManager->getAncestors($a));
            $levelB = count($this->groupManager->getAncestors($b));
            return $levelB <=> $levelA; // Deeper level first
        });
        
        return $roots;
    }
    
    /**
     * Recursively find potential roots
     */
    private function findRootsRecursive(array $groups, array $selectedGroups, array &$roots): void
    {
        foreach ($groups as $key => $group) {
            if (!is_array($group) || !isset($group['id'])) {
                continue;
            }
            
            $groupId = $group['id'];
            $coverage = $this->calculateCoverage($selectedGroups, $groupId);
            
            // Only consider groups with at least 50% coverage of selected groups
            if ($coverage >= 0.5) {
                $roots[] = $groupId;
            }
            
            // Recurse into children
            if (isset($group['children'])) {
                $this->findRootsRecursive($group['children'], $selectedGroups, $roots);
            }
        }
    }
    
    /**
     * Calculate how much of the selected groups are covered by this root
     */
    private function calculateCoverage(array $selectedGroups, string $rootId): float
    {
        $rootInstruments = $this->getAllInstrumentsInGroup($rootId);
        $selectedInstruments = [];
        
        foreach ($selectedGroups as $groupId) {
            $selectedInstruments = array_merge($selectedInstruments, $this->getAllInstrumentsInGroup($groupId));
        }
        
        $selectedInstruments = array_unique($selectedInstruments);
        
        if (empty($selectedInstruments) || empty($rootInstruments)) {
            return 0.0;
        }
        
        $covered = array_intersect($rootInstruments, $selectedInstruments);
        return count($covered) / count($rootInstruments); // Changed: coverage as fraction of root, not selection
    }
    
    /**
     * Generate description from a specific root
     */
    private function generateFromRoot(array $selectedGroups, string $rootId): string
    {
        $rootGroup = $this->groupManager->getGroup($rootId);
        if (!$rootGroup) {
            return $this->generateSimpleList($selectedGroups);
        }
        
        $rootName = $rootGroup['display_name'] ?? $rootId;
        
        // Get all descendants of the root
        $allDescendants = $this->groupManager->getDescendants($rootId);
        $descendantIds = array_map(fn($d) => $d['id'], $allDescendants);
        $descendantIds[] = $rootId; // Include root itself
        
        // Check if all descendants are selected (full coverage)
        $selectedInRoot = array_intersect($selectedGroups, $descendantIds);
        $allInstrumentsInRoot = $this->getAllInstrumentsInGroup($rootId);
        $selectedInstruments = [];
        
        foreach ($selectedInRoot as $groupId) {
            $selectedInstruments = array_merge($selectedInstruments, $this->getAllInstrumentsInGroup($groupId));
        }
        $selectedInstruments = array_unique($selectedInstruments);
        
        // If we have full coverage of the root, check what's missing
        if (count($selectedInstruments) === count($allInstrumentsInRoot) && 
            array_diff($allInstrumentsInRoot, $selectedInstruments) === []) {
            
            // Check if there are groups outside this root
            $groupsOutsideRoot = array_diff($selectedGroups, $descendantIds);
            if (empty($groupsOutsideRoot)) {
                return $rootName; // Simple case: just the root name
            } else {
                // We have the full root plus other groups
                $otherGroupsText = $this->generateSimpleList($groupsOutsideRoot);
                return $rootName . ' ' . $this->language['and'] . ' ' . $otherGroupsText;
            }
        }
        
        // Check for "without" patterns
        $missingGroups = $this->findMissingGroups($rootId, $selectedGroups);
        $addedGroups = $this->findAddedGroups($rootId, $selectedGroups);
        
        if (!empty($missingGroups)) {
            $description = $rootName . ' ' . $this->language['without'] . ' ';
            $description .= $this->generateSimpleList($missingGroups);
            
            if (!empty($addedGroups)) {
                $description .= ' ' . $this->language['but'] . ' ';
                $description .= $this->generateSimpleList($addedGroups);
            }
            
            return $description;
        }
        
        // Fallback to simple list
        return $this->generateSimpleList($selectedGroups);
    }
    
    /**
     * Find groups that are missing from the root coverage
     */
    private function findMissingGroups(string $rootId, array $selectedGroups): array
    {
        $allChildrenIds = array_map(fn($child) => $child['id'], $this->groupManager->getChildren($rootId));
        $missing = [];
        
        foreach ($allChildrenIds as $childId) {
            $childInstruments = $this->getAllInstrumentsInGroup($childId);
            $selectedInstruments = [];
            
            foreach ($selectedGroups as $groupId) {
                if ($this->isGroupDescendantOf($groupId, $rootId)) {
                    $selectedInstruments = array_merge($selectedInstruments, $this->getAllInstrumentsInGroup($groupId));
                }
            }
            $selectedInstruments = array_unique($selectedInstruments);
            
            // If none of this child's instruments are selected, it's missing
            $intersection = array_intersect($childInstruments, $selectedInstruments);
            if (empty($intersection)) {
                $missing[] = $childId;
            }
        }
        
        return $missing;
    }
    
    /**
     * Find groups that were added back despite being excluded by parent
     */
    private function findAddedGroups(string $rootId, array $selectedGroups): array
    {
        $added = [];
        $missingGroups = $this->findMissingGroups($rootId, $selectedGroups);
        
        foreach ($missingGroups as $missingGroup) {
            $missingInstruments = $this->getAllInstrumentsInGroup($missingGroup);
            
            foreach ($selectedGroups as $selectedGroup) {
                if ($this->isGroupDescendantOf($selectedGroup, $missingGroup)) {
                    $added[] = $selectedGroup;
                }
            }
        }
        
        return array_unique($added);
    }
    
    /**
     * Check if a group is descendant of another group
     */
    private function isGroupDescendantOf(string $groupId, string $ancestorId): bool
    {
        $ancestors = $this->groupManager->getAncestors($groupId);
        foreach ($ancestors as $ancestor) {
            if ($ancestor['id'] === $ancestorId) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get all instruments (leaf nodes) within a group
     */
    private function getAllInstrumentsInGroup(string $groupId): array
    {
        $group = $this->groupManager->getGroup($groupId);
        if (!$group) {
            return [];
        }
        
        // If it's an instrument itself, return it
        if (($group['type'] ?? '') === 'instrument') {
            return [$groupId];
        }
        
        // If it's a section without children, it represents itself
        if (($group['type'] ?? '') === 'section' && !isset($group['children'])) {
            return [$groupId];
        }
        
        // Get all instrument descendants and sections without children
        $instruments = $this->groupManager->getInstrumentsByGroup($groupId);
        
        // Also include sections without children that are direct descendants
        $children = $this->groupManager->getChildren($groupId);
        foreach ($children as $child) {
            if (($child['type'] ?? '') === 'section' && !isset($child['children'])) {
                $instruments[] = $child['id'];
            }
        }
        
        return array_unique($instruments);
    }
    
    /**
     * Generate simple list format: "Group1, Group2 und Group3"
     */
    private function generateSimpleList(array $groups): string
    {
        // Filter out null/empty values
        $groups = array_filter($groups, fn($g) => !empty($g));
        
        if (empty($groups)) {
            return '';
        }
        
        if (count($groups) === 1) {
            $groupId = reset($groups);
            $group = $this->groupManager->getGroup($groupId);
            return $group['display_name'] ?? $groupId;
        }
        
        $names = [];
        foreach ($groups as $groupId) {
            if (empty($groupId)) continue;
            $group = $this->groupManager->getGroup($groupId);
            $names[] = $group['display_name'] ?? $groupId;
        }
        
        if (count($names) === 2) {
            return $names[0] . ' ' . $this->language['and'] . ' ' . $names[1];
        }
        
        $last = array_pop($names);
        return implode(', ', $names) . ' ' . $this->language['and'] . ' ' . $last;
    }
    
    /**
     * Calculate a score for description quality (lower is better)
     * Considers length and complexity
     */
    private function calculateScore(string $description): float
    {
        // Base score is character length
        $score = strlen($description);
        
        // Penalty for complex constructions
        if (strpos($description, $this->language['without']) !== false) {
            $score += 20; // "without" adds complexity but is often worth it
        }
        if (strpos($description, $this->language['but']) !== false) {
            $score += 30; // "but" adds even more complexity
        }
        
        // Count commas as complexity indicator
        $score += substr_count($description, ',') * 10;
        
        return $score;
    }
    
    /**
     * Set language constants
     */
    public function setLanguage(array $language): void
    {
        $this->language = array_merge($this->language, $language);
    }
    
    /**
     * Get current language settings
     */
    public function getLanguage(): array
    {
        return $this->language;
    }
    
    /**
     * Find the most concise representation of the tree state
     * This is a generic tree compression algorithm that works with any hierarchy
     */
    private function compressTreeRepresentation(array $selectedGroups): ?string
    {
        // Try different compression strategies and pick the best one
        $candidates = [];
        
        // Strategy 1: Find single root with exclusions (e.g., "Tutti ohne Oboe")
        $singleRootCandidate = $this->findSingleRootWithExclusions($selectedGroups);
        if ($singleRootCandidate) {
            $candidates[] = $singleRootCandidate;
        }
        
        // Strategy 2: Find composite expressions (e.g., "A und B ohne C")
        $compositeCandidate = $this->findCompositeExpression($selectedGroups);
        if ($compositeCandidate) {
            $candidates[] = $compositeCandidate;
        }
        
        // Strategy 3: Compress individual sections
        $compressedGroups = $this->compressSections($selectedGroups);
        if ($compressedGroups !== $selectedGroups) {
            $candidates[] = $this->generateSimpleList($compressedGroups);
        }
        
        // Pick the best candidate based on score, with preference for single root exclusions
        if (empty($candidates)) {
            return null;
        }
        
        $bestCandidate = null;
        $bestScore = PHP_INT_MAX;
        $originalScore = $this->calculateCompressionScore($this->generateSimpleList($selectedGroups));
        
        foreach ($candidates as $i => $candidate) {
            $score = $this->calculateCompressionScore($candidate);
            
            // Give preference to single root exclusions (strategy 1) when scores are close
            if ($i === 0 && strpos($candidate, $this->language['without']) !== false) {
                $score -= 8; // Larger bonus for single root exclusions
            }
            
            // Small bonus for composite expressions (strategy 2) when scores are close
            if ($i === 1 && isset($candidates[0])) { // If this is the composite candidate
                $score -= 3; // Smaller bonus for composite expressions
            }
            
            if ($score < $bestScore && $score < $originalScore) {
                $bestCandidate = $candidate;
                $bestScore = $score;
            }
        }
        
        return $bestCandidate;
    }
    
    /**
     * Find single root with exclusions (e.g., "Tutti ohne Oboe")
     */
    private function findSingleRootWithExclusions(array $selectedGroups): ?string
    {
        $allGroups = $this->groupManager->getAllGroups();
        $bestCandidate = null;
        $bestScore = PHP_INT_MAX;
        
        foreach ($allGroups as $rootId => $rootData) {
            $candidate = $this->tryDescribeAsRootWithExclusions($selectedGroups, $rootId);
            if ($candidate) {
                $score = $this->calculateCompressionScore($candidate);
                if ($score < $bestScore) {
                    $bestCandidate = $candidate;
                    $bestScore = $score;
                }
            }
        }
        
        return $bestCandidate;
    }
    
    /**
     * Find composite expressions (e.g., "A und B ohne C")
     */
    private function findCompositeExpression(array $selectedGroups): ?string
    {
        // Try multiple strategies to find the best composite expression
        $bestCombination = null;
        $bestScore = PHP_INT_MAX;
        
        // Strategy A: Greedy approach (current algorithm)
        $greedyResult = $this->findCompositeGreedy($selectedGroups);
        if ($greedyResult) {
            $score = $this->calculateCompressionScore($greedyResult);
            if ($score < $bestScore) {
                $bestCombination = $greedyResult;
                $bestScore = $score;
            }
        }
        
        // Strategy B: Try specific high-value combinations first
        $targetResult = $this->findCompositeTargeted($selectedGroups);
        if ($targetResult) {
            $score = $this->calculateCompressionScore($targetResult);
            if ($score < $bestScore) {
                $bestCombination = $targetResult;
                $bestScore = $score;
            }
        }
        
        return $bestCombination;
    }
    
    /**
     * Greedy composite expression finding (original algorithm)
     */
    private function findCompositeGreedy(array $selectedGroups): ?string
    {
        $allGroups = $this->groupManager->getAllGroups();
        
        // Sort groups by size (larger groups first)
        $sortedRoots = [];
        foreach ($allGroups as $rootId => $rootData) {
            $rootInstruments = $this->getAllInstrumentsInGroup($rootId);
            if (count($rootInstruments) >= 2) {
                $sortedRoots[] = $rootId;
            }
        }
        
        usort($sortedRoots, function($a, $b) {
            $countA = count($this->getAllInstrumentsInGroup($a));
            $countB = count($this->getAllInstrumentsInGroup($b));
            return $countB <=> $countA;
        });
        
        // Find units greedily
        $units = [];
        $remainingGroups = $selectedGroups;
        
        foreach ($sortedRoots as $rootId) {
            if (empty($remainingGroups)) break;
            
            $unit = $this->tryExtractUnitWithExclusions($remainingGroups, $rootId);
            if ($unit && count($unit['covered_groups']) >= 2) {
                $units[] = $unit['description'];
                $remainingGroups = array_diff($remainingGroups, $unit['covered_groups']);
            }
        }
        
        foreach ($remainingGroups as $groupId) {
            $units[] = $this->groupManager->getDisplayName($groupId);
        }
        
        if (count($units) < count($selectedGroups) && count($units) > 0) {
            return $this->combineUnits($units);
        }
        
        return null;
    }
    
    /**
     * Targeted composite expression finding (try specific patterns)
     */
    private function findCompositeTargeted(array $selectedGroups): ?string
    {
        // Try all possible combinations and pick the best one
        $allGroups = $this->groupManager->getAllGroups();
        $allCandidates = [];
        
        foreach ($allGroups as $rootId => $rootData) {
            $rootInstruments = $this->getAllInstrumentsInGroup($rootId);
            if (count($rootInstruments) >= 2) {
                $result = $this->tryBuildCompositeWithRoot($selectedGroups, $rootId);
                if ($result) {
                    $allCandidates[] = $result;
                }
            }
        }
        
        // Pick the candidate with the best score (lowest)
        if (empty($allCandidates)) {
            return null;
        }
        
        $bestCandidate = null;
        $bestScore = PHP_INT_MAX;
        
        foreach ($allCandidates as $candidate) {
            $score = $this->calculateCompressionScore($candidate);
            if ($score < $bestScore) {
                $bestCandidate = $candidate;
                $bestScore = $score;
            }
        }
        
        return $bestCandidate;
    }
    
    /**
     * Try to build a composite expression starting with a specific root
     */
    private function tryBuildCompositeWithRoot(array $selectedGroups, string $startingRoot): ?string
    {
        $units = [];
        $remainingGroups = $selectedGroups;
        
        // First, try the starting root
        $unit = $this->tryExtractUnitWithExclusions($remainingGroups, $startingRoot);
        if ($unit && count($unit['covered_groups']) >= 2) {
            $units[] = $unit['description'];
            $remainingGroups = array_diff($remainingGroups, $unit['covered_groups']);
        }
        
        // Then try other groups for remaining
        $allGroups = $this->groupManager->getAllGroups();
        foreach ($allGroups as $rootId => $rootData) {
            if (empty($remainingGroups) || $rootId === $startingRoot) continue;
            
            $rootInstruments = $this->getAllInstrumentsInGroup($rootId);
            if (count($rootInstruments) >= 2) {
                $unit = $this->tryExtractUnitWithExclusions($remainingGroups, $rootId);
                if ($unit && count($unit['covered_groups']) >= 2) {
                    $units[] = $unit['description'];
                    $remainingGroups = array_diff($remainingGroups, $unit['covered_groups']);
                }
            }
        }
        
        // Add remaining individual groups
        foreach ($remainingGroups as $groupId) {
            $units[] = $this->groupManager->getDisplayName($groupId);
        }
        
        if (count($units) < count($selectedGroups) && count($units) > 0) {
            return $this->combineUnits($units);
        }
        
        return null;
    }
    
    /**
     * Try to describe selection as a single root with exclusions
     */
    private function tryDescribeAsRootWithExclusions(array $selectedGroups, string $rootId): ?string
    {
        $rootInstruments = $this->getAllInstrumentsInGroup($rootId);
        if (empty($rootInstruments)) {
            return null;
        }
        
        $selectedInstruments = [];
        foreach ($selectedGroups as $groupId) {
            $selectedInstruments = array_merge($selectedInstruments, $this->getAllInstrumentsInGroup($groupId));
        }
        $selectedInstruments = array_unique($selectedInstruments);
        
        // Check if all selected instruments are within this root
        $instrumentsOutsideRoot = array_diff($selectedInstruments, $rootInstruments);
        if (!empty($instrumentsOutsideRoot)) {
            return null; // Some selected instruments are not in this root
        }
        
        // Find missing instruments from the root
        $missingInstruments = array_diff($rootInstruments, $selectedInstruments);
        
        // Only proceed if we're missing a reasonable number of instruments
        if (count($missingInstruments) === 0) {
            // Perfect match - just return the root name
            return $this->groupManager->getDisplayName($rootId);
        }
        
        if (count($missingInstruments) > count($rootInstruments) / 2) {
            return null; // Too many missing - not a good "root ohne X" candidate
        }
        
        // Find the most concise way to describe the missing instruments
        $missingGroups = $this->findGroupsForInstruments($missingInstruments, $rootId);
        if (empty($missingGroups)) {
            return null;
        }
        
        $rootName = $this->groupManager->getDisplayName($rootId);
        $missingDescription = $this->generateSimpleList($missingGroups);
        
        return $rootName . ' ' . $this->language['without'] . ' ' . $missingDescription;
    }
    
    /**
     * Try to extract a unit with exclusions from the remaining groups
     */
    private function tryExtractUnitWithExclusions(array $remainingGroups, string $rootId): ?array
    {
        $rootInstruments = $this->getAllInstrumentsInGroup($rootId);
        if (empty($rootInstruments) || count($rootInstruments) < 2) {
            return null; // Not worth considering small groups
        }
        
        $selectedInstruments = [];
        $coveredGroups = [];
        
        foreach ($remainingGroups as $groupId) {
            $groupInstruments = $this->getAllInstrumentsInGroup($groupId);
            $intersection = array_intersect($groupInstruments, $rootInstruments);
            if (!empty($intersection)) {
                $selectedInstruments = array_merge($selectedInstruments, $intersection);
                $coveredGroups[] = $groupId;
            }
        }
        
        $selectedInstruments = array_unique($selectedInstruments);
        
        // Check if this forms a meaningful "root minus exclusions" pattern
        $missingInstruments = array_diff($rootInstruments, $selectedInstruments);
        
        // Must have some coverage of the root (lowered threshold)
        if (count($selectedInstruments) < count($rootInstruments) * 0.4) {
            return null;
        }
        
        // Check for meaningful patterns
        if (!empty($missingInstruments) && count($missingInstruments) <= count($rootInstruments) * 0.6) {
            $missingGroups = $this->findGroupsForInstruments($missingInstruments, $rootId);
            if (!empty($missingGroups) && count($coveredGroups) >= 2) { // Must cover at least 2 original groups
                $rootName = $this->groupManager->getDisplayName($rootId);
                $missingDescription = $this->generateSimpleList($missingGroups);
                $description = $rootName . ' ' . $this->language['without'] . ' ' . $missingDescription;
                
                return [
                    'description' => $description,
                    'covered_groups' => $coveredGroups
                ];
            }
        } elseif (empty($missingInstruments) && count($coveredGroups) >= 2) {
            // Perfect match - covers at least 2 groups
            return [
                'description' => $this->groupManager->getDisplayName($rootId),
                'covered_groups' => $coveredGroups
            ];
        }
        
        return null;
    }
    
    /**
     * Find the most concise groups that represent the given instruments within a root
     */
    private function findGroupsForInstruments(array $instruments, string $withinRootId): array
    {
        if (empty($instruments)) {
            return [];
        }
        
        $groups = [];
        $remainingInstruments = $instruments;
        
        // Get all descendants of the root to work within that scope
        $descendants = $this->groupManager->getDescendants($withinRootId);
        $descendants[] = $this->groupManager->getGroup($withinRootId); // Include root itself
        
        // Sort by hierarchy depth (shallowest first for broader matching)
        usort($descendants, function($a, $b) {
            $depthA = count($this->groupManager->getAncestors($a['id']));
            $depthB = count($this->groupManager->getAncestors($b['id']));
            return $depthA <=> $depthB; // Changed: shallowest first
        });
        
        foreach ($descendants as $group) {
            if (empty($remainingInstruments)) break;
            
            $groupInstruments = $this->getAllInstrumentsInGroup($group['id']);
            $covered = array_intersect($groupInstruments, $remainingInstruments);
            
            // If this group covers a significant portion of the remaining instruments, use it
            if (!empty($covered) && (
                count($covered) === count($groupInstruments) || // Complete group
                (count($covered) >= 3 && count($covered) >= count($groupInstruments) * 0.7) // Or >= 70% of a larger group
            )) {
                $groups[] = $group['id'];
                $remainingInstruments = array_diff($remainingInstruments, $covered);
            }
        }
        
        // Add any remaining individual instruments
        foreach ($remainingInstruments as $instrument) {
            $groups[] = $instrument;
        }
        
        return $groups;
    }
    
    /**
     * Combine multiple units into a single description
     */
    private function combineUnits(array $units): string
    {
        if (count($units) === 1) {
            return $units[0];
        }
        
        // Sort units: simple instruments first, then complex expressions
        usort($units, function($a, $b) {
            $aIsComplex = strpos($a, $this->language['without']) !== false;
            $bIsComplex = strpos($b, $this->language['without']) !== false;
            
            // If both are simple or both are complex, maintain original order
            if ($aIsComplex === $bIsComplex) {
                return 0;
            }
            
            // Simple units (no "without") come first
            return $aIsComplex ? 1 : -1;
        });
        
        if (count($units) === 2) {
            return $units[0] . ' ' . $this->language['and'] . ' ' . $units[1];
        }
        
        $last = array_pop($units);
        return implode(', ', $units) . ' ' . $this->language['and'] . ' ' . $last;
    }
    
    /**
     * Compress sections by replacing complete child sets with their parent
     */
    private function compressSections(array $selectedGroups): array
    {
        $compressed = $selectedGroups;
        $allSections = $this->groupManager->getAllSections();
        
        foreach ($allSections as $sectionId => $sectionData) {
            // Skip if section is already in the list
            if (in_array($sectionId, $compressed)) {
                continue;
            }
            
            // Get all children of this section
            $children = $this->groupManager->getChildren($sectionId);
            if (empty($children)) {
                continue;
            }
            
            $childIds = array_map(fn($child) => $child['id'], $children);
            
            // Check if ALL children are selected
            $selectedChildren = array_intersect($compressed, $childIds);
            if (count($selectedChildren) === count($childIds) && count($selectedChildren) > 1) {
                // Replace all children with the parent section
                $compressed = array_diff($compressed, $childIds);
                $compressed[] = $sectionId;
            }
        }
        
        return array_values($compressed);
    }
    
    /**
     * Find possible roots for compression (nodes that have children)
     */
    private function findPossibleCompressionRoots(array $selectedGroups): array
    {
        $roots = [];
        $allGroups = $this->groupManager->getAllGroups();
        
        foreach ($allGroups as $groupId => $groupData) {
            // Only consider groups that have children (can be compressed)
            $children = $this->groupManager->getChildren($groupId);
            if (!empty($children)) {
                $roots[] = $groupId;
            }
        }
        
        // Sort by hierarchy depth (deeper first for more specific compression)
        usort($roots, function($a, $b) {
            $depthA = count($this->groupManager->getAncestors($a));
            $depthB = count($this->groupManager->getAncestors($b));
            return $depthB <=> $depthA;
        });
        
        return $roots;
    }
    
    /**
     * Try to compress the selection using a specific root
     */
    private function tryCompressWithRoot(array $selectedGroups, string $rootId): ?string
    {
        // Get all descendants of this root
        $allDescendants = $this->groupManager->getDescendants($rootId);
        $descendantIds = array_map(fn($d) => $d['id'], $allDescendants);
        $descendantIds[] = $rootId; // Include root itself
        
        // Check which descendants are selected and which are missing
        $selectedDescendants = array_intersect($selectedGroups, $descendantIds);
        $missingDescendants = array_diff($descendantIds, $selectedGroups);
        
        // Only consider this root if some of its descendants are selected
        if (empty($selectedDescendants)) {
            return null;
        }
        
        // Calculate coverage within this root's scope
        $totalDescendants = count($descendantIds);
        $selectedCount = count($selectedDescendants);
        $coverage = $selectedCount / $totalDescendants;
        
        // If we have high coverage and few missing items, use "root without X" format
        if ($coverage >= 0.7 && count($missingDescendants) <= 3 && count($missingDescendants) > 0) {
            // But only if ALL selected groups are within this root's scope
            $groupsOutsideRoot = array_diff($selectedGroups, $descendantIds);
            if (empty($groupsOutsideRoot)) {
                $rootName = $this->groupManager->getDisplayName($rootId);
                $description = $rootName . ' ' . $this->language['without'] . ' ';
                $description .= $this->generateSimpleList($missingDescendants);
                return $description;
            }
        }
        
        // If we have complete coverage AND no missing descendants, use the root name
        if ($coverage === 1.0 && empty($missingDescendants)) {
            $groupsOutsideRoot = array_diff($selectedGroups, $descendantIds);
            if (empty($groupsOutsideRoot)) {
                return $this->groupManager->getDisplayName($rootId);
            }
        }
        
        return null;
    }
    
    /**
     * Calculate a score for how concise a representation is (lower = better)
     */
    private function calculateCompressionScore(string $description): int
    {
        // Base score is character length
        $score = strlen($description);
        
        // Penalty for complexity
        if (strpos($description, $this->language['without']) !== false) {
            $score += 10; // "without" adds some complexity but can be worth it
        }
        
        // Count commas as complexity
        $score += substr_count($description, ',') * 5;
        
        // Bonus for compositional descriptions (instrument + section combinations)
        if (strpos($description, $this->language['and']) !== false && 
            strpos($description, $this->language['without']) !== false) {
            // This is a "X und Y ohne Z" pattern - favor it slightly
            $score -= 3; // Small bonus for compositional expressions
        }
        
        // Penalty for very broad exclusions from large groups
        if (strpos($description, 'Tutti') === 0 && strpos($description, $this->language['without']) !== false) {
            $score += 2; // Small penalty for starting with "Tutti ohne"
        }
        
        return $score;
    }
    
    /**
     * Debug method to show analysis of selected groups
     */
    public function debugAnalysis(array $selectedGroups): array
    {
        $analysis = [
            'selected_groups' => $selectedGroups,
            'possible_roots' => $this->findPossibleRoots($selectedGroups),
            'coverage_analysis' => [],
            'compression_check' => $this->compressTreeRepresentation($selectedGroups)
        ];
        
        foreach ($analysis['possible_roots'] as $rootId) {
            $coverage = $this->calculateCoverage($selectedGroups, $rootId);
            $analysis['coverage_analysis'][$rootId] = [
                'coverage' => $coverage,
                'description' => $this->generateFromRoot($selectedGroups, $rootId),
                'score' => $this->calculateScore($this->generateFromRoot($selectedGroups, $rootId))
            ];
        }
        
        $analysis['final_description'] = $this->generateDescription($selectedGroups);
        
        return $analysis;
    }
}
