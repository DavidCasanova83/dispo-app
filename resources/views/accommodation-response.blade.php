<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de disponibilité</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
            @if($success)
                <!-- Succès -->
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                        <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>

                    <h1 class="text-2xl font-bold text-gray-900 mb-2">
                        Merci pour votre réponse !
                    </h1>

                    <p class="text-gray-600 mb-6">
                        {{ $message }}
                    </p>

                    @if(isset($accommodation))
                        <div class="bg-blue-50 rounded-lg p-4 mb-6">
                            <p class="text-sm text-gray-700 font-semibold">{{ $accommodation->name }}</p>
                            <p class="text-xs text-gray-600 mt-1">{{ $accommodation->city }}</p>
                            <div class="mt-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    {{ $status === 'disponible' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $status === 'disponible' ? '✓ Disponible' : '✗ Indisponible' }}
                                </span>
                            </div>
                        </div>
                    @endif

                    <p class="text-sm text-gray-500">
                        Votre statut a été enregistré avec succès.<br>
                        Vous pouvez fermer cette fenêtre.
                    </p>
                </div>
            @else
                <!-- Erreur -->
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                        <svg class="h-10 w-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>

                    <h1 class="text-2xl font-bold text-gray-900 mb-2">
                        Erreur
                    </h1>

                    <p class="text-gray-600 mb-6">
                        {{ $message }}
                    </p>

                    <p class="text-sm text-gray-500">
                        Si vous pensez qu'il s'agit d'une erreur,<br>
                        veuillez contacter le support.
                    </p>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
