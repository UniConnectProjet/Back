# Changelog – Uni’connect

Toutes les dates sont au format AAAA-MM-JJ. Suivi selon SemVer.

## [v0.5.0] – 2025-08-22
### Ajouté
- Ajout de tests complémentaires et correctifs liés aux environnements **dev** et **test**.
- Nouveaux endpoints et ajustements pour **StudentController** (erreurs de saisie, intégration Grafana).
- PR #45 : intégration **Grafana** (setup initial, dashboard connecté).

### Modifié
- Mise à jour du fichier **ci.yml** pour la mise en production (ajout du déclencheur par tags).
- Exclusion de `LoadFixture` en environnement **prod**.
- Mise à jour du **Dockerfile** (optimisation build).
- Ajustement du fichier `service.yaml` et configurations associées.
- Nettoyage de la structure (`not inside services`).

### Corrigé
- Fix sur le **Loader** pour les environnements dev/test.
- Correction des erreurs de configuration liées à Grafana.
- Correctifs divers sur la configuration CI/CD (`ci.yml`).

---

## [v0.4.1] – 2025-08-21
### Ajouté
- Tests unitaires validés pour plusieurs contrôleurs : **Auth, Absence, Classe, Course, CourseUnit, MeController, Grade, Level, Student, Schedule, User, Semester** (PR #44).
- Mise en place de l’intégration **Grafana** (PR #45).

### Modifié
- Améliorations de la configuration CI/CD (`ci.yml`) : tags, rollback, tests spécifiques, ajustements d’URL DB.
- Suppression de fichiers inutiles (`sonar.yml`, `doctrine:database:drop`).
- Mise à jour du **Dockerfile** et `service.yaml`.
- Fixtures : exclusion de `LoadFixture` en production, ajustements loader pour env dev/test.

### Corrigé
- Corrections sur **StudentController** (erreur de paramétrage + intégration Grafana).

## [v0.4.0] – 2025-08-20
### Ajouté
- Nouvelle entité **CourseSession** avec routes associées.
- Méthodes de **planning** (dont *addMethodForPlanning*, *nextDay planning*).
- Méthodes d’absences côté étudiant : **getAbsenceForStudentBySemester** et endpoints liés.
- Stockage du **JWT dans les cookies** (auth persistante).
- Nouvelles routes / corrections sur **StudentController** et routage par défaut de l’API.
- Automatisation **SonarQube** et analyse qualité dans la pipeline.

### Modifié
- Mise à jour vers **Symfony 7.3** (+ dépendances) après analyse d’impact.
- Plusieurs améliorations du **Dockerfile** (taille, couches, build).
- Ajustement des rôles pour **Professor**.
- **AppFixture** : refactor/typage et cohérence des entités.
- Fichiers de config mis à jour (**bundles.php**, **security.yml**, configs *preprod/prod*).
- Documentation : mises à jour **README.md** et **CHANGELOG.md**.

### Corrigé
- Série de correctifs sur **getScheduleByStudent** (PR #40 → #43).
- Corrections de **routes** et bugs mineurs divers.
- Nettoyage SonarQube (duplications, accolades, style).

### CI/CD
- Multiples mises à jour de **ci.yml** (stratégies par branche *preprod/prod*, rollback, ajout des tests d’environnement).
- Ajout des étapes **composer install/update** dans la pipeline.
- Intégration de l’analyse **SonarQube**.

### Sécurité
- Mise à jour **security.yml** et durcissement des règles d’auth (JWT en cookies).

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
