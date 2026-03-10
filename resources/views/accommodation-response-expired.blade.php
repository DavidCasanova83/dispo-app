<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lien expiré</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-orange-100 mb-4">
                    <svg class="h-10 w-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                    Ce lien n'est plus valide
                </h1>

                <p class="text-gray-600 mb-6">
                    Le lien sur lequel vous avez cliqué a expiré ou n'est plus actif.
                </p>

                <div class="bg-blue-50 rounded-lg p-4">
                    <p class="text-sm text-gray-700">
                        Un nouvel email vous est envoyé <strong>chaque matin à 6h</strong>.<br>
                        Veuillez cliquer sur le lien contenu dans le <strong>dernier email reçu</strong>.
                    </p>
                </div>

                <p class="text-sm text-gray-400 mt-6">
                    Les liens de disponibilité sont valables 7 jours.
                </p>

                @include('partials.accommodation-report-form', ['accommodationName' => 'Non identifié'])
            </div>
        </div>
    </div>
</body>
</html>
