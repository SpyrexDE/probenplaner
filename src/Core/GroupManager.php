<?php
namespace App\Core;

/**
 * Group Manager Class
 * 
 * Handles all group/section/instrument related operations dynamically
 * based on the configuration file. Supports any hierarchical tree structure.
 */
class GroupManager
{
    private array $config;
    private array $flatMapping = [];
    private array $parentMapping = [];
    private array $childMapping = [];
    
    public function __construct()
    {
        $this->loadConfig();
        $this->buildMappings();
    }
    
    /**
     * Load group configuration from file
     */
    private function loadConfig(): void
    {
        $configPath = __DIR__ . '/../config/orchestra_groups.php';
        if (!file_exists($configPath)) {
            throw new \Exception("Group configuration file not found: " . $configPath);
        }
        
        $config = require $configPath;
        
        if (!is_array($config)) {
            throw new \Exception("Group configuration file must return an array. Got: " . gettype($config));
        }
        
        $this->config = $config;
    }
    
    /**
     * Build flat mappings for quick lookups
     */
    private function buildMappings(): void
    {
        $this->flatMapping = [];
        $this->parentMapping = [];
        $this->childMapping = [];
        
        $this->buildMappingsRecursive($this->config, null);
    }
    
    /**
     * Recursively build mappings from the configuration tree
     */
    private function buildMappingsRecursive(array $nodes, ?string $parentId): void
    {
        foreach ($nodes as $key => $node) {
            if (!is_array($node) || !isset($node['id'])) {
                continue;
            }
            
            $id = $node['id'];
            
            // Store flat mapping
            $this->flatMapping[$id] = $node;
            
            // Store parent mapping
            if ($parentId) {
                $this->parentMapping[$id] = $parentId;
                
                // Store child mapping
                if (!isset($this->childMapping[$parentId])) {
                    $this->childMapping[$parentId] = [];
                }
                $this->childMapping[$parentId][] = $id;
            }
            
            // Process children
            if (isset($node['children']) && is_array($node['children'])) {
                $this->buildMappingsRecursive($node['children'], $id);
            }
        }
    }
    
    /**
     * Get the complete configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
    
    /**
     * Get a specific group by ID
     */
    public function getGroup(string $id): ?array
    {
        return $this->flatMapping[$id] ?? null;
    }
    
    /**
     * Get all groups as a flat array
     */
    public function getAllGroups(): array
    {
        return $this->flatMapping;
    }
    
    /**
     * Get all instruments (leaf nodes)
     */
    public function getAllInstruments(): array
    {
        return array_filter($this->flatMapping, function($group) {
            return ($group['type'] ?? '') === 'instrument';
        });
    }
    
    /**
     * Get all sections (non-leaf nodes, excluding special groups)
     */
    public function getAllSections(): array
    {
        return array_filter($this->flatMapping, function($group) {
            return ($group['type'] ?? '') === 'section';
        });
    }
    
    /**
     * Get children of a specific group
     */
    public function getChildren(string $groupId): array
    {
        $children = $this->childMapping[$groupId] ?? [];
        return array_map(fn($id) => $this->flatMapping[$id], $children);
    }
    
    /**
     * Get all descendants of a group (recursive)
     */
    public function getDescendants(string $groupId): array
    {
        $descendants = [];
        $directChildren = $this->childMapping[$groupId] ?? [];
        
        foreach ($directChildren as $childId) {
            $descendants[] = $this->flatMapping[$childId];
            $descendants = array_merge($descendants, $this->getDescendants($childId));
        }
        
        return $descendants;
    }
    
    /**
     * Get all instrument IDs that belong to a specific group (recursive)
     */
    public function getInstrumentsByGroup(string $groupId): array
    {
        $instruments = [];
        
        // If this group itself is an instrument, return it
        if (($this->flatMapping[$groupId]['type'] ?? '') === 'instrument') {
            return [$groupId];
        }
        
        // Get all descendants and filter instruments
        $descendants = $this->getDescendants($groupId);
        foreach ($descendants as $desc) {
            if (($desc['type'] ?? '') === 'instrument') {
                $instruments[] = $desc['id'];
            }
        }
        
        return $instruments;
    }
    
    /**
     * Get parent group of a specific group
     */
    public function getParent(string $groupId): ?array
    {
        $parentId = $this->parentMapping[$groupId] ?? null;
        return $parentId ? $this->flatMapping[$parentId] : null;
    }
    
    /**
     * Get all ancestors of a group (up to root)
     */
    public function getAncestors(string $groupId): array
    {
        $ancestors = [];
        $currentId = $groupId;
        
        while (isset($this->parentMapping[$currentId])) {
            $parentId = $this->parentMapping[$currentId];
            $ancestors[] = $this->flatMapping[$parentId];
            $currentId = $parentId;
        }
        
        return array_reverse($ancestors); // Root first
    }
    
    /**
     * Check if a user type belongs to a specific rehearsal group
     */
    public function isUserInGroup(string $userType, string $groupId): bool
    {
        // Handle special groups that affect all users
        $group = $this->getGroup($groupId);
        if ($group && isset($group['special_rules']['affects_all']) && $group['special_rules']['affects_all']) {
            return true;
        }
        
        // Direct match
        if ($userType === $groupId) {
            return true;
        }
        
        // Check if user type is a descendant of the group
        $instruments = $this->getInstrumentsByGroup($groupId);
        return in_array($userType, $instruments);
    }
    
    /**
     * Check if a user belongs to any of the specified groups
     */
    public function isUserInAnyGroup(string $userType, array $groupIds): bool
    {
        foreach ($groupIds as $groupId) {
            if ($this->isUserInGroup($userType, $groupId)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get section name for a user type (for display purposes)
     */
    public function getSectionForInstrument(string $instrumentId): ?string
    {
        $ancestors = $this->getAncestors($instrumentId);
        
        // Find the first section-type ancestor
        foreach ($ancestors as $ancestor) {
            if (($ancestor['type'] ?? '') === 'section') {
                return $ancestor['id'];
            }
        }
        
        return null;
    }
    

    
    /**
     * Get display name for a group, with fallback to ID
     */
    public function getDisplayName(string $groupId): string
    {
        $group = $this->getGroup($groupId);
        return $group['display_name'] ?? $groupId;
    }
    
    /**
     * Get plural form if available
     */
    public function getPluralName(string $groupId): string
    {
        $group = $this->getGroup($groupId);
        return $group['plural'] ?? $this->getDisplayName($groupId);
    }
    
    /**
     * Check if a group ID matches any aliases
     */
    public function resolveAlias(string $id): string
    {
        foreach ($this->flatMapping as $groupId => $group) {
            if (isset($group['aliases']) && in_array($id, $group['aliases'])) {
                return $groupId;
            }
        }
        return $id;
    }
    
    /**
     * Get the tree structure for a specific branch (for component rendering)
     */
    public function getTreeForComponent(string $rootId): array
    {
        $root = $this->getGroup($rootId);
        if (!$root) {
            return [];
        }
        
        $tree = $root;
        if (isset($tree['children'])) {
            $tree['children'] = $this->buildTreeChildren($tree['children']);
        }
        
        return $tree;
    }
    
    /**
     * Helper method to build tree children recursively
     */
    private function buildTreeChildren(array $children): array
    {
        $result = [];
        foreach ($children as $key => $child) {
            if (is_array($child) && isset($child['id'])) {
                $childData = $this->flatMapping[$child['id']];
                if (isset($childData['children'])) {
                    $childData['children'] = $this->buildTreeChildren($childData['children']);
                }
                $result[$key] = $childData;
            }
        }
        return $result;
    }
}
