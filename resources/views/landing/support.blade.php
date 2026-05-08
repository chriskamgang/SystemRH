@extends('landing.layout')

@section('title', 'Support - Estuaire RH')
@section('description', 'Centre d\'aide et support pour l\'application Estuaire RH')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-bold gradient-text mb-2">Centre d'Aide & Support</h1>
    <p class="text-gray-500 mb-8">Application Estuaire RH - Gestion des Ressources Humaines</p>

    <div class="bg-white rounded-2xl shadow-lg p-8 space-y-8 text-gray-700 leading-relaxed">

        {{-- Contact --}}
        <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">Nous contacter</h2>
            <p>Pour toute question, probleme technique ou demande d'assistance concernant l'application Estuaire RH, vous pouvez nous joindre par :</p>
            <div class="mt-4 space-y-3">
                <div class="flex items-center gap-3 p-4 bg-blue-50 rounded-xl">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Email</p>
                        <a href="mailto:support@estuaire-services.com" class="text-blue-600 hover:underline">support@estuaire-services.com</a>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-4 bg-green-50 rounded-xl">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Telephone / WhatsApp</p>
                        <a href="tel:+237690000000" class="text-green-600 hover:underline">+237 6 90 00 00 00</a>
                    </div>
                </div>
            </div>
        </section>

        {{-- FAQ --}}
        <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">Questions frequentes</h2>

            <div class="space-y-4">
                <div class="border rounded-xl p-4">
                    <h3 class="font-semibold text-gray-900">Comment me connecter a l'application ?</h3>
                    <p class="mt-2 text-sm">Utilisez l'adresse email et le mot de passe fournis par votre administrateur RH. Si vous n'avez pas encore de compte, contactez le service des ressources humaines de votre etablissement.</p>
                </div>

                <div class="border rounded-xl p-4">
                    <h3 class="font-semibold text-gray-900">Le pointage GPS ne fonctionne pas, que faire ?</h3>
                    <p class="mt-2 text-sm">Verifiez que la localisation est activee sur votre telephone et que vous avez autorise l'application a acceder a votre position. Vous devez etre physiquement present sur le campus pour pointer. Si le probleme persiste, essayez de redemarrer l'application.</p>
                </div>

                <div class="border rounded-xl p-4">
                    <h3 class="font-semibold text-gray-900">Puis-je pointer sans connexion internet ?</h3>
                    <p class="mt-2 text-sm">Oui ! L'application fonctionne en mode hors-ligne. Vos pointages sont enregistres localement et se synchronisent automatiquement des que vous retrouvez une connexion internet.</p>
                </div>

                <div class="border rounded-xl p-4">
                    <h3 class="font-semibold text-gray-900">Comment demander un conge ?</h3>
                    <p class="mt-2 text-sm">Depuis l'ecran Profil, appuyez sur "Mes conges", puis sur le bouton "+" pour creer une nouvelle demande. Remplissez le formulaire avec les dates et le motif, puis soumettez. Votre responsable sera notifie automatiquement.</p>
                </div>

                <div class="border rounded-xl p-4">
                    <h3 class="font-semibold text-gray-900">Mon telephone affiche "appareil deja utilise par un autre compte"</h3>
                    <p class="mt-2 text-sm">Pour des raisons de securite, un telephone ne peut etre lie qu'a un seul compte. Contactez votre administrateur RH pour reinitialiser l'association de votre appareil.</p>
                </div>

                <div class="border rounded-xl p-4">
                    <h3 class="font-semibold text-gray-900">Comment telecharger mes fiches de paie ?</h3>
                    <p class="mt-2 text-sm">Rendez-vous dans Profil > Historique fiches de paie. Selectionnez le mois souhaite et appuyez sur le bouton de telechargement pour obtenir votre bulletin en PDF.</p>
                </div>
            </div>
        </section>

        {{-- Infos app --}}
        <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">A propos de l'application</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-gray-500">Editeur</p>
                    <p class="font-semibold text-gray-900">ESTUAIRE SERVICES SARL</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-gray-500">Version actuelle</p>
                    <p class="font-semibold text-gray-900">1.0</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-gray-500">Compatibilite</p>
                    <p class="font-semibold text-gray-900">iOS 14+ / Android 8+</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-gray-500">Politique de confidentialite</p>
                    <a href="{{ route('landing.privacy-policy') }}" class="font-semibold text-blue-600 hover:underline">Consulter</a>
                </div>
            </div>
        </section>

    </div>
</div>
@endsection
