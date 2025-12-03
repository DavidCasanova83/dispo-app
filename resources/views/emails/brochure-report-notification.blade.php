<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signalement de problème sur une brochure</title>
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
            background-color: #f59e0b;
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
            color: #f59e0b;
        }

        .content p {
            margin-bottom: 15px;
            font-size: 16px;
        }

        .info-box {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .info-box p {
            margin: 5px 0;
            font-size: 14px;
        }

        .comment-box {
            background-color: #f3f4f6;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }

        .comment-box p {
            margin: 0;
            font-style: italic;
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

        .notification-icon {
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
            <h1>Signalement de brochure</h1>
        </div>

        <div class="content">
            <div class="notification-icon">&#9888;</div>

            <h2>Nouveau signalement de problème</h2>

            <p>
                Un utilisateur a signalé un problème concernant une brochure.
            </p>

            <div class="divider"></div>

            <div class="info-box">
                <p><strong>Brochure concernée :</strong></p>
                <p>{{ $brochureTitle }}</p>
            </div>

            <div class="info-box">
                <p><strong>Signalé par :</strong></p>
                <p>{{ $userName }} ({{ $userEmail }})</p>
                <p><strong>Date :</strong> {{ $reportDate }}</p>
            </div>

            <p><strong>Commentaire :</strong></p>
            <div class="comment-box">
                <p>{{ $comment }}</p>
            </div>

            <div class="buttons-container">
                <a href="{{ $adminUrl }}" class="button">
                    Accéder à la gestion des images
                </a>
            </div>

            <p style="font-size: 14px; color: #6b7280; margin-top: 30px;">
                Vous pouvez consulter et résoudre ce signalement depuis le panel d'administration.
            </p>
        </div>

        <div class="footer">
            <p>
                Notification automatique - Panel d'administration<br>
                Cet email a été envoyé automatiquement suite à un signalement utilisateur.
            </p>
        </div>
    </div>
</body>

</html>
