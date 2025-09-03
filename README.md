# Pantherophis Guttatus — Suivi des serpents (PHP/MySQL)

Un mini-site pour gérer des fiches de Pantherophis guttatus (corn snakes).

## Fonctionnalités
- Thème clair/sombre (bouton lune/soleil sur toutes les pages, mémorisé via localStorage).
- Liste des serpents, ajout, édition, suppression.
- Fiche détaillée par serpent.
- Ajout d'une ou plusieurs photos (jpg/png/webp/gif) par serpent.
- Âge calculé automatiquement à partir de l'année de naissance.
- Sexe coloré (bleu = mâle, rose = femelle).
- Morph, poids (facultatif), commentaire libre.

## Structure
```
pantherophis_tracker/
├─ config.php                  # Paramètres base de données (à éditer)
├─ schema.sql                  # Script SQL de création des tables
├─ includes/
│  ├─ db.php                   # Connexion PDO
│  └─ functions.php            # Fonctions utilitaires
└─ public/
   ├─ index.php                # Liste + ajout
   ├─ snake.php                # Fiche + édition + upload photos
   ├─ delete.php               # Suppression d'un serpent
   ├─ upload.php               # Traitement upload de photos
   └─ assets/
      ├─ style.css             # Styles
      └─ theme.js              # Gestion du thème
      └─ (uploads/)            # Dossier des images (écriture nécessaire)
```

## Installation
1. Copiez le dossier sur votre serveur (Apache/Nginx + PHP 8+).
2. Créez une base MySQL/MariaDB et un utilisateur.
3. Importez `schema.sql` dans la base.
4. Éditez `config.php` avec vos identifiants (hôte, base, utilisateur, mot de passe).
5. Assurez-vous que `public/uploads/` est **inscriptible** par PHP (ex: `chmod 775` ou `chmod 777` selon votre environnement).

## Accès
- Placez le **DocumentRoot**/racine web sur le dossier `public/`.
- Ouvrez `index.php` dans un navigateur.

## Notes
- Le poids est facultatif (laisser vide possible).
- L'année de naissance doit être un entier (ex. 2022). L’âge est calculé dynamiquement.
- Les téléchargements d’images sont limités à 10 Mo par fichier, extensions autorisées : jpg/jpeg/png/gif/webp.
- Les suppressions demandent une confirmation côté client (JS).

Bon élevage 🐍
