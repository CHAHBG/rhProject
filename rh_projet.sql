-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 25 déc. 2025 à 21:23
-- Version du serveur : 8.2.0
-- Version de PHP : 8.2.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `rh_projet`
--

-- --------------------------------------------------------

--
-- Structure de la table `adroit`
--

DROP TABLE IF EXISTS `adroit`;
CREATE TABLE IF NOT EXISTS `adroit` (
  `codeGr` varchar(5) NOT NULL,
  `codeInd` varchar(5) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  PRIMARY KEY (`codeGr`,`codeInd`),
  KEY `fk_adroit_ind` (`codeInd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `adroit`
--

INSERT INTO `adroit` (`codeGr`, `codeInd`, `montant`) VALUES
('A1', 'I1', 5000.00),
('A2', 'I3', 2500.00),
('A4', 'I1', 8000.00),
('A4', 'I2', 60000.00),
('A4', 'I3', 5000.00),
('A4', 'I4', 30000.00),
('A5', 'I1', 10000.00),
('A5', 'I2', 70000.00),
('A5', 'I3', 5000.00),
('A5', 'I4', 50000.00);

-- --------------------------------------------------------

--
-- Structure de la table `employe`
--

DROP TABLE IF EXISTS `employe`;
CREATE TABLE IF NOT EXISTS `employe` (
  `matricule` varchar(10) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `tel` varchar(15) DEFAULT NULL,
  `codeGr` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`matricule`),
  KEY `fk_emp_grade` (`codeGr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `employe`
--

INSERT INTO `employe` (`matricule`, `nom`, `tel`, `codeGr`) VALUES
('M01', 'Toto', '30641617', 'A3'),
('M02', 'Fatou', '30640188', 'A4'),
('M03', 'Adjoua', '20320188', 'A5'),
('M04', 'Froto', '20320132', 'A5'),
('M05', 'Sery', '20320132', 'A5'),
('M06', 'Mankou', '30642018', 'A3');

-- --------------------------------------------------------

--
-- Structure de la table `grade`
--

DROP TABLE IF EXISTS `grade`;
CREATE TABLE IF NOT EXISTS `grade` (
  `codeGr` varchar(5) NOT NULL,
  `salaireBase` decimal(10,2) NOT NULL,
  `intitule` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`codeGr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `grade`
--

INSERT INTO `grade` (`codeGr`, `salaireBase`, `intitule`) VALUES
('A1', 150000.00, NULL),
('A2', 200000.00, NULL),
('A3', 250000.00, NULL),
('A4', 300000.00, NULL),
('A5', 400000.00, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `indemnite`
--

DROP TABLE IF EXISTS `indemnite`;
CREATE TABLE IF NOT EXISTS `indemnite` (
  `codeInd` varchar(5) NOT NULL,
  `libelle` varchar(50) NOT NULL,
  PRIMARY KEY (`codeInd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `indemnite`
--

INSERT INTO `indemnite` (`codeInd`, `libelle`) VALUES
('I1', 'Transport'),
('I2', 'Logement'),
('I3', 'Allocation familiale'),
('I4', 'Prime de recherche');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `adroit`
--
ALTER TABLE `adroit`
  ADD CONSTRAINT `fk_adroit_grade` FOREIGN KEY (`codeGr`) REFERENCES `grade` (`codeGr`),
  ADD CONSTRAINT `fk_adroit_ind` FOREIGN KEY (`codeInd`) REFERENCES `indemnite` (`codeInd`);

--
-- Contraintes pour la table `employe`
--
ALTER TABLE `employe`
  ADD CONSTRAINT `fk_emp_grade` FOREIGN KEY (`codeGr`) REFERENCES `grade` (`codeGr`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
