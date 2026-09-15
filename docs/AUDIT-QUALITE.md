# Audit qualité et sécurité

Date de l'audit : 2026-09-15  
Révision analysée : `952fd68` (`main`)

## Périmètre et limites

L'audit couvre le code PHP/Symfony, les contrôleurs, les entités, les formulaires,
les templates, la configuration, les dépendances verrouillées et les tests présents
dans le dépôt. Il s'agit d'une analyse statique ciblée, complétée par des recherches
automatisées dans le code.

Le runtime d'audit ne fournit ni PHP, ni Composer, ni Docker, ni SonarScanner. Les tests,
`composer audit` et une analyse Sonar complète n'ont donc pas pu être exécutés.
Cette limitation doit être levée dans la CI afin de rendre les résultats reproductibles.

## Ordre de priorité

### P0 — Corriger avant toute mise en production

1. **Documents stockés dans le répertoire public**
   - Preuve : `documents_directory` pointe vers `public/uploads/documents` et les
     templates construisent directement cette URL.
   - Risque : une personne connaissant ou obtenant l'URL peut télécharger un fichier
     sans passer par le contrôle d'autorisation de `DocumentController::show()`.
   - Statut : corrigé dans le lot 4 par stockage sous `var/documents`, route contrôlée
     et `DocumentVoter`. La copie des fichiers existants reste une étape de déploiement.

2. **Autorisation incomplète sur le module documentaire**
   - Preuve : `/document` n'était couvert par aucune règle `access_control` et la
     suppression ne vérifiait pas le rôle administrateur.
   - Risque : accès anonyme involontaire et suppression horizontale d'un document.
   - Statut : première protection corrigée dans la branche d'audit ; le téléchargement
     privé reste à implémenter.

3. **Actions destructrices exposées en GET**
   - Preuve : suppression d'un contrat, d'un mot-clé SERP et d'un contact par des
     routes sans restriction de méthode, jeton CSRF transmis dans la query string.
   - Risque : fuite du jeton dans les journaux/historiques et déclenchement indésirable
     par préchargement de liens ou robots.
   - Correction : routes `POST` ou `DELETE`, jeton dans le corps d'un formulaire,
     tests vérifiant que GET retourne 405.

4. **Framework hors support**
   - Preuve : tous les composants Symfony sont figés sur `6.2.*`.
   - Risque : absence de correctifs de sécurité et incompatibilités avec l'écosystème.
   - Correction : migrer d'abord vers Symfony 6.4 LTS, puis planifier Symfony 7.4 LTS
     après vérification de PHP et des bundles tiers.

### P1 — Corriger juste après les risques critiques

5. **Suite de tests non opérationnelle**
   - 12 appels à `markTestIncomplete()` dans trois contrôleurs de test.
   - Les jeux de données générés sont incompatibles avec les types des entités
     (`DateTimeInterface`, collections Doctrine, nombres).
   - Certains chemins attendus ne correspondent plus aux routes actuelles.
   - Correction : fixtures dédiées, authentification explicite admin/utilisateur,
     tests d'autorisation 302/403, tests CRUD et tests de propriété des documents.

6. **Absence de CI qualité**
   - Une configuration SonarQube et un workflow GitHub Actions ont été ajoutés dans
     `sonar-project.properties` et `.github/workflows/sonarqube.yml`.
   - Il reste à renseigner `SONAR_TOKEN` et `SONAR_HOST_URL`, puis à compléter la CI
     avec `composer validate`, `composer audit`, PHPStan et PHP-CS-Fixer.

7. **Gestion fragile des ressources absentes**
   - Plusieurs contrôleurs déréférencent une entité avant de vérifier qu'elle existe,
     par exemple `ContactController` et `ContractController`.
   - Risque : erreurs 500 à la place de réponses 404 maîtrisées.
   - Correction : utiliser le ParamConverter/EntityValueResolver typé ou vérifier
     immédiatement le résultat du repository.

8. **Appel HTTP Google dans le contrôleur**
   - `ContractController` instancie directement `HttpClient`, construit l'URL à la main
     et ne gère ni timeout, ni statut non-2xx, ni quota, ni réponse JSON invalide.
   - Correction : extraire un `GoogleSearchClient`, injecter `HttpClientInterface`,
     configurer timeout/retry et tester les erreurs avec `MockHttpClient`.

9. **Fuite potentielle d'informations dans la sortie HTTP**
   - `Mail.php` utilise `var_dump()` sur la réponse Mailjet.
   - Correction : retourner un résultat typé ou journaliser sans données sensibles
     avec PSR-3 ; ne jamais écrire la réponse fournisseur dans le corps HTTP.

### P2 — Maintenabilité et dette technique

10. **Contrôleurs trop volumineux et responsabilités mélangées**
    - `AdminMainController` dépasse 400 lignes et combine utilisateurs, clients et
      contenu dynamique ; d'autres contrôleurs portent logique métier et infrastructure.
    - Correction : séparer les contrôleurs et introduire des services applicatifs.

11. **Dépendances et API vieillissantes**
    - `sensio/framework-extra-bundle` et les annotations Doctrine peuvent être retirés
      après migration vers les attributs et les mécanismes Symfony natifs.
    - `fakerphp/faker` est une dépendance de production alors qu'elle sert aux fixtures.

12. **Modèle de rôles ambigu**
    - `User` conserve à la fois `roles` (sécurité) et `role` (statut métier), tandis que
      les templates supposent que le rôle utile est toujours à l'index zéro.
    - Correction : renommer le statut métier, utiliser `is_granted()` dans Twig et
      tester les hiérarchies de rôles.

13. **Duplication importante dans les formulaires**
    - `CustomerType`/`EditCustomerType` et `ContactType`/`EditContactType` dupliquent
      la majorité des champs et contraintes.
    - Correction : type parent commun, options ou groupes de validation.

14. **Typage et conventions hétérogènes**
    - Propriétés de services non typées, noms de méthodes non PSR, imports inutilisés,
      commentaires obsolètes et comparaisons de rôles fragiles.
    - Correction : PHPStan, PHP-CS-Fixer, types de retour et propriétés `readonly`
      là où c'est possible.

## Plan de correction proposé

1. Fermer les accès documentaires et sécuriser le téléchargement.
2. Convertir toutes les suppressions en POST et couvrir les autorisations par tests.
3. Installer une CI minimale et remettre les tests existants en état.
4. Migrer Symfony 6.2 vers 6.4 LTS avec audit des dépendances.
5. Extraire les clients externes et la logique métier des contrôleurs.
6. Réduire la duplication et augmenter progressivement le niveau PHPStan.

Chaque lot doit rester petit, être associé à des tests et être documenté dans
`docs/JOURNAL-REFACTORING.md`.
