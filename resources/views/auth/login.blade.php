<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion - Attendance Admin</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo et Titre -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-lg mb-4">
                <i class="fas fa-map-marker-alt text-4xl text-blue-600"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-2">Attendance</h1>
            <p class="text-blue-100">Tableau de bord administrateur</p>
        </div>

        <!-- Carte de connexion -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Connexion</h2>

            <!-- Messages d'erreur -->
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                        <div class="text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Message de succès (déconnexion) -->
            @if (session('success'))
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            <!-- Formulaire de connexion -->
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email -->
                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2 text-gray-400"></i>
                        Adresse email
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('email') border-red-500 @enderror"
                        placeholder="admin@example.com"
                    >
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Mot de passe -->
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2 text-gray-400"></i>
                        Mot de passe
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('password') border-red-500 @enderror"
                        placeholder="••••••••"
                    >
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Se souvenir de moi -->
                <div class="mb-6">
                    <label class="flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            name="remember"
                            class="form-checkbox h-4 w-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-600">Se souvenir de moi</span>
                    </label>
                </div>

                <!-- Bouton de connexion -->
                <button
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Se connecter
                </button>
            </form>

            <!-- Informations supplémentaires -->
            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500">
                    Accès réservé aux administrateurs uniquement
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-white text-sm">
            <p>&copy; {{ date('Y') }} Attendance App. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
