# Elix 🐙
> Application web modulaire (Laravel + Livewire + Tailwind) pour booster la productivité : routines, finances personnelles (budget/catégorisation), prise de notes Markdown, etc.

- elix-app.com
- preprod.elix-app.com

## Sommaire

- [Elix 🐙](#elix-)
  - [Sommaire](#sommaire)
  - [Caractéristiques](#caractéristiques)
  - [Stack technique](#stack-technique)
  - [Prérequis](#prérequis)
  - [Démarrage rapide (Docker Sail)](#démarrage-rapide-docker-sail)
  - [Configuration environnement](#configuration-environnement)
  - [Scripts utiles](#scripts-utiles)
  - [Tests \& Qualité](#tests--qualité)
  - [CI/CD \& Déploiement](#cicd--déploiement)
  - [Dépannage](#dépannage)



## Caractéristiques

- 🧩 **Modules** : Routines • Money (transactions / catégories / dashboards) • Notes (Markdown).
  *(Prioritaires — Roadmap : TODO, Chat IA personnel, météo, boîte à idées communautaire.)*
- 👤 **Gestion utilisateurs** : profils, préférences, policies/guards Laravel.
- ⚡ **UI** : Tailwind + composants Livewire, interactions réactives.
- ☁️ **Infra** : Docker, reverse-proxy Traefik, stockage S3-compatible.
- 🛡️ **Qualité** : Pint (PSR-12), PHPStan, tests Pest, pipeline CI.


## Stack technique

- **Backend** : Laravel 12, PHP ≥ 8.2
- **Frontend** : Blade + Livewire, Vite, Tailwind CSS
- **DB** : PostgreSQL (local via Sail)
- **Conteneurisation** : Docker + Laravel Sail
- **CI/CD** : GitHub Actions (`preprod.yml`, `prod.yml`)
- **Divers** : S3-compatible pour les médias, Uptime Kuma (monitoring)



## Prérequis

- PHP ≥ 8.2, **Composer**
- Node.js ≥ 18 + **npm**
- **Docker** & **Docker Compose** (recommandé)
- Git



## Démarrage rapide (Docker Sail)

> *Si vous n’avez pas l’alias `sail`, utilisez `./vendor/bin/sail`.*

```bash
# 1) Cloner
git clone git@github.com:QuentinSAIL/elix.git
cd elix

# 2) Dépendances
composer install
npm install

# 3) Environnement
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate

# 4) Base & jeux de données
./vendor/bin/sail artisan migrate:fresh --seed

# 5) Assets
npm run dev

```

L’application est accessible sur **[http://localhost](http://localhost)**.



## Configuration environnement

-   Copier le fichier **`.env.example`** en **`.env`** puis ajuster les valeurs locales (BDD, mail, URL…).

-   Pour les **clés sensibles** (ex. intégrations externes), contacter un·e développeur·euse Elix.


> Exemples de variables utiles : `APP_URL`, `DB_*`, `FILESYSTEM_DISK`, `AWS_*`, `MAIL_*`.



## Scripts utiles

```bash
# Lancer / arrêter les services
sail up -d
sail down

# Cache / optimisation
sail artisan optimize
sail artisan config:cache && sail artisan route:cache && sail artisan view:cache

# Migrations / seed
sail artisan migrate --seed
sail artisan migrate:fresh --seed

# Build de production des assets
npm run build

```


## Tests & Qualité

```bash
# Tests (PHPUnit/Pest)
sail artisan test (--coverage)

# Lint (PSR-12)
./vendor/bin/pint

# Analyse statique
./vendor/bin/phpstan analyse --memory-limit=4G

```

## CI/CD & Déploiement

-   **Pré-production** : workflow `preprod.yml`
    Lint (Pint), PHPStan, tests Pest, build Vite, publication d’image, déploiement preprod.

-   **Production** : workflow `prod.yml`
    Déclenché selon la configuration (branche/tag/release). Build & déploiement en production.


**Badges d’état**

[![CI + Preprod Deploy](https://github.com/QuentinSAIL/elix/actions/workflows/preprod.yml/badge.svg)](https://github.com/QuentinSAIL/elix/actions/workflows/preprod.yml)
[![Release → Prod Deploy](https://github.com/QuentinSAIL/elix/actions/workflows/prod.yml/badge.svg?branch=main)](https://github.com/QuentinSAIL/elix/actions/workflows/prod.yml)



## Dépannage

-   **Port déjà utilisé** : `sail down -v && sail up -d`

-   **Cache incohérent** : `sail artisan optimize:clear`

-   **Migrations** : `sail artisan migrate:fresh --seed`

-   **Assets qui ne se rafraîchissent pas** : `npm run dev` (ou `npm run build` en prod)
