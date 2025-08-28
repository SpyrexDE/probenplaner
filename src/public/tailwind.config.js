/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.{php,html,js}",
    "./src/Views/**/*.php",
    "./src/public/**/*.{html,js,php}"
  ],
  theme: {
    extend: {
      // Custom colors matching our theme system
      colors: {
        // Primary brand colors
        primary: {
          DEFAULT: '#478cf4',
          50: '#f0f7ff',
          100: '#dbeafe',
          200: '#bfdbfe', 
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#478cf4',
          600: '#3a7bd5',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        },
        // Secondary brand colors
        secondary: {
          DEFAULT: '#f4476b',
          50: '#fef2f2',
          100: '#fee2e2',
          200: '#fecaca',
          300: '#fca5a5',
          400: '#f87171',
          500: '#f4476b',
          600: '#eb3b60',
          700: '#dc2626',
          800: '#b91c1c',
          900: '#991b1b',
        },
        // Status colors
        success: {
          DEFAULT: '#10b981',
          50: '#ecfdf5',
          100: '#d1fae5',
          200: '#a7f3d0',
          300: '#6ee7b7',
          400: '#34d399',
          500: '#10b981',
          600: '#059669',
          700: '#047857',
          800: '#065f46',
          900: '#064e3b',
        },
        error: {
          DEFAULT: '#ef4444',
          50: '#fef2f2',
          100: '#fee2e2',
          200: '#fecaca',
          300: '#fca5a5',
          400: '#f87171',
          500: '#ef4444',
          600: '#dc2626',
          700: '#b91c1c',
          800: '#991b1b',
          900: '#7f1d1d',
        },
        warning: {
          DEFAULT: '#f59e0b',
          50: '#fffbeb',
          100: '#fef3c7',
          200: '#fde68a',
          300: '#fcd34d',
          400: '#fbbf24',
          500: '#f59e0b',
          600: '#d97706',
          700: '#b45309',
          800: '#92400e',
          900: '#78350f',
        },
        info: {
          DEFAULT: '#3b82f6',
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        },
      },
      
      // Custom spacing scale
      spacing: {
        '1': '0.25rem',    // 4px
        '2': '0.5rem',     // 8px
        '3': '0.75rem',    // 12px
        '4': '1rem',       // 16px
        '5': '1.25rem',    // 20px
        '6': '1.5rem',     // 24px
        '8': '2rem',       // 32px
        '10': '2.5rem',    // 40px
        '12': '3rem',      // 48px
        '16': '4rem',      // 64px
        '20': '5rem',      // 80px
        '24': '6rem',      // 96px
        '32': '8rem',      // 128px
      },
      
      // Custom font families
      fontFamily: {
        'sans': ['Roboto', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
        'brand': ['Fugaz One', 'cursive'],
        'mono': ['SF Mono', 'Monaco', 'Cascadia Code', 'Roboto Mono', 'Consolas', 'Courier New', 'monospace'],
      },
      
      // Custom font sizes
      fontSize: {
        'xs': ['0.75rem', { lineHeight: '1rem' }],      // 12px
        'sm': ['0.875rem', { lineHeight: '1.25rem' }],  // 14px
        'base': ['1rem', { lineHeight: '1.5rem' }],     // 16px
        'lg': ['1.125rem', { lineHeight: '1.75rem' }],  // 18px
        'xl': ['1.25rem', { lineHeight: '1.75rem' }],   // 20px
        '2xl': ['1.5rem', { lineHeight: '2rem' }],      // 24px
        '3xl': ['1.875rem', { lineHeight: '2.25rem' }], // 30px
        '4xl': ['2.25rem', { lineHeight: '2.5rem' }],   // 36px
        '5xl': ['3rem', { lineHeight: '1' }],           // 48px
        '6xl': ['3.75rem', { lineHeight: '1' }],        // 60px
      },
      
      // Custom border radius
      borderRadius: {
        'none': '0',
        'sm': '0.25rem',    // 4px
        'DEFAULT': '0.5rem', // 8px
        'md': '0.75rem',    // 12px
        'lg': '1rem',       // 16px
        'xl': '1.5rem',     // 24px
        '2xl': '2rem',      // 32px
        'full': '9999px',
      },
      
      // Custom shadows
      boxShadow: {
        'sm': '0 1px 2px rgba(0, 0, 0, 0.05)',
        'DEFAULT': '0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06)',
        'md': '0 4px 6px rgba(0, 0, 0, 0.07), 0 2px 4px rgba(0, 0, 0, 0.06)',
        'lg': '0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05)',
        'xl': '0 20px 25px rgba(0, 0, 0, 0.1), 0 10px 10px rgba(0, 0, 0, 0.04)',
        '2xl': '0 25px 50px rgba(0, 0, 0, 0.25)',
        'inner': 'inset 0 2px 4px rgba(0, 0, 0, 0.06)',
      },
      
      // Custom transitions
      transitionDuration: {
        'fast': '150ms',
        'base': '200ms',
        'slow': '300ms',
      },
      
      transitionTimingFunction: {
        'ease-out-cubic': 'cubic-bezier(0.4, 0, 0.2, 1)',
      },
      
      // Custom z-index scale
      zIndex: {
        'dropdown': '1000',
        'sticky': '1020',
        'fixed': '1030',
        'modal-backdrop': '1040',
        'modal': '1050',
        'popover': '1060',
        'tooltip': '1070',
        'toast': '1080',
      },
      
      // Custom screen sizes
      screens: {
        'xs': '480px',
        'sm': '640px',
        'md': '768px',
        'lg': '1024px',
        'xl': '1280px',
        '2xl': '1536px',
        'sidebar': '1200px',
      },
      
      // Custom widths for specific components
      width: {
        'sidebar': '280px',
      },
      
      // Custom heights for specific components
      height: {
        'navbar': '64px',
        'btn-sm': '36px',
        'btn-base': '44px',
        'btn-lg': '52px',
      },
      
      // Custom backdrop blur
      backdropBlur: {
        'xs': '2px',
        'sm': '4px',
        'DEFAULT': '8px',
        'md': '12px',
        'lg': '16px',
        'xl': '24px',
      },
    },
  },
  plugins: [
    // Plugin for creating component classes
    function({ addComponents, theme }) {
      addComponents({
        // Button components using Tailwind classes
        '.btn': {
          '@apply inline-flex items-center justify-center px-4 py-3 text-sm font-medium leading-none border border-transparent rounded-lg cursor-pointer transition-all duration-fast select-none whitespace-nowrap': {},
        },
        '.btn-sm': {
          '@apply px-3 py-2 text-xs h-btn-sm': {},
        },
        '.btn-lg': {
          '@apply px-6 py-4 text-base h-btn-lg': {},
        },
        '.btn-primary': {
          '@apply text-white bg-primary-500 hover:bg-primary-600 focus:ring-2 focus:ring-primary-200': {},
        },
        '.btn-secondary': {
          '@apply text-white bg-secondary-500 hover:bg-secondary-600 focus:ring-2 focus:ring-secondary-200': {},
        },
        '.btn-success': {
          '@apply text-white bg-success-500 hover:bg-success-600 focus:ring-2 focus:ring-success-200': {},
        },
        '.btn-danger': {
          '@apply text-white bg-error-500 hover:bg-error-600 focus:ring-2 focus:ring-error-200': {},
        },
        '.btn-outline': {
          '@apply text-primary-500 bg-transparent border-primary-500 hover:text-white hover:bg-primary-500': {},
        },
        '.btn-ghost': {
          '@apply text-gray-700 bg-transparent hover:bg-gray-100': {},
        },
        '.btn-disabled': {
          '@apply opacity-50 cursor-not-allowed pointer-events-none': {},
        },
        
        // Form components
        '.form-input': {
          '@apply block w-full px-4 py-3 text-base text-gray-900 bg-white border border-gray-300 rounded-lg transition-colors duration-fast focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-100 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-100': {},
        },
        '.form-textarea': {
          '@apply form-input resize-vertical': {},
        },
        '.form-select': {
          '@apply form-input appearance-none bg-no-repeat bg-right pr-10': {},
          backgroundImage: `url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e")`,
        },
        
        // Card components
        '.card': {
          '@apply bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden': {},
        },
        '.card-header': {
          '@apply px-6 py-4 border-b border-gray-200 bg-gray-50': {},
        },
        '.card-body': {
          '@apply px-6 py-4': {},
        },
        '.card-footer': {
          '@apply px-6 py-4 border-t border-gray-200 bg-gray-50': {},
        },
        '.card-hover': {
          '@apply transition-all duration-base hover:shadow-md hover:-translate-y-1': {},
        },
        
        // Status classes
        '.status-attending': {
          '@apply text-success-600 bg-success-50 border-success-500': {},
        },
        '.status-not-attending': {
          '@apply text-error-600 bg-error-50 border-error-500': {},
        },
        '.status-pending': {
          '@apply text-gray-600 bg-gray-50 border-gray-400': {},
        },
        
        // Layout classes
        '.container-app': {
          '@apply w-full px-2 pb-16 max-w-3xl mx-auto': {},
          '@screen md': {
            '@apply px-4': {},
          },
        },
        
        // Empty state
        '.empty-state': {
          '@apply bg-info-50 text-info-800 p-10 rounded-lg shadow-sm text-center my-8': {},
        },
        
        // Loading spinner
        '.loading-spinner': {
          '@apply animate-spin rounded-full border-2 border-primary-500 border-t-transparent': {},
        },
        
        // Utility classes for common patterns
        '.text-muted': {
          '@apply text-gray-600': {},
        },
        '.text-subtle': {
          '@apply text-gray-500': {},
        },
        '.bg-surface': {
          '@apply bg-white': {},
        },
        '.bg-surface-secondary': {
          '@apply bg-gray-50': {},
        },
        '.border-default': {
          '@apply border-gray-200': {},
        },
      })
    },
    
    // Plugin for responsive utilities
    function({ addUtilities, theme, variants }) {
      const newUtilities = {
        '.hide-scrollbar': {
          '-ms-overflow-style': 'none',
          'scrollbar-width': 'none',
          '&::-webkit-scrollbar': {
            display: 'none'
          }
        },
        '.transition-fast': {
          'transition-duration': theme('transitionDuration.fast'),
          'transition-timing-function': theme('transitionTimingFunction.ease-out-cubic'),
        },
        '.transition-base': {
          'transition-duration': theme('transitionDuration.base'),
          'transition-timing-function': theme('transitionTimingFunction.ease-out-cubic'),
        },
        '.transition-slow': {
          'transition-duration': theme('transitionDuration.slow'),
          'transition-timing-function': theme('transitionTimingFunction.ease-out-cubic'),
        },
      }
      
      addUtilities(newUtilities, variants('transitionDuration'))
    }
  ],
}
