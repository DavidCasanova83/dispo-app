@php
    use Illuminate\Support\Facades\DB;

    // Test connexion base de données
    $dbConnected = false;
    $dbError = null;
    try {
        DB::connection()->getPdo();
        $dbConnected = true;
    } catch (\Exception $e) {
        $dbError = $e->getMessage();
    }

    // Compter les enregistrements
    $accommodationsCount = \App\Models\Accommodation::count();
    $qualificationsCount = \App\Models\Qualification::count();
    $usersCount = \App\Models\User::count();

    // Récupérer les permissions de l'utilisateur
    $userPermissions = Auth::user()->getAllPermissions()->pluck('name')->toArray();
    $userRoles = Auth::user()->getRoleNames()->toArray();
@endphp

<x-layouts.app :title="__('Page de Test')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Page de Test</h1>
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                ← Retour au Dashboard
            </a>
        </div>

        {{-- Status Global --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">État du Système</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <p class="text-green-800 dark:text-green-200 font-semibold">✅ Application</p>
                    <p class="text-sm text-green-700 dark:text-green-300 mt-1">Opérationnelle</p>
                </div>

                <div
                    class="p-4 {{ $dbConnected ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' }} rounded-lg border">
                    <p
                        class="{{ $dbConnected ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }} font-semibold">
                        {{ $dbConnected ? '✅' : '❌' }} Base de données
                    </p>
                    <p class="text-sm {{ $dbConnected ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }} mt-1">
                        {{ $dbConnected ? 'Connectée' : 'Erreur : ' . $dbError }}
                    </p>
                </div>

                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <p class="text-blue-800 dark:text-blue-200 font-semibold">📊 Environnement</p>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">{{ config('app.env') }}</p>
                </div>
            </div>
        </div>

        {{-- Informations Utilisateur --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Informations Utilisateur</h2>
            <div class="space-y-3">
                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                    <p class="text-purple-800 dark:text-purple-200">
                        👤 <strong>Nom :</strong> {{ Auth::user()->name }}
                    </p>
                </div>
                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                    <p class="text-purple-800 dark:text-purple-200">
                        📧 <strong>Email :</strong> {{ Auth::user()->email }}
                    </p>
                </div>
                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                    <p class="text-purple-800 dark:text-purple-200">
                        🔑 <strong>Rôles :</strong>
                        @if (count($userRoles) > 0)
                            {{ implode(', ', $userRoles) }}
                        @else
                            <span class="text-gray-500">Aucun rôle</span>
                        @endif
                    </p>
                </div>
                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                    <p class="text-purple-800 dark:text-purple-200">
                        ✅ <strong>Approuvé :</strong> {{ Auth::user()->approved ? 'Oui' : 'Non' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Permissions --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Permissions</h2>
            @if (count($userPermissions) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach ($userPermissions as $permission)
                        <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-800">
                            <p class="text-sm text-green-800 dark:text-green-200">✓ {{ $permission }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400">Aucune permission attribuée</p>
            @endif
        </div>

        {{-- Statistiques Base de Données --}}
        @if ($dbConnected)
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Statistiques Base de Données</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 text-center">
                        <p class="text-3xl font-bold text-blue-800 dark:text-blue-200">{{ $usersCount }}</p>
                        <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">Utilisateurs</p>
                    </div>
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 text-center">
                        <p class="text-3xl font-bold text-green-800 dark:text-green-200">{{ $accommodationsCount }}</p>
                        <p class="text-sm text-green-700 dark:text-green-300 mt-1">Hébergements</p>
                    </div>
                    <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800 text-center">
                        <p class="text-3xl font-bold text-orange-800 dark:text-orange-200">{{ $qualificationsCount }}</p>
                        <p class="text-sm text-orange-700 dark:text-orange-300 mt-1">Qualifications</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Informations Techniques --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Informations Techniques</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="font-medium mb-2 text-gray-900 dark:text-white">Configuration Laravel</h3>
                    <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                        <li>• Version PHP : <code
                                class="bg-gray-200 dark:bg-gray-600 px-1 rounded">{{ PHP_VERSION }}</code></li>
                        <li>• Version Laravel : <code
                                class="bg-gray-200 dark:bg-gray-600 px-1 rounded">{{ app()->version() }}</code></li>
                        <li>• Environnement : <code
                                class="bg-gray-200 dark:bg-gray-600 px-1 rounded">{{ config('app.env') }}</code></li>
                        <li>• Debug : <code
                                class="bg-gray-200 dark:bg-gray-600 px-1 rounded">{{ config('app.debug') ? 'Activé' : 'Désactivé' }}</code>
                        </li>
                        <li>• Timezone : <code
                                class="bg-gray-200 dark:bg-gray-600 px-1 rounded">{{ config('app.timezone') }}</code>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-medium mb-2 text-gray-900 dark:text-white">Informations Route</h3>
                    <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                        <li>• Route : <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">/test</code></li>
                        <li>• Nom de route : <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">test</code></li>
                        <li>• Middleware : <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">auth,
                                approved</code></li>
                        <li>• Vue : <code
                                class="bg-gray-200 dark:bg-gray-600 px-1 rounded">test.blade.php</code></li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Variables d'environnement importantes --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Configuration Services</h2>
            <div class="space-y-2">
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Mailjet configuré</span>
                    <span
                        class="px-2 py-1 text-xs rounded {{ config('services.mailjet.key') ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                        {{ config('services.mailjet.key') ? 'Oui' : 'Non' }}
                    </span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Base de données</span>
                    <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ config('database.default') }}
                    </span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Cache driver</span>
                    <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ config('cache.default') }}
                    </span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Queue driver</span>
                    <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ config('queue.default') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
