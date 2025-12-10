<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de votre mot de passe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #3A9C92;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .content {
            padding: 40px 30px;
        }

        .content h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #3A9C92;
        }

        .content p {
            margin-bottom: 15px;
            font-size: 16px;
        }

        .info-box {
            background-color: #fef3cd;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .info-box p {
            margin: 5px 0;
            font-size: 14px;
            color: #92400e;
        }

        .buttons-container {
            margin: 40px 0;
            text-align: center;
        }

        .button {
            display: inline-block;
            padding: 15px 40px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            border-radius: 6px;
            transition: opacity 0.3s;
            background-color: #3A9C92;
            color: #ffffff;
        }

        .button:hover {
            opacity: 0.9;
        }

        .footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }

        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 30px 0;
        }

        .lock-icon {
            text-align: center;
            font-size: 48px;
            margin-bottom: 20px;
        }

        .url-fallback {
            word-break: break-all;
            font-size: 12px;
            color: #6b7280;
            background-color: #f3f4f6;
            padding: 10px;
            border-radius: 4px;
            margin-top: 20px;
        }

        @media only screen and (max-width: 600px) {
            .button {
                display: block;
                margin: 10px 0;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Réinitialisation du mot de passe</h1>
        </div>

        <div class="content">
            <div class="lock-icon">🔐</div>

            <h2>Bonjour {{ $userName }},</h2>

            <p>
                Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe.
            </p>

            <div class="buttons-container">
                <a href="{{ $resetUrl }}" class="button">
                    Réinitialiser mon mot de passe
                </a>
            </div>

            <div class="info-box">
                <p><strong>⏰ Important :</strong> Ce lien expire dans <strong>60 minutes</strong>.</p>
                <p>Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email en toute sécurité.</p>
            </div>

            <div class="divider"></div>

            <p style="font-size: 14px; color: #6b7280;">
                Si le bouton ne fonctionne pas, copiez et collez le lien suivant dans votre navigateur :
            </p>
            <div class="url-fallback">
                {{ $resetUrl }}
            </div>
        </div>

        <div class="footer">
            <p>
                Cet email a été envoyé automatiquement suite à une demande de réinitialisation de mot de passe.<br>
                Si vous n'êtes pas à l'origine de cette demande, aucune action n'est requise.
            </p>
        </div>
    </div>
</body>

</html>
