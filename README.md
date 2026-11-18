# BugTracker — suivi de tickets de bugs

Application web de gestion de tickets de bugs, développée en **PHP natif (sans framework) et MySQL**, dans le cadre de ma formation. Les utilisateurs créent un compte, déclarent des bugs, les classent par catégorie et par priorité, se les assignent et suivent leur avancement jusqu'à la résolution.

> 🔗 **Démo en ligne** : _à compléter_
> 👤 **Compte de démonstration** : `demo@bugtracker.dev` / `demo-bugtracker` (les données d'exemple sont partagées : n'y mets rien de personnel)
> 📄 **Dossier de conception** : _à compléter (lien vers le PDF dans le dépôt)_
>
> L'interface est en anglais ; la documentation est en français.

## Fonctionnalités

- Inscription et connexion (mots de passe hachés, sessions sécurisées)
- Tableau de bord avec **statistiques** (total, ouverts, en cours, fermés)
- **Filtres** : tous les tickets, mes tickets, par catégorie (Front-end, Back-end, Infrastructure)
- Création, modification et suppression de tickets (titre, catégorie, priorité, statut, assigné)
- **Changement de statut en un clic** depuis le tableau, sans recharger la page entière (requête AJAX vers une API JSON)
- Date de résolution renseignée automatiquement à la fermeture d'un ticket, et conservée si on le referme

## Stack

PHP 7.4+ (testé avec PHP 8.3) · MySQL / MariaDB (PDO) · HTML, CSS · JavaScript (`fetch`)

## Structure du projet

```
├── index.php            redirige vers le tableau de bord ou la connexion
├── login.php            connexion
├── subscribe.php        inscription
├── logout.php           déconnexion
├── dashboard.php        tableau de bord : statistiques, filtres, liste
├── form.php             création et modification d'un ticket
├── api.php              API JSON : changement de statut, suppression
├── config.php           configuration, sessions, fonctions communes (CSRF, échappement)
├── config.local.example.php   modèle des identifiants de base de données
├── database.sql         schéma + données de démonstration
├── css/style.css
├── js/dashboard.js
└── tests/smoke-test.sh  test de bout en bout
```

## Modèle de données

```
users (id, name, email UNIQUE, password)
categories (id, title UNIQUE)
tickets (id, title, category_id → categories, priority 0-2, status 0-2,
         created_by → users, assigned_to → users NULL, created_at, resolved_at NULL)
```

Priorité : 0 faible, 1 standard, 2 haute. Statut : 0 ouvert, 1 en cours, 2 fermé.
Les clés étrangères sont définies avec leurs règles de suppression (`CASCADE` pour la catégorie et le créateur, `SET NULL` pour l'assigné).

## Sécurité

- **Injection SQL** : toutes les requêtes utilisent de vraies requêtes préparées PDO (`ATTR_EMULATE_PREPARES = false`) ; les filtres du tableau de bord passent par une liste blanche
- **XSS** : toute donnée affichée est échappée avec `htmlspecialchars`
- **CSRF** : un jeton aléatoire par session est exigé sur tous les formulaires et sur l'API
- **Mots de passe** : hachés avec `password_hash` / `password_verify`, 8 caractères minimum
- **Sessions** : cookie `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS, mode strict, et **nouvel identifiant de session à la connexion** (contre la fixation de session)
- **Validation côté serveur** de chaque champ (statut, priorité, catégorie et assigné doivent exister, longueurs maximales)
- **API** : renvoie `401` sans session, `403` sans jeton CSRF, `404` pour un ticket inconnu, `405` pour une méthode non autorisée, `400` pour des données invalides
- **Secrets hors du dépôt** : les identifiants de base de données vivent dans `config.local.php` (ignoré par Git) ou dans des variables d'environnement
- **Apache** (`.htaccess`) : liste des dossiers désactivée, fichiers `.sql`, `.md` et `config.local.php` inaccessibles, en-têtes de sécurité

## Installation en local

Prérequis : PHP 7.4+ avec les extensions `pdo_mysql` et `mbstring`, et MySQL ou MariaDB (par exemple via XAMPP).

1. Crée une base `bugtracker`, puis importe le schéma et les données de démonstration :
   ```bash
   mysql -u root -p -e "CREATE DATABASE bugtracker CHARACTER SET utf8mb4"
   mysql -u root -p bugtracker < database.sql
   ```
2. Copie `config.local.example.php` en `config.local.php` et renseigne tes identifiants (les valeurs par défaut conviennent à un XAMPP standard : `root`, sans mot de passe).
3. Lance le serveur et ouvre <http://127.0.0.1:8000> :
   ```bash
   php -S 127.0.0.1:8000
   ```
4. Connecte-toi avec `demo@bugtracker.dev` / `demo-bugtracker`.

## Tests

`tests/smoke-test.sh` parcourt l'application comme un utilisateur (avec `curl`) : redirections sans connexion, protection CSRF, changement de l'identifiant de session, création et édition d'un ticket, échappement d'une tentative de XSS, tentative d'injection SQL, codes de réponse de l'API, inscription, déconnexion.

```bash
php -S 127.0.0.1:8000        # dans un terminal
bash tests/smoke-test.sh     # dans un autre
```

Le test crée puis supprime un ticket et crée un compte utilisateur : lance-le sur une base de test.

## Déploiement (alwaysdata, offre gratuite)

1. Crée un compte sur [alwaysdata.com](https://www.alwaysdata.com) (l'offre gratuite inclut PHP, MySQL, HTTPS, SSH et FTP ; vérifie ses limites à l'inscription).
2. Dans l'administration : **Web → Sites** pour créer un site PHP, puis **Bases de données → MySQL** pour créer une base et un utilisateur.
3. Récupère le code dans le dossier web, en SSH :
   ```bash
   cd ~/www && git clone https://github.com/emilie-tankou/bugtracker.git
   ```
4. Importe `database.sql` dans la nouvelle base avec phpMyAdmin (lien dans l'administration).
5. Crée `config.local.php` à partir de `config.local.example.php` avec l'hôte (`mysql-TONCOMPTE.alwaysdata.net`), le nom de la base, l'utilisateur et le mot de passe.
6. Fais pointer le site vers le dossier `bugtracker` et ouvre l'adresse `TONCOMPTE.alwaysdata.net`.

## Limites connues et pistes d'amélioration

- Tous les utilisateurs connectés peuvent modifier ou supprimer **n'importe quel ticket** : il n'y a pas encore de rôles ni de droits par propriétaire
- La déconnexion se fait par un lien simple (pas de jeton CSRF), ce qui est sans gravité mais perfectible
- Pas de limitation du nombre de tentatives de connexion
- Pas de pagination de la liste de tickets
- Interface uniquement en anglais

## Ce que ce projet m'a appris

- Concevoir une base relationnelle (clés étrangères, règles de suppression) à partir d'un dossier de conception
- Écrire une application PHP sans framework en séparant ce qui touche aux données, à la sécurité et à l'affichage
- Appliquer concrètement les protections de base d'une application web : injection SQL, XSS, CSRF, sessions
- Tester une application de bout en bout et corriger les défauts que ces tests révèlent