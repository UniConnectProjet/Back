# Backend Setup

Ce guide explique comment démarrer le backend de l'application avec Docker et PostgreSQL.

## Prérequis

- **Docker** : Assurez-vous que Docker et Docker Compose sont installés sur votre machine.
  
  - Si vous n'avez pas encore installé Docker, vous pouvez le faire en suivant les instructions officielles :  
    [Installation Docker](https://docs.docker.com/get-docker/)

  - Pour installer Docker Compose, suivez les instructions :  
    [Installation Docker Compose](https://docs.docker.com/compose/install/)

## Démarrer l'environnement avec Docker

1. **Cloner le dépôt**

   Si ce n'est pas déjà fait, clonez le dépôt du projet sur votre machine locale.

   ```bash
        git clone https://github.com/UniConnectProjet/Back.git
        cd Back
   ```
2. **Configurer la base de données**

   Dans le répertoire racine du projet, il y a un fichier .env qui contient les paramètres de la base de données. Assurez-vous que le fichier .env est configuré avec les bonnes valeurs pour DATABASE_URL.

   Exemple de configuration :
   ```bash
        DATABASE_URL="postgresql://symfony:symfony@db:5432/symfony?serverVersion=15.12"
   ```
   - symfony : L'utilisateur PostgreSQL.
   - symfony : Le mot de passe PostgreSQL.
   - db : Le nom du service PostgreSQL dans le fichier docker-compose.yml.
   - 5432 : Le port d'écoute de PostgreSQL.
   - symfony : Le nom de la base de données à utiliser.

3. **Démarrer les conteneurs Docker**

    Pour démarrer tous les services (backend et base de données), utilisez la commande suivante :
    ```bash
        cd ..
        git clone https://github.com/UniConnectProjet/DockerDeployment.git
        cd DockerDeployment/
        docker-compose up -d
    ``` 
    Cette commande lance tous les services définis dans docker-compose.yml (backend et base de données PostgreSQL). Les services démarrent en arrière-plan grâce à l'option -d.

4. **Vérification du démarrage des conteneurs**

    Vous pouvez vérifier si les conteneurs ont démarré correctement avec la commande suivante :

    ```bash
        docker-compose ps
    ```
    Cette commande affichera les conteneurs en cours d'exécution. Vous devriez voir des conteneurs pour le backend (symfony_app) et pour la base de données PostgreSQL (symfony_db).

5. **Appliquer les migrations Doctrine**

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

6. **Vérification des tables dans la base de données**

    Si vous souhaitez vérifier que les tables ont bien été créées, vous pouvez vous connecter au conteneur PostgreSQL (symfony_db) et lister les tables avec la commande suivante :

    ```bash
        docker exec -it symfony_db psql -U symfony -d symfony -c "\dt"
    ```
    Cela vous montrera toutes les tables présentes dans la base de données symfony.

7. **Arrêter les conteneurs**
    Si vous souhaitez arrêter les services Docker, vous pouvez utiliser la commande suivante :

    ```bash
        docker-compose down
    ```
    Cela arrêtera et supprimera les conteneurs Docker, mais les données dans la base de données persisteront grâce au volume Docker.

8. **Accéder à l'application**
    Une fois que les conteneurs sont en cours d'exécution, vous pouvez accéder à l'application Symfony via votre navigateur à l'adresse suivante :

    ```arduino
        http://localhost:8000
    ```