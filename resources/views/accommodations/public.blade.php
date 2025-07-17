<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Hébergements</title>
    <meta name="description" content="Liste des hébergements touristiques disponibles">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <style>
        :root {
            --color-primary: {{ $colors['primary_color'] }};
            --color-secondary: {{ $colors['secondary_color'] }};
            --color-accent: {{ $colors['accent_color'] }};
            --color-background: {{ $colors['background_color'] }};
            
            /* Couleurs dérivées pour un design harmonieux */
            --color-primary-light: {{ $colors['primary_color'] }}20;
            --color-primary-dark: {{ $colors['primary_color'] }}cc;
            --color-secondary-light: {{ $colors['secondary_color'] }}30;
            --color-text-primary: #1f2937;
            --color-text-secondary: #6b7280;
            --color-text-muted: #9ca3af;
            --color-border: #e5e7eb;
            --color-border-light: #f3f4f6;
            --color-success: #10b981;
            --color-warning: #f59e0b;
            --color-error: #ef4444;
            --color-info: #3b82f6;
            
            /* Ombres personnalisées */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            
            /* Transitions */
            --transition-fast: 0.15s ease-out;
            --transition-normal: 0.3s ease-out;
            
            /* Bordures */
            --border-radius: 8px;
            --border-radius-lg: 12px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: var(--color-text-primary);
            background: var(--color-background);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .container {
            max-width: 100%;
            margin: 0;
            padding: 0;
            background: var(--color-background);
        }
        
        .header {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header h1 {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .count {
            font-size: 12px;
            opacity: 0.9;
            margin-top: 6px;
            font-weight: 500;
        }
        
        .content {
            padding: 20px;
        }
        
        .accommodation-list {
            display: grid;
            gap: 12px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .accommodation-item {
            background: white;
            border: 1px solid var(--color-border);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-normal);
            overflow: hidden;
        }
        
        .accommodation-item:hover {
            box-shadow: var(--shadow-lg);
            border-color: var(--color-primary);
            transform: translateY(-2px);
        }
        
        .accommodation-content {
            padding: 16px;
        }
        
        .accommodation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            gap: 12px;
            position: relative;
        }
        
        .accommodation-title {
            flex: 1;
            min-width: 0;
            padding-right: 8px;
        }
        
        .accommodation-title h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-text-primary);
            margin: 0 0 4px 0;
            line-height: 1.3;
        }
        
        .accommodation-location {
            font-size: 12px;
            color: var(--color-text-secondary);
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 2px;
        }
        
        .accommodation-type {
            font-size: 11px;
            color: var(--color-text-muted);
            background: var(--color-border-light);
            padding: 2px 8px;
            border-radius: 4px;
            white-space: nowrap;
        }
        
        .accommodation-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: flex-start;
            flex-shrink: 0;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-badge.active {
            background: var(--color-success);
            color: white;
        }
        
        .status-badge.inactive {
            background: var(--color-text-muted);
            color: white;
        }
        
        .status-badge.pending {
            background: var(--color-warning);
            color: white;
        }
        
        .accommodation-details {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            align-items: center;
        }
        
        .accommodation-contact {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--color-text-secondary);
        }
        
        .contact-item a {
            color: var(--color-primary);
            text-decoration: none;
            transition: color var(--transition-fast);
        }
        
        .contact-item a:hover {
            color: var(--color-primary-dark);
            text-decoration: underline;
        }
        
        .accommodation-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            transition: all var(--transition-fast);
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .btn-website {
            background: var(--color-primary);
            color: white;
        }
        
        .btn-website:hover {
            background: var(--color-primary-dark);
            transform: translateY(-1px);
        }
        
        .btn-email {
            background: var(--color-info);
            color: white;
        }
        
        .btn-email:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .btn-phone {
            background: var(--color-success);
            color: white;
        }
        
        .btn-phone:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--color-text-secondary);
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            margin: 20px auto;
            max-width: 400px;
        }
        
        .empty-state .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 18px;
            color: var(--color-text-primary);
            margin-bottom: 8px;
        }
        
        .empty-state p {
            font-size: 14px;
            color: var(--color-text-secondary);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header {
                padding: 16px;
            }
            
            .header h1 {
                font-size: 18px;
            }
            
            .content {
                padding: 16px;
            }
            
            .accommodation-item {
                border-radius: 6px;
            }
            
            .accommodation-content {
                padding: 12px;
            }
            
            .accommodation-header {
                flex-direction: row;
                align-items: flex-start;
                gap: 12px;
            }
            
            .accommodation-title {
                flex: 1;
                min-width: 0;
                padding-right: 8px;
            }
            
            .accommodation-badges {
                flex-shrink: 0;
                justify-content: flex-end;
            }
            
            .accommodation-details {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .accommodation-contact {
                order: 1;
            }
            
            .accommodation-actions {
                order: 2;
                justify-content: flex-start;
                width: 100%;
            }
            
            .action-btn {
                flex: 1;
                justify-content: center;
                min-width: 80px;
            }
        }
        
        @media (max-width: 480px) {
            .accommodation-list {
                gap: 8px;
            }
            
            .accommodation-content {
                padding: 10px;
            }
            
            .accommodation-header {
                flex-direction: row;
                align-items: flex-start;
                gap: 8px;
            }
            
            .accommodation-title {
                flex: 1;
                min-width: 0;
                padding-right: 4px;
            }
            
            .accommodation-title h3 {
                font-size: 14px;
                line-height: 1.2;
            }
            
            .accommodation-badges {
                flex-shrink: 0;
                justify-content: flex-end;
            }
            
            .status-badge {
                padding: 3px 6px;
                font-size: 10px;
            }
            
            .action-btn {
                padding: 6px 8px;
                font-size: 10px;
            }
        }
        
        /* Animation pour l'apparition des éléments */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .accommodation-item {
            animation: fadeInUp 0.3s ease-out;
        }
        
        /* Optimisation pour l'impression */
        @media print {
            .header {
                position: static;
                box-shadow: none;
                background: var(--color-primary) !important;
            }
            
            .accommodation-item {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .action-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Liste des Hébergements</h1>
            <div class="count">{{ $accommodations->count() }} établissement(s) disponible(s)</div>
        </div>
        
        <div class="content">
            @if($accommodations->isEmpty())
                <div class="empty-state">
                    <div class="icon">🏨</div>
                    <h3>Aucun hébergement disponible</h3>
                    <p>Les hébergements apparaîtront ici une fois qu'ils seront activés.</p>
                </div>
            @else
                <div class="accommodation-list">
                    @foreach($accommodations as $accommodation)
                        <div class="accommodation-item">
                            <div class="accommodation-content">
                                <div class="accommodation-header">
                                    <div class="accommodation-title">
                                        <h3>{{ $accommodation->name }}</h3>
                                        <div class="accommodation-location">
                                            <span>📍</span>
                                            {{ $accommodation->city }}
                                        </div>
                                        @if($accommodation->type)
                                            <div class="accommodation-type">
                                                {{ $accommodation->type }}
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="accommodation-badges">
                                        <span class="status-badge {{ $accommodation->status }}">
                                            @switch($accommodation->status)
                                                @case('active')
                                                    ✅ Actif
                                                    @break
                                                @case('inactive')
                                                    ❌ Inactif
                                                    @break
                                                @case('pending')
                                                    ⏳ En attente
                                                    @break
                                                @default
                                                    ❓ Statut inconnu
                                            @endswitch
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="accommodation-details">
                                    <div class="accommodation-contact">
                                        @if($accommodation->email)
                                            <div class="contact-item">
                                                <span>✉️</span>
                                                <a href="mailto:{{ $accommodation->email }}">{{ $accommodation->email }}</a>
                                            </div>
                                        @endif
                                        @if($accommodation->phone)
                                            <div class="contact-item">
                                                <span>📞</span>
                                                <a href="tel:{{ $accommodation->phone }}">{{ $accommodation->phone }}</a>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="accommodation-actions">
                                        @if($accommodation->website)
                                            <a href="{{ $accommodation->website }}" target="_blank" class="action-btn btn-website">
                                                🌐 Site web
                                            </a>
                                        @endif
                                        @if($accommodation->email)
                                            <a href="mailto:{{ $accommodation->email }}" class="action-btn btn-email">
                                                ✉️ Email
                                            </a>
                                        @endif
                                        @if($accommodation->phone)
                                            <a href="tel:{{ $accommodation->phone }}" class="action-btn btn-phone">
                                                📞 Téléphone
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</body>
</html>