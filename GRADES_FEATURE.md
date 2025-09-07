# Feature: Notes par classes du professeur connecté + moyennes

## Description

Cette fonctionnalité permet aux professeurs connectés de :
- Récupérer les notes des classes où ils sont affectés
- Voir la moyenne de tous les étudiants sur leur matière (par classe et globale)
- Obtenir l'historique de leurs saisies avec date de création et semestre déduit
- Avoir, pour chaque contrôle, les notes et la moyenne

## Dates des semestres

Basées sur les données fournies :
- **Semestre 1 (S1)** : du 2 septembre au 21 janvier
- **Semestre 2 (S2)** : du 2 février au 26 juin

## Modifications apportées

### 1. Entité Grade
- Ajout du champ `created_at` (DateTimeImmutable) pour tracer la date de saisie
- Migration créée et exécutée pour ajouter le champ à la base de données

### 2. Service ProfessorGradeService
- Logique métier pour le calcul des semestres basé sur les dates
- Normalisation des notes sur 20
- Calcul des moyennes
- Groupement des notes par contrôle
- Récupération des données pour les différents endpoints

### 3. Contrôleur ProfessorGradeController
- **GET /api/prof/grades/overview** : Vue synthèse du professeur
- **GET /api/prof/courses/{courseId}/classes/{classId}/grades** : Notes détaillées par cours/classe
- **GET /api/prof/grades/history** : Historique des saisies

### 4. Repository GradeRepository
- Méthodes pour récupérer les notes par professeur
- Filtrage par cours, classe et période
- Optimisation des requêtes avec des jointures appropriées

### 5. EventSubscriber GradeEventSubscriber
- Définition automatique de la date de création lors de la création d'une note

## Endpoints API

### 1. Vue synthèse
```
GET /api/prof/grades/overview?from=YYYY-MM-DD&to=YYYY-MM-DD
```

**Réponse :**
```json
{
  "professor": {
    "id": 42,
    "fullName": "Ada Lovelace"
  },
  "range": {
    "from": "2025-09-01",
    "to": "2026-07-01"
  },
  "byCourse": [
    {
      "courseId": 10,
      "courseTitle": "Algorithmique",
      "classes": [
        {
          "classId": 5,
          "classLabel": "L3 Info A",
          "controls": [
            {
              "title": "DS 1",
              "createdAt": "2025-10-14T09:30:00+02:00",
              "semester": "S1",
              "divisor": 20,
              "average": 12.7,
              "count": 28,
              "grades": [
                {
                  "studentId": 101,
                  "fullName": "Student A",
                  "grade": 14.0
                }
              ]
            }
          ],
          "classAverage": 11.9
        }
      ],
      "courseAverage": 12.3
    }
  ],
  "courseAverageGlobal": 12.3
}
```

### 2. Notes détaillées par cours/classe
```
GET /api/prof/courses/{courseId}/classes/{classId}/grades?from=YYYY-MM-DD&to=YYYY-MM-DD
```

### 3. Historique des saisies
```
GET /api/prof/grades/history?from=YYYY-MM-DD&to=YYYY-MM-DD
```

## Règles métier implémentées

1. **Calcul des semestres** : Basé sur la date de création si `semester_id` est NULL
2. **Normalisation des notes** : Si le diviseur ≠ 0, normalisation sur 20
3. **Agrégation des contrôles** : Groupement par (title, created_at day)
4. **Contrôle d'accès** : Un professeur ne peut voir que ses cours/classes
5. **Tri** : Historique par `created_at` DESC, vues par `courseTitle` ASC puis `classLabel` ASC

## Tests

Un fichier de test `ProfessorGradeControllerTest.php` a été créé pour tester les endpoints.

## Sécurité

- Tous les endpoints nécessitent le rôle `ROLE_PROFESSOR`
- Vérification que le professeur a accès aux cours/classes demandés
- Filtrage automatique des données selon les permissions du professeur connecté
