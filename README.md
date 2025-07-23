# Backend Setup - UniConnect 

Ce guide explique comment configurer, exécuter et tester le backend de l'application Uniconnect, en local avec ou sans Docker.

# Sommaire 
1. Prérequis
2. Installation locale (XAMPP)
3. Exécution des tests
4. Déploiement avec Docker
5. Démarrage du VPS

## Prérequis

- **Php (8.2)** : Assurez-vous que Php est installé sur votre machine.

- **Composer** : Assurez-vous que Composer est installé sur votre machine.

- **Docker & Docker Compose** : Assurez-vous que Docker et Docker Compose sont installés sur votre machine.
  
  - Si vous n'avez pas encore installé Docker, vous pouvez le faire en suivant les instructions officielles :  
    [Installation Docker](https://docs.docker.com/get-docker/)

  - Pour installer Docker Compose, suivez les instructions :  
    [Installation Docker Compose](https://docs.docker.com/compose/install/)

- **Xampp** : Assurez-vous que Xampp est installé sur votre machine. (lancement en local)
 - Si vous n'avez pas encore installé Xampp, vous pourvez le faire en suivant les instructions officielles : 
    [Installation Xampp](https://www.apachefriends.org/fr/index.html)

## Installation locale (XAMPP)

1. **Cloner le dépôt**
```bash
    git clone https://github.com/UniConnectProjet/Back.git
    cd Back
```

2. **Installer les dépendances PHP**
```bash 
    composer i
```

3. **Configuration de la base de données locale**

    Lancer **XAMPP**, démarrer **Apache** et **MySQL**.

**a. Supprimer l'ancienne BDD (optionnel)**
```bash
    php bin/console doctrine:database:drop --force
```

**b. Créer la bdd**
```bash 
    php bin/console doctrine:database:create
```
Si ça échoue, créer la base manuellement via phpmyadmin

**c. Créer & exécuter les migrations**
```bash
    php bin/console make:migration
    php bin/console doctrine:migrations:migrate
```

**d. Charger les fixtures**
```bash
    php bin/console app:fixtures:load --mode=light
```

**e. Lancer le serveur local**
```bash
    php -S 127.0.0.1:8000 -t public
```

## Exécution des tests

### Prérequis
Avant de lancer les tests, assurez-vous que :
- La base de données test est correctement configurée (dans ```.env.test```)
- Les migrations ont été bien exécutées : 
```bash
    php bin/console doctrine:migrations:migrate --env=test
```

### Lancer tous les tests
```bash
    php bin/phpunit --testdox
```
Cela exécute tous les tests présents dans le répertoire ```tests/``` avec un affichage lisible.

### Structure des tests
- Les tests sont dans le dossier ``tests/``
- Les tests d'entités valident les getters/setters et les relations
- Les tests fonctionnels utilisent ``WebTestCase`` pour simuler des requêtes HTTP

### Base de données de test et fixtures
Avant chaque test fonctionnel :
- La base est vidée (``ORMPurger``)
- Un utilisateur est injecté via des fixtures personnalisées (``TestUserFixtures``)
- L'utilisateur de test à l'email ``test@example.com`` et le mot de passe ``test``

### Authentification JWT dans les tests
Une grande partie des tests utilisent une méthode ``createAuthentificatedClient`` qui :
- Envoie une requête ``POST /api/login_check`` avec les identifiants
- Récupère le token JWT de la réponse 
- L'utilise pour authentifier les requêtes via l'en-tête ``Authorization: Bearer <token>``