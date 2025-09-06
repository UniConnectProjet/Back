#!/bin/bash
set -e

echo "🚀 Déploiement avec migration automatique..."

# Variables
ENVIRONMENT=${1:-prod}
DOCKER_IMAGE="inesbrm1/uniconnect-backend"

if [ "$ENVIRONMENT" = "preprod" ]; then
    DOCKER_TAG="preprod"
    COMPOSE_SERVICE="backend-preprod"
else
    DOCKER_TAG="latest"
    COMPOSE_SERVICE="backend"
fi

echo "Construction de l'image Docker..."
docker build -t $DOCKER_IMAGE:$DOCKER_TAG .

echo "Démarrage de la base de données..."
docker compose up -d db

echo "Attente que la base de données soit prête..."
sleep 10

echo "Exécution des migrations..."
# Exécuter les migrations dans un conteneur temporaire
docker run --rm \
    --network uniconnect_app-network \
    -e DATABASE_URL="mysql://symfony:Azertyines..@db:3306/uniconnect" \
    -e APP_ENV=prod \
    $DOCKER_IMAGE:$DOCKER_TAG \
    php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "Démarrage de l'application..."
docker compose up -d --no-deps --build $COMPOSE_SERVICE

echo "Déploiement terminé avec succès!"
echo "Vérification du statut des conteneurs:"
docker compose ps
