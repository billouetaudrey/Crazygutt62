# Pantherophis Guttatus — Suivi des serpents (PHP/MySQL)

Un mini-site pour gérer des fiches de Pantherophis guttatus (cornsnakes). Créé à l'aide de chatgpt et gemini
Compatible smartphone (Testé sur chrome Andeoid uniquement)

Démo ici : https://darkgoldenrod-clam-492509.hostingersite.com/Snakager/public/

## Fonctionnalités
- Thème clair/sombre (bouton lune/soleil sur toutes les pages, mémorisé via localStorage).
- Liste des serpents, ajout, édition, suppression.
- Fiche détaillée par serpent.
- Ajout d'une ou plusieurs photos (jpg/png/webp/gif) par serpent.
- Âge calculé automatiquement à partir de l'année de naissance.
- Sexe coloré (bleu = mâle, rose = femelle).
- Morph, poids (facultatif), commentaire libre.
- Accouplement, pontes, date d'éclosion théorique calculé automatiquement
- Édition de serpent multiple
- Affichage répartition des ages, répartition des types de repas
- Alerte repas à +7 jours ( reset ignoré si " refus " )
- Page statistiques ( globale, par catégories de serpent ( adulte sub bébé ), par serpent )

## Structure
```
guttata/
├─ config.php                  # Paramètres base de données (à éditer)
├─ guttata.sql                  # Script SQL de création des tables
├─ includes/
│  ├─ db.php                   # Connexion PDO
│  └─ functions.php            # Fonctions utilitaires
└─ public/
   ├─ index.php                # Liste + ajout
   ├─ snake.php                # Fiche + édition + upload photos
   ├─ delete.php               # Suppression d'un serpent
   ├─ upload.php               # Traitement upload de photos
   ├─ ajout_mue.php            # Ajout mue
   ├─ ajout_ponte.php          # Ajout ponte
   ├─ ajout_repas.php          # Ajout repas
   ├─ bulk_edit_edit.php       # Modifier plusieurs serpents en même temps
   ├─ delete_clutch.php        # Suppression d'une ponte
   ├─ delete_feeding.php       # Suppression d'un repas
   ├─ delete_photos.php        # Suppression d'une photo
   ├─ delete_shed.php          # Suppression d'une mue
   ├─ upload.php               # Traitement upload de photos
   ├─ edit_snake.php           # Edition fiche serpent
   ├─ gestion_donnees.php      # Export/Import, Reset
   ├─ update_snake.php         # Mise a jour fiche serpent
   ├─ stats.php                # Statistiques     
   └─ assets/
      ├─ style.css             # Styles
      └─ theme.js              # Gestion du thème
└─ (uploads/)            # Dossier des images (écriture nécessaire)

```

## Installation
1. Copiez le dossier sur votre serveur (Apache/Nginx + PHP 8+).
2. Créez une base MySQL/MariaDB et un utilisateur.
3. Importez `guttata.sql` dans la base.
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

<img width="1144" height="920" alt="image" src="https://github.com/user-attachments/assets/9266779a-d95c-4c89-8d08-14c61a384d88" />
<img width="1199" height="922" alt="image" src="https://github.com/user-attachments/assets/15093cb1-2596-419c-b5e8-912535a0126f" />
<img width="1115" height="356" alt="image" src="https://github.com/user-attachments/assets/8aa7791c-3e01-43fc-8f4b-f9b6265fcef8" />
<img width="1141" height="755" alt="image" src="https://github.com/user-attachments/assets/9f13a245-1fac-4424-bf76-5fd145088e1d" />
<img width="1230" height="742" alt="image" src="https://github.com/user-attachments/assets/96b54c52-43a5-4fe7-a85e-8b1020683d79" />
<img width="1197" height="333" alt="image" src="https://github.com/user-attachments/assets/fa0f044e-880f-44ad-abca-900905b49a5c" />





