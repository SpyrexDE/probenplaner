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

}
