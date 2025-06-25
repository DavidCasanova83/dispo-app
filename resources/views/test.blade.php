<x-layouts.app :title="__('Page de Test')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Page de Test</h1>
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                ← Retour au Dashboard
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Bienvenue sur la page de test !</h2>

            <div class="space-y-4">
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <p class="text-blue-800 dark:text-blue-200">
                        ✅ Cette page fonctionne correctement !
                    </p>
                </div>

                <div
                    class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <p class="text-green-800 dark:text-green-200">
                        🎉 Vous êtes connecté en tant que : <strong>{{ Auth::user()->name }}</strong>
                    </p>
                </div>

                <div
                    class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                    <p class="text-purple-800 dark:text-purple-200">
                        📧 Email : <strong>{{ Auth::user()->email }}</strong>
                    </p>
                </div>
            </div>

            <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <h3 class="font-medium mb-2 text-gray-900 dark:text-white">Informations techniques :</h3>
                <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                    <li>• Route : <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">/test</code></li>
                    <li>• Nom de route : <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">test</code></li>
                    <li>• Middleware : <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">auth</code></li>
                    <li>• Vue : <code class="bg-gray-200 dark:bg-gray-600 px-1 rounded">test.blade.php</code></li>
                </ul>
            </div>
        </div>
    </div>
</x-layouts.app>
