# Changelog – Uni’connect

Toutes les dates sont au format AAAA-MM-JJ. Suivi selon SemVer.

## [v0.3.0] – 2025-07-04
### Ajouté
- Analyse de code avec SonarQube (qualité et dette technique).
- Amélioration du **Dockerfile** pour optimiser la taille et les dépendances.
- Documentation mise à jour : connexion au VPS et configuration de déploiement.
### Modifié
- Fusion branche **dev** dans **main** avec regroupement des derniers correctifs.

## [v0.2.2] – 2025-06-28
### Ajouté
- Workflows GitHub Actions optimisés pour déclenchement CI/CD.
### Corrigé
- Problèmes de synchronisation **composer.lock** avec PHPUnit.
- Bugs mineurs dans pipeline CI/CD.

## [v0.2.1] – 2025-05-31
### Ajouté
- Mise à jour des fixtures (**AppFixture**) avec données plus complètes.
### Modifié
- Ajustement des méthodes API **Course** et **CourseUnit**.

## [v0.2.0] – 2025-05-19
### Ajouté
- Endpoints complets pour **AbsenceController** et **ClassController**.
- Nouvelles entités **Level** et **Category** avec endpoints CRUD.
### Modifié
- Paramètre JWT déplacé dans **.env**.
- Routes API **Grade** mises à jour.

## [v0.1.2] – 2025-04-23
### Ajouté
- Méthode **getUserConnected** pour récupérer l’utilisateur courant.
### Modifié
- Ajustements sur la coordination des modules et paramètres API.

## [v0.1.1] – 2025-04-18
### Ajouté
- Authentification JWT finalisée et testée.
- Génération d’AppFixture pour remplir la BDD avec des données réalistes.
### Corrigé 
- Bug sur relation **manyToMany** entre entités.
- Correction du lien entre **User** et **Student**.

## [v0.1.0] – 2025-03-28
### Ajouté
- Serveur web intégré et configuration Nginx regroupée.
- Dockerisation complète du back avec port dédié et entrypoint personnalisé.
- Tests API initiaux.
### Sécurité 
- Correction des erreurs HTTP 403 et messages d’erreur standardisés.

## [v0.0.3] – 2025-03-16
### Ajouté
- Migration de la base vers Railway.
- Fixtures initiales pour **User**, **Student**, **Grade**, **Semester**.
### Corrigé 
- Correction des erreurs HTTP 403 et messages d’erreur standardisés.

## [v0.0.2] – 2025-02-09
### Ajouté
- Entités principales (**Semester**, **Grade**, **Student**, **Classe**, **Module**, **Absence**).
- Contrôleurs CRUD complets pour toutes les entités.

## [v0.0.1] – 2024-11-05
### Ajouté
- Initialisation projet back Symfony.
- Création entité **User** et contrôleur associé.
- Ajout d’informations supplémentaires pour utilisateur.