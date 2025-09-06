# Script de déploiement avec migration automatique
param(
    [string]$Environment = "prod"
)

Write-Host "🚀 Déploiement avec migration automatique..." -ForegroundColor Green

# Variables
$DockerImage = "inesbrm1/uniconnect-backend"

if ($Environment -eq "preprod") {
    $DockerTag = "preprod"
    $ComposeService = "backend-preprod"
} else {
    $DockerTag = "latest"
    $ComposeService = "backend"
}

Write-Host "📦 Construction de l'image Docker..." -ForegroundColor Yellow
docker build -t "$DockerImage`:$DockerTag" .

Write-Host "🐳 Démarrage de la base de données..." -ForegroundColor Yellow
docker compose up -d db

Write-Host "⏳ Attente que la base de données soit prête..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

Write-Host "🔄 Exécution des migrations..." -ForegroundColor Yellow
# Exécuter les migrations dans un conteneur temporaire
docker run --rm `
    --network uniconnect_app-network `
    -e DATABASE_URL="mysql://symfony:Azertyines..@db:3306/uniconnect" `
    -e APP_ENV=prod `
    "$DockerImage`:$DockerTag" `
    php bin/console doctrine:migrations:migrate --no-interaction --env=prod

Write-Host "🚀 Démarrage de l'application..." -ForegroundColor Yellow
docker compose up -d --no-deps --build $ComposeService

Write-Host "✅ Déploiement terminé avec succès!" -ForegroundColor Green
Write-Host "📊 Vérification du statut des conteneurs:" -ForegroundColor Cyan
docker compose ps
