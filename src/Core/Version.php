<?php

namespace App\Core;

class Version
{
    /**
     * Get the current version string from environment or git
     * 
     * @return string
     */
    public static function getVersion(): string
    {
        // Check for git version from environment variable (set during Docker build)
        $gitVersion = $_ENV['GIT_VERSION'] ?? $_SERVER['GIT_VERSION'] ?? getenv('GIT_VERSION') ?? null;
        
        if (!empty($gitVersion) && $gitVersion !== 'N/A') {
            return $gitVersion;
        }
        
        // Try to get version directly from git as fallback
        return self::getGitVersionDirect();
    }
    
    /**
     * Get version directly from git command
     * 
     * @return string
     */
    private static function getGitVersionDirect(): string
    {
        try {
            // Try to get git version if git is available and we're in a git repo
            $gitVersion = @shell_exec('git describe --tags --always 2>/dev/null');
            if ($gitVersion && trim($gitVersion) !== '') {
                return trim($gitVersion);
            }
        } catch (\Exception $e) {
            // Ignore git errors and fallback
        }
        
        return 'N/A';
    }
    
    /**
     * Get a detailed version string for display
     * 
     * @return string
     */
    public static function getDetailedVersion(): string
    {
        $version = self::getVersion();
        
        if ($version === 'N/A' || $version === 'vN/A') {
            return $version;
        }
        
        // Parse version string like "v0.1.5-3-g0b8d464"
        if (preg_match('/^(v\d+\.\d+\.\d+)(?:-(\d+)-g([a-f0-9]+))?$/', $version, $matches)) {
            $tag = $matches[1];
            $commitsBehind = isset($matches[2]) ? intval($matches[2]) : 0;
            $commitHash = isset($matches[3]) ? $matches[3] : null;
            
            if ($commitsBehind === 0) {
                return $tag; // Exact tag match
            } else {
                return sprintf('%s (%d commits, %s)', $tag, $commitsBehind, $commitHash);
            }
        }
        
        // If parsing fails, return the raw version
        return $version;
    }
    
    /**
     * Get a short version string (just the commit hash)
     * 
     * @return string
     */
    public static function getShortVersion(): string
    {
        $version = self::getVersion();
        
        if ($version === 'N/A' || $version === 'vN/A') {
            return $version;
        }
        
        // Extract commit hash from version string like "v0.1.5-3-g0b8d464"
        if (preg_match('/^v\d+\.\d+\.\d+(?:-\d+-g([a-f0-9]+))?$/', $version, $matches)) {
            if (isset($matches[1])) {
                return $matches[1]; // Return just the commit hash
            }
        }
        
        // If no commit hash found, return the full version
        return $version;
    }

    /**
     * Get just the tag from the version string
     * 
     * @return string
     */
    public static function getTag(): string
    {
        $version = self::getVersion();
        
        // Extract tag from version string like "v0.0.1-1-g53fab78"
        if (preg_match('/^(v\d+\.\d+\.\d+)/', $version, $matches)) {
            return $matches[1];
        }
        
        // If no tag found, return the full version
        return $version;
    }
    
    /**
     * Get the number of commits behind the current tag
     * 
     * @return int
     */
    public static function getCommitsBehind(): int
    {
        $version = self::getVersion();
        
        // Extract commits behind from version string like "v0.1.5-3-g0b8d464"
        if (preg_match('/^v\d+\.\d+\.\d+-(\d+)-g[a-f0-9]+$/', $version, $matches)) {
            return intval($matches[1]);
        }
        
        return 0;
    }
    
    /**
     * Get the commit hash from version string
     * 
     * @return string|null
     */
    public static function getCommitHash(): ?string
    {
        $version = self::getVersion();
        
        // Extract commit hash from version string like "v0.1.5-3-g0b8d464"
        if (preg_match('/^v\d+\.\d+\.\d+(?:-\d+-g([a-f0-9]+))?$/', $version, $matches)) {
            return isset($matches[1]) ? $matches[1] : null;
        }
        
        return null;
    }

}
