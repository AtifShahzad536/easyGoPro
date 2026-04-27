<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Dashboard') - {{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts: Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

        <!-- Scripts & Assets -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Hotwire Turbo: Enabling Seamless Navigation -->
        <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@7.3.0/dist/turbo.es2017-umd.js" data-turbo-track="reload"></script>

        <script>
            /* ─── Sidebar Scroll Persistence ─── */
            /* Before we leave, save the current scroll position of the sidebar */
            document.addEventListener('turbo:before-visit', () => {
                const nav = document.getElementById('sidebar-nav');
                if (nav) sessionStorage.setItem('sidebarScroll', nav.scrollTop);
            });

            /* After the new page content is swapped in, restore the scroll position */
            document.addEventListener('turbo:render', () => {
                const nav = document.getElementById('sidebar-nav');
                const scroll = sessionStorage.getItem('sidebarScroll');
                if (nav && scroll) {
                    nav.scrollTop = parseInt(scroll, 10);
                }
            });
        </script>

        <style>
            [x-cloak] { display: none !important; }

            /* ─── Premium Thin Scrollbar ─── */
            * {
                scrollbar-width: thin;
                scrollbar-color: #CBD5E1 transparent;
            }

            /* Chrome / Safari / Edge */
            ::-webkit-scrollbar {
                width: 4px;
                height: 4px;
            }
            ::-webkit-scrollbar-track {
                background: transparent;
                border-radius: 99px;
            }
            ::-webkit-scrollbar-thumb {
                background: #CBD5E1;
                border-radius: 99px;
                transition: background 0.2s;
            }
            ::-webkit-scrollbar-thumb:hover {
                background: #94A3B8;
            }
            ::-webkit-scrollbar-corner {
                background: transparent;
            }

            /* Sidebar scrollbar — even thinner */
            nav::-webkit-scrollbar,
            aside::-webkit-scrollbar {
                width: 3px;
            }

            /* ─── Border Radius Normalization ─── */
            /* Tone down the overly-rounded cards and modals globally */

            /* rounded-2xl (16px) → 10px */
            .rounded-2xl { border-radius: 10px !important; }

            /* rounded-3xl (24px) → 14px */
            .rounded-3xl { border-radius: 14px !important; }

            /* rounded-xl (12px) → 8px */
            .rounded-xl  { border-radius: 8px  !important; }

            /* arbitrary large radii used on modals e.g. rounded-[2.5rem] → 16px */
            .rounded-\[2\.5rem\] { border-radius: 16px !important; }
            .rounded-\[3rem\]    { border-radius: 16px !important; }

            /* avatar / badge circles — keep them fully round */
            .rounded-full { border-radius: 9999px !important; }

            /* ─── Font & Weight Normalization ─── */
            /* Switch entire UI to Inter */
            *, *::before, *::after {
                font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
            }

            /* Tone down heavy Tailwind weight classes */
            .font-black  { font-weight: 600 !important; }   /* was 900 */
            .font-bold   { font-weight: 500 !important; }   /* was 700 */
            .font-semibold { font-weight: 500 !important; } /* was 600 */
            .font-medium { font-weight: 400 !important; }   /* was 500 */

            /* ─── Global Compact Table Styles ─── */
            .table-wrap { width: 100%; overflow: hidden; }

            .admin-table thead th {
                padding: 10px 12px;
                font-size: 9px;
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: 0.12em;
                color: #9CA3AF;
                border-bottom: 1px solid #F3F4F6;
                background: rgba(249,250,251,0.7);
                white-space: nowrap;
            }
            .admin-table tbody td {
                padding: 10px 12px;
                font-size: 12px;
            }
            .admin-table tbody tr {
                border-bottom: 1px solid #F9FAFB;
            }
            .admin-table tbody tr:hover {
                background-color: rgba(219,234,254,0.07);
            }
            .bg-white .overflow-x-auto {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 8px; /* Space for scrollbar */
            }

            /* ─── Mobile Scaling ─── */
            @media (max-width: 1024px) {
                main { padding: 1.25rem !important; }
                .text-2xl { font-size: 1.25rem !important; }
                .p-8 { padding: 1.25rem !important; }
                .px-8 { padding-left: 1rem !important; padding-right: 1rem !important; }
                
                /* Compact table cells for mobile */
                .admin-table tbody td {
                    padding: 8px 10px !important;
                    font-size: 11px !important;
                }
            }

            /* ─── Turbo Progress Bar ─── */
            .turbo-progress-bar {
                height: 3px;
                background-color: #1C69D4;
                box-shadow: 0 0 10px rgba(28, 105, 212, 0.5);
            }
        </style>
    </head>
    <body class="font-sans antialiased text-gray-900 bg-[#F1F5F9]" x-data="{ mobileMenuOpen: false }">
        <div class="flex h-screen overflow-hidden">
            <!-- Mobile Menu Backdrop -->
            <div x-show="mobileMenuOpen" 
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="mobileMenuOpen = false"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-[40] md:hidden"
                 x-cloak></div>

            <!-- Sidebar Navigation -->
            @include('layouts.sidebar')

            <!-- Main Application Canvas -->
            <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
                <!-- Top Nav Header -->
                @include('layouts.header')

                <!-- Main Scrollable Body Content -->
                <main class="flex-1 overflow-y-auto p-6 lg:p-8">
                    <div class="max-w-[1600px] mx-auto">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
        @stack('scripts')
    </body>
</html>
