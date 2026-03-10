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
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full {{ $available ? 'bg-green-100' : 'bg-red-100' }} mb-4">
                    @if($available)
                        <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    @else
                        <svg class="h-10 w-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    @endif
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                    Confirmation de disponibilité
                </h1>

                <div class="bg-blue-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-gray-700 font-semibold">{{ $accommodation->name }}</p>
                    @if($accommodation->city)
                        <p class="text-xs text-gray-600 mt-1">{{ $accommodation->city }}</p>
                    @endif
                </div>

                <p class="text-gray-600 mb-6">
                    Confirmez-vous que votre établissement est actuellement
                    <strong class="{{ $available ? 'text-green-700' : 'text-red-700' }}">
                        {{ $available ? 'disponible' : 'indisponible' }}
                    </strong> ?
                </p>

                <form method="POST" action="{{ $fullUrl }}">
                    @csrf
                    <button type="submit"
                        class="w-full inline-flex justify-center items-center px-6 py-3 rounded-lg text-white font-bold text-lg transition-opacity hover:opacity-90
                            {{ $available ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }}">
                        {{ $available ? 'Oui, je suis disponible' : 'Oui, je suis indisponible' }}
                    </button>
                </form>

                <p class="text-sm text-gray-400 mt-6">
                    Ce lien est valable 7 jours.<br>
                    Un nouvel email vous est envoyé chaque matin à 6h.
                </p>

                @include('partials.accommodation-report-form', ['accommodationName' => $accommodation->name])
            </div>
        </div>
    </div>
</body>
</html>
