<?php
namespace App\Core;

/**
 * Language Constants for Smart Group Display
 * 
 * Provides language-specific constants for generating natural language
 * descriptions of rehearsal groups.
 */
class SmartDisplayLanguage
{
    public const LANGUAGES = [
        'de' => [
            'and' => 'und',
            'without' => 'ohne',
            'but' => 'aber',
            'all' => 'alle',
            'except' => 'außer'
        ],
        'en' => [
            'and' => 'and', 
            'without' => 'without',
            'but' => 'but',
            'all' => 'all',
            'except' => 'except'
        ],
        'fr' => [
            'and' => 'et',
            'without' => 'sans', 
            'but' => 'mais',
            'all' => 'tous',
            'except' => 'sauf'
        ],
        'es' => [
            'and' => 'y',
            'without' => 'sin',
            'but' => 'pero', 
            'all' => 'todos',
            'except' => 'excepto'
        ],
        'it' => [
            'and' => 'e',
            'without' => 'senza',
            'but' => 'ma',
            'all' => 'tutti',
            'except' => 'eccetto'
        ]
    ];
    
    /**
     * Get language constants for a specific language
     * 
     * @param string $language Language code (de, en, fr, es, it)
     * @return array Language constants
     */
    public static function getLanguage(string $language = 'de'): array
    {
        return self::LANGUAGES[$language] ?? self::LANGUAGES['de'];
    }
    
    /**
     * Get all available language codes
     * 
     * @return array Available language codes
     */
    public static function getAvailableLanguages(): array
    {
        return array_keys(self::LANGUAGES);
    }
    
    /**
     * Create SmartGroupDisplay instance with specific language
     * 
     * @param string $language Language code
     * @return SmartGroupDisplay Configured instance
     */
    public static function createDisplay(string $language = 'de'): SmartGroupDisplay
    {
        return new SmartGroupDisplay(self::getLanguage($language));
    }
}
