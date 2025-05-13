@props(['name' => false])

<div {{ $attributes->merge(['class' => 'app-logo-icon']) }}>
    <img class="dark:hidden" src="{{ asset('img/elix-logo-light'  . ($name ? '-name' : '') . '.png') }}" alt="Elix Logo">
    <img class="hidden dark:block" src="{{ asset('img/elix-logo-dark' . ($name ? '-name' : '') . '.png') }}" alt="Elix Logo">
</div>
