# Journal des corrections et refactorisations

Ce journal doit être mis à jour à chaque lot. Une entrée décrit le problème, le
choix effectué, les fichiers modifiés, la vérification réalisée et les risques restants.

## 2026-09-15 — Lot 1 : protection du module documentaire

### Problème

Les routes `/document` n'étaient pas protégées globalement. La suppression d'un
document vérifiait le jeton CSRF, mais pas le rôle administrateur côté serveur.
Masquer le bouton dans Twig ne constitue pas un contrôle d'accès.

### Correction

- ajout de `^/document -> ROLE_USER` dans `config/packages/security.yaml` ;
- refus explicite des utilisateurs non administrateurs sur l'édition ;
- refus explicite des utilisateurs non administrateurs sur la suppression.

### Vérification

- revue statique du routage et du contrôleur ;
- tests automatiques non exécutés : PHP et Composer sont absents de l'environnement.

### Suite obligatoire

Les fichiers restent servis depuis `public/uploads/documents`. Le prochain lot doit
les déplacer dans un stockage privé et ajouter une route de téléchargement avec un
contrôle d'accès centralisé (voter). La migration des fichiers existants devra être
prévue avant le déploiement. Ce risque est traité par le lot 4 ci-dessous.

## 2026-09-15 — Lot 3 : suppressions via POST

- les suppressions de contacts, contrats et mots-clés SERP n'acceptent plus GET ;
- les jetons CSRF sont maintenant transmis dans le corps d'un formulaire HTML ;
- les liens de suppression ont été remplacés par des formulaires POST confirmés ;
- vérification statique réalisée avec `git diff --check` ; PHPUnit reste à exécuter
  dès qu'un environnement PHP/Composer sera disponible.

## 2026-09-15 — Lot 2 : préparation de l'analyse SonarQube

- ajout de `sonar-project.properties` avec les sources, tests, exclusions et rapport
  de couverture PHPUnit ;
- ajout du workflow `.github/workflows/sonarqube.yml` déclenché sur `main` et les PR ;
- exécution non possible dans cet environnement faute de PHP, Composer, Docker et
  SonarScanner ; la première exécution doit donc être faite après configuration des
  secrets GitHub `SONAR_TOKEN` et `SONAR_HOST_URL`.

## 2026-09-15 — Lot 4 : stockage privé des documents

- déplacement du répertoire cible de `public/uploads/documents` vers `var/documents` ;
- ajout de `DocumentVoter` pour centraliser les droits VIEW, EDIT et DELETE ;
- ajout de la route authentifiée `app_document_file` qui vérifie l'autorisation avant
  de retourner le fichier ;
- remplacement de toutes les URL publiques de documents dans les templates ;
- protection contre une traversée de chemin avec `basename()` et réponse 404 lorsque
  le fichier physique est absent.

### Migration avant déploiement

Copier les fichiers déjà présents vers le nouveau stockage, puis vérifier les droits
du compte qui exécute PHP :

```bash
mkdir -p var/documents
cp -p public/uploads/documents/* var/documents/
```

Après validation fonctionnelle et sauvegarde, l'ancien dossier public pourra être
vidé. Cette suppression n'est pas automatisée afin d'éviter toute perte de données.

## 2026-09-15 — Lot 5 : socle de tests et CI

- remplacement de 12 tests incomplets générés par des tests de contrôle d'accès ;
- correction des chemins de test pour correspondre aux routes réelles ;
- ajout de tests unitaires du `DocumentVoter` pour les rôles administrateur,
  utilisateur affecté et utilisateur non autorisé ;
- ajout d'un service PostgreSQL et de la création du schéma de test dans la CI ;
- configuration de valeurs factices pour éviter l'utilisation de secrets réels.

L'exécution locale reste à confirmer sur un poste disposant de PHP et Composer. Les
prochains tests devront couvrir les opérations CRUD authentifiées et les réponses 403.

### Correction après première exécution CI

La première exécution a échoué pendant `composer install` : les scripts Symfony
cherchaient un fichier `.env` absent du dépôt. Le workflow copie désormais
`.env.example` vers `.env` avant l'installation. Les valeurs non sensibles de test
restent imposées par l'environnement GitHub Actions.

La deuxième exécution a atteint PostgreSQL, mais cherchait `alice_test_test` : le nom
`alice_test` présent dans l'URL recevait une seconde fois le suffixe `_test` défini
dans `config/packages/doctrine.yaml`. L'URL utilise désormais la base logique `alice` ;
Doctrine construit ainsi le nom final attendu `alice_test`.
