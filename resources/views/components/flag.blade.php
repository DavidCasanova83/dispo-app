{{--
    Drapeau d'une langue des brochures, en SVG (les émojis drapeaux ne
    s'affichent pas sous Windows) : <x-flag code="en" class="w-5 h-3.5" />.

    fr = France, en = Royaume-Uni, it = Italie. Format 3:2 ; pas d'identifiant
    SVG (clipPath…), pour pouvoir répéter le drapeau sur chaque carte.
    Décoratif : le bouton qui le contient porte le nom de la langue.
--}}
@props(['code'])

<svg viewBox="0 0 60 40" preserveAspectRatio="none" aria-hidden="true" focusable="false"
    {{ $attributes->merge(['class' => 'inline-block shrink-0 rounded-sm']) }}>
    @switch($code)
        @case('fr')
            <rect width="20" height="40" fill="#0055A4"/>
            <rect x="20" width="20" height="40" fill="#FFFFFF"/>
            <rect x="40" width="20" height="40" fill="#EF4135"/>
            @break
        @case('it')
            <rect width="20" height="40" fill="#009246"/>
            <rect x="20" width="20" height="40" fill="#FFFFFF"/>
            <rect x="40" width="20" height="40" fill="#CE2B37"/>
            @break
        @case('en')
            <rect width="60" height="40" fill="#012169"/>
            <path d="M0,0 L60,40 M60,0 L0,40" stroke="#FFFFFF" stroke-width="8"/>
            <path d="M0,0 L60,40 M60,0 L0,40" stroke="#C8102E" stroke-width="3"/>
            <path d="M30,0 V40 M0,20 H60" stroke="#FFFFFF" stroke-width="12"/>
            <path d="M30,0 V40 M0,20 H60" stroke="#C8102E" stroke-width="7"/>
            @break
    @endswitch
    {{-- Liseré : le blanc des drapeaux reste visible sur fond blanc --}}
    <rect x="0.5" y="0.5" width="59" height="39" fill="none" stroke="#000000" stroke-opacity="0.15"/>
</svg>
