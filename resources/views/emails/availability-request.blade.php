<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour de vos disponibilités</title>
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
        }

        .content p {
            margin-bottom: 15px;
            font-size: 16px;
        }

        .accommodation-name {
            font-weight: bold;
            color: #3A9C92;
            font-size: 18px;
        }

        .buttons-container {
            color: #f4f8f8;
            margin: 40px 0;
            text-align: center;
        }

        .button {
            display: inline-block;
            padding: 15px 40px;
            margin: 10px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            border-radius: 6px;
            transition: opacity 0.3s;
        }

        .button:hover {
            opacity: 0.9;
        }

        .button-available {
            background-color: #10b981;
            color: #ffffff;
        }

        .button-not-available {
            background-color: #ef4444;
            color: #ffffff;
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
            <h1>Mise à jour de vos disponibilités</h1>
        </div>

        <div class="content">
            <h2>Bonjour,</h2>

            <p>
                Nous souhaitons mettre à jour les informations de disponibilité pour votre établissement :
            </p>

            <p class="accommodation-name">{{ $accommodationName }}</p>

            <div class="divider"></div>

            <p>
                Merci de nous indiquer votre situation actuelle en cliquant sur l'un des boutons ci-dessous :
            </p>

            <div class="buttons-container">
                <a href="{{ $availableUrl }}" class="button button-available" style="text-decoration: none;">
                    ✓ Disponibilités
                </a>

                <a href="{{ $notAvailableUrl }}" class="button button-not-available" style="text-decoration: none;">
                    ✗ Pas de disponibilités
                </a>
            </div>

            <p style="font-size: 14px; color: #6b7280; margin-top: 30px;">
                Cliquez sur le bouton correspondant à votre situation actuelle. Votre réponse nous permettra de mettre à
                jour instantanément vos informations.<br>
                <strong>Ces boutons sont actifs toutes la journée.</strong> Vous pouvez les utiliser
                autant de fois que nécessaire.
            </p>

            <p style="font-size: 14px; color: #6b7280; margin-top: 30px;">La réponse apportée permet de mettre à jour
                les informations présentes sur cette page :</p>
            <p><a href="https://www.verdontourisme.com/disponibilites/">Hébergements disponibles ce soir</a></p>
        </div>

        <div class="footer">
            <p>
                Merci de votre collaboration.<br>
                Cet email a été envoyé automatiquement.<br>
                <strong>Vous pouvez y répondre si vous avez des questions.</strong><br>
                Nous vous répondrons dès que possible.<br>
            </p>
        </div>
    </div>
</body>

</html>
