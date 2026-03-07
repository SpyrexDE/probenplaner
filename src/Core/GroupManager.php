<?php

namespace App\Core;

/**
 * Group Manager Class
 * 
 * Handles all group/section/instrument related operations dynamically
 * based on the configuration file. Supports any hierarchical tree structure.
 * Uses singleton pattern to avoid redundant config loading.
 */
class GroupManager
{
    /** @var array<int|string, GroupManager> Orchestra-keyed instance cache */
    private static array $instances = [];

    private array $config;
    private array $flatMapping = [];
    private array $parentMapping = [];
    private array $childMapping = [];
    private array $instrumentsCache = [];
    private array $aliasMap = [];
    private array $membershipCache = [];

    /**
     * Create a GroupManager from an arbitrary config (for per-orchestra overrides).
     */
    public static function fromConfig(array $config): self
    {
        $gm = new self(skipLoad: true);
        $gm->config = $config;
        $gm->buildMappings();
        $gm->buildAliasMap();
        return $gm;
    }

    /**
     * @return array The global default config from orchestra_groups.php
     */
    public static function getDefaultConfig(): array
    {
        $path = __DIR__ . '/../config/orchestra_groups.php';
        return require $path;
    }

    /**
     * Get the singleton for the current orchestra context.
     * Uses custom section_config from DB if available, otherwise global default.
     */
    public static function getInstance(): self
    {
        $orchestraId = $_SESSION['current_orchestra_id'] ?? 'default';

        if (!isset(self::$instances[$orchestraId])) {
            self::$instances[$orchestraId] = new self();
        }
        return self::$instances[$orchestraId];
    }

    /**
     * Reset cached instance for an orchestra (call after saving new section_config).
     */
    public static function resetInstance(?int $orchestraId = null): void
    {
        $key = $orchestraId ?? ($_SESSION['current_orchestra_id'] ?? 'default');
        unset(self::$instances[$key]);
    }

    public function __construct(bool $skipLoad = false)
    {
        if ($skipLoad) return;
        $this->loadConfig();
        $this->buildMappings();
        $this->buildAliasMap();
    }

