import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/Bendahara/**/*.php',
        './resources/views/filament/bendahara/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                treasury: {
                    50: '#fefbf3',
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
                'treasury-gold': '#fbbd23',
                'treasury-gold-dark': '#d97706',
                'treasury-gold-light': '#fef3c7',
                'treasury-gold-hover': '#f59e0b',
            },
            fontFamily: {
                'mono': ['JetBrains Mono', 'Monaco', 'Menlo', 'monospace'],
            },
            animation: {
                'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'slide-in': 'slideIn 0.3s ease-out',
                'fade-in': 'fadeIn 0.5s ease-in',
            },
            keyframes: {
                slideIn: {
                    '0%': { transform: 'translateX(-100%)', opacity: '0' },
                    '100%': { transform: 'translateX(0)', opacity: '1' },
                },
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
            },
            boxShadow: {
                'treasury': '0 4px 6px -1px rgba(251, 189, 35, 0.1), 0 2px 4px -1px rgba(251, 189, 35, 0.06)',
                'treasury-lg': '0 10px 15px -3px rgba(251, 189, 35, 0.1), 0 4px 6px -2px rgba(251, 189, 35, 0.05)',
                'treasury-xl': '0 20px 25px -5px rgba(251, 189, 35, 0.1), 0 10px 10px -5px rgba(251, 189, 35, 0.04)',
            },
            backgroundImage: {
                'treasury-gradient': 'linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%)',
                'treasury-gradient-dark': 'linear-gradient(135deg, #451a03 0%, #78350f 100%)',
            },
        },
    },
}