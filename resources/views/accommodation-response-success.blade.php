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
                    Votre établissement a été marqué comme
                    <strong class="{{ $status === 'disponible' ? 'text-green-700' : 'text-red-700' }}">
                        {{ $status }}
                    </strong>.
                </p>

                <div class="bg-blue-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-gray-700 font-semibold">{{ $accommodation->name }}</p>
                    @if($accommodation->city)
                        <p class="text-xs text-gray-600 mt-1">{{ $accommodation->city }}</p>
                    @endif
                    <div class="mt-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            {{ $status === 'disponible' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $status === 'disponible' ? '&#10003; Disponible' : '&#10007; Indisponible' }}
                        </span>
                    </div>
                </div>

                <p class="text-sm text-gray-500">
                    Votre statut a été enregistré avec succès.<br>
                    Vous pouvez fermer cette fenêtre.
                </p>

                @include('partials.accommodation-report-form', ['accommodationName' => $accommodation->name])
            </div>
        </div>
    </div>
</body>
</html>
