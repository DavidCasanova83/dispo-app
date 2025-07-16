<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour de vos informations d'hébergement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 24px;
        }
        .accommodation-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .accommodation-info h2 {
            color: #2c3e50;
            margin-top: 0;
            font-size: 20px;
        }
        .info-item {
            margin: 10px 0;
            font-size: 14px;
        }
        .info-item strong {
            color: #495057;
        }
        .cta-button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #0056b3;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
        }
        .instructions {
            background-color: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
        }
        .instructions h3 {
            color: #2c3e50;
            margin-top: 0;
        }
        .instructions ul {
            padding-left: 20px;
        }
        .instructions li {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏨 Mise à jour de vos informations d'hébergement</h1>
            <p>Veuillez vérifier et mettre à jour le statut de votre établissement</p>
        </div>

        <p>Bonjour,</p>

        <p>Nous avons récemment mis à jour notre base de données d'hébergements touristiques. Votre établissement figure dans notre système et nous souhaitons nous assurer que ses informations sont à jour.</p>

        <div class="accommodation-info">
            <h2>{{ $accommodation->name }}</h2>
            <div class="info-item">
                <strong>🏷️ ID Apidae :</strong> {{ $accommodation->apidae_id }}
            </div>
            @if($accommodation->city)
                <div class="info-item">
                    <strong>🏙️ Ville :</strong> {{ $accommodation->city }}
                </div>
            @endif
            @if($accommodation->type)
                <div class="info-item">
                    <strong>🏨 Type :</strong> {{ $accommodation->type }}
                </div>
            @endif
            <div class="info-item">
                <strong>📊 Statut actuel :</strong> 
                <span class="status-badge status-{{ $accommodation->status }}">
                    {{ $accommodation->status_label }}
                </span>
            </div>
        </div>

        <div class="instructions">
            <h3>📝 Action requise</h3>
            <p>Nous vous demandons de vérifier et mettre à jour le statut de votre hébergement :</p>
            <ul>
                <li><strong>✅ Actif</strong> : Si votre établissement est ouvert et prêt à accueillir des clients</li>
                <li><strong>❌ Inactif</strong> : Si votre établissement est temporairement fermé ou non disponible</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $manageUrl }}" class="cta-button">
                🔗 Mettre à jour le statut de mon hébergement
            </a>
        </div>

        <p><strong>Important :</strong> Ce lien est unique et sécurisé pour votre établissement. Il vous permettra de modifier facilement le statut sans avoir besoin de créer un compte.</p>

        <p>Si vous avez des questions ou rencontrez des difficultés, n'hésitez pas à nous contacter.</p>

        <p>Cordialement,<br>
        L'équipe Dispo App</p>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement suite à la mise à jour de notre base de données d'hébergements touristiques.</p>
            <p>Lien de gestion : <a href="{{ $manageUrl }}">{{ $manageUrl }}</a></p>
            <p>Si vous ne souhaitez plus recevoir ces emails, veuillez nous contacter.</p>
        </div>
    </div>
</body>
</html>