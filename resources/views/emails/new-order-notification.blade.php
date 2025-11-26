<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle commande d'images</title>
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

        .alert-box {
            background-color: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 6px;
            padding: 15px 20px;
            margin: 20px 0;
        }

        .alert-box p {
            margin: 0;
            color: #92400e;
            font-weight: bold;
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

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-pro {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .badge-particulier {
            background-color: #fae8ff;
            color: #86198f;
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

        .button {
            display: inline-block;
            padding: 15px 40px;
            margin: 20px 0;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            border-radius: 6px;
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

        .notes-box {
            background-color: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 6px;
            padding: 15px 20px;
            margin: 15px 0;
        }

        .notes-box p {
            margin: 0;
            font-size: 14px;
            color: #78350f;
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

            .button {
                display: block;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Nouvelle commande d'images</h1>
        </div>

        <div class="content">
            <div class="alert-box">
                <p>Une nouvelle commande vient d'etre passee !</p>
            </div>

            <div class="order-number">
                <span>Numero de commande</span>
                <strong>{{ $orderNumber }}</strong>
            </div>

            <p>
                <strong>Type de client :</strong>
                @if($customerType === 'professionnel')
                    <span class="badge badge-pro">Professionnel</span>
                @else
                    <span class="badge badge-particulier">Particulier</span>
                @endif
            </p>

            <p>
                <strong>Langue de correspondance :</strong>
                {{ ucfirst($language) }}
            </p>

            <div class="section-title">Informations client</div>

            <div class="info-box">
                @if($company)
                <p><strong>Societe :</strong> {{ $company }}</p>
                @endif
                <p><strong>Nom :</strong> {{ $civility === 'mr' ? 'M.' : ($civility === 'mme' ? 'Mme' : '') }} {{ $fullName }}</p>
                <p><strong>Email :</strong> {{ $email }}</p>
                @if($phone)
                <p><strong>Telephone :</strong> {{ $phone }}</p>
                @endif
            </div>

            <div class="section-title">Adresse de livraison</div>

            <div class="info-box">
                <p>{{ $addressLine1 }}</p>
                @if($addressLine2)
                <p>{{ $addressLine2 }}</p>
                @endif
                <p>{{ $postalCode }} {{ $city }}</p>
                <p>{{ $country }}</p>
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

            @if($customerNotes)
            <div class="section-title">Notes du client</div>
            <div class="notes-box">
                <p>{{ $customerNotes }}</p>
            </div>
            @endif

            <div class="divider"></div>

            <div style="text-align: center;">
                <a href="{{ $adminUrl }}" class="button" style="color: #ffffff;">
                    Voir la commande
                </a>
            </div>

            <p style="font-size: 14px; color: #6b7280; text-align: center;">
                Cliquez sur le bouton ci-dessus pour acceder au panel d'administration
                et traiter cette commande.
            </p>
        </div>

        <div class="footer">
            <p>
                Cet email a ete envoye automatiquement suite a une nouvelle commande.
            </p>
        </div>
    </div>
</body>

</html>