    /**
     * Load config: prefer orchestra-specific section_config from DB, fall back to global default.
     */
    private function loadConfig(): void
    {
        $orchestraId = $_SESSION['current_orchestra_id'] ?? null;

        if ($orchestraId) {
            $orchestra = (new \App\Models\Orchestra())->findById((int) $orchestraId);
            if ($orchestra && !empty($orchestra['section_config'])) {
                $custom = is_string($orchestra['section_config'])
                    ? json_decode($orchestra['section_config'], true)
                    : $orchestra['section_config'];

                if (is_array($custom) && !empty($custom)) {
                    $this->config = $custom;
                    return;
                }
            }
        }

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
     * Pre-build reverse alias lookup map
     */
    private function buildAliasMap(): void
    {
        $this->aliasMap = [];
        foreach ($this->flatMapping as $groupId => $group) {
            if (isset($group['aliases'])) {
                foreach ($group['aliases'] as $alias) {
                    $this->aliasMap[$alias] = $groupId;
                }
            }
        }
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

            $this->flatMapping[$id] = $node;

            if ($parentId) {
                $this->parentMapping[$id] = $parentId;

                if (!isset($this->childMapping[$parentId])) {
                    $this->childMapping[$parentId] = [];
                }
                $this->childMapping[$parentId][] = $id;
            }

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
        return array_filter($this->flatMapping, function ($group) {
            return ($group['type'] ?? '') === 'instrument';
        });
    }

    /**
     * Get all sections (non-leaf nodes)
     */
    public function getAllSections(): array
    {
        return array_filter($this->flatMapping, function ($group) {
            return ($group['type'] ?? '') === 'section';
        });
    }

    /**
     * @return array Top-level config nodes (those without a parent)
     */
    public function getRootNodes(): array
    {
        $roots = [];
        foreach ($this->flatMapping as $id => $group) {
            if (!isset($this->parentMapping[$id])) {
                $roots[$id] = $group;
            }
        }
        return $roots;
    }

    /**
     * Build a pruned config tree containing only branches with present instruments.
     * Single-child chains are collapsed so the tree stays minimal.
     *
     * @param string[] $presentInstrumentIds Instrument IDs that have members
     * @return array Pruned tree nodes ready for rendering
     */
    public function pruneTree(array $presentInstrumentIds): array
    {
        $present = array_flip($presentInstrumentIds);
        $roots = $this->getRootNodes();

        $pruned = [];
        foreach ($roots as $id => $root) {
            $node = $this->pruneNode($root, $present);
            if ($node !== null) {
                $pruned[$id] = $node;
            }
        }

        // Collapse single-child wrapper chains at the top level
        return $this->collapseSingleChildRoots($pruned);
    }

    /**
     * Recursively prune a single node: strip empty branches, collapse single-child chains.
     */
    private function pruneNode(array $node, array $presentMap): ?array
    {
        $nodeId = $node['id'] ?? null;
        $children = $node['children'] ?? [];

        if (empty($children)) {
            // Leaf node — keep only if instrument is present
            return isset($presentMap[$nodeId]) ? $node : null;
        }

        // Recurse into children
        $kept = [];
        foreach ($children as $key => $child) {
            if (!is_array($child) || !isset($child['id'])) continue;
            $pruned = $this->pruneNode($child, $presentMap);
            if ($pruned !== null) {
                $kept[$key] = $pruned;
            }
        }

        if (empty($kept)) {
            return null;
        }

        // Collapse: if only one child remains, promote it
        if (count($kept) === 1) {
            return reset($kept);
        }

        $node['children'] = $kept;
        return $node;
    }

    /**
     * Second pass: collapse any remaining single-child wrappers at root level.
     */
    private function collapseSingleChildRoots(array $roots): array
    {
        $result = [];
        foreach ($roots as $key => $node) {
            $children = $node['children'] ?? [];
            if (count($children) === 1 && !empty($children)) {
                $only = reset($children);
                $result[array_key_first($children)] = $only;
            } else {
                $result[$key] = $node;
            }
        }
        return $result;
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
     * Get all instrument IDs that belong to a specific group (cached)
     */
    public function getInstrumentsByGroup(string $groupId): array
    {
        if (isset($this->instrumentsCache[$groupId])) {
            return $this->instrumentsCache[$groupId];
        }

        if (($this->flatMapping[$groupId]['type'] ?? '') === 'instrument') {
            $this->instrumentsCache[$groupId] = [$groupId];
            return [$groupId];
        }

        $instruments = [];
        $descendants = $this->getDescendants($groupId);
        foreach ($descendants as $desc) {
            if (($desc['type'] ?? '') === 'instrument') {
                $instruments[] = $desc['id'];
            }
        }

        $this->instrumentsCache[$groupId] = $instruments;
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

        return array_reverse($ancestors);
    }

    /**
     * Check if a user type belongs to a specific rehearsal group (cached)
     */
    public function isUserInGroup(string $userType, string $groupId): bool
    {
        $cacheKey = "$userType|$groupId";
        if (isset($this->membershipCache[$cacheKey])) {
            return $this->membershipCache[$cacheKey];
        }

        if ($userType === $groupId) {
            $this->membershipCache[$cacheKey] = true;
            return true;
        }

        $instruments = $this->getInstrumentsByGroup($groupId);
        $result = in_array($userType, $instruments);
        $this->membershipCache[$cacheKey] = $result;
        return $result;
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
     * Resolve alias to canonical group ID (O(1) lookup)
     */
    public function resolveAlias(string $id): string
    {
        return $this->aliasMap[$id] ?? $id;
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

    /**
     * Flatten the config tree into ordered [parentId => [leafId, ...]] groups.
     * Only the immediate parent of leaf nodes becomes a group key.
     * Ungrouped root-level leaves use '' as key.
     *
     * @return array<string, string[]> Ordered parent ID → leaf IDs
     */
    public function getFlattenedSections(): array
    {
        // Collect children of all root nodes as the flattening source
        $nodes = [];
        foreach ($this->getRootNodes() as $root) {
            if (!empty($root['children'])) {
                foreach ($root['children'] as $k => $child) {
                    $nodes[$k] = $child;
                }
            } else {
                // Root with no children is itself a leaf
                $nodes[$root['id']] = $root;
            }
        }

        return self::flattenRecursive($nodes);
    }

    /**
     * Recursively flatten tree nodes into ordered [parentId => [leafId, ...]] groups.
     */
    private static function flattenRecursive(array $nodes, ?string $parentId = null): array
    {
        $groups = [];
        foreach ($nodes as $node) {
            if (!is_array($node) || !isset($node['id'])) continue;

            if (empty($node['children'])) {
                $key = $parentId ?? '';
                $groups[$key][] = $node['id'];
                continue;
            }

            $allLeaves = true;
            foreach ($node['children'] as $child) {
                if (!empty($child['children'])) {
                    $allLeaves = false;
                    break;
                }
            }

            if ($allLeaves) {
                foreach ($node['children'] as $child) {
                    if (!is_array($child) || !isset($child['id'])) continue;
                    $groups[$node['id']][] = $child['id'];
                }
            } else {
                $sub = self::flattenRecursive($node['children'], null);
                foreach ($sub as $k => $v) {
                    if (isset($groups[$k])) {
                        $groups[$k] = array_merge($groups[$k], $v);
                    } else {
                        $groups[$k] = $v;
                    }
                }
            }
        }
        return $groups;
    }
}
