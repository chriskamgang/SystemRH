<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion — IUEs/INSAM</title>

    <link rel="icon" href="{{ asset('images/estuare_rh.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* La carte retenue se signale par sa bordure et un léger relief :
           l'anneau seul se confond avec le survol sur petit écran. */
        .espace input:checked + .carte {
            border-color: var(--teinte);
            background-color: var(--fond);
            box-shadow: 0 10px 25px -12px var(--teinte);
        }
        .espace input:checked + .carte .marque { transform: scale(1.06); }
        .espace input:focus-visible + .carte { outline: 2px solid var(--teinte); outline-offset: 3px; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">

    <div class="w-full max-w-lg">

        {{-- En-tête --}}
        <div class="text-center mb-7">
            <img src="{{ asset('images/estuare_rh.png') }}" alt="IUEs/INSAM"
                 class="w-20 h-20 mx-auto object-contain drop-shadow">
            <h1 class="mt-3 text-2xl font-bold text-slate-800">IUEs/INSAM</h1>
            <p class="text-sm text-slate-500">Administration</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-7">

            {{-- Erreurs --}}
            @if ($errors->any())
                <div class="mb-5 p-3.5 bg-red-50 border-l-4 border-red-500 rounded-r">
                    <div class="flex gap-2">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                        <div class="text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if (session('success'))
                <div class="mb-5 p-3.5 bg-green-50 border-l-4 border-green-500 rounded-r flex gap-2">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Choix de l'espace.
                     Une seule connexion dessert les deux : le choix ne décide
                     que de la page d'arrivée, et le menu permet de basculer
                     ensuite sans se reconnecter. --}}
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2.5">
                    Espace de travail
                </p>

                @php $espaceChoisi = old('espace', 'rh'); @endphp

                <div class="grid grid-cols-2 gap-3 mb-6">

                    {{-- INSAM BUS --}}
                    <label class="espace cursor-pointer" style="--teinte:#d97706; --fond:#fffbeb;">
                        <input type="radio" name="espace" value="bus" class="sr-only"
                               @checked($espaceChoisi === 'bus')>
                        <div class="carte h-full rounded-xl border-2 border-slate-200 bg-white p-4 text-center
                                    transition-all duration-200 hover:border-amber-300">
                            <div class="marque w-16 h-16 mx-auto mb-2.5 flex items-center justify-center
                                        transition-transform duration-200">
                                <img src="{{ asset('images/logo_bus.png') }}" alt="INSAM BUS"
                                     class="w-full h-full object-contain">
                            </div>
                            <p class="font-bold text-slate-800 text-sm">INSAM BUS</p>
                            <p class="text-xs text-slate-500 mt-0.5">Transport</p>
                        </div>
                    </label>

                    {{-- Estuaire RH --}}
                    <label class="espace cursor-pointer" style="--teinte:#2563eb; --fond:#eff6ff;">
                        <input type="radio" name="espace" value="rh" class="sr-only"
                               @checked($espaceChoisi === 'rh')>
                        <div class="carte h-full rounded-xl border-2 border-slate-200 bg-white p-4 text-center
                                    transition-all duration-200 hover:border-blue-300">
                            <div class="marque w-16 h-16 mx-auto mb-2.5 flex items-center justify-center
                                        transition-transform duration-200">
                                <img src="{{ asset('images/estuare_rh.png') }}" alt="Estuaire RH"
                                     class="w-full h-full object-contain">
                            </div>
                            <p class="font-bold text-slate-800 text-sm">Estuaire RH</p>
                            <p class="text-xs text-slate-500 mt-0.5">Personnel</p>
                        </div>
                    </label>
                </div>

                {{-- Identifiants --}}
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Adresse email
                    </label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                               placeholder="admin@insam.cm"
                               class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg text-sm
                                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                      @error('email') border-red-400 @enderror">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Mot de passe
                    </label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="password" id="password" name="password" required
                               placeholder="••••••••"
                               class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg text-sm
                                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                      @error('password') border-red-400 @enderror">
                    </div>
                </div>

                <label class="flex items-center cursor-pointer mb-5">
                    <input type="checkbox" name="remember"
                           class="h-4 w-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-slate-600">Se souvenir de moi</span>
                </label>

                <button type="submit"
                        class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3 rounded-lg
                               text-sm transition-colors">
                    <i class="fas fa-arrow-right-to-bracket mr-2"></i>
                    Se connecter
                </button>
            </form>

            <p class="mt-5 text-center text-xs text-slate-400">
                Accès réservé aux administrateurs
            </p>
        </div>

        <p class="text-center mt-6 text-xs text-slate-400">
            &copy; {{ date('Y') }} IUEs/INSAM
        </p>
    </div>
</body>
</html>
