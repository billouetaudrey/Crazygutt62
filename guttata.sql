-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : mer. 03 sep. 2025 à 13:16
-- Version du serveur : 10.11.13-MariaDB-0ubuntu0.24.04.1
-- Version de PHP : 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `guttata`
--

-- --------------------------------------------------------

--
-- Structure de la table `clutches`
--

CREATE TABLE `clutches` (
  `id` int(11) NOT NULL,
  `male_id` int(11) NOT NULL,
  `female_id` int(11) NOT NULL,
  `lay_date` date NOT NULL,
  `hatch_date` date GENERATED ALWAYS AS (`lay_date` + interval 60 day) STORED,
  `comment` text DEFAULT NULL,
  `egg_count` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `feedings`
--

CREATE TABLE `feedings` (
  `id` int(11) NOT NULL,
  `snake_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `count` tinyint(4) NOT NULL DEFAULT 1,
  `prey_type` enum('vivant','mort','congelé') NOT NULL,
  `refused` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `dernier_repas` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `photos`
--

CREATE TABLE `photos` (
  `id` int(11) NOT NULL,
  `snake_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sheds`
--

CREATE TABLE `sheds` (
  `id` int(11) NOT NULL,
  `snake_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `complete` tinyint(1) NOT NULL DEFAULT 1,
  `comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `snakes`
--

CREATE TABLE `snakes` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sex` enum('M','F','I') NOT NULL,
  `morph` varchar(255) DEFAULT NULL,
  `birth_year` int(11) NOT NULL,
  `weight` decimal(6,2) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `default_meal_type` varchar(50) DEFAULT NULL,
  `meal_type` varchar(50) NOT NULL DEFAULT 'inconnu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `clutches`
--
ALTER TABLE `clutches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `male_id` (`male_id`),
  ADD KEY `female_id` (`female_id`);

--
-- Index pour la table `feedings`
--
ALTER TABLE `feedings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_feedings_snake` (`snake_id`);

--
-- Index pour la table `photos`
--
ALTER TABLE `photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_photos_snake` (`snake_id`);

--
-- Index pour la table `sheds`
--
ALTER TABLE `sheds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `snake_id` (`snake_id`);

--
-- Index pour la table `snakes`
--
ALTER TABLE `snakes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `clutches`
--
ALTER TABLE `clutches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `feedings`
--
ALTER TABLE `feedings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `photos`
--
ALTER TABLE `photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sheds`
--
ALTER TABLE `sheds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `snakes`
--
ALTER TABLE `snakes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `clutches`
--
ALTER TABLE `clutches`
  ADD CONSTRAINT `clutches_ibfk_1` FOREIGN KEY (`male_id`) REFERENCES `snakes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clutches_ibfk_2` FOREIGN KEY (`female_id`) REFERENCES `snakes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `feedings`
--
ALTER TABLE `feedings`
  ADD CONSTRAINT `fk_feedings_snake` FOREIGN KEY (`snake_id`) REFERENCES `snakes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `photos`
--
ALTER TABLE `photos`
  ADD CONSTRAINT `fk_photos_snake` FOREIGN KEY (`snake_id`) REFERENCES `snakes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sheds`
--
ALTER TABLE `sheds`
  ADD CONSTRAINT `sheds_ibfk_1` FOREIGN KEY (`snake_id`) REFERENCES `snakes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
