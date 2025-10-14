# 🚀 Système de Mise à Jour Automatique des Prix

## 📋 Vue d'ensemble

Le système de mise à jour automatique des prix permet de maintenir les prix des actions et crypto-monnaies à jour sans intervention manuelle.

## ⚙️ Configuration

### 1. Scheduler Laravel
Les prix sont automatiquement mis à jour **toutes les 15 minutes** via le scheduler Laravel configuré dans `routes/console.php`.

### 2. Cache Optimisé
- **Durée du cache** : 15 minutes (réduit les appels API)
- **Nettoyage automatique** : Tous les jours à 2h00
- **Évite les doublons** : Skip les positions mises à jour récemment (< 10 min)

## 🛠️ Commandes Disponibles

### Mise à jour manuelle
```bash
# Mise à jour normale
php artisan wallets:update-prices

# Forcer la mise à jour (ignore les positions récentes)
php artisan wallets:update-prices --force

# Exécuter en arrière-plan (job queue)
php artisan wallets:update-prices --background

# Nettoyer le cache avant mise à jour
php artisan wallets:update-prices --clear-cache
```

### Vérifier le scheduler
```bash
# Lister les tâches programmées
php artisan schedule:list

# Exécuter le scheduler manuellement
php artisan schedule:run
```

## 🔄 Jobs en Arrière-plan

Le système utilise des **jobs Laravel** pour éviter de bloquer les requêtes utilisateur :

- **Timeout** : 5 minutes
- **Tentatives** : 3 maximum
- **Logs** : Tous les succès/échecs sont loggés

## 📊 Monitoring

### Logs
Les logs sont disponibles dans `storage/logs/laravel.log` :
```
[INFO] Starting background wallet price update job
[INFO] Price update job completed: 5 updated, 0 failed
[WARNING] Failed to update price for AAPL: API timeout
```

### Métriques
- ✅ **Mis à jour** : Nombre de positions mises à jour
- ⏭️ **Ignorées** : Positions récemment mises à jour
- ❌ **Échecs** : Positions qui ont échoué

## 🚀 Déploiement

### 1. Configurer le cron
Ajouter cette ligne au crontab du serveur :
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Configurer les queues
```bash
# Démarrer le worker de queue
php artisan queue:work

# Ou utiliser Supervisor pour la production
```

### 3. Variables d'environnement
```env
QUEUE_CONNECTION=database  # ou redis pour de meilleures performances
CACHE_DRIVER=redis         # recommandé pour la production
```

## 🔧 Personnalisation

### Modifier la fréquence
Dans `routes/console.php` :
```php
// Toutes les 5 minutes
Schedule::command('wallets:update-prices')->everyFiveMinutes();

// Toutes les heures
Schedule::command('wallets:update-prices')->hourly();
```

### Modifier la durée du cache
Dans `app/Services/PriceService.php` :
```php
private const CACHE_DURATION = 1800; // 30 minutes
```

## 🐛 Dépannage

### Problèmes courants

1. **Les prix ne se mettent pas à jour**
   - Vérifier que le cron est configuré
   - Vérifier les logs : `tail -f storage/logs/laravel.log`

2. **Erreurs API**
   - Les APIs gratuites ont des limites de taux
   - Le système utilise plusieurs APIs en fallback

3. **Queue qui ne fonctionne pas**
   - Vérifier `QUEUE_CONNECTION` dans `.env`
   - Démarrer le worker : `php artisan queue:work`

### Commandes de debug
```bash
# Vérifier les jobs en attente
php artisan queue:work --once

# Vider la queue
php artisan queue:clear

# Vérifier le cache
php artisan cache:clear
```

## 📈 Performance

- **Cache intelligent** : Évite les appels API répétés
- **Skip récent** : Ignore les positions mises à jour < 10 min
- **Jobs asynchrones** : N'impacte pas les performances utilisateur
- **Fallback APIs** : Utilise plusieurs sources pour la fiabilité

---

**🎯 Résultat** : Tes prix d'actions et crypto seront maintenant automatiquement mis à jour toutes les 15 minutes !
