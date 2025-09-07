#!/bin/bash
set -e

# Attendre que la base de données soit prête
echo "Attente de la base de données..."
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
    echo "Base de données non disponible - attente..."
    sleep 2
done

echo "Base de données disponible - exécution des migrations..."

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "Migrations terminées - démarrage de l'application..."

# Exécuter la commande passée en argument
exec "$@"

