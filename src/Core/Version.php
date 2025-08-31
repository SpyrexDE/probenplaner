<?php

namespace App\Core;

class Version
{
    /**
     * Get the current version string
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
        
        // Fallback to vN/A if no git version available
        return 'vN/A';
    }
    
    /**
     * Get a short version string (just the commit hash)
     * 
     * @return string
     */
    public static function getShortVersion(): string
    {
        // Check for git version from environment variable (set during Docker build)
        $gitVersion = $_ENV['GIT_VERSION'] ?? $_SERVER['GIT_VERSION'] ?? getenv('GIT_VERSION') ?? null;
        
        if (!empty($gitVersion) && $gitVersion !== 'N/A') {
            return $gitVersion;
        }
        
        return 'N/A';
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

}
