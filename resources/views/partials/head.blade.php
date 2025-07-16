<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

@auth
    @if(auth()->user()->colorSettings)
        <style>
            :root {
                --color-primary: {{ auth()->user()->colorSettings->primary_color }};
                --color-secondary: {{ auth()->user()->colorSettings->secondary_color }};
                --color-accent: {{ auth()->user()->colorSettings->accent_color }};
                --color-background: {{ auth()->user()->colorSettings->background_color }};
            }
            .dark {
                --color-primary-dark: {{ auth()->user()->colorSettings->primary_color }}CC;
                --color-secondary-dark: {{ auth()->user()->colorSettings->secondary_color }}CC;
                --color-accent-dark: #2A2A2A;
                --color-background-dark: #1A1A1A;
            }
        </style>
    @else
        <style>
            :root {
                --color-primary: #3A9C92;
                --color-secondary: #7AB6A8;
                --color-accent: #FFFDF4;
                --color-background: #FAF7F3;
            }
            .dark {
                --color-primary-dark: #4DAAA0;
                --color-secondary-dark: #8CC4B8;
                --color-accent-dark: #2A2A2A;
                --color-background-dark: #1A1A1A;
            }
        </style>
    @endif
@else
    <style>
        :root {
            --color-primary: #3A9C92;
            --color-secondary: #7AB6A8;
            --color-accent: #FFFDF4;
            --color-background: #FAF7F3;
        }
        .dark {
            --color-primary-dark: #4DAAA0;
            --color-secondary-dark: #8CC4B8;
            --color-accent-dark: #2A2A2A;
            --color-background-dark: #1A1A1A;
        }
    </style>
@endauth
