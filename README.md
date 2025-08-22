# Backend Setup
[Voir le changelog du projet](CHANGELOG.md)


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

## Démarrer l'environnement avec Docker

1. **Configurer la base de données pour l'environnement Docker**

   Dans le répertoire racine du projet, il y a un fichier .env.docker qui contient les paramètres de la base de données. Assurez-vous que le fichier .env.docker est configuré avec les bonnes valeurs pour DATABASE_URL.

Exemple de configuration :
   ```bash
        DATABASE_URL="mysql://symfony:symfony@db:3306/symfony"
   ```
   - symfony : L'utilisateur.
   - symfony : Le mot de passe.
   - db : Le nom du service Mysql dans le fichier docker-compose.yml.
   - 3306 : Le port d'écoute de MySQL.
   - symfony : Le nom de la base de données à utiliser.

2. **Démarrer les conteneurs Docker**

    Pour démarrer tous les services (backend et base de données), utilisez la commande suivante :
    ```bash
        cd ..
        git clone https://github.com/UniConnectProjet/DockerDeployment.git
        cd DockerDeployment/
        docker-compose up -d
    ``` 
    Cette commande lance tous les services définis dans docker-compose.yml (backend et base de données PostgreSQL). Les services démarrent en arrière-plan grâce à l'option -d.

3. **Vérification du démarrage des conteneurs**

    Vous pouvez vérifier si les conteneurs ont démarré correctement avec la commande suivante :

    ```bash
        docker-compose ps
    ```
    Cette commande affichera les conteneurs en cours d'exécution. Vous devriez voir des conteneurs pour le backend (symfony_app) et pour la base de données PostgreSQL (symfony_db).

4. **Appliquer les migrations Doctrine**

    Si c'est la première fois que vous lancez le projet, ou si vous avez modifié les entités, vous devez exécuter les migrations pour créer les tables dans la base de données PostgreSQL.
    Exécutez la commande suivante dans le conteneur Symfony :

    ```bash
        cd ../Back
        docker exec -it symfony_app php bin/console
        php bin/console make:migration
        php bin/console doctrine:migrations:migrate
        php bin/console doctrine:fixtures:load
    ```
    Cette commande appliquera les migrations pour mettre à jour le schéma de la base de données selon les entités définies dans le projet.

5. **Vérification des tables dans la base de données**

    Si vous souhaitez vérifier que les tables ont bien été créées, vous pouvez vous connecter au conteneur PostgreSQL (symfony_db) et lister les tables avec la commande suivante :

    ```bash
        docker exec -it symfony_db psql -U symfony -d symfony -c "\dt"
    ```
    Cela vous montrera toutes les tables présentes dans la base de données symfony.

6. **Arrêter les conteneurs**
    Si vous souhaitez arrêter les services Docker, vous pouvez utiliser la commande suivante :

    ```bash
        docker-compose down
    ```
    Cela arrêtera et supprimera les conteneurs Docker, mais les données dans la base de données persisteront grâce au volume Docker.

7. **Accéder à l'application**
    Une fois que les conteneurs sont en cours d'exécution, vous pouvez accéder à l'application Symfony via votre navigateur à l'adresse suivante :

    ```arduino
        http://localhost:8000
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
- L'utilise pour authentifier les requêtes via l'en-tête ``Authorization: Bearer <token>