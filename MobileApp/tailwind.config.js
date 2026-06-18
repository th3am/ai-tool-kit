/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
      },
      colors: {
        brand: {
          50:  '#f5f3ff',
          100: '#ede9fe',
          200: '#ddd6fe',
          300: '#c4b5fd',
          400: '#a78bfa',
          500: '#8b5cf6',
          600: '#7c3aed',
          700: '#6d28d9',
          800: '#5b21b6',
          900: '#4c1d95',
        },
        dark: {
          50:  '#1a1a2e',
          100: '#161628',
          200: '#111120',
          300: '#0d0d1a',
          400: '#0a0a12',
        },
      },
      boxShadow: {
        'glow':    '0 0 20px rgba(124,58,237,0.3)',
        'glow-sm': '0 0 10px rgba(124,58,237,0.2)',
      },
      animation: {
        'spin-slow': 'spin 1.5s linear infinite',
        'pulse-dot': 'pulse 1.4s ease-in-out infinite',
        'fade-up':   'fadeUp 0.3s ease forwards',
        'skeleton':  'skeleton 1.5s ease-in-out infinite',
      },
      keyframes: {
        fadeUp: {
          '0%':   { opacity: '0', transform: 'translateY(10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        skeleton: {
          '0%, 100%': { opacity: '1' },
          '50%': { opacity: '0.35' },
        },
      },
    },
  },
  plugins: [],
}
