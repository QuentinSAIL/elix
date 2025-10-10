<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:inline-block focus:h-auto focus:w-auto focus:border focus:border-white focus:bg-zinc-900 focus:p-4">
            {{ __('Skip to main content') }}
        </a>
        <main id="main-content" class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                        <x-app-logo-icon class="size-32 fill-current text-black dark:text-white" name />
                    <span class="sr-only">{{ config('app.name', 'Elix') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </main>
        {{-- @fluxScripts --}}
    </body>
</html>
