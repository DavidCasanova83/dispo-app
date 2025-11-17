<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre compte a été approuvé</title>
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

        .user-name {
            font-weight: bold;
            color: #3A9C92;
            font-size: 18px;
        }

        .info-box {
            background-color: #f0f9f8;
            border-left: 4px solid #3A9C92;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .info-box p {
            margin: 5px 0;
            font-size: 14px;
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

        .success-icon {
            text-align: center;
            font-size: 48px;
            margin-bottom: 20px;
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
            <h1>Compte approuvé</h1>
        </div>

        <div class="content">
            <div class="success-icon">✓</div>

            <h2>Bonjour {{ $userName }},</h2>

            <p>
                Nous avons le plaisir de vous informer que votre compte a été <strong>approuvé avec succès</strong> !
            </p>

            <p>
                Vous pouvez maintenant accéder à l'application et profiter de toutes les fonctionnalités disponibles.
            </p>

            <div class="divider"></div>

            <div class="info-box">
                <p><strong>Informations de connexion :</strong></p>
                <p>Email : <strong>{{ $userEmail }}</strong></p>
                <p>URL de connexion : <a href="{{ $loginUrl }}" style="color: #3A9C92;">{{ $loginUrl }}</a></p>
            </div>

            <div class="buttons-container">
                <a href="{{ $loginUrl }}" class="button">
                    Se connecter maintenant
                </a>
            </div>

            <p style="font-size: 14px; color: #6b7280; margin-top: 30px;">
                Si vous rencontrez des difficultés pour vous connecter ou si vous avez des questions,
                n'hésitez pas à nous contacter.
            </p>
        </div>

        <div class="footer">
            <p>
                Bienvenue dans notre application !<br>
                Cet email a été envoyé automatiquement, vous pouvez y répondre si vous avez des questions.
            </p>
        </div>
    </div>
</body>

</html>
