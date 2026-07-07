<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'HRM') | {{ config('app.name', 'Dylan HRM') }}</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                "colors": {
                    "on-primary-fixed-variant": "#003ea8",
                    "secondary-fixed-dim": "#b7c8e1",
                    "on-secondary": "#ffffff",
                    "primary": "#004ac6",
                    "outline": "#737686",
                    "on-secondary-container": "#54647a",
                    "primary-fixed": "#dbe1ff",
                    "on-surface": "#191b23",
                    "surface-variant": "#e1e2ed",
                    "on-tertiary-fixed": "#360f00",
                    "primary-container": "#2563eb",
                    "inverse-on-surface": "#f0f0fb",
                    "on-secondary-fixed-variant": "#38485d",
                    "on-background": "#191b23",
                    "secondary-fixed": "#d3e4fe",
                    "outline-variant": "#c3c6d7",
                    "error": "#ba1a1a",
                    "surface": "#faf8ff",
                    "error-container": "#ffdad6",
                    "tertiary-container": "#bc4800",
                    "on-secondary-fixed": "#0b1c30",
                    "on-error": "#ffffff",
                    "tertiary-fixed-dim": "#ffb596",
                    "on-primary": "#ffffff",
                    "surface-container-highest": "#e1e2ed",
                    "on-primary-container": "#eeefff",
                    "on-error-container": "#93000a",
                    "tertiary": "#943700",
                    "surface-tint": "#0053db",
                    "surface-dim": "#d9d9e5",
                    "on-tertiary-container": "#ffede6",
                    "primary-fixed-dim": "#b4c5ff",
                    "background": "#faf8ff",
                    "secondary-container": "#d0e1fb",
                    "secondary": "#505f76",
                    "surface-bright": "#faf8ff",
                    "surface-container": "#ededf9",
                    "inverse-primary": "#b4c5ff",
                    "on-tertiary-fixed-variant": "#7d2d00",
                    "inverse-surface": "#2e3039",
                    "surface-container-low": "#f3f3fe",
                    "on-tertiary": "#ffffff",
                    "tertiary-fixed": "#ffdbcd",
                    "surface-container-lowest": "#ffffff",
                    "on-surface-variant": "#434655",
                    "on-primary-fixed": "#00174b",
                    "surface-container-high": "#e7e7f3"
                },
                "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
                },
                "spacing": {
                    "xl": "32px",
                    "md": "16px",
                    "sm": "12px",
                    "sidebar-width": "260px",
                    "base": "4px",
                    "xs": "8px",
                    "lg": "24px",
                    "container-max": "1280px"
                },
                "fontFamily": {
                    "headline-md": ["Inter"],
                    "display-lg": ["Inter"],
                    "headline-lg": ["Inter"],
                    "label-md": ["Inter"],
                    "headline-lg-mobile": ["Inter"],
                    "body-lg": ["Inter"],
                    "body-md": ["Inter"]
                },
                "fontSize": {
                    "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                    "display-lg": ["36px", {"lineHeight": "44px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                    "headline-lg": ["24px", {"lineHeight": "32px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                    "label-md": ["12px", {"lineHeight": "16px", "fontWeight": "500"}],
                    "headline-lg-mobile": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                    "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                    "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}]
                }
            },
        },
    }
</script>
<style>
    body { font-family: 'Inter', sans-serif; background-color: #faf8ff; }
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(226, 232, 240, 0.5);
    }
    .glass-morphism {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    .sidebar-item-active {
        background-color: #d0e1fb;
        color: #0b1c30;
    }
    .active-tab {
        background-color: #d0e1fb;
        color: #0b1c30;
    }
    .chart-bar { transition: height 1s ease-in-out; }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #c3c6d7; border-radius: 10px; }
    .input-focus-ring:focus, .form-input-ring:focus {
        outline: none;
        box-shadow: 0 0 0 4px rgba(0, 74, 198, 0.1);
        border-color: #004ac6;
    }
</style>
