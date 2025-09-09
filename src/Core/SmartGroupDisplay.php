<?php
namespace App\Core;

/**
 * Smart Group Display Class
 * 
 * Generates intelligent, natural language descriptions of rehearsal groups
 * based on any hierarchical ensemble configuration. Works dynamically with
 * any group hierarchy and can generate descriptions like:
 * - "[Root Group]" (e.g., "Tutti", "Full Band")
 * - "[Root Group] ohne [Section]" (e.g., "Tutti ohne Schlagwerk")
 * - "[Section] und [Section]" (e.g., "Streicher und Blechbläser") 
 * - "[Root Group] ohne [Section] aber [Instrument]"
 * - "[Subsection] ohne [Instrument]" (e.g., "Holzbläser ohne Fagott")
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
     * Get all section names dynamically from GroupManager
     * Returns sections that are direct children of root group and subsections
     */
    private function getSpecificSections(): array
    {
        $specificSections = [];
        
        // Get all sections from GroupManager
        $allSections = $this->groupManager->getAllSections();
        
        foreach ($allSections as $section) {
            $specificSections[] = $section['display_name'];
        }
        
        return array_unique($specificSections);
    }
    
    /**
     * Get the root group dynamically (the top-level group with special rules)
     */
    private function getRootGroup(): ?array
    {
        $allGroups = $this->groupManager->getAllGroups();
        
        foreach ($allGroups as $group) {
            if (($group['type'] ?? '') === 'special' && 
                isset($group['special_rules']['affects_all']) && 
                $group['special_rules']['affects_all'] === true) {
                return $group;
            }
        }
        
        // Fallback: find the first group with no parent (top-level)
        foreach ($allGroups as $group) {
            $parent = $this->groupManager->getParent($group['id']);
            if (!$parent) {
                return $group;
            }
        }
        
        return null;
    }
    
    /**
     * Get the dynamic root group ID (fallback for when root group is not found)
     */
    private function getDynamicRootId(): string
    {
        $rootGroup = $this->getRootGroup();
        return $rootGroup ? $rootGroup['id'] : 'tutti'; // Keep 'tutti' as ultimate fallback
    }
    
    /**
     * Get main sections (direct children of root group)
     */
    private function getMainSections(): array
    {
        $mainSections = [];
        $rootGroup = $this->getRootGroup();
        
        if (!$rootGroup) {
            return [];
        }
        
        // Get all sections that are direct children of the root group
        $allSections = $this->groupManager->getAllSections();
        
        foreach ($allSections as $section) {
            $parent = $this->groupManager->getParent($section['id']);
            if ($parent && $parent['id'] === $rootGroup['id']) {
                $mainSections[] = $section['id'];
            }
        }
        
        return $mainSections;
    }
    
    /**
     * Try to generate a simple root-level exclusion (e.g., "Tutti ohne Klarinette und Fagott")
     * This has priority over complex nested patterns for small exclusion sets
     */
    private function trySimpleRootExclusion(array $selectedGroups, string $rootId): ?string
    {
        // Get all instruments in the root group and selected instruments
        $rootInstruments = $this->getAllInstrumentsInGroup($rootId);
        $selectedInstruments = [];
        
        foreach ($selectedGroups as $groupId) {
            $selectedInstruments = array_merge($selectedInstruments, $this->getAllInstrumentsInGroup($groupId));
        }
        $selectedInstruments = array_unique($selectedInstruments);
        
        // Check if most of the root is selected (high coverage)
        $coverage = count($selectedInstruments) / count($rootInstruments);
        if ($coverage < 0.7) { // Less than 70% coverage - not a good root exclusion candidate
            return null;
        }
        
        // Find missing instruments
        $missingInstruments = array_diff($rootInstruments, $selectedInstruments);
        
        // Only use simple root exclusion for small exclusion sets (≤ 5 instruments)
        // This prevents verbose "Tutti ohne [many items]" descriptions
        if (count($missingInstruments) === 0) {
            // Perfect match - just return the root name
            return $this->groupManager->getDisplayName($rootId);
        } elseif (count($missingInstruments) <= 5) {
            // Small exclusion set - prefer simple root exclusion
            $rootName = $this->groupManager->getDisplayName($rootId);
            $missingNames = array_map([$this->groupManager, 'getDisplayName'], $missingInstruments);
            
            return $rootName . ' ' . $this->language['without'] . ' ' . $this->generateSimpleList($missingNames);
        }
        
        return null;
    }
    
    /**
     * Count the number of items being excluded in a description
     * Used to determine if a single root exclusion is simple enough to prefer
     */
    private function countExclusions(string $description): int
    {
        // Find all "ohne" (without) patterns
        $withoutWord = $this->language['without'];
        $withoutPos = strpos($description, $withoutWord);
        
        if ($withoutPos === false) {
            return 0; // No exclusions
        }
        
        // Extract the part after "ohne"
        $exclusionPart = substr($description, $withoutPos + strlen($withoutWord));
        
        // Stop at "aber" (but) if present
        $butWord = $this->language['but'];
        $butPos = strpos($exclusionPart, $butWord);
        if ($butPos !== false) {
            $exclusionPart = substr($exclusionPart, 0, $butPos);
        }
        
        // Count items by splitting on "und" and commas
        $excludedItems = preg_split('/[,]?\s+und\s+|[,\s]+/', trim($exclusionPart));
        $excludedItems = array_map('trim', $excludedItems);
        $excludedItems = array_filter($excludedItems, function($item) {
            return !empty($item) && $item !== $this->language['and'];
        });
        
        return count($excludedItems);
    }
    
    /**
     * Generate smart description for selected groups with rehearsal context
     * 
     * @param array $selectedGroups Array of selected group IDs
     * @param array|null $rehearsal Optional rehearsal data for context (e.g., small group status)
     * @param bool $isAdminView Whether this is an admin view
     * @return string Natural language description
     */
    public function generateDescription(array $selectedGroups, ?array $rehearsal = null, bool $isAdminView = false): string
    {
        $baseDescription = $this->generateBaseDescription($selectedGroups);
        
        // Apply Kleingruppe prefix if this is a small group rehearsal
        if ($rehearsal && \App\Core\RehearsalTypeManager::isSmallGroupRehearsal($rehearsal)) {
            return \App\Core\RehearsalTypeManager::LABEL_KLEINGRUPPE . ': ' . $baseDescription;
        }
        
        return $baseDescription;
    }
    
    /**
     * Generate base smart description for selected groups (without context)
     * 
     * @param array $selectedGroups Array of selected group IDs
     * @return string Natural language description
     */
    public function generateBaseDescription(array $selectedGroups): string
    {
        if (empty($selectedGroups)) {
            return '';
        }
        
        // Handle single root group
        if (count($selectedGroups) === 1) {
            $singleGroup = $selectedGroups[0];
            $displayName = $this->groupManager->getDisplayName($singleGroup);
            return $displayName;
        }
        
        // Normalize input - remove duplicates but preserve order
        $selectedGroups = array_unique($selectedGroups);
        
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
                // We have the full root plus other groups - create unified list
                $allItems = [$rootName];
                foreach ($groupsOutsideRoot as $groupId) {
                    $group = $this->groupManager->getGroup($groupId);
                    $allItems[] = $group['display_name'] ?? $groupId;
                }
                return $this->generateSimpleListFromNames($allItems);
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
            // Handle special exclusion markers
            if (strpos($groupId, '__EXCLUSION__') === 0) {
                return substr($groupId, strlen('__EXCLUSION__'));
            }
            $group = $this->groupManager->getGroup($groupId);
            return $group['display_name'] ?? $groupId;
        }
        
        $names = [];
        foreach ($groups as $groupId) {
            if (empty($groupId)) continue;
            
            // Handle special exclusion markers
            if (strpos($groupId, '__EXCLUSION__') === 0) {
                $names[] = substr($groupId, strlen('__EXCLUSION__'));
            } else {
                $group = $this->groupManager->getGroup($groupId);
                $names[] = $group['display_name'] ?? $groupId;
            }
        }
        
        if (count($names) === 2) {
            return $names[0] . ' ' . $this->language['and'] . ' ' . $names[1];
        }
        
        $last = array_pop($names);
        return implode(', ', $names) . ' ' . $this->language['and'] . ' ' . $last;
    }
    
    /**
     * Generate simple list format from already formatted names: "Name1, Name2 und Name3"
     * This is similar to generateSimpleList but works with display names rather than group IDs
     */
    private function generateSimpleListFromNames(array $names): string
    {
        // Filter out null/empty values
        $names = array_filter($names, fn($name) => !empty($name));
        
        if (empty($names)) {
            return '';
        }
        
        if (count($names) === 1) {
            return reset($names);
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
        // PRIORITY 1: Check for simple root-level exclusions first
        // For cases like "Tutti ohne Klarinette und Fagott", prefer this over complex nested patterns
        $rootGroup = $this->getRootGroup();
        if ($rootGroup) {
            $simpleRootExclusion = $this->trySimpleRootExclusion($selectedGroups, $rootGroup['id']);
            if ($simpleRootExclusion) {
                return $simpleRootExclusion;
            }
        }
        
        // Try different compression strategies and pick the best one
        $candidates = [];
        
        // Strategy 1: Find single root with exclusions (e.g., "Tutti ohne Oboe")
        $singleRootCandidate = $this->findSingleRootWithExclusions($selectedGroups);
        if ($singleRootCandidate) {
            $candidates[] = [
                'description' => $singleRootCandidate,
                'strategy' => 'single_root',
                'score' => $this->calculateCompressionScore($singleRootCandidate)
            ];
        }
        
        // Strategy 2: Find composite expressions (e.g., "A und B ohne C")
        $compositeCandidate = $this->findCompositeExpression($selectedGroups);
        if ($compositeCandidate) {
            $candidates[] = [
                'description' => $compositeCandidate,
                'strategy' => 'composite',
                'score' => $this->calculateCompressionScore($compositeCandidate)
            ];
        }
        
        // Strategy 3: Find multiple exclusion patterns (e.g., "A ohne X und B ohne Y")
        $multipleExclusionsCandidate = $this->findMultipleExclusionPatterns($selectedGroups);
        if ($multipleExclusionsCandidate) {
            $candidates[] = [
                'description' => $multipleExclusionsCandidate,
                'strategy' => 'multiple_exclusions',
                'score' => $this->calculateCompressionScore($multipleExclusionsCandidate)
            ];
        }
        
        // Strategy 4: Compress individual sections
        $compressedGroups = $this->compressSections($selectedGroups);
        if ($compressedGroups !== $selectedGroups) {
            $description = $this->generateSimpleList($compressedGroups);
            // Give higher priority to compressed sections that contain exclusions
            $strategy = 'compressed_sections';
            if (strpos($description, $this->language['without']) !== false) {
                $strategy = 'compressed_sections_with_exclusions';
            }
            
            $candidates[] = [
                'description' => $description,
                'strategy' => $strategy,
                'score' => $this->calculateCompressionScore($description)
            ];
        }
        
        // Pick the best candidate using multi-criteria approach
        if (empty($candidates)) {
            return null;
        }
        
        $originalScore = $this->calculateCompressionScore($this->generateSimpleList($selectedGroups));
        
        // Sort candidates by multiple criteria:
        // 1. Avoid redundant multiple exclusions (prefer single root when simpler)
        // 2. Strategy preference (single root > compressed sections with exclusions > composite > multiple exclusions > compressed sections)
        // 3. Lower score (more concise)
        usort($candidates, function($a, $b) use ($originalScore) {
            // First check: avoid redundant multiple exclusions
            // If we have a single root exclusion that's concise, prefer it over multiple exclusions
            if ($a['strategy'] === 'single_root' && $b['strategy'] === 'multiple_exclusions') {
                // Count exclusions in single root - if ≤ 5, prefer it
                $aExclusionCount = $this->countExclusions($a['description']);
                if ($aExclusionCount <= 5) {
                    return -1; // Prefer single root
                }
            }
            if ($b['strategy'] === 'single_root' && $a['strategy'] === 'multiple_exclusions') {
                $bExclusionCount = $this->countExclusions($b['description']);
                if ($bExclusionCount <= 5) {
                    return 1; // Prefer single root
                }
            }
            
            // Also prefer compressed sections with exclusions over multiple exclusions when simpler
            if ($a['strategy'] === 'compressed_sections_with_exclusions' && $b['strategy'] === 'multiple_exclusions') {
                $aExclusionCount = $this->countExclusions($a['description']);
                if ($aExclusionCount <= 3) { // Even stricter threshold
                    return -1; // Prefer compressed sections with simple exclusions
                }
            }
            if ($b['strategy'] === 'compressed_sections_with_exclusions' && $a['strategy'] === 'multiple_exclusions') {
                $bExclusionCount = $this->countExclusions($b['description']);
                if ($bExclusionCount <= 3) {
                    return 1; // Prefer compressed sections with simple exclusions
                }
            }
            
            // Strategy preference - fixed prioritization  
            $strategyOrder = [
                'single_root' => 5, 
                'compressed_sections_with_exclusions' => 4,  // Higher priority for exclusions from compressed sections
                'composite' => 3, 
                'multiple_exclusions' => 2, 
                'compressed_sections' => 1
            ];
            $aStrategy = $strategyOrder[$a['strategy']] ?? 0;
            $bStrategy = $strategyOrder[$b['strategy']] ?? 0;
            
            if ($aStrategy !== $bStrategy) {
                return $bStrategy <=> $aStrategy; // Higher strategy number first
            }
            
            // Then by score (lower is better)
            return $a['score'] <=> $b['score'];
        });
        
        // Return the best candidate that's better than the original
        foreach ($candidates as $candidate) {
            if ($candidate['score'] < $originalScore) {
                return $candidate['description'];
            }
        }
        
        // If no candidate is better, but we have compressed sections with exclusions, use that
        foreach ($candidates as $candidate) {
            if ($candidate['strategy'] === 'compressed_sections_with_exclusions') {
                return $candidate['description'];
            }
        }
        
        return null;
    }
    
    /**
     * Find single root with exclusions (e.g., "Tutti ohne Oboe")
     */
    private function findSingleRootWithExclusions(array $selectedGroups): ?string
    {
        $allGroups = $this->groupManager->getAllGroups();
        $candidates = [];
        
        foreach ($allGroups as $rootId => $rootData) {
            $candidate = $this->tryDescribeAsRootWithExclusions($selectedGroups, $rootId);
            if ($candidate) {
                $score = $this->calculateCompressionScore($candidate);
                $coverage = $this->calculateRootCoverage($selectedGroups, $rootId);
                $specificity = $this->calculateRootSpecificity($rootId);
                
                $candidates[] = [
                    'description' => $candidate,
                    'score' => $score,
                    'coverage' => $coverage,
                    'specificity' => $specificity,
                    'rootId' => $rootId
                ];
            }
        }
        
        if (empty($candidates)) {
            return null;
        }
        
        // Sort candidates by multiple criteria:
        // 1. Quality of description (prefer descriptions that mention missing instruments)
        // 2. Meaningful exclusion patterns (prefer specific section exclusions over general ones)
        // 3. Higher specificity (more specific roots preferred)
        // 4. Higher coverage (better match)
        // 5. Lower score (more concise)
        usort($candidates, function($a, $b) {
            // First priority: quality of description (prefer descriptions that mention missing instruments)
            $aHasExclusions = strpos($a['description'], $this->language['without']) !== false;
            $bHasExclusions = strpos($b['description'], $this->language['without']) !== false;
            if ($aHasExclusions !== $bHasExclusions) {
                return $bHasExclusions <=> $aHasExclusions; // Prefer descriptions with exclusions
            }
            
            // Second priority: meaningful exclusion patterns
            // Prefer specific section exclusions (like "Holzbläser ohne Flöte") over general ones
            $aIsSpecificSection = $this->isSpecificSectionExclusion($a['description']);
            $bIsSpecificSection = $this->isSpecificSectionExclusion($b['description']);
            if ($aIsSpecificSection !== $bIsSpecificSection) {
                return $bIsSpecificSection <=> $aIsSpecificSection; // Prefer specific section exclusions
            }
            
            // If both are specific section exclusions, prefer the one with fewer excluded items
            if ($aIsSpecificSection && $bIsSpecificSection) {
                $aExclusionScore = $this->calculateSpecificSectionExclusionScore($a['description']);
                $bExclusionScore = $this->calculateSpecificSectionExclusionScore($b['description']);
                if ($aExclusionScore !== $bExclusionScore) {
                    return $aExclusionScore <=> $bExclusionScore; // Lower is better
                }
            }
            
            // Third priority: specificity (higher is better)
            if ($a['specificity'] !== $b['specificity']) {
                return $b['specificity'] <=> $a['specificity'];
            }
            
            // Fourth priority: coverage (higher is better)
            if (abs($a['coverage'] - $b['coverage']) > 0.1) {
                return $b['coverage'] <=> $a['coverage'];
            }
            
            // Fifth priority: score (lower is better)
            return $a['score'] <=> $b['score'];
        });
        
        return $candidates[0]['description'];
    }
    
    /**
     * Calculate how well a root covers the selected groups
     */
    private function calculateRootCoverage(array $selectedGroups, string $rootId): float
    {
        $rootInstruments = $this->getAllInstrumentsInGroup($rootId);
        if (empty($rootInstruments)) {
            return 0.0;
        }
        
        $selectedInstruments = [];
        foreach ($selectedGroups as $groupId) {
            $selectedInstruments = array_merge($selectedInstruments, $this->getAllInstrumentsInGroup($groupId));
        }
        $selectedInstruments = array_unique($selectedInstruments);
        
        $intersection = array_intersect($rootInstruments, $selectedInstruments);
        return count($intersection) / count($rootInstruments);
    }
    
    /**
     * Check if a description represents a specific section exclusion (like "Holzbläser ohne Flöte")
     */
    private function isSpecificSectionExclusion(string $description): bool
    {
        // Look for patterns like "Holzbläser ohne X" or "Blechbläser ohne X"
        // These are more meaningful than general exclusions like "Streicher ohne X und Y"
        $specificSections = $this->getSpecificSections();
        
        foreach ($specificSections as $section) {
            // Check for patterns that start with the section name
            if (strpos($description, $section . ' ' . $this->language['without']) === 0) {
                // Check if it's a single exclusion (more specific) vs multiple exclusions
                $exclusionCount = substr_count($description, $this->language['without']);
                if ($exclusionCount === 1) {
                    return true; // This is a specific section exclusion
                }
            }
            
            // Check for patterns that contain the section name with exclusion in the middle
            // Like "Other groups und Holzbläser ohne Flöte"
            if (strpos($description, ' ' . $this->language['and'] . ' ' . $section . ' ' . $this->language['without']) !== false) {
                // Check if it's a single exclusion (more specific) vs multiple exclusions
                $exclusionCount = substr_count($description, $this->language['without']);
                if ($exclusionCount === 1) {
                    return true; // This is a specific section exclusion
                }
            }
        }
        
        return false;
    }
    
    /**
     * Calculate the quality score for a specific section exclusion (lower = better)
     */
    private function calculateSpecificSectionExclusionScore(string $description): int
    {
        // Prefer single instrument exclusions over multiple instrument exclusions
        // "Holzbläser ohne Flöte" is better than "Streicher ohne Violine 1 und Kontrabass"
        
        $specificSections = $this->getSpecificSections();
        
        foreach ($specificSections as $section) {
            // Check for patterns that start with the section name
            if (strpos($description, $section . ' ' . $this->language['without']) === 0) {
                // Count how many items are being excluded
                $exclusionPart = substr($description, strlen($section . ' ' . $this->language['without']));
                // Split by comma and "und" to get individual items
                $excludedItems = preg_split('/[,]?\s+und\s+|[,\s]+/', $exclusionPart);
                $excludedItems = array_map('trim', $excludedItems);
                $excludedItems = array_filter($excludedItems, function($item) {
                    return !empty($item) && $item !== $this->language['and'];
                });
                
                return count($excludedItems); // Lower is better
            }
            
            // Check for patterns that contain the section name with exclusion in the middle
            if (strpos($description, ' ' . $this->language['and'] . ' ' . $section . ' ' . $this->language['without']) !== false) {
                // Extract the exclusion part
                $pattern = ' ' . $this->language['and'] . ' ' . $section . ' ' . $this->language['without'] . ' ';
                $pos = strpos($description, $pattern);
                if ($pos !== false) {
                    $exclusionPart = substr($description, $pos + strlen($pattern));
                    // Split by comma and "und" to get individual items
                    $excludedItems = preg_split('/[,]?\s+und\s+|[,\s]+/', $exclusionPart);
                    $excludedItems = array_map('trim', $excludedItems);
                    $excludedItems = array_filter($excludedItems, function($item) {
                        return !empty($item) && $item !== $this->language['and'];
                    });
                    
                    return count($excludedItems); // Lower is better
                }
            }
        }
        
        return 999; // High penalty for non-specific exclusions
    }
    
    /**
     * Calculate how specific a root is (higher = more specific)
     */
    private function calculateRootSpecificity(string $rootId): int
    {
        // Get the depth of this root in the hierarchy
        $ancestors = $this->groupManager->getAncestors($rootId);
        $depth = count($ancestors);
        
        // Special penalty for root group - it's very general
        $rootGroup = $this->getRootGroup();
        if ($rootGroup && $rootId === $rootGroup['id']) {
            $depth = -10; // Much larger penalty for root group
        }
        
        // Bonus for section-level groups (like "Bläser", "Streicher")
        $group = $this->groupManager->getGroup($rootId);
        if ($group && ($group['type'] ?? '') === 'section') {
            $depth += 5; // Much larger bonus for section-level groups
        }
        
        // Additional bonus for groups that are direct children of root group
        if (in_array($rootId, $this->getMainSections())) {
            $depth += 3; // Extra bonus for main sections
        }
        
        return $depth;
    }
    
    /**
     * Find multiple exclusion patterns (e.g., "A ohne X und B ohne Y")
     */
    private function findMultipleExclusionPatterns(array $selectedGroups): ?string
    {
        // Look for cases where we have multiple meaningful exclusion patterns
        // Like "Holzbläser ohne Flöte" AND "Blechbläser ohne Horn"
        
        $specificSections = $this->getSpecificSections();
        $rawExclusionPatterns = [];
        
        foreach ($specificSections as $section) {
            $result = $this->tryDescribeAsRootWithExclusions($selectedGroups, $section);
            if ($result && $this->isSpecificSectionExclusion($result)) {
                // Extract just the exclusion part (e.g., "Holzbläser ohne Flöte")
                $exclusionPart = $this->extractExclusionPart($result, $section);
                if ($exclusionPart) {
                    $rawExclusionPatterns[] = [
                        'pattern' => $exclusionPart,
                        'section' => $section
                    ];
                }
            }
        }
        
        // CRITICAL: Remove redundant exclusions at multiple hierarchy levels
        // E.g., if we have both "Bläser ohne Klarinette" and "Holzbläser ohne Klarinette",
        // keep only the higher-level one ("Bläser ohne Klarinette")
        $deduplicatedPatterns = $this->deduplicateHierarchyExclusions($rawExclusionPatterns);
        
        // If we have multiple meaningful exclusion patterns, combine them
        if (count($deduplicatedPatterns) >= 2) {
            // Find the remaining groups that aren't covered by these exclusions
            $remainingGroups = $this->findRemainingGroupsForMultipleExclusions($selectedGroups, $deduplicatedPatterns);
            
            if (!empty($remainingGroups)) {
                // Create one unified list instead of joining two separate lists
                $allItems = [];
                
                // Add remaining groups as display names
                foreach ($remainingGroups as $groupId) {
                    $group = $this->groupManager->getGroup($groupId);
                    $allItems[] = $group['display_name'] ?? $groupId;
                }
                
                // Add exclusion patterns
                foreach ($deduplicatedPatterns as $pattern) {
                    $allItems[] = $pattern;
                }
                
                // Use generateSimpleList to format the unified list properly
                return $this->generateSimpleListFromNames($allItems);
            } else {
                return implode(' und ', $deduplicatedPatterns);
            }
        }
        
        return null;
    }
    
    /**
     * Remove redundant exclusions at multiple hierarchy levels
     * E.g., if we have both "Bläser ohne Klarinette" and "Holzbläser ohne Klarinette",
     * keep only the higher-level one since it's more general and less redundant
     */
    private function deduplicateHierarchyExclusions(array $rawPatterns): array
    {
        if (count($rawPatterns) <= 1) {
            return array_column($rawPatterns, 'pattern');
        }
        
        $deduplicatedPatterns = [];
        
        foreach ($rawPatterns as $patternA) {
            $isRedundant = false;
            
            // Check if this pattern is made redundant by a higher-level pattern
            foreach ($rawPatterns as $patternB) {
                if ($patternA === $patternB) continue;
                
                // Check if patternB covers the same exclusions at a higher hierarchy level
                if ($this->isPatternMadeRedundantBy($patternA, $patternB)) {
                    $isRedundant = true;
                    break;
                }
            }
            
            if (!$isRedundant) {
                $deduplicatedPatterns[] = $patternA['pattern'];
            }
        }
        
        return array_unique($deduplicatedPatterns);
    }
    
    /**
     * Check if patternA is made redundant by patternB (higher in hierarchy)
     */
    private function isPatternMadeRedundantBy(array $patternA, array $patternB): bool
    {
        $sectionA = $patternA['section'];
        $sectionB = $patternB['section'];
        
        // Check if sectionB is an ancestor of sectionA in the hierarchy
        $sectionAGroup = $this->groupManager->getGroup($sectionA);
        $sectionBGroup = $this->groupManager->getGroup($sectionB);
        
        if (!$sectionAGroup || !$sectionBGroup) {
            return false;
        }
        
        // Check if B is an ancestor of A
        $ancestorsOfA = $this->groupManager->getAncestors($sectionA);
        $isBAncestorOfA = in_array($sectionB, array_column($ancestorsOfA, 'id'));
        
        if (!$isBAncestorOfA) {
            return false;
        }
        
        // Extract excluded instruments from both patterns
        $excludedInstrumentsA = $this->extractExcludedInstruments($patternA['pattern']);
        $excludedInstrumentsB = $this->extractExcludedInstruments($patternB['pattern']);
        
        // Check if all instruments excluded in A are also excluded in B
        // If so, pattern A is redundant because B already covers it at a higher level
        return count(array_diff($excludedInstrumentsA, $excludedInstrumentsB)) === 0;
    }
    
    /**
     * Extract the list of excluded instruments from a pattern like "Holzbläser ohne Klarinette und Fagott"
     */
    private function extractExcludedInstruments(string $pattern): array
    {
        $withoutWord = $this->language['without'];
        $withoutPos = strpos($pattern, $withoutWord);
        
        if ($withoutPos === false) {
            return [];
        }
        
        // Extract the part after "ohne"
        $exclusionPart = trim(substr($pattern, $withoutPos + strlen($withoutWord)));
        
        // Split by "und" and commas to get individual instruments
        $excludedItems = preg_split('/[,]?\s+und\s+|[,\s]+/', $exclusionPart);
        $excludedItems = array_map('trim', $excludedItems);
        $excludedItems = array_filter($excludedItems, function($item) {
            return !empty($item) && $item !== $this->language['and'];
        });
        
        return array_values($excludedItems);
    }
    
    /**
     * Extract the exclusion part from a description (e.g., "Holzbläser ohne Flöte" from "Other groups und Holzbläser ohne Flöte")
     */
    private function extractExclusionPart(string $description, string $section): ?string
    {
        // Look for patterns that start with the section name
        if (strpos($description, $section . ' ' . $this->language['without']) === 0) {
            return $section . ' ' . $this->language['without'] . ' ' . substr($description, strlen($section . ' ' . $this->language['without']));
        }
        
        // Look for patterns that contain the section name with exclusion in the middle
        $pattern = ' ' . $this->language['and'] . ' ' . $section . ' ' . $this->language['without'] . ' ';
        $pos = strpos($description, $pattern);
        if ($pos !== false) {
            $exclusionPart = substr($description, $pos + strlen($pattern));
            // Find the end of the exclusion (before the next "und" or end of string)
            $endPos = strpos($exclusionPart, ' ' . $this->language['and'] . ' ');
            if ($endPos !== false) {
                $exclusionPart = substr($exclusionPart, 0, $endPos);
            }
            return $section . ' ' . $this->language['without'] . ' ' . $exclusionPart;
        }
        
        return null;
    }
    
    /**
     * Find remaining groups that aren't covered by the exclusion patterns
     */
    private function findRemainingGroupsForMultipleExclusions(array $selectedGroups, array $exclusionPatterns): array
    {
        $remainingGroups = [];
        
        foreach ($selectedGroups as $groupId) {
            $isCovered = false;
            
            foreach ($exclusionPatterns as $pattern) {
                // Check if this group is covered by any of the exclusion patterns
                if ($this->isGroupCoveredByExclusionPattern($groupId, $pattern)) {
                    $isCovered = true;
                    break;
                }
            }
            
            if (!$isCovered) {
                $remainingGroups[] = $groupId;
            }
        }
        
        return $remainingGroups;
    }
    
    /**
     * Check if a group is covered by an exclusion pattern
     */
    private function isGroupCoveredByExclusionPattern(string $groupId, string $pattern): bool
    {
        // Extract the section and excluded items from the pattern
        // Pattern format: "Section ohne item1, item2"
        $specificSections = $this->getSpecificSections();
        
        foreach ($specificSections as $section) {
            if (strpos($pattern, $section . ' ' . $this->language['without']) === 0) {
                // Check if the group is part of this section
                $groupInstruments = $this->getAllInstrumentsInGroup($groupId);
                $sectionInstruments = $this->getAllInstrumentsInGroup($section);
                
                $intersection = array_intersect($groupInstruments, $sectionInstruments);
                return !empty($intersection);
            }
        }
        
        return false;
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
        
        // Skip individual instruments and section-level groups that represent themselves
        // These should not be used as roots for exclusions
        $group = $this->groupManager->getGroup($rootId);
        if ($group) {
            $groupType = $group['type'] ?? '';
            if ($groupType === 'instrument') {
                return null; // Individual instruments cannot be used as roots for exclusions
            }
            if ($groupType === 'section') {
                // Check if this section has children - if not, it represents itself
                $children = $this->groupManager->getChildren($rootId);
                if (empty($children)) {
                    return null; // This section represents itself, not a group to exclude from
                }
            }
        }
        
        $selectedInstruments = [];
        foreach ($selectedGroups as $groupId) {
            $selectedInstruments = array_merge($selectedInstruments, $this->getAllInstrumentsInGroup($groupId));
        }
        $selectedInstruments = array_unique($selectedInstruments);
        
        // Find missing instruments from the root
        $missingInstruments = array_diff($rootInstruments, $selectedInstruments);
        
        // Find instruments outside the root
        $instrumentsOutsideRoot = array_diff($selectedInstruments, $rootInstruments);
        
        // Calculate coverage - what percentage of the selection is within this root
        $rootCoverage = count(array_intersect($selectedInstruments, $rootInstruments)) / count($selectedInstruments);
        
        // Only proceed if this root covers most of the selection (at least 70%)
        if ($rootCoverage < 0.7) {
            return null; // This root doesn't represent most of the selection
        }
        
        // Only proceed if we're missing a reasonable number of instruments
        if (count($missingInstruments) === 0) {
            // Perfect match within root - just return the root name
            if (empty($instrumentsOutsideRoot)) {
                return $this->groupManager->getDisplayName($rootId);
            } else {
                // Root is complete but we have additional instruments outside
                // Only create unified list if the outside instruments are few
                if (count($instrumentsOutsideRoot) <= count($rootInstruments) / 3) {
                    $rootName = $this->groupManager->getDisplayName($rootId);
                    $rootGroupId = $this->getDynamicRootId();
                    $outsideGroups = $this->findGroupsForInstruments($instrumentsOutsideRoot, $rootGroupId);
                    
                    $allItems = [$rootName];
                    foreach ($outsideGroups as $groupId) {
                        $group = $this->groupManager->getGroup($groupId);
                        $allItems[] = $group['display_name'] ?? $groupId;
                    }
                    return $this->generateSimpleListFromNames($allItems);
                } else {
                    return null; // Too many outside instruments
                }
            }
        }
        
        if (count($missingInstruments) > count($rootInstruments) / 2) {
            return null; // Too many missing - not a good "root ohne X" candidate
        }
        
        // Find the most concise way to describe the missing instruments
        $missingGroups = $this->findGroupsForInstruments($missingInstruments, $rootId);
        if (empty($missingGroups)) {
            return null;
        }
        
        // Validate that the exclusion doesn't exclude instruments that are actually selected
        foreach ($missingGroups as $missingGroup) {
            $missingGroupInstruments = $this->getAllInstrumentsInGroup($missingGroup);
            $intersection = array_intersect($missingGroupInstruments, $selectedInstruments);
            if (!empty($intersection)) {
                // This exclusion would exclude instruments that are actually selected - invalid
                return null;
            }
        }
        
        $rootName = $this->groupManager->getDisplayName($rootId);
        $missingDescription = $this->generateSimpleList($missingGroups);
        
        // Separate individual instruments from section groups in instrumentsOutsideRoot
        $individualInstrumentsOutside = [];
        $sectionGroupsOutside = [];
        
        if (!empty($instrumentsOutsideRoot)) {
            foreach ($selectedGroups as $groupId) {
                $groupInstruments = $this->getAllInstrumentsInGroup($groupId);
                foreach ($groupInstruments as $instrument) {
                    if (in_array($instrument, $instrumentsOutsideRoot)) {
                        $group = $this->groupManager->getGroup($instrument);
                        if ($group && ($group['type'] ?? '') === 'section') {
                            // This is a section that represents itself
                            if (!in_array($instrument, $sectionGroupsOutside)) {
                                $sectionGroupsOutside[] = $instrument;
                            }
                        } else {
                            // This is an individual instrument
                            if (!in_array($instrument, $individualInstrumentsOutside)) {
                                $individualInstrumentsOutside[] = $instrument;
                            }
                        }
                    }
                }
            }
        }
        
        // Build description - only if we don't have too many outside instruments
        if (empty($individualInstrumentsOutside) && empty($sectionGroupsOutside)) {
            // Simple case: just exclusions within the root
            $description = $rootName . ' ' . $this->language['without'] . ' ' . $missingDescription;
        } else {
            // We have groups outside the root
            // If too many outside instruments, return null (this root doesn't represent most of the selection)
            $totalOutside = count($individualInstrumentsOutside) + count($sectionGroupsOutside);
            if ($totalOutside > count($rootInstruments)) {
                return null; // Too many outside instruments for this to be a good root description
            }
            
            // Continue with unified description only if reasonable
            $outsideGroups = [];
            
            // Add individual instruments outside
            if (!empty($individualInstrumentsOutside)) {
                $rootGroupId = $this->getDynamicRootId();
                $individualGroups = $this->findGroupsForInstruments($individualInstrumentsOutside, $rootGroupId);
                $outsideGroups = array_merge($outsideGroups, $individualGroups);
            }
            
            // Add section groups outside
            if (!empty($sectionGroupsOutside)) {
                // Sort section groups based on their position in selectedGroups
                usort($sectionGroupsOutside, function($a, $b) use ($selectedGroups) {
                    $posA = array_search($a, $selectedGroups);
                    $posB = array_search($b, $selectedGroups);
                    return $posA <=> $posB;
                });
                $outsideGroups = array_merge($outsideGroups, $sectionGroupsOutside);
            }
            
            // Remove duplicates and preserve order
            $outsideGroups = array_values(array_unique($outsideGroups));
            
            if (!empty($outsideGroups)) {
                // Add the root group to the list for proper comma formatting
                $allGroups = array_merge($outsideGroups, [$rootId]);
                $allGroupsDescription = $this->generateSimpleList($allGroups);
                $description = $allGroupsDescription . ' ' . $this->language['without'] . ' ' . $missingDescription;
            } else {
                $description = $rootName . ' ' . $this->language['without'] . ' ' . $missingDescription;
            }
        }
        
        return $description;
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
     * Handles pre-formatted strings properly to avoid double "und" issues
     */
    private function combineUnits(array $units): string
    {
        if (count($units) === 1) {
            return $units[0];
        }
        
        // Parse each unit to extract individual items and avoid double "und"
        $allItems = [];
        
        foreach ($units as $unit) {
            $items = $this->parseUnitIntoItems($unit);
            $allItems = array_merge($allItems, $items);
        }
        
        // Use the proper list formatter for all items
        return $this->generateSimpleListFromNames($allItems);
    }
    
    /**
     * Parse a unit string into individual items
     * E.g., "Streicher und Schlagwerk" -> ["Streicher", "Schlagwerk"]
     * E.g., "Bläser ohne Klarinette" -> ["Bläser ohne Klarinette"] (keep complex patterns intact)
     */
    private function parseUnitIntoItems(string $unit): array
    {
        // If the unit contains "ohne" (without), keep it as a single complex item
        if (strpos($unit, $this->language['without']) !== false) {
            return [$unit];
        }
        
        // If the unit contains "aber" (but), keep it as a single complex item  
        if (strpos($unit, $this->language['but']) !== false) {
            return [$unit];
        }
        
        // Otherwise, split on "und" and commas to get individual items
        $items = preg_split('/[,]?\s+' . preg_quote($this->language['and']) . '\s+|[,\s]+/', $unit);
        $items = array_map('trim', $items);
        $items = array_filter($items, function($item) {
            return !empty($item) && $item !== $this->language['and'];
        });
        
        return array_values($items);
    }
    
    /**
     * Compress sections by replacing complete child sets with their parent
     * Also handle sections already in the list that have missing children
     * AND handle individual instruments that form most of a section
     */
    private function compressSections(array $selectedGroups): array
    {
        $compressed = $selectedGroups;
        $allSections = $this->groupManager->getAllSections();
        
        foreach ($allSections as $sectionId => $sectionData) {
            // Get all children of this section
            $children = $this->groupManager->getChildren($sectionId);
            if (empty($children)) {
                continue;
            }
            
            $childIds = array_map(fn($child) => $child['id'], $children);
            
            // If section is already in the list, check if it should be replaced with exclusion description
            if (in_array($sectionId, $compressed)) {
                // Check if some children are missing (not all selected)
                $selectedChildren = array_intersect($compressed, $childIds);
                $missingChildren = array_diff($childIds, $compressed);
                
                // If we have some children selected individually AND some missing, 
                // replace with "Section ohne missing" format
                if (!empty($selectedChildren) && !empty($missingChildren) && count($missingChildren) <= count($childIds) / 2) {
                    // Get all instruments in this section
                    $sectionInstruments = $this->getAllInstrumentsInGroup($sectionId);
                    $selectedInstruments = [];
                    
                    foreach ($compressed as $groupId) {
                        if ($groupId !== $sectionId) { // Don't include the section itself in the selected instruments
                            $groupInstruments = $this->getAllInstrumentsInGroup($groupId);
                            $intersection = array_intersect($groupInstruments, $sectionInstruments);
                            $selectedInstruments = array_merge($selectedInstruments, $intersection);
                        }
                    }
                    $selectedInstruments = array_unique($selectedInstruments);
                    
                    $missingSectionInstruments = array_diff($sectionInstruments, $selectedInstruments);
                    
                    // Only replace if we have a reasonable number of missing instruments
                    if (!empty($missingSectionInstruments) && count($missingSectionInstruments) <= count($sectionInstruments) / 2) {
                        $missingGroups = $this->findGroupsForInstruments($missingSectionInstruments, $sectionId);
                        if (!empty($missingGroups)) {
                            $sectionName = $this->groupManager->getDisplayName($sectionId);
                            $missingDescription = $this->generateSimpleList($missingGroups);
                            $exclusionDescription = $sectionName . ' ' . $this->language['without'] . ' ' . $missingDescription;
                            
                            // Remove the original section and add the exclusion description as a special marker
                            $compressed = array_diff($compressed, [$sectionId]);
                            $compressed[] = '__EXCLUSION__' . $exclusionDescription;
                        }
                    }
                }
                continue;
            }
            
            // NEW: Check if most children are selected individually (not the section itself)
            // This handles cases like [Flöte, Oboe, Fagott] where Klarinette is missing
            $selectedChildren = array_intersect($compressed, $childIds);
            if (count($selectedChildren) >= 2 && count($selectedChildren) < count($childIds)) {
                // We have some but not all children selected
                $missingChildren = array_diff($childIds, $selectedChildren);
                
                // Only proceed if we have high coverage (at least 75%) and reasonable number of missing items
                // This prevents over-compression of sections like Streicher where individual instruments should remain separate
                $coverage = count($selectedChildren) / count($childIds);
                if ($coverage >= 0.75 && count($missingChildren) <= 2) {
                    // Additional check: Only compress sections with compact instruments (wind/brass sections)
                    // Skip sections like Streicher where individual instruments are commonly selected separately
                    if ($this->shouldCompressSection($sectionId, $selectedChildren, $missingChildren)) {
                        // Get all instruments in this section and check what's actually missing
                        $sectionInstruments = $this->getAllInstrumentsInGroup($sectionId);
                        $selectedInstruments = [];
                        
                        foreach ($selectedChildren as $childId) {
                            $childInstruments = $this->getAllInstrumentsInGroup($childId);
                            $selectedInstruments = array_merge($selectedInstruments, $childInstruments);
                        }
                        $selectedInstruments = array_unique($selectedInstruments);
                        
                        $missingSectionInstruments = array_diff($sectionInstruments, $selectedInstruments);
                        
                        // Only proceed if we have missing instruments and they're not too many
                        if (!empty($missingSectionInstruments) && count($missingSectionInstruments) <= count($sectionInstruments) / 2) {
                            $missingGroups = $this->findGroupsForInstruments($missingSectionInstruments, $sectionId);
                            if (!empty($missingGroups)) {
                                $sectionName = $this->groupManager->getDisplayName($sectionId);
                                $missingDescription = $this->generateSimpleList($missingGroups);
                                $exclusionDescription = $sectionName . ' ' . $this->language['without'] . ' ' . $missingDescription;
                                
                                // Replace the selected children with the exclusion description
                                $compressed = array_diff($compressed, $selectedChildren);
                                $compressed[] = '__EXCLUSION__' . $exclusionDescription;
                            }
                        }
                    }
                }
                continue;
            }
            
            // Check if ALL children are selected (original compression logic)
            if (count($selectedChildren) === count($childIds) && count($selectedChildren) > 1) {
                // Replace all children with the parent section
                $compressed = array_diff($compressed, $childIds);
                $compressed[] = $sectionId;
            }
        }
        
        return array_values($compressed);
    }
    
    /**
     * Determine if a section should be compressed with exclusions
     * Some sections (like wind/brass) work well with "section ohne instrument" descriptions
     * Others (like strings) are better left as individual instruments
     */
    private function shouldCompressSection(string $sectionId, array $selectedChildren, array $missingChildren): bool
    {
        // Wind and brass sections work well with exclusion descriptions
        $compressibleSections = ['Holzbläser', 'Blechbläser', 'Bläser'];
        
        // String sections are commonly selected as individual instruments and should remain separate
        $nonCompressibleSections = ['Streicher'];
        
        if (in_array($sectionId, $nonCompressibleSections)) {
            return false; // Never compress these sections
        }
        
        if (in_array($sectionId, $compressibleSections)) {
            return true; // Always compress these sections when criteria are met
        }
        
        // For other sections, use heuristics
        $group = $this->groupManager->getGroup($sectionId);
        if (!$group) {
            return false;
        }
        
        // Check if this is a wind/brass related section by looking at its children
        $children = $this->groupManager->getChildren($sectionId);
        $windBrassInstruments = ['Flöte', 'Oboe', 'Klarinette', 'Fagott', 'Horn', 'Trompete', 'Posaune', 'Tuba'];
        
        foreach ($children as $child) {
            if (in_array($child['id'], $windBrassInstruments)) {
                return true; // This section contains wind/brass instruments
            }
        }
        
        // Default to not compressing unknown sections
        return false;
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
        
        // Heavy penalty for very broad exclusions from large groups
        $rootGroup = $this->getRootGroup();
        $rootDisplayName = $rootGroup ? $rootGroup['display_name'] : $this->getDynamicRootId();
        if (strpos($description, $rootDisplayName) === 0 && strpos($description, $this->language['without']) !== false) {
            $score += 50; // Heavy penalty for starting with root group exclusions - prefer specific sections
        }
        
        // Handle multiple exclusion patterns
        $ohneCount = substr_count($description, $this->language['without']);
        if ($ohneCount > 1) {
            // Check if these are meaningful multiple exclusions (like "Holzbläser ohne Flöte und Blechbläser ohne Horn")
            $meaningfulMultipleExclusions = $this->isMeaningfulMultipleExclusions($description);
            if ($meaningfulMultipleExclusions) {
                $score -= 10; // Bonus for meaningful multiple exclusions
            } else {
                $score += 15; // Penalty for overly complex descriptions
            }
        }
        
        // MAJOR bonus for compressed sections with clean exclusions
        // Prefer "Holzbläser ohne Klarinette" over "Flöte, Oboe, Fagott"
        $specificSections = $this->getSpecificSections();
        foreach ($specificSections as $section) {
            if (strpos($description, $section . ' ' . $this->language['without']) !== false) {
                $score -= 50; // Major bonus for section-level exclusions
            }
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
        
        $analysis['final_description'] = $this->generateBaseDescription($selectedGroups);
        
        return $analysis;
    }
    
    /**
     * Check if a description contains meaningful multiple exclusions
     */
    private function isMeaningfulMultipleExclusions(string $description): bool
    {
        // Look for patterns like "Holzbläser ohne Flöte und Blechbläser ohne Horn"
        // These are meaningful because they represent specific missing instruments from different sections
        
        $specificSections = $this->getSpecificSections();
        $exclusionPatterns = [];
        
        foreach ($specificSections as $section) {
            // Count how many times this section appears with "ohne"
            $pattern = $section . ' ' . $this->language['without'];
            $count = substr_count($description, $pattern);
            if ($count > 0) {
                $exclusionPatterns[] = $section;
            }
        }
        
        // If we have multiple different sections with exclusions, it's meaningful
        return count($exclusionPatterns) >= 2;
    }
}
