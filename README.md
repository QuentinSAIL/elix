# Elix 🐙

## Présentation
Elix est une application web développée avec Laravel, conçue pour la booster votre productiver via des modules de gestion de routines, de finances personnelles, de prise de notes et bien plus. Elle propose une interface moderne et modulaire, avec une gestion avancée des utilisateurs et des modules personnalisables.

## Prérequis
- PHP >= 8.2
- Composer
- Node.js >= 18.x et npm
- Docker & Docker Compose (optionnel mais recommandé)

## Installation

### 1. Clonage du dépôt
```bash
git clone git@github.com:QuentinSAIL/elix.git
cd elix
```

### 2. Installation des dépendances PHP
```bash
composer install
```

### 3. Installation des dépendances front-end
```bash
npm install
```

### 4. Configuration de l'environnement
Copiez le fichier `.env.example` en `.env` puis configurez vos variables d'environnement (DB, mail, etc.) :
```bash
cp .env.example .env
```

Générez la clé d'application :
```bash
sail artisan key:generate
```

### 5. Lancer le container
```bash
sail up -d
```

### 6. Lancer les migrations et les seeders
```bash
sail artisan migrate --seed
```

### 7. Compiler les assets front-end
```bash
npm run dev
```


L'application sera accessible sur `http://localhost`.

## Lancer les tests

```bash
sail artisan test
# ou
./vendor/bin/pest
```

## Structure du projet

- `app/` : Code applicatif (contrôleurs, modèles, services, Livewire...)
- `config/` : Fichiers de configuration
- `database/` : Migrations, seeders, factories
- `public/` : Fichiers accessibles publiquement (index.php, assets)
- `resources/` : Vues Blade, assets front-end (CSS, JS, lang)
- `routes/` : Fichiers de routes (web, API, console)
- `tests/` : Tests unitaires et fonctionnels
