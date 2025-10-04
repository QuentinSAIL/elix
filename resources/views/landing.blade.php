<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Elix - Gestion financière intelligente</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">
    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 bg-white/80 dark:bg-zinc-800/80 backdrop-blur-md border-b border-zinc-200 dark:border-zinc-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-2">
                <x-app-logo />
                </div>
                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-zinc-600 dark:text-zinc-300 hover:text-primary-500 dark:hover:text-primarydark-500 transition-colors">Fonctionnalités</a>
                    <a href="#modules" class="text-zinc-600 dark:text-zinc-300 hover:text-primary-500 dark:hover:text-primarydark-500 transition-colors">Modules</a>
                    <a href="#pricing" class="text-zinc-600 dark:text-zinc-300 hover:text-primary-500 dark:hover:text-primarydark-500 transition-colors">Tarifs</a>
                </div>

                <!-- CTA Button -->
                <div class="flex items-center space-x-4">
                    <!-- Dark/Light Mode Toggle -->
                    <button id="theme-toggle" class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" aria-label="Toggle theme">
                        <!-- Sun icon (visible in dark mode) -->
                        <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5 text-zinc-300" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path>
                        </svg>
                        <!-- Moon icon (visible in light mode) -->
                        <svg id="theme-toggle-light-icon" class="hidden w-5 h-5 text-zinc-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                    </button>

                    <a href="{{ route('login') }}" class="text-zinc-600 dark:text-zinc-300 hover:text-primary-500 dark:hover:text-primarydark-500 transition-colors">Connexion</a>
                    <a href="{{ route('register') }}" class="bg-primary-500 dark:bg-primarydark-500 text-white px-4 py-2 rounded-lg hover:bg-primary-600 dark:hover:bg-primarydark-600 transition-colors">
                        Commencer
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-20 pb-16 bg-gradient-to-br from-primary-50 to-white dark:from-zinc-900 dark:to-zinc-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-parkinsans font-bold text-primary-900 dark:text-primarydark-500 mb-6">
                    Maîtrisez vos finances avec <span class="text-primary-500 dark:text-primarydark-500">Elix</span>
                </h1>
                <p class="text-xl text-zinc-600 dark:text-zinc-300 mb-8 max-w-3xl mx-auto">
                    La plateforme modulaire et communautaire pour gérer vos finances, organiser vos routines et prendre des notes.
                    Activez uniquement les modules dont vous avez besoin et participez à l'évolution de la plateforme.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('register') }}" class="bg-primary-500 dark:bg-primarydark-500 text-white px-8 py-4 rounded-xl text-lg font-semibold hover:bg-primary-600 dark:hover:bg-primarydark-600 transition-all transform hover:scale-105">
                        Commencer gratuitement
                    </a>
                    <a href="#features" class="border-2 border-primary-500 dark:border-primarydark-500 text-primary-500 dark:text-primarydark-500 px-8 py-4 rounded-xl text-lg font-semibold hover:bg-primary-50 dark:hover:bg-zinc-700 transition-all">
                        Découvrir les fonctionnalités
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Money Module Highlight -->
    <section id="features" class="py-20 bg-white dark:bg-zinc-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-parkinsans font-bold text-primary-900 dark:text-primarydark-500 mb-4">
                    Module Money - Votre centre de contrôle financier
                </h2>
                <p class="text-xl text-zinc-600 dark:text-zinc-300 max-w-3xl mx-auto">
                    Connectez vos comptes bancaires, analysez vos dépenses et prenez des décisions éclairées avec des tableaux de bord personnalisables.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Bank Accounts -->
                <div class="bg-custom-accent p-8 rounded-2xl border border-zinc-200 dark:border-zinc-700 hover:shadow-lg transition-all">
                    <div class="w-12 h-12 bg-primary-100 dark:bg-zinc-700 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-primary-500 dark:text-primarydark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Comptes Bancaires</h3>
                    <p class="text-zinc-600 dark:text-zinc-300 mb-4">
                        Connectez vos comptes bancaires via GoCardless pour un suivi automatique et sécurisé de vos transactions.
                    </p>
                    <ul class="text-sm text-zinc-500 dark:text-zinc-400 space-y-2">
                        <li>• Synchronisation automatique</li>
                        <li>• Support multi-banques</li>
                        <li>• Sécurité bancaire</li>
                    </ul>
                </div>

                <!-- Transactions -->
                <div class="bg-custom-accent p-8 rounded-2xl border border-zinc-200 dark:border-zinc-700 hover:shadow-lg transition-all">
                    <div class="w-12 h-12 bg-primary-100 dark:bg-zinc-700 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-primary-500 dark:text-primarydark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Transactions Intelligentes</h3>
                    <p class="text-zinc-600 dark:text-zinc-300 mb-4">
                        Catégorisez automatiquement vos transactions et analysez vos habitudes de dépenses avec des filtres avancés.
                    </p>
                    <ul class="text-sm text-zinc-500 dark:text-zinc-400 space-y-2">
                        <li>• Catégorisation automatique</li>
                        <li>• Recherche avancée</li>
                        <li>• Filtres par période</li>
                    </ul>
                </div>

                <!-- Dashboard -->
                <div class="bg-custom-accent p-8 rounded-2xl border border-zinc-200 dark:border-zinc-700 hover:shadow-lg transition-all">
                    <div class="w-12 h-12 bg-primary-100 dark:bg-zinc-700 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-primary-500 dark:text-primarydark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Tableaux de Bord</h3>
                    <p class="text-zinc-600 dark:text-zinc-300 mb-4">
                        Visualisez vos finances avec des graphiques personnalisables et des métriques clés pour prendre les bonnes décisions.
                    </p>
                    <ul class="text-sm text-zinc-500 dark:text-zinc-400 space-y-2">
                        <li>• Graphiques interactifs</li>
                        <li>• Métriques personnalisées</li>
                        <li>• Analyses temporelles</li>
                    </ul>
                </div>

                <!-- Budget -->
                <div class="bg-custom-accent p-8 rounded-2xl border border-zinc-200 dark:border-zinc-700 hover:shadow-lg transition-all">
                    <div class="w-12 h-12 bg-primary-100 dark:bg-zinc-700 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-primary-500 dark:text-primarydark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Gestion de Budget</h3>
                    <p class="text-zinc-600 dark:text-zinc-300 mb-4">
                        Définissez des budgets par catégorie et suivez vos dépenses pour rester maître de vos finances.
                    </p>
                    <ul class="text-sm text-zinc-500 dark:text-zinc-400 space-y-2">
                        <li>• Budgets par catégorie</li>
                        <li>• Alertes de dépassement</li>
                        <li>• Suivi mensuel</li>
                    </ul>
                </div>

                <!-- Wallets -->
                <div class="bg-custom-accent p-8 rounded-2xl border border-zinc-200 dark:border-zinc-700 hover:shadow-lg transition-all">
                    <div class="w-12 h-12 bg-primary-100 dark:bg-zinc-700 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-primary-500 dark:text-primarydark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Portefeuilles</h3>
                    <p class="text-zinc-600 dark:text-zinc-300 mb-4">
                        Gérez vos investissements et portefeuilles avec un suivi en temps réel des performances.
                    </p>
                    <ul class="text-sm text-zinc-500 dark:text-zinc-400 space-y-2">
                        <li>• Suivi des positions</li>
                        <li>• Calcul des performances</li>
                        <li>• Multi-devises</li>
                    </ul>
                </div>

                <!-- Categories -->
                <div class="bg-custom-accent p-8 rounded-2xl border border-zinc-200 dark:border-zinc-700 hover:shadow-lg transition-all">
                    <div class="w-12 h-12 bg-primary-100 dark:bg-zinc-700 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-primary-500 dark:text-primarydark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Catégories</h3>
                    <p class="text-zinc-600 dark:text-zinc-300 mb-4">
                        Organisez vos transactions avec des catégories personnalisables et des règles de correspondance automatique.
                    </p>
                    <ul class="text-sm text-zinc-500 dark:text-zinc-400 space-y-2">
                        <li>• Catégories personnalisées</li>
                        <li>• Règles automatiques</li>
                        <li>• Couleurs et icônes</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Modularity & Community -->
    <section id="modules" class="py-20 bg-zinc-50 dark:bg-zinc-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-parkinsans font-bold text-primary-900 dark:text-primarydark-500 mb-4">
                    Plateforme modulaire et communautaire
                </h2>
                <p class="text-xl text-zinc-600 dark:text-zinc-300 max-w-3xl mx-auto">
                    Activez uniquement les modules dont vous avez besoin et participez à l'évolution de la plateforme avec la communauté.
                </p>
            </div>

            <!-- Modularity Section -->
            <div class="mb-20">
                <div class="text-center mb-12">
                    <h3 class="text-3xl font-semibold text-primary-900 dark:text-primarydark-500 mb-4">
                        🧩 Architecture modulaire
                    </h3>
                    <p class="text-lg text-zinc-600 dark:text-zinc-300 max-w-2xl mx-auto">
                        Chaque fonctionnalité est un module indépendant que vous pouvez activer ou désactiver selon vos besoins.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-8 mb-12">
                    <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-zinc-200 dark:border-zinc-700 text-center">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Activation simple</h4>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Activez/désactivez les modules en un clic depuis vos paramètres</p>
                    </div>

                    <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-zinc-200 dark:border-zinc-700 text-center">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Performance optimisée</h4>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Seuls les modules activés consomment des ressources</p>
                    </div>

                    <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-zinc-200 dark:border-zinc-700 text-center">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <h4 class="font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Interface épurée</h4>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Navigation simplifiée avec uniquement vos modules actifs</p>
                    </div>
                </div>
            </div>

            <!-- Community Section -->
            <div class="mb-16">
                <div class="text-center mb-12">
                    <h3 class="text-3xl font-semibold text-primary-900 dark:text-primarydark-500 mb-4">
                        👥 Communauté active
                    </h3>
                    <p class="text-lg text-zinc-600 dark:text-zinc-300 max-w-2xl mx-auto">
                        Participez à l'évolution d'Elix en votant pour les nouveaux modules et en proposant des améliorations.
                    </p>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    <div class="bg-white dark:bg-zinc-800 p-8 rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-xl flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h4 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Système de vote</h4>
                        </div>
                        <p class="text-zinc-600 dark:text-zinc-300 mb-4">
                            Votez pour les nouveaux modules que vous souhaitez voir ajoutés à la plateforme.
                            Les propositions les plus populaires sont développées en priorité.
                        </p>
                        <ul class="text-sm text-zinc-500 dark:text-zinc-400 space-y-2">
                            <li>• Propositions de nouveaux modules</li>
                            <li>• Améliorations des fonctionnalités existantes</li>
                            <li>• Intégrations avec des services tiers</li>
                            <li>• Thèmes et personnalisations</li>
                        </ul>
                    </div>

                    <div class="bg-white dark:bg-zinc-800 p-8 rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-xl flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                                </svg>
                            </div>
                            <h4 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Feedback communautaire</h4>
                        </div>
                        <p class="text-zinc-600 dark:text-zinc-300 mb-4">
                            Partagez vos idées, signalez des bugs et participez aux discussions pour améliorer
                            continuellement l'expérience utilisateur.
                        </p>
                        <ul class="text-sm text-zinc-500 dark:text-zinc-400 space-y-2">
                            <li>• Forum communautaire actif</li>
                            <li>• Système de tickets pour les bugs</li>
                            <li>• Roadmap publique et transparente</li>
                            <li>• Bêta-tests des nouvelles fonctionnalités</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Available Modules -->
            <div class="mb-16">
                <div class="text-center mb-12">
                    <h3 class="text-3xl font-semibold text-primary-900 dark:text-primarydark-500 mb-4">
                        📦 Modules disponibles
                    </h3>
                    <p class="text-lg text-zinc-600 dark:text-zinc-300 max-w-2xl mx-auto">
                        Découvrez les modules actuellement disponibles et ceux en cours de développement par la communauté.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Money Module -->
                    <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-primary-100 dark:bg-zinc-700 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-primary-500 dark:text-primarydark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">Money</h4>
                            </div>
                            <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs rounded-full">Disponible</span>
                        </div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300 mb-3">
                            Gestion complète de vos finances avec comptes bancaires, budgets et investissements.
                        </p>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="font-medium">6 sous-modules :</span> Comptes, Transactions, Budget, Portefeuilles, Catégories, Dashboard
                        </div>
                    </div>

                    <!-- Routine Module -->
                    <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-primary-100 dark:bg-zinc-700 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-primary-500 dark:text-primarydark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">Routine</h4>
                            </div>
                            <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs rounded-full">Disponible</span>
                        </div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300 mb-3">
                            Créez des routines personnalisées avec des tâches chronométrées pour optimiser votre productivité.
                        </p>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="font-medium">Fonctionnalités :</span> Fréquences, Minuteries, Suivi de progression
                        </div>
                    </div>

                    <!-- Notes Module -->
                    <div class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-primary-100 dark:bg-zinc-700 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-primary-500 dark:text-primarydark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">Notes</h4>
                            </div>
                            <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs rounded-full">Disponible</span>
                        </div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300 mb-3">
                            Prenez des notes rapidement avec une interface Markdown intuitive et une sauvegarde automatique.
                        </p>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="font-medium">Fonctionnalités :</span> Markdown, Sauvegarde auto, Interface épurée
                        </div>
                    </div>
                </div>

                <!-- Upcoming Modules -->
                <div class="mt-12">
                    <h4 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100 mb-6 text-center">
                        🚀 Modules en développement (votés par la communauté)
                    </h4>
                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 text-center">
                            <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <h5 class="font-medium text-zinc-900 dark:text-zinc-100 mb-1">Tasks</h5>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Gestion de tâches</p>
                            <div class="flex items-center justify-center text-xs text-zinc-500 dark:text-zinc-400">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                <span>847 votes</span>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 text-center">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h5 class="font-medium text-zinc-900 dark:text-zinc-100 mb-1">Calendar</h5>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Calendrier intégré</p>
                            <div class="flex items-center justify-center text-xs text-zinc-500 dark:text-zinc-400">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                <span>623 votes</span>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 text-center">
                            <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <h5 class="font-medium text-zinc-900 dark:text-zinc-100 mb-1">Team</h5>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Collaboration</p>
                            <div class="flex items-center justify-center text-xs text-zinc-500 dark:text-zinc-400">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                <span>456 votes</span>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 text-center">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h5 class="font-medium text-zinc-900 dark:text-zinc-100 mb-1">Analytics</h5>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Analyses avancées</p>
                            <div class="flex items-center justify-center text-xs text-zinc-500 dark:text-zinc-400">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                <span>389 votes</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section class="py-20 bg-white dark:bg-zinc-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-parkinsans font-bold text-primary-900 dark:text-primarydark-500 mb-4">
                    Pourquoi choisir Elix ?
                </h2>
                <p class="text-xl text-zinc-600 dark:text-zinc-300 max-w-3xl mx-auto">
                    Une plateforme moderne qui combine simplicité et puissance pour transformer votre gestion financière.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-100 dark:bg-zinc-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-primary-500 dark:text-primarydark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Rapide</h3>
                    <p class="text-zinc-600 dark:text-zinc-300">Interface optimisée pour une navigation fluide</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-100 dark:bg-zinc-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-primary-500 dark:text-primarydark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Sécurisé</h3>
                    <p class="text-zinc-600 dark:text-zinc-300">Protection bancaire de niveau professionnel</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-100 dark:bg-zinc-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-primary-500 dark:text-primarydark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Intuitif</h3>
                    <p class="text-zinc-600 dark:text-zinc-300">Design pensé pour l'expérience utilisateur</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-100 dark:bg-zinc-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-primary-500 dark:text-primarydark-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Communautaire</h3>
                    <p class="text-zinc-600 dark:text-zinc-300">Développement guidé par les votes et retours utilisateurs</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-primary-500 to-primary-600 dark:from-primarydark-500 dark:to-primarydark-600">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-parkinsans font-bold text-white mb-6">
                Prêt à transformer votre gestion financière ?
            </h2>
            <p class="text-xl text-white/90 mb-8">
                Rejoignez une communauté active qui façonne l'avenir de la gestion financière personnelle.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="bg-white text-primary-500 px-8 py-4 rounded-xl text-lg font-semibold hover:bg-zinc-50 transition-all transform hover:scale-105">
                    Créer un compte gratuit
                </a>
                <a href="{{ route('login') }}" class="border-2 border-white text-white px-8 py-4 rounded-xl text-lg font-semibold hover:bg-white/10 transition-all">
                    Se connecter
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-zinc-900 dark:bg-zinc-950 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div class="md:col-span-2">
                    <div class="flex items-center space-x-2">
                        <x-app-logo />
                        </div>
                    <p class="text-zinc-400 mb-4">
                        La plateforme modulaire et communautaire pour maîtriser tous les aspects de votre vie quotidienne.
                    </p>
                </div>

                <div>
                    <h3 class="text-white font-semibold mb-4">Produit</h3>
                    <ul class="space-y-2 text-zinc-400">
                        <li><a href="#features" class="hover:text-white transition-colors">Fonctionnalités</a></li>
                        <li><a href="#modules" class="hover:text-white transition-colors">Modules</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Tarifs</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-white font-semibold mb-4">Communauté</h3>
                    <ul class="space-y-2 text-zinc-400">
                        <li><a href="#" class="hover:text-white transition-colors">Forum</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Voter pour les modules</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Roadmap</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-zinc-800 mt-8 pt-8 text-center text-zinc-400">
                <p>&copy; {{ date('Y') }} Elix. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    @fluxScripts
    @livewireScripts

    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('theme-toggle');
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        // Check for saved theme preference or default to 'dark'
        const currentTheme = localStorage.getItem('theme') || 'dark';

        // Apply the current theme
        if (currentTheme === 'dark') {
            document.documentElement.classList.add('dark');
            themeToggleDarkIcon.classList.remove('hidden');
            themeToggleLightIcon.classList.add('hidden');
        } else {
            document.documentElement.classList.remove('dark');
            themeToggleDarkIcon.classList.add('hidden');
            themeToggleLightIcon.classList.remove('hidden');
        }

        // Toggle theme on button click
        themeToggle.addEventListener('click', function() {
            // Toggle the dark class
            document.documentElement.classList.toggle('dark');

            // Update icon visibility
            if (document.documentElement.classList.contains('dark')) {
                themeToggleDarkIcon.classList.remove('hidden');
                themeToggleLightIcon.classList.add('hidden');
                localStorage.setItem('theme', 'dark');
            } else {
                themeToggleDarkIcon.classList.add('hidden');
                themeToggleLightIcon.classList.remove('hidden');
                localStorage.setItem('theme', 'light');
            }
        });
    </script>
</body>
</html>
