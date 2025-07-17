<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestion du Statut - {{ $accommodation->name }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --color-primary: #3A9C92;
            --color-secondary: #7AB6A8;
            --color-accent: #FFFDF4;
            --color-background: #FAF7F3;
        }
        .dark {
            --color-primary-dark: #4DAAA0;
            --color-secondary-dark: #8CC4B8;
            --color-accent-dark: #2A2A2A;
            --color-background-dark: #1A1A1A;
        }
    </style>
</head>
<body class="font-sans antialiased bg-adaptive-background dark:bg-zinc-900">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    🏨 Gestion du Statut
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    Choisissez le statut pour l'hébergement ci-dessous
                </p>
            </div>

            <!-- Accommodation Info Card -->
            <div class="bg-adaptive-accent dark:bg-zinc-800 rounded-xl shadow-lg p-6 border border-adaptive-secondary dark:border-zinc-700">
                <div class="flex items-start justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $accommodation->name }}</h2>
                    <span class="px-3 py-1 text-sm rounded-full font-medium
                        @if ($accommodation->status === 'active') bg-green-100 text-green-800
                        @elseif($accommodation->status === 'pending') bg-yellow-100 text-yellow-800
                        @else bg-red-100 text-red-800 @endif">
                        {{ $accommodation->status_label }}
                    </span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    @if ($accommodation->city)
                        <div class="flex items-center text-gray-600 dark:text-gray-300">
                            <span class="mr-2">🏙️</span>
                            <span><strong>Ville:</strong> {{ $accommodation->city }}</span>
                        </div>
                    @endif

                    @if ($accommodation->type)
                        <div class="flex items-center text-gray-600">
                            <span class="mr-2">🏷️</span>
                            <span><strong>Type:</strong> {{ $accommodation->type }}</span>
                        </div>
                    @endif

                    @if ($accommodation->email)
                        <div class="flex items-center text-gray-600">
                            <span class="mr-2">📧</span>
                            <span><strong>Email:</strong> {{ $accommodation->email }}</span>
                        </div>
                    @endif

                    @if ($accommodation->phone)
                        <div class="flex items-center text-gray-600">
                            <span class="mr-2">📞</span>
                            <span><strong>Téléphone:</strong> {{ $accommodation->phone }}</span>
                        </div>
                    @endif

                    <div class="flex items-center text-gray-600">
                        <span class="mr-2">🆔</span>
                        <span><strong>ID Apidae:</strong> <code class="bg-adaptive-background dark:bg-zinc-700 px-2 py-1 rounded text-xs">{{ $accommodation->apidae_id }}</code></span>
                    </div>

                    <div class="flex items-center text-gray-600">
                        <span class="mr-2">📅</span>
                        <span><strong>Créé le:</strong> {{ $accommodation->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- Success Message -->
            @if (session('success'))
                <div class="bg-green-50 dark:bg-green-900/20 border border-adaptive-primary dark:border-green-700 rounded-lg p-4">
                    <div class="flex items-center">
                        <span class="text-adaptive-primary mr-2">✅</span>
                        <p class="text-adaptive-primary dark:text-green-300 font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            <!-- Status Management Form -->
            <div class="bg-adaptive-accent dark:bg-zinc-800 rounded-xl shadow-lg p-6 border border-adaptive-secondary dark:border-zinc-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Changer le statut</h3>
                
                <form method="POST" action="{{ route('accommodation.update-status', $accommodation->apidae_id) }}">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button type="submit" 
                                name="status" 
                                value="active" 
                                class="flex items-center justify-center px-6 py-4 bg-adaptive-primary hover:bg-adaptive-secondary text-white font-bold rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl transform hover:scale-105"
                                @if($accommodation->status === 'active') disabled @endif>
                            <span class="mr-2">✅</span>
                            <span>Activer</span>
                        </button>
                        
                        <button type="submit" 
                                name="status" 
                                value="inactive" 
                                class="flex items-center justify-center px-6 py-4 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl transform hover:scale-105"
                                @if($accommodation->status === 'inactive') disabled @endif>
                            <span class="mr-2">❌</span>
                            <span>Désactiver</span>
                        </button>
                    </div>
                </form>

                <!-- Status Explanations -->
                <div class="mt-6 bg-adaptive-background dark:bg-zinc-700 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">Explication des statuts :</h4>
                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <div class="flex items-start">
                            <span class="text-adaptive-primary mr-2">✅</span>
                            <div>
                                <strong>Actif :</strong> L'hébergement est validé et visible publiquement
                            </div>
                        </div>
                        <div class="flex items-start">
                            <span class="text-red-600 mr-2">❌</span>
                            <div>
                                <strong>Inactif :</strong> L'hébergement est désactivé et non visible publiquement
                            </div>
                        </div>
                        <div class="flex items-start">
                            <span class="text-yellow-600 mr-2">⏳</span>
                            <div>
                                <strong>En attente :</strong> L'hébergement nécessite une validation manuelle
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                <p>Cette page permet de gérer le statut de votre hébergement</p>
                <p class="mt-1">Dernière mise à jour : {{ $accommodation->updated_at->format('d/m/Y à H:i') }}</p>
            </div>
        </div>
    </div>
</body>
</html>