/**
 * Token warna/spacing/radius berikut disalin persis dari design system proyek
 * referensi (frontend-sippp) — lihat ai-docs/SIPPP-Design-System-Phase-1-Revised.md
 * §4-§9 — supaya SI-PPG mewarisi karakter visual yang sama (institusional,
 * dominan hijau, aksen emas) meski stack-nya Blade/Livewire, bukan React.
 */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', 'sans-serif'],
            },
            colors: {
                brand: {
                    primary: '#076B3B',
                    'primary-hover': '#036B38',
                    'primary-active': '#115E3F',
                    secondary: '#4FAE41',
                    accent: '#D2AA26',
                    yellow: '#EAC413',
                },
                surface: {
                    page: '#EBEBEB',
                    DEFAULT: '#FFFFFF',
                    subtle: '#F5F6F5',
                },
                ink: {
                    primary: '#232C28',
                    secondary: '#535D59',
                    muted: '#707A76',
                    disabled: '#9AA3A0',
                    inverse: '#FFFFFF',
                },
                border: {
                    DEFAULT: '#DEE2E0',
                    subtle: '#ECEEED',
                    strong: '#C8CECB',
                    focus: '#298C3C',
                },
                success: { bg: '#EDF8F0', border: '#B6E0C0', text: '#0A572B', solid: '#237746' },
                warning: { bg: '#FFF9E5', border: '#F5DF78', text: '#745706', solid: '#D2AA26' },
                danger: { bg: '#FFF1F0', border: '#F6B8B3', text: '#9E2820', solid: '#C9362B' },
                info: { bg: '#EEF6FF', border: '#B8D8FA', text: '#175A94', solid: '#287BBF' },
                inactive: { bg: '#F5F6F5', border: '#DEE2E0', text: '#535D59', solid: '#707A76' },
            },
            borderRadius: {
                sm: '4px',
                md: '8px',
                lg: '12px',
                xl: '16px',
            },
            boxShadow: {
                xs: '0 1px 2px rgba(17, 23, 20, 0.05)',
                sm: '0 1px 3px rgba(17, 23, 20, 0.08), 0 1px 2px rgba(17, 23, 20, 0.04)',
                md: '0 6px 16px rgba(17, 23, 20, 0.08), 0 2px 6px rgba(17, 23, 20, 0.04)',
                lg: '0 16px 32px rgba(17, 23, 20, 0.12), 0 4px 10px rgba(17, 23, 20, 0.06)',
            },
            backgroundImage: {
                sidebar: 'linear-gradient(180deg, #4FAE41 0%, #298C3C 48%, #036B38 100%)',
                topbar: 'linear-gradient(90deg, #4BAB40 0%, #298C3C 50%, #036B38 100%)',
            },
        },
    },
    plugins: [],
};
