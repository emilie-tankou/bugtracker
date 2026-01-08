BugTracker - Application de Gestion de Tickets
Application web de suivi de bugs développée pour GoodStufForDev.

Description

BugTracker est une application web permettant à une équipe de développeurs de regrouper, suivre et partager les bugs remontés par les utilisateurs ou le département qualité d'une entreprise.

Charte Graphique

Typographie: Space Grotesk
Couleurs:

Sombre: #333333
Principal (Teal): #48e5c2
Clair: #fcfaf9



Fonctionnalités
Gestion des Utilisateurs

Inscription avec email, mot de passe et nom
Connexion sécurisée
Déconnexion
Consultation de tous les tickets
Consultation des tickets assignés

Gestion des Tickets

Création de nouveaux tickets
Modification des tickets existants
Suppression de tickets
Changement de statut (Ouvert, En cours, Fermé)
Filtrage par catégorie et assignation
Niveaux de priorité (Bas, Standard, Élevé)

Catégories

Front-end
Back-end
Infrastructure

Technologies Utilisées

Backend: PHP 7.4+
Base de données: MySQL / MariaDB
Frontend: HTML5, CSS3, JavaScript (Vanilla)
Police: Space Grotesk (Google Fonts)

Structure du Projet
bugtracker/
├── config.php              # Configuration et connexion DB
├── index.php               # Page d'accueil (redirection)
├── login.php               # Page de connexion
├── subscribe.php           # Page d'inscription
├── dashboard.php           # Tableau de bord principal
├── form.php                # Formulaire création/édition
├── logout.php              # Déconnexion
├── api.php                 # API pour AJAX
├── database.sql            # Structure et données initiales
├── css/
│   └── style.css          # Feuille de style
├── js/
│   └── dashboard.js       # JavaScript
└── README.md              # Documentation

Installation
1. Configuration de la base de données
sql-- Créer la base de données
CREATE DATABASE bugtracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Importer le fichier database.sql
mysql -u root -p bugtracker < database.sql
2. Configuration de l'application
Modifier le fichier config.php avec vos paramètres :
phpdefine('DB_HOST', 'localhost');
define('DB_NAME', 'bugtracker');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', 'http://localhost/projetbt/bugtracker');
3. Lancement

Placer les fichiers dans le répertoire web (htdocs, www, public_html)
Accéder à l'application via navigateur: http://localhost/projetbt/bugtracker

Compte par Défaut

Email: admin@bugtracker.com
Password: 123456

Base de Données
Tables
users

id (INT, PRIMARY KEY, AUTO_INCREMENT)
name (VARCHAR)
email (VARCHAR, UNIQUE)
password (VARCHAR, hashed)
created_at (TIMESTAMP)

categories

id (INT, PRIMARY KEY, AUTO_INCREMENT)
title (VARCHAR, UNIQUE)
created_at (TIMESTAMP)

tickets

id (INT, PRIMARY KEY, AUTO_INCREMENT)
title (VARCHAR)
category_id (INT, FOREIGN KEY)
priority (TINYINT: 0=low, 1=standard, 2=high)
status (TINYINT: 0=open, 1=in progress, 2=closed)
created_by (INT, FOREIGN KEY)
assigned_to (INT, FOREIGN KEY, NULL)
created_at (TIMESTAMP)
resolved_at (TIMESTAMP, NULL)

Sécurité

Mots de passe hashés avec password_hash() (bcrypt)
Requêtes préparées (PDO) pour prévenir les injections SQL
Protection XSS avec htmlspecialchars()
Sessions sécurisées (httponly cookies)
Validation des données côté serveur

Responsive Design
L'application est entièrement responsive et s'adapte à :

Desktop (1400px et +)
Tablette (768px - 1399px)
Mobile (320px - 767px)
Orientations portrait et paysage

Pages de l'Application
Publiques

subscribe.php : Inscription
login.php : Connexion

Privées (authentification requise)

dashboard.php : Tableau de bord avec liste des tickets
form.php : Création/modification de ticket

Flux de Travail

L'utilisateur s'inscrit ou se connecte
Il accède au dashboard avec la liste des tickets
Il peut filtrer les tickets par catégorie ou assignation
Il peut créer un nouveau ticket
Il peut modifier le statut d'un ticket directement depuis le dashboard
Il peut éditer ou supprimer un ticket

Statistiques Dashboard
Le dashboard affiche en temps réel :

Nombre total de tickets
Nombre de tickets ouverts
Nombre de tickets en cours
Nombre de tickets fermés

Déploiement (AlwaysData)
Étapes pour héberger sur alwaysdata.com

Créer un compte gratuit sur alwaysdata.com
Accéder au panneau de configuration
Créer une base de données MySQL
Uploader les fichiers via FTP ou Git
Importer database.sql dans PhpMyAdmin
Modifier config.php avec les paramètres alwaysdata
Accéder à l'application via l'URL fournie

Bonnes Pratiques Appliquées

Code documenté en anglais
Règles de nommage respectées (camelCase pour variables, snake_case pour DB)
Validation des données côté serveur
Gestion des erreurs
Code DRY (Don't Repeat Yourself)
Séparation des préoccupations (MVC léger)
Commits Git réguliers et descriptifs

Gestion des Erreurs

Messages d'erreur utilisateur-friendly
Logs d'erreurs pour le développeur
Gestion des cas d'exception
Validation complète des entrées

License
Projet académique - GoodStufForDev

Auteur
Développé dans le cadre du module CDA (Concepteur Développeur d'Applications)

Note: Ce projet répond aux compétences RNCP 7 et 8 de la certification Concepteur Développeur d'Applications.