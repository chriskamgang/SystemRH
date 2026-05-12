<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Estuaire RH</title>

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

    <!-- Choices.js - Searchable Select -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

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
            @php
                $currentCompany = null;
                if (auth()->user()->company_id) {
                    $currentCompany = \App\Models\Company::find(auth()->user()->company_id);
                } elseif (session('current_company_id')) {
                    $currentCompany = \App\Models\Company::find(session('current_company_id'));
                }
            @endphp
            <div class="flex items-center justify-center h-16 bg-gray-800 flex-shrink-0 px-3">
                @if($currentCompany && $currentCompany->logo)
                    <img src="{{ asset('storage/' . $currentCompany->logo) }}" alt="{{ $currentCompany->name }}" class="w-8 h-8 rounded object-cover">
                @else
                    <i class="fas fa-building text-2xl text-blue-500"></i>
                @endif
                <span class="ml-3 text-lg font-bold truncate">{{ $currentCompany->name ?? 'Estuaire RH' }}</span>
            </div>

            <!-- Navigation -->
            @php
                $u = auth()->user();
                $isSuperAdmin = $u->isSuperAdmin();
                $isAdmin = $u->isAdmin();
                // Super admin sans switch = pas de menus metier
                $superAdminWithoutCompany = $isSuperAdmin && !session('current_company_id');
                // Helper: admin voit tout, sinon vérifier module.view
                $can = function($module) use ($u, $isAdmin, $superAdminWithoutCompany) {
                    if ($superAdminWithoutCompany) return false;
                    return $isAdmin || $u->hasPermission($module . '.view');
                };
            @endphp
            <nav class="flex-1 overflow-y-auto mt-4 px-4 pb-24">

                {{-- ========== SUPER ADMIN : ENTREPRISES ========== --}}
                @if(auth()->user()->isSuperAdmin())
                <p class="px-4 pt-4 pb-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Super Admin</p>
                <a href="{{ route('admin.companies.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.companies.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-globe w-5"></i>
                    <span class="ml-3">Entreprises</span>
                </a>
                @if(session('switched_company_name'))
                <div class="mx-4 mb-2 px-3 py-2 bg-yellow-900/30 rounded-lg text-xs text-yellow-300">
                    <i class="fas fa-building mr-1"></i> {{ session('switched_company_name') }}
                    <form action="{{ route('admin.companies.switch-back') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="ml-1 underline hover:text-yellow-100">Quitter</button>
                    </form>
                </div>
                @endif
                @endif

                {{-- ========== TABLEAU DE BORD ========== --}}
                @if($can('dashboard'))
                <p class="px-4 pt-4 pb-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Tableau de bord</p>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-home w-5"></i>
                    <span class="ml-3">Dashboard</span>
                </a>
                <a href="{{ route('admin.app-usage') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.app-usage*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-mobile-alt w-5"></i>
                    <span class="ml-3">Utilisation App</span>
                </a>
                @endif

                {{-- ========== PERSONNEL & PRESENCES ========== --}}
                @if(!$superAdminWithoutCompany)
                <p class="px-4 pt-5 pb-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Personnel</p>

                @if($can('employees'))
                <a href="{{ route('admin.employees.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.employees.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-users w-5"></i>
                    <span class="ml-3">Employes</span>
                </a>
                @endif

                @if($can('campus'))
                <a href="{{ route('admin.campuses.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.campuses.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-building w-5"></i>
                    <span class="ml-3">Campus</span>
                </a>
                @endif

                @if($can('attendance'))
                <a href="{{ route('admin.attendances.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.attendances.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-clock w-5"></i>
                    <span class="ml-3">Presences</span>
                </a>
                @endif

                @if($can('manual_attendance'))
                <a href="{{ route('admin.manual-attendances.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.manual-attendances.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-edit w-5"></i>
                    <span class="ml-3">Presences Manuelles</span>
                </a>
                @endif

                @if($can('realtime_map'))
                <a href="{{ route('admin.realtime') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.realtime') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-map w-5"></i>
                    <span class="ml-3">Carte temps reel</span>
                </a>
                @endif

                @if($can('realtime_tracking'))
                <a href="{{ route('admin.real-time-tracking.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.real-time-tracking.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-map-marked-alt w-5 text-green-400"></i>
                    <span class="ml-3">Suivi GPS</span>
                    <span class="ml-auto px-2 py-0.5 text-xs font-bold text-white bg-green-500 rounded-full animate-pulse">LIVE</span>
                </a>
                @endif

                {{-- ========== DEMANDES RH ========== --}}
                @if($isAdmin || $can('leaves') || $can('justifications') || $can('certificates') || $can('salary_advances') || $can('tickets'))
                <p class="px-4 pt-5 pb-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Demandes RH</p>

                @if($can('leaves'))
                <a href="{{ route('admin.leaves.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.leaves.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-calendar-check w-5 text-blue-400"></i>
                    <span class="ml-3">Conges</span>
                    @php $sidebarLeaves = \App\Models\LeaveRequest::where('status', 'pending')->count(); @endphp
                    @if($sidebarLeaves > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">{{ $sidebarLeaves }}</span>
                    @endif
                </a>
                @endif

                @if($can('justifications'))
                <a href="{{ route('admin.justification-requests.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.justification-requests.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-file-medical w-5 text-orange-400"></i>
                    <span class="ml-3">Justifications</span>
                    @php $sidebarJustifs = \App\Models\JustificationRequest::where('status', 'pending')->count(); @endphp
                    @if($sidebarJustifs > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">{{ $sidebarJustifs }}</span>
                    @endif
                </a>
                @endif

                @if($can('certificates'))
                <a href="{{ route('admin.certificates.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.certificates.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-file-alt w-5 text-teal-400"></i>
                    <span class="ml-3">Attestations</span>
                    @php $sidebarCerts = \App\Models\WorkCertificate::where('status', 'pending')->count(); @endphp
                    @if($sidebarCerts > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">{{ $sidebarCerts }}</span>
                    @endif
                </a>
                @endif

                @if($can('salary_advances'))
                <a href="{{ route('admin.salary-advances.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.salary-advances.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-money-check-alt w-5 text-green-400"></i>
                    <span class="ml-3">Avances Salaire</span>
                    @php $sidebarAdvances = \App\Models\SalaryAdvanceRequest::where('status', 'pending')->count(); @endphp
                    @if($sidebarAdvances > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">{{ $sidebarAdvances }}</span>
                    @endif
                </a>
                @endif

                @if($isAdmin)
                <a href="{{ route('admin.complaints.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.complaints.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-exclamation-triangle w-5 text-red-400"></i>
                    <span class="ml-3">Reclamations</span>
                </a>

                <a href="{{ route('admin.tasks.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.tasks.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-tasks w-5"></i>
                    <span class="ml-3">Taches</span>
                </a>
                @endif

                @if($can('tickets'))
                <a href="{{ route('admin.tickets.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.tickets.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-ticket-alt w-5 text-cyan-400"></i>
                    <span class="ml-3">Tickets</span>
                    @php $sidebarTickets = \App\Models\Ticket::whereIn('status', ['new', 'responded'])->count(); @endphp
                    @if($sidebarTickets > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">{{ $sidebarTickets }}</span>
                    @endif
                </a>
                @endif
                @endif

                {{-- ========== RESSOURCES HUMAINES ========== --}}
                <p class="px-4 pt-5 pb-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Ressources Humaines</p>

                <a href="{{ route('admin.cnps.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.cnps.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-shield-alt w-5 text-green-400"></i>
                    <span class="ml-3">CNPS</span>
                </a>

                <a href="{{ route('admin.evaluations.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.evaluations.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-star w-5 text-yellow-400"></i>
                    <span class="ml-3">Evaluations</span>
                </a>

                <a href="{{ route('admin.training.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.training.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-graduation-cap w-5 text-purple-400"></i>
                    <span class="ml-3">Formations</span>
                </a>

                <a href="{{ route('admin.orgchart.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.orgchart.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-sitemap w-5 text-cyan-400"></i>
                    <span class="ml-3">Organigramme</span>
                </a>

                <a href="{{ route('admin.hr-analytics.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.hr-analytics.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-chart-line w-5 text-orange-400"></i>
                    <span class="ml-3">Analytique RH</span>
                </a>

                {{-- ========== ENSEIGNEMENT ========== --}}
                <p class="px-4 pt-5 pb-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Enseignement</p>

                @if($can('ue'))
                <div x-data="{ open: {{ request()->routeIs('admin.unites-enseignement.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2.5 mb-1 rounded-lg text-sm hover:bg-gray-800 {{ request()->routeIs('admin.unites-enseignement.*') ? 'bg-blue-600' : '' }}">
                        <div class="flex items-center">
                            <i class="fas fa-book w-5"></i>
                            <span class="ml-3">Unites d'Enseignement</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="{'rotate-180': open}"></i>
                    </button>
                    <div x-show="open" x-transition class="ml-4 space-y-1">
                        <a href="{{ route('admin.unites-enseignement.catalog') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.unites-enseignement.catalog') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-list w-4"></i><span class="ml-2">Bibliotheque</span>
                        </a>
                        <a href="{{ route('admin.unites-enseignement.create-standalone') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.unites-enseignement.create-standalone') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-plus w-4"></i><span class="ml-2">Creer une UE</span>
                        </a>
                        <a href="{{ route('admin.unites-enseignement.assign') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.unites-enseignement.assign') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-user-tag w-4"></i><span class="ml-2">Attribuer</span>
                        </a>
                        <a href="{{ route('admin.unites-enseignement.import') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.unites-enseignement.import') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-file-import w-4"></i><span class="ml-2">Importer</span>
                        </a>
                    </div>
                </div>
                @endif

                @if($can('schedule'))
                <div x-data="{ open: {{ request()->routeIs('admin.emploi-du-temps.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2.5 mb-1 rounded-lg text-sm hover:bg-gray-800 {{ request()->routeIs('admin.emploi-du-temps.*') ? 'bg-blue-600' : '' }}">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt w-5"></i>
                            <span class="ml-3">Emploi du Temps</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="{'rotate-180': open}"></i>
                    </button>
                    <div x-show="open" x-transition class="ml-4 space-y-1">
                        <a href="{{ route('admin.emploi-du-temps.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.emploi-du-temps.index') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-list w-4"></i><span class="ml-2">Tous les creneaux</span>
                        </a>
                        <a href="{{ route('admin.emploi-du-temps.create') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.emploi-du-temps.create') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-plus w-4"></i><span class="ml-2">Ajouter</span>
                        </a>
                        <a href="{{ route('admin.emploi-du-temps.bulk-create') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.emploi-du-temps.bulk-create') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-layer-group w-4"></i><span class="ml-2">Creation en lot</span>
                        </a>
                    </div>
                </div>
                @endif

                @if($can('vacataires'))
                <div x-data="{ open: {{ request()->routeIs('admin.vacataires.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2.5 mb-1 rounded-lg text-sm hover:bg-gray-800 {{ request()->routeIs('admin.vacataires.*') ? 'bg-blue-600' : '' }}">
                        <div class="flex items-center">
                            <i class="fas fa-user-clock w-5"></i>
                            <span class="ml-3">Vacataires</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="{'rotate-180': open}"></i>
                    </button>
                    <div x-show="open" x-transition class="ml-4 space-y-1">
                        <a href="{{ route('admin.vacataires.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.vacataires.index') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-list w-4"></i><span class="ml-2">Liste</span>
                        </a>
                        <a href="{{ route('admin.vacataires.payments') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.vacataires.payments') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-money-bill-wave w-4"></i><span class="ml-2">Paiements</span>
                        </a>
                        <a href="{{ route('admin.vacataires.manual-payments.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.vacataires.manual-payments.*') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-hand-holding-usd w-4"></i><span class="ml-2">Paiement Manuel</span>
                        </a>
                        <a href="{{ route('admin.vacataires.report') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.vacataires.report') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-file-alt w-4"></i><span class="ml-2">Rapport</span>
                        </a>
                    </div>
                </div>
                @endif

                @if($can('semi_permanents'))
                <div x-data="{ open: {{ request()->routeIs('admin.semi-permanents.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2.5 mb-1 rounded-lg text-sm hover:bg-gray-800 {{ request()->routeIs('admin.semi-permanents.*') ? 'bg-blue-600' : '' }}">
                        <div class="flex items-center">
                            <i class="fas fa-user-tie w-5"></i>
                            <span class="ml-3">Semi-permanents</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="{'rotate-180': open}"></i>
                    </button>
                    <div x-show="open" x-transition class="ml-4 space-y-1">
                        <a href="{{ route('admin.semi-permanents.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.semi-permanents.index') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-list w-4"></i><span class="ml-2">Liste</span>
                        </a>
                        <a href="{{ route('admin.semi-permanents.payments') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.semi-permanents.payments') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-money-check-alt w-4"></i><span class="ml-2">Paiements</span>
                        </a>
                        <a href="{{ route('admin.semi-permanents.report') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.semi-permanents.report') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-chart-bar w-4"></i><span class="ml-2">Rapport</span>
                        </a>
                    </div>
                </div>
                @endif

                {{-- ========== FINANCE ========== --}}
                <p class="px-4 pt-5 pb-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Finance</p>

                <a href="{{ route('admin.wallets.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.wallets.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-wallet w-5 text-green-400"></i>
                    <span class="ml-3">Portefeuilles</span>
                </a>

                @if($can('loans'))
                <a href="{{ route('admin.loans.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.loans.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-hand-holding-usd w-5"></i>
                    <span class="ml-3">Prets</span>
                </a>
                @endif

                @if($can('deductions'))
                <a href="{{ route('admin.manual-deductions.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.manual-deductions.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-minus-circle w-5"></i>
                    <span class="ml-3">Deductions</span>
                </a>
                @endif

                @if($can('reports'))
                <a href="{{ route('admin.payroll.report') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.payroll.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-dollar-sign w-5"></i>
                    <span class="ml-3">Rapport Paie</span>
                </a>

                <a href="{{ route('admin.generic-calculator.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.generic-calculator.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-calculator w-5"></i>
                    <span class="ml-3">Calculateur</span>
                </a>

                <div x-data="{ open: {{ request()->routeIs('admin.rapports.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2.5 mb-1 rounded-lg text-sm hover:bg-gray-800 {{ request()->routeIs('admin.rapports.*') ? 'bg-blue-600' : '' }}">
                        <div class="flex items-center">
                            <i class="fas fa-file-invoice-dollar w-5"></i>
                            <span class="ml-3">Rapports Paiements</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="{'rotate-180': open}"></i>
                    </button>
                    <div x-show="open" x-transition class="ml-4 space-y-1">
                        <a href="{{ route('admin.rapports.personnel-paye') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.rapports.personnel-paye') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-users-cog w-4"></i><span class="ml-2">Personnel Paye</span>
                        </a>
                        <a href="{{ route('admin.rapports.cours-payes') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.rapports.cours-payes') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-check-circle w-4 text-green-500"></i><span class="ml-2">Cours Payes</span>
                        </a>
                        <a href="{{ route('admin.rapports.cours-non-payes') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.rapports.cours-non-payes') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-times-circle w-4 text-red-500"></i><span class="ml-2">Cours Non Payes</span>
                        </a>
                        <a href="{{ route('admin.rapports.masse-payes-specialite') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.rapports.masse-payes-specialite') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-graduation-cap w-4"></i><span class="ml-2">Masse/Specialite</span>
                        </a>
                        <a href="{{ route('admin.rapports.masse-payes-cycle') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.rapports.masse-payes-cycle') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-layer-group w-4"></i><span class="ml-2">Masse/Cycle</span>
                        </a>
                    </div>
                </div>

                <a href="{{ route('admin.reports.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.reports.*') && !request()->routeIs('admin.rapports.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span class="ml-3">Rapports</span>
                </a>
                @endif

                {{-- ========== ACADEMIQUE ========== --}}
                <p class="px-4 pt-5 pb-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Academique</p>

                <div x-data="{ open: {{ request()->routeIs('admin.students.*') || request()->routeIs('admin.levels.*') || request()->routeIs('admin.specialties.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2.5 mb-1 rounded-lg text-sm hover:bg-gray-800 {{ request()->routeIs('admin.students.*') || request()->routeIs('admin.levels.*') || request()->routeIs('admin.specialties.*') ? 'bg-blue-600' : '' }}">
                        <div class="flex items-center">
                            <i class="fas fa-graduation-cap w-5 text-indigo-400"></i>
                            <span class="ml-3">Gestion Academique</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open" x-collapse class="pl-4 space-y-1 mb-2">
                        <a href="{{ route('admin.levels.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.levels.*') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-layer-group w-4"></i><span class="ml-2">Niveaux</span>
                        </a>
                        <a href="{{ route('admin.specialties.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.specialties.*') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-microscope w-4"></i><span class="ml-2">Specialites</span>
                        </a>
                        <a href="{{ route('admin.students.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.students.*') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-user-graduate w-4"></i><span class="ml-2">Etudiants</span>
                        </a>
                    </div>
                </div>

                <a href="{{ route('admin.moratoriums.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.moratoriums.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-file-invoice-dollar w-5 text-yellow-400"></i>
                    <span class="ml-3">Moratoires</span>
                    @php $pendingMoratoriums = \App\Models\MoratoriumRequest::where('status', 'pending')->count(); @endphp
                    @if($pendingMoratoriums > 0)
                        <span class="ml-auto px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">{{ $pendingMoratoriums }}</span>
                    @endif
                </a>

                {{-- ========== SYSTEME ========== --}}
                <p class="px-4 pt-5 pb-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Systeme</p>

                @if($can('presence_alerts'))
                <div x-data="{ open: {{ request()->routeIs('admin.presence-alerts.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2.5 mb-1 rounded-lg text-sm hover:bg-gray-800 {{ request()->routeIs('admin.presence-alerts.*') ? 'bg-blue-600' : '' }}">
                        <div class="flex items-center">
                            <i class="fas fa-bell w-5"></i>
                            <span class="ml-3">Alertes Presence</span>
                            @php $pendingCount = \App\Models\PresenceIncident::where('status', 'pending')->count(); @endphp
                            @if($pendingCount > 0)
                                <span class="ml-2 px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">{{ $pendingCount }}</span>
                            @endif
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="{'rotate-180': open}"></i>
                    </button>
                    <div x-show="open" x-transition class="ml-4 space-y-1">
                        <a href="{{ route('admin.presence-alerts.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.presence-alerts.index') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-list w-4"></i><span class="ml-2">Incidents</span>
                        </a>
                        <a href="{{ route('admin.presence-alerts.settings') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.presence-alerts.settings') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-cog w-4"></i><span class="ml-2">Configuration</span>
                        </a>
                        <a href="{{ route('admin.presence-alerts.statistics') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.presence-alerts.statistics') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-chart-line w-4"></i><span class="ml-2">Statistiques</span>
                        </a>
                    </div>
                </div>
                @endif

                @if($can('security'))
                <div x-data="{ open: {{ request()->routeIs('admin.security.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="flex items-center justify-between w-full px-4 py-2.5 mb-1 rounded-lg text-sm hover:bg-gray-800 {{ request()->routeIs('admin.security.*') ? 'bg-blue-600' : '' }}">
                        <div class="flex items-center">
                            <i class="fas fa-shield-alt w-5 text-red-400"></i>
                            <span class="ml-3">Securite</span>
                            @php $pendingViolations = \App\Models\SecurityViolation::where('status', 'pending')->count(); @endphp
                            @if($pendingViolations > 0)
                                <span class="ml-2 px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">{{ $pendingViolations }}</span>
                            @endif
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform" :class="{'rotate-180': open}"></i>
                    </button>
                    <div x-show="open" x-transition class="ml-4 space-y-1">
                        <a href="{{ route('admin.security.dashboard') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.security.dashboard') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-chart-line w-4"></i><span class="ml-2">Dashboard</span>
                        </a>
                        <a href="{{ route('admin.security.violations.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-800 text-xs {{ request()->routeIs('admin.security.violations.*') ? 'bg-gray-700' : '' }}">
                            <i class="fas fa-exclamation-triangle w-4"></i><span class="ml-2">Violations</span>
                        </a>
                    </div>
                </div>
                @endif

                @if($can('roles'))
                <a href="{{ route('admin.roles.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.roles.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-user-shield w-5 text-purple-400"></i>
                    <span class="ml-3">Roles & Permissions</span>
                </a>
                @endif

                @if($can('firebase'))
                <a href="{{ route('admin.firebase.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.firebase.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-fire w-5 text-orange-500"></i>
                    <span class="ml-3">Firebase</span>
                </a>
                <a href="{{ route('admin.ios-beta.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.ios-beta.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fab fa-apple w-5 text-gray-300"></i>
                    <span class="ml-3">iOS Beta</span>
                </a>
                @endif

                <a href="{{ route('admin.credentials.index') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.credentials.*') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-id-card w-5 text-yellow-400"></i>
                    <span class="ml-3">Identifiants PDF</span>
                </a>

                <a href="{{ route('admin.brochure.preview') }}" target="_blank" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm hover:bg-gray-800">
                    <i class="fas fa-file-pdf w-5 text-red-400"></i>
                    <span class="ml-3">Brochure PDF</span>
                    <i class="fas fa-external-link-alt w-3 ml-auto text-gray-500"></i>
                </a>

                @if($can('settings'))
                <a href="{{ route('admin.settings') }}" class="flex items-center px-4 py-2.5 mb-1 rounded-lg text-sm {{ request()->routeIs('admin.settings') ? 'bg-blue-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-cog w-5"></i>
                    <span class="ml-3">Parametres</span>
                </a>
                @endif
                @endif {{-- fin !$superAdminWithoutCompany (2 sections: Personnel + Systeme) --}}
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
                    @php
                        if ($superAdminWithoutCompany) {
                            $notifTickets = $notifLeaves = $notifJustifs = $notifCerts = $notifAdvances = $totalNotifs = 0;
                        } else {
                            $notifTickets = \App\Models\Ticket::whereIn('status', ['new', 'responded'])->count();
                            $notifLeaves = \App\Models\LeaveRequest::where('status', 'pending')->count();
                            $notifJustifs = \App\Models\JustificationRequest::where('status', 'pending')->count();
                            $notifCerts = \App\Models\WorkCertificate::where('status', 'pending')->count();
                            $notifAdvances = \App\Models\SalaryAdvanceRequest::where('status', 'pending')->count();
                            $totalNotifs = $notifTickets + $notifLeaves + $notifJustifs + $notifCerts + $notifAdvances;
                        }
                    @endphp
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="relative p-2 text-gray-600 hover:text-gray-800">
                            <i class="fas fa-bell text-xl"></i>
                            @if($totalNotifs > 0)
                                <span class="absolute -top-1 -right-1 px-1.5 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full min-w-[20px] text-center">{{ $totalNotifs }}</span>
                            @endif
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak
                             class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border z-50">
                            <div class="px-4 py-3 border-b">
                                <h3 class="font-bold text-sm text-gray-700">Notifications</h3>
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                @if($notifTickets > 0)
                                <a href="{{ route('admin.tickets.index') }}" class="flex items-center px-4 py-3 hover:bg-gray-50 border-b">
                                    <span class="w-8 h-8 bg-cyan-100 text-cyan-600 rounded-full flex items-center justify-center text-xs font-bold mr-3">{{ $notifTickets }}</span>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">Tickets en attente</p>
                                        <p class="text-xs text-gray-500">Nouveaux ou avec reponse</p>
                                    </div>
                                </a>
                                @endif
                                @if($notifLeaves > 0)
                                <a href="{{ route('admin.leaves.index') }}" class="flex items-center px-4 py-3 hover:bg-gray-50 border-b">
                                    <span class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xs font-bold mr-3">{{ $notifLeaves }}</span>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">Demandes de conge</p>
                                        <p class="text-xs text-gray-500">En attente de validation</p>
                                    </div>
                                </a>
                                @endif
                                @if($notifJustifs > 0)
                                <a href="{{ route('admin.justification-requests.index') }}" class="flex items-center px-4 py-3 hover:bg-gray-50 border-b">
                                    <span class="w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-xs font-bold mr-3">{{ $notifJustifs }}</span>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">Justifications</p>
                                        <p class="text-xs text-gray-500">En attente de validation</p>
                                    </div>
                                </a>
                                @endif
                                @if($notifCerts > 0)
                                <a href="{{ route('admin.certificates.index') }}" class="flex items-center px-4 py-3 hover:bg-gray-50 border-b">
                                    <span class="w-8 h-8 bg-teal-100 text-teal-600 rounded-full flex items-center justify-center text-xs font-bold mr-3">{{ $notifCerts }}</span>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">Attestations</p>
                                        <p class="text-xs text-gray-500">En attente de traitement</p>
                                    </div>
                                </a>
                                @endif
                                @if($notifAdvances > 0)
                                <a href="{{ route('admin.salary-advances.index') }}" class="flex items-center px-4 py-3 hover:bg-gray-50 border-b">
                                    <span class="w-8 h-8 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xs font-bold mr-3">{{ $notifAdvances }}</span>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">Avances salaire</p>
                                        <p class="text-xs text-gray-500">En attente d'approbation</p>
                                    </div>
                                </a>
                                @endif
                                @if($totalNotifs === 0)
                                <div class="px-4 py-6 text-center text-gray-400 text-sm">
                                    <i class="fas fa-check-circle text-2xl mb-2 text-green-400"></i>
                                    <p>Aucune notification</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="hidden md:block">
                        <form action="{{ route('admin.employees.index') }}" method="GET" class="flex gap-2">
                            <input
                                type="text"
                                name="search"
                                placeholder="Rechercher un employé..."
                                class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                value="{{ request('search') }}"
                            >
                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
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
