<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de votre commande</title>
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

        .order-number {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 15px 20px;
            text-align: center;
            margin: 25px 0;
        }

        .order-number span {
            font-size: 14px;
            color: #6b7280;
            display: block;
            margin-bottom: 5px;
        }

        .order-number strong {
            font-size: 24px;
            color: #166534;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #3A9C92;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #3A9C92;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .items-table th {
            background-color: #f9fafb;
            padding: 12px;
            text-align: left;
            font-size: 14px;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .info-box {
            background-color: #f9fafb;
            border-radius: 6px;
            padding: 20px;
            margin: 15px 0;
        }

        .info-box p {
            margin: 5px 0;
            font-size: 14px;
        }

        .info-box strong {
            color: #374151;
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
            width: 60px;
            height: 60px;
            background-color: #10b981;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-icon svg {
            width: 30px;
            height: 30px;
            fill: #ffffff;
        }

        @media only screen and (max-width: 600px) {
            .content {
                padding: 20px 15px;
            }

            .items-table th,
            .items-table td {
                padding: 8px;
                font-size: 13px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Confirmation de commande</h1>
        </div>

        <div class="content">
            <div style="text-align: center;">
                <div class="success-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 01.208 1.04l-9 13.5a.75.75 0 01-1.154.114l-6-6a.75.75 0 011.06-1.06l5.353 5.353 8.493-12.739a.75.75 0 011.04-.208z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>

            <h2>Merci pour votre commande !</h2>

            <p>
                Bonjour {{ $civility === 'mr' ? 'Monsieur' : ($civility === 'mme' ? 'Madame' : '') }} {{ $fullName }},
            </p>

            <p>
                Nous avons bien recu votre commande d'images et nous vous en remercions.
            </p>

            <div class="order-number">
                <span>Numero de commande</span>
                <strong>{{ $orderNumber }}</strong>
            </div>

            <div class="section-title">Images commandees</div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th style="text-align: center;">Quantite</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $item['title'] }}</td>
                        <td style="text-align: center;">{{ $item['quantity'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="section-title">Adresse de livraison</div>

            <div class="info-box">
                @if($company)
                <p><strong>Societe :</strong> {{ $company }}</p>
                @endif
                <p><strong>Nom :</strong> {{ $fullName }}</p>
                <p><strong>Adresse :</strong> {{ $addressLine1 }}</p>
                @if($addressLine2)
                <p>{{ $addressLine2 }}</p>
                @endif
                <p>{{ $postalCode }} {{ $city }}</p>
                <p>{{ $country }}</p>
            </div>

            <div class="divider"></div>

            <p style="font-size: 14px; color: #6b7280;">
                Nous traiterons votre commande dans les meilleurs delais.
                Vous recevrez une notification lorsque votre commande sera expediee.
            </p>
        </div>

        <div class="footer">
            <p>
                Merci de votre confiance.<br>
                Cet email a ete envoye automatiquement suite a votre commande.
            </p>
        </div>
    </div>
</body>

</html>
