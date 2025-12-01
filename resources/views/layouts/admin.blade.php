<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Attendance Admin</title>

    <!-- Maps Configuration -->
    @php
        $mapProvider = \App\Models\Setting::get('map_provider', 'openstreetmap');
        $googleMapsKey = \App\Models\Setting::get('google_maps_api_key', '');
    @endphp

    @if($mapProvider === 'google' && $googleMapsKey)
        <!-- Google Maps -->
        <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&libraries=places"></script>
    @else
        <!-- Leaflet.js (OpenStreetMap) -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <style>
            .leaflet-container { height: 100%; width: 100%; }
        </style>
    @endif

    <script>
        // Configuration globale
        window.mapProvider = '{{ $mapProvider }}';
        window.googleMapsKey = '{{ $googleMapsKey }}';
    </script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Map Helper -->
    <script src="{{ asset('js/map-helper.js') }}"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* Scrollbar personnalisée pour le sidebar */
        nav::-webkit-scrollbar {
            width: 6px;
        }

        nav::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 3px;
        }

        nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Pour Firefox */
        nav {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.2) rgba(0, 0, 0, 0.1);
        }
    </style>

    <!-- Alpine.js Plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')
</head>
<body class="bg-gray-100">
    <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">

        <!-- Mobile Backdrop -->
        <div
            x-show="sidebarOpen"
            @click="sidebarOpen = false"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-600 bg-opacity-75 z-40 lg:hidden"
            x-cloak
        ></div>

        <!-- Sidebar -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white transform transition-transform duration-300 lg:translate-x-0 lg:static lg:inset-0 flex flex-col"
        >
            <!-- Logo -->
            <div class="flex items-center justify-center h-16 bg-gray-800 flex-shrink-0">
                <i class="fas fa-map-marker-alt text-2xl text-blue-500"></i>
                <span class="ml-3 text-xl font-bold">Attendance</span>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto mt-8 px-4 pb-24">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-home w-5"></i>
                    <span class="ml-3">Dashboard</span>
                </a>

                <a href="{{ route('admin.employees.index') }}" class="flex items-center px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.employees.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-users w-5"></i>
                    <span class="ml-3">Employés</span>
                </a>

                <a href="{{ route('admin.campuses.index') }}" class="flex items-center px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.campuses.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-building w-5"></i>
                    <span class="ml-3">Campus</span>
                </a>

                <a href="{{ route('admin.semesters.index') }}" class="flex items-center px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.semesters.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-calendar-alt w-5"></i>
                    <span class="ml-3">Semestres</span>
                </a>

                <a href="{{ route('admin.attendances.index') }}" class="flex items-center px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.attendances.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-clock w-5"></i>
                    <span class="ml-3">Présences</span>
                </a>

                <!-- Unités d'Enseignement (UE) Section with Submenu -->
                <div x-data="{ open: {{ request()->routeIs('admin.unites-enseignement.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 mb-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.unites-enseignement.*') ? 'bg-blue-600' : '' }}">
                        <div class="flex items-center">
                            <i class="fas fa-book w-5"></i>
                            <span class="ml-3">Unités d'Enseignement</span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform" :class="{'rotate-180': open}"></i>
                    </button>
                    <div x-show="open" x-transition class="ml-4 space-y-1">
                        <a href="{{ route('admin.unites-enseignement.catalog') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-sm {{ request()->routeIs('admin.unites-enseignement.catalog') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-list w-4"></i>
                            <span class="ml-2">Bibliothèque des UE</span>
                        </a>
                        <a href="{{ route('admin.unites-enseignement.create-standalone') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-sm {{ request()->routeIs('admin.unites-enseignement.create-standalone') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-plus w-4"></i>
                            <span class="ml-2">Créer une UE</span>
                        </a>
                        <a href="{{ route('admin.unites-enseignement.assign') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-sm {{ request()->routeIs('admin.unites-enseignement.assign') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-user-tag w-4"></i>
                            <span class="ml-2">Attribuer une UE</span>
                        </a>
                        <a href="{{ route('admin.unites-enseignement.import') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-sm {{ request()->routeIs('admin.unites-enseignement.import') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-file-import w-4"></i>
                            <span class="ml-2">Importer des UE</span>
                        </a>
                    </div>
                </div>

                <!-- Vacataires Section with Submenu -->
                <div x-data="{ open: {{ request()->routeIs('admin.vacataires.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 mb-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.vacataires.*') ? 'bg-blue-600' : '' }}">
                        <div class="flex items-center">
                            <i class="fas fa-user-clock w-5"></i>
                            <span class="ml-3">Vacataires</span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform" :class="{'rotate-180': open}"></i>
                    </button>
                    <div x-show="open" x-transition class="ml-4 space-y-1">
                        <a href="{{ route('admin.vacataires.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-sm {{ request()->routeIs('admin.vacataires.index') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-list w-4"></i>
                            <span class="ml-2">Liste des vacataires</span>
                        </a>
                        <a href="{{ route('admin.vacataires.payments') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-sm {{ request()->routeIs('admin.vacataires.payments') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-money-bill-wave w-4"></i>
                            <span class="ml-2">Gestion des paiements</span>
                        </a>
                        <a href="{{ route('admin.vacataires.report') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-sm {{ request()->routeIs('admin.vacataires.report') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-file-alt w-4"></i>
                            <span class="ml-2">Rapport</span>
                        </a>
                    </div>
                </div>

                <!-- Semi-permanents Section with Submenu -->
                <div x-data="{ open: {{ request()->routeIs('admin.semi-permanents.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 mb-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.semi-permanents.*') ? 'bg-blue-600' : '' }}">
                        <div class="flex items-center">
                            <i class="fas fa-user-tie w-5"></i>
                            <span class="ml-3">Semi-permanents</span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform" :class="{'rotate-180': open}"></i>
                    </button>
                    <div x-show="open" x-transition class="ml-4 space-y-1">
                        <a href="{{ route('admin.semi-permanents.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-sm {{ request()->routeIs('admin.semi-permanents.index') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-list w-4"></i>
                            <span class="ml-2">Liste</span>
                        </a>
                        <a href="{{ route('admin.semi-permanents.payments') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-sm {{ request()->routeIs('admin.semi-permanents.payments') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-money-check-alt w-4"></i>
                            <span class="ml-2">Gestion des paiements</span>
                        </a>
                        <a href="{{ route('admin.semi-permanents.report') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-sm {{ request()->routeIs('admin.semi-permanents.report') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-chart-bar w-4"></i>
                            <span class="ml-2">Rapport</span>
                        </a>
                    </div>
                </div>

                <a href="{{ route('admin.realtime') }}" class="flex items-center px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.realtime') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-map w-5"></i>
                    <span class="ml-3">Carte en temps réel</span>
                </a>

                <a href="{{ route('admin.reports.index') }}" class="flex items-center px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.reports.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span class="ml-3">Rapports</span>
                </a>

                <!-- Rapport sur la paie -->
                <a href="{{ route('admin.payroll.report') }}" class="flex items-center px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.payroll.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-dollar-sign w-5"></i>
                    <span class="ml-3">Rapport sur la paie</span>
                </a>

                <!-- Calculateur de Paie Manuelle -->
                <a href="{{ route('admin.manual-payroll.index') }}" class="flex items-center px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.manual-payroll.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-calculator w-5"></i>
                    <span class="ml-3">Calculateur de Paie</span>
                </a>

                <!-- Déductions Manuelles -->
                <a href="{{ route('admin.manual-deductions.index') }}" class="flex items-center px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.manual-deductions.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-minus-circle w-5"></i>
                    <span class="ml-3">Déductions Manuelles</span>
                </a>

                <!-- Prêts -->
                <a href="{{ route('admin.loans.index') }}" class="flex items-center px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.loans.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-hand-holding-usd w-5"></i>
                    <span class="ml-3">Prêts</span>
                </a>

                <!-- Alertes de Présence (Notifications Push) -->
                <div x-data="{ open: {{ request()->routeIs('admin.presence-alerts.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-3 mb-2 rounded-lg hover:bg-gray-800 {{ request()->routeIs('admin.presence-alerts.*') ? 'bg-blue-600' : '' }}">
                        <div class="flex items-center">
                            <i class="fas fa-bell w-5"></i>
                            <span class="ml-3">Alertes de Présence</span>
                            @php
                                $pendingCount = \App\Models\PresenceIncident::where('status', 'pending')->count();
                            @endphp
                            @if($pendingCount > 0)
                                <span class="ml-2 px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full">{{ $pendingCount }}</span>
                            @endif
                        </div>
                        <i class="fas fa-chevron-down transition-transform" :class="{'rotate-180': open}"></i>
                    </button>
                    <div x-show="open" x-transition class="ml-4 space-y-1">
                        <a href="{{ route('admin.presence-alerts.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-sm {{ request()->routeIs('admin.presence-alerts.index') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-list w-4"></i>
                            <span class="ml-2">Liste des incidents</span>
                        </a>
                        <a href="{{ route('admin.presence-alerts.settings') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-sm {{ request()->routeIs('admin.presence-alerts.settings') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-cog w-4"></i>
                            <span class="ml-2">Configuration</span>
                        </a>
                        <a href="{{ route('admin.presence-alerts.statistics') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-sm {{ request()->routeIs('admin.presence-alerts.statistics') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-chart-line w-4"></i>
                            <span class="ml-2">Statistiques</span>
                        </a>
                    </div>
                </div>

                <!-- Utilisateurs (Gestion des accès) -->
                {{-- Temporairement désactivé jusqu'à la création du module
                <a href="{{ route('admin.users.index') }}" class="flex items-center px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-user-shield w-5"></i>
                    <span class="ml-3">Utilisateurs</span>
                </a>
                --}}

                <!-- Suivi en Temps Réel -->
                <a href="{{ route('admin.real-time-tracking.index') }}" class="flex items-center px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.real-time-tracking.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-map-marked-alt w-5 text-green-500"></i>
                    <span class="ml-3">Suivi en Temps Réel</span>
                    <span class="ml-auto">
                        <span class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full animate-pulse">LIVE</span>
                    </span>
                </a>

                <a href="{{ route('admin.firebase.index') }}" class="flex items-center px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.firebase.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-fire w-5 text-orange-500"></i>
                    <span class="ml-3">Firebase</span>
                </a>

                <a href="{{ route('admin.settings') }}" class="flex items-center px-4 py-3 mb-2 rounded-lg {{ request()->routeIs('admin.settings') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-cog w-5"></i>
                    <span class="ml-3">Paramètres</span>
                </a>
            </nav>

            <!-- User Info -->
            <div class="absolute bottom-0 w-64 p-4 bg-gray-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center">
                            <span class="text-white font-bold">{{ substr(auth()->user()->first_name, 0, 1) }}</span>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium">{{ auth()->user()->full_name }}</p>
                        <p class="text-xs text-gray-400">{{ auth()->user()->role->display_name }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-white">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="flex items-center justify-between px-6 py-4 bg-white border-b">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 focus:outline-none lg:hidden">
                    <i class="fas fa-bars text-xl"></i>
                </button>

                <div class="flex items-center">
                    <h1 class="text-2xl font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h1>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="relative p-2 text-gray-600 hover:text-gray-800">
                            <i class="fas fa-bell text-xl"></i>
                            <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>
                    </div>

                    <!-- Search -->
                    <div class="hidden md:block">
                        <input type="text" placeholder="Rechercher..." class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700">
                        <i class="fas fa-check-circle mr-2"></i>
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
