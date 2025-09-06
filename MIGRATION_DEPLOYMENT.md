# Migration automatique lors du déploiement

Ce document explique comment les migrations de base de données sont automatiquement exécutées lors du déploiement.

## 🚀 Solutions implémentées

### 1. Script d'entrée Docker (`docker-entrypoint.sh`)

Le script `docker-entrypoint.sh` est exécuté automatiquement au démarrage de chaque conteneur backend :

- ✅ Attend que la base de données soit disponible
- ✅ Exécute les migrations automatiquement
- ✅ Démarre l'application après les migrations

### 2. Workflow GitHub Actions mis à jour

Le workflow CI/CD a été modifié pour :

- ✅ Démarrer la base de données en premier
- ✅ Attendre que la DB soit prête
- ✅ Démarrer le backend (qui exécute automatiquement les migrations)

### 3. Scripts de déploiement local

#### Pour Linux/Mac :
```bash
# Déploiement en production
./deploy.sh

# Déploiement en préproduction
./deploy.sh preprod
```

#### Pour Windows :
```powershell
# Déploiement en production
.\deploy.ps1

# Déploiement en préproduction
.\deploy.ps1 -Environment preprod
```

## 🔧 Configuration requise

### Variables d'environnement

Assurez-vous que ces variables sont configurées :

```env
DATABASE_URL=mysql://symfony:Azertyines..@db:3306/uniconnect
APP_ENV=prod
```

### Docker Compose

Le service `db` doit être démarré avant le service `backend` :

```yaml
services:
  db:
    # ... configuration de la base de données
  
  backend:
    depends_on:
      - db
    # ... configuration du backend
```

## 🛠️ Commandes de migration manuelles

Si vous devez exécuter les migrations manuellement :

```bash
# Dans le conteneur
docker exec -it uniconnect-backend php bin/console doctrine:migrations:migrate --no-interaction

# En local
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

## 🔍 Vérification

Pour vérifier que les migrations ont été appliquées :

```bash
# Voir le statut des migrations
php bin/console doctrine:migrations:status

# Voir l'historique des migrations
php bin/console doctrine:migrations:list
```

## ⚠️ Notes importantes

1. **Sauvegarde** : Toujours sauvegarder la base de données avant un déploiement en production
2. **Tests** : Tester les migrations en préproduction avant la production
3. **Rollback** : Avoir un plan de rollback en cas de problème
4. **Monitoring** : Surveiller les logs lors du déploiement

## 🐛 Dépannage

### Problème : Base de données non disponible
```bash
# Vérifier que la DB est démarrée
docker compose ps db

# Vérifier les logs
docker compose logs db
```

### Problème : Migration échoue
```bash
# Voir les logs détaillés
docker compose logs backend

# Exécuter manuellement
docker exec -it uniconnect-backend php bin/console doctrine:migrations:migrate --no-interaction -v
```
