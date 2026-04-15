-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 16 avr. 2026 à 01:09
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `siges`
--

-- --------------------------------------------------------

--
-- Structure de la table `affecter`
--

CREATE TABLE `affecter` (
  `Id_Professeur` int(11) NOT NULL,
  `Id_Classe` int(11) NOT NULL,
  `annee_scolaire` varchar(20) DEFAULT '2023-2024'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `affecter`
--

INSERT INTO `affecter` (`Id_Professeur`, `Id_Classe`, `annee_scolaire`) VALUES
(1, 1, '2023-2024'),
(1, 2, '2023-2024'),
(2, 2, '2023-2024'),
(3, 3, '2023-2024'),
(4, 4, '2023-2024'),
(5, 5, '2023-2024'),
(6, 6, '2023-2024'),
(7, 7, '2023-2024'),
(8, 8, '2023-2024');

-- --------------------------------------------------------

--
-- Structure de la table `classe`
--

CREATE TABLE `classe` (
  `Id_Classe` int(11) NOT NULL,
  `libelle` varchar(50) NOT NULL,
  `niveau` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `classe`
--

INSERT INTO `classe` (`Id_Classe`, `libelle`, `niveau`) VALUES
(1, 'L1-Informatique', 'Licence 1'),
(2, 'L2-Informatique', 'Licence 2'),
(3, 'Master 1-Génie Logiciel', 'Master 1'),
(4, 'L1-Mathematiques', 'Licence 1'),
(5, 'L2-Mathematiques', 'Licence 2'),
(6, 'L3-Mathematiques', 'Licence 3'),
(7, 'M1-Mathematiques', 'Master 1'),
(8, 'M2-Mathematiques', 'Master 2'),
(9, 'L1-Physique-Chimie', 'Licence 1'),
(10, 'L2-Physique-Chimie', 'Licence 2'),
(11, 'L3-Physique-Chimie', 'Licence 3'),
(12, 'M1-Physique-Chimie', 'Master 1'),
(13, 'M2-Physique-Chimie', 'Master 2');

-- --------------------------------------------------------

--
-- Structure de la table `creneau`
--

CREATE TABLE `creneau` (
  `Id_Creneau` int(11) NOT NULL,
  `jour` varchar(20) NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `Id_Classe` int(11) NOT NULL,
  `Id_Professeur` int(11) NOT NULL,
  `Id_Matiere` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `creneau`
--

INSERT INTO `creneau` (`Id_Creneau`, `jour`, `heure_debut`, `heure_fin`, `Id_Classe`, `Id_Professeur`, `Id_Matiere`) VALUES
(1, 'Lundi', '08:00:00', '10:00:00', 2, 1, 2),
(2, 'Lundi', '08:00:00', '10:00:00', 1, 1, 1),
(3, 'Lundi', '10:00:00', '12:00:00', 2, 2, 2),
(4, 'Mardi', '08:00:00', '10:00:00', 3, 3, 3),
(5, 'Mardi', '10:00:00', '12:00:00', 4, 4, 4),
(6, 'Mercredi', '08:00:00', '10:00:00', 5, 5, 1),
(7, 'Mercredi', '10:00:00', '12:00:00', 6, 6, 2),
(8, 'Jeudi', '08:00:00', '10:00:00', 7, 7, 3),
(9, 'Vendredi', '08:00:00', '10:00:00', 8, 8, 4);

-- --------------------------------------------------------

--
-- Structure de la table `effectue`
--

CREATE TABLE `effectue` (
  `id_Etudiant` int(11) NOT NULL,
  `Id_Evaluation` int(11) NOT NULL,
  `note` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `effectue`
--

INSERT INTO `effectue` (`id_Etudiant`, `Id_Evaluation`, `note`) VALUES
(1, 1, 14.50),
(1, 2, 12.00),
(1, 3, 10.06),
(1, 4, 15.58),
(1, 5, 18.65),
(1, 6, 13.84),
(2, 1, 17.00),
(2, 2, 15.50),
(2, 3, 14.15),
(2, 4, 10.49),
(2, 5, 15.01),
(2, 6, 13.01),
(3, 1, 9.00),
(3, 2, 11.00),
(3, 3, 10.56),
(3, 4, 15.71),
(3, 5, 19.11),
(3, 6, 13.52),
(4, 1, 12.50),
(4, 3, 19.13),
(4, 4, 10.37),
(4, 5, 17.07),
(4, 6, 10.51),
(5, 1, 14.00),
(5, 3, 14.04),
(5, 4, 10.15),
(5, 5, 18.22),
(5, 6, 15.21),
(6, 1, 9.50),
(6, 3, 12.81),
(6, 4, 19.67),
(6, 5, 19.89),
(6, 6, 14.52),
(7, 1, 15.00),
(7, 3, 11.95),
(7, 4, 17.88),
(7, 5, 14.78),
(7, 6, 17.00),
(8, 1, 11.00),
(8, 3, 11.30),
(8, 4, 10.38),
(8, 5, 14.23),
(8, 6, 11.40),
(9, 1, 13.00),
(9, 3, 10.65),
(9, 4, 18.28),
(9, 5, 16.81),
(9, 6, 16.04),
(10, 1, 10.00),
(10, 3, 19.37),
(10, 4, 10.24),
(10, 5, 11.37),
(10, 6, 15.97),
(11, 1, 16.00),
(11, 3, 14.90),
(11, 4, 16.36),
(11, 5, 16.41),
(11, 6, 11.75),
(12, 1, 14.00),
(12, 3, 16.39),
(12, 4, 11.11),
(12, 5, 17.94),
(12, 6, 10.85),
(13, 1, 12.00),
(13, 3, 17.25),
(13, 4, 16.44),
(13, 5, 10.45),
(13, 6, 18.98),
(14, 1, 11.00),
(14, 3, 17.09),
(14, 4, 18.88),
(14, 5, 18.46),
(14, 6, 12.35),
(15, 1, 15.00),
(15, 3, 13.71),
(15, 4, 15.08),
(15, 5, 10.96),
(15, 6, 14.80),
(16, 1, 13.00),
(16, 3, 17.27),
(16, 4, 18.76),
(16, 5, 19.41),
(16, 6, 16.95),
(17, 1, 10.00),
(17, 3, 15.23),
(17, 4, 18.56),
(17, 5, 14.17),
(17, 6, 10.36),
(18, 1, 14.00),
(18, 3, 14.33),
(18, 4, 16.54),
(18, 5, 12.63),
(18, 6, 10.94),
(19, 1, 9.00),
(19, 3, 15.96),
(19, 4, 17.00),
(19, 5, 10.61),
(19, 6, 13.64),
(20, 1, 12.00),
(20, 3, 16.79),
(20, 4, 15.38),
(20, 5, 15.19),
(20, 6, 15.36),
(21, 1, 13.00),
(21, 3, 16.09),
(21, 4, 15.90),
(21, 5, 14.12),
(21, 6, 15.87),
(22, 1, 11.00),
(22, 3, 10.06),
(22, 4, 13.37),
(22, 5, 15.02),
(22, 6, 13.26),
(23, 1, 15.00),
(23, 3, 12.05),
(23, 4, 19.14),
(23, 5, 12.74),
(23, 6, 18.73);

-- --------------------------------------------------------

--
-- Structure de la table `etudiant`
--

CREATE TABLE `etudiant` (
  `id_Etudiant` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `Id_Classe` int(11) DEFAULT NULL,
  `login` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `etudiant`
--

INSERT INTO `etudiant` (`id_Etudiant`, `nom`, `prenom`, `Id_Classe`, `login`) VALUES
(1, 'BA', 'Amadou', 2, 'amadou.ba@gmail.com'),
(2, 'DIALLO', 'Mariama', 2, 'mariama.diallo@gmail.com'),
(3, 'SANE', 'Ibrahima', 2, 'ibrahima.sane@gmail.com'),
(4, 'Ndiaye', 'Ali', 1, 'ali.ndiaye@etudiant.sn'),
(5, 'Fall', 'Fatou', 1, 'fatou.fall@etudiant.sn'),
(6, 'Ba', 'Moussa', 1, 'moussa.ba@etudiant.sn'),
(7, 'Diop', 'Awa', 1, 'awa.diop@etudiant.sn'),
(8, 'Sarr', 'Ibrahima', 1, 'ibrahima.sarr@etudiant.sn'),
(9, 'Seck', 'Mariama', 1, 'mariama.seck@etudiant.sn'),
(10, 'Sy', 'Cheikh', 1, 'cheikh.sy@etudiant.sn'),
(11, 'Gueye', 'Aminata', 1, 'aminata.gueye@etudiant.sn'),
(12, 'Kane', 'Omar', 1, 'omar.kane@etudiant.sn'),
(13, 'Faye', 'Khadija', 1, 'khadija.faye@etudiant.sn'),
(14, 'Ndour', 'Aliou', 1, 'aliou.ndour@etudiant.sn'),
(15, 'Sow', 'Aissatou', 1, 'aissatou.sow@etudiant.sn'),
(16, 'Ba', 'Mamadou', 1, 'mamadou.ba@etudiant.sn'),
(17, 'Fall', 'Omar', 1, 'omar.fall@etudiant.sn'),
(18, 'Diagne', 'Fatou', 1, 'fatou.diagne@etudiant.sn'),
(19, 'Camara', 'Moussa', 1, 'moussa.camara@etudiant.sn'),
(20, 'Diallo', 'Awa', 1, 'awa.diallo@etudiant.sn'),
(21, 'Barry', 'Ousmane', 1, 'ousmane.barry@etudiant.sn'),
(22, 'Bah', 'Mariama', 1, 'mariama.bah@etudiant.sn'),
(23, 'Keita', 'Ali', 1, 'ali.keita@etudiant.sn');

-- --------------------------------------------------------

--
-- Structure de la table `evaluation`
--

CREATE TABLE `evaluation` (
  `Id_Evaluation` int(11) NOT NULL,
  `date_eval` date NOT NULL,
  `semestre` int(11) NOT NULL,
  `Id_Matiere` int(11) NOT NULL,
  `Id_Professeur` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `evaluation`
--

INSERT INTO `evaluation` (`Id_Evaluation`, `date_eval`, `semestre`, `Id_Matiere`, `Id_Professeur`) VALUES
(1, '2024-03-15', 1, 2, 1),
(2, '2024-03-20', 1, 1, 2),
(3, '2024-04-10', 1, 3, 3),
(4, '2024-04-15', 1, 4, 4),
(5, '2024-05-02', 2, 1, 5),
(6, '2024-05-10', 2, 2, 6),
(7, '2024-05-15', 2, 3, 7),
(8, '2024-05-20', 2, 4, 8);

-- --------------------------------------------------------

--
-- Structure de la table `matiere`
--

CREATE TABLE `matiere` (
  `Id_Matiere` int(11) NOT NULL,
  `libelle` varchar(100) NOT NULL,
  `coefficient` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `matiere`
--

INSERT INTO `matiere` (`Id_Matiere`, `libelle`, `coefficient`) VALUES
(1, 'Algorithmique', 4),
(2, 'Développement PHP', 3),
(3, 'Base de Données', 3),
(4, 'Réseaux Informatiques', 2),
(5, 'Analyse', 4),
(6, 'Algèbre', 4),
(7, 'Physique', 3),
(8, 'Chimie', 3);

-- --------------------------------------------------------

--
-- Structure de la table `professeur`
--

CREATE TABLE `professeur` (
  `Id_Professeur` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `Id_Matiere` int(11) DEFAULT NULL,
  `login` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `professeur`
--

INSERT INTO `professeur` (`Id_Professeur`, `nom`, `prenom`, `Id_Matiere`, `login`) VALUES
(1, 'DIOP', 'Fatou', 2, 'fatou.diop@siges.sn'),
(2, 'NDIAYE', 'Moussa', 1, 'moussa.ndiaye@siges.sn'),
(3, 'Sow', 'Alioune', 1, 'alioune.sow@siges.sn'),
(4, 'Fall', 'Aminata', 2, 'aminata.fall@siges.sn'),
(5, 'Ba', 'Omar', 3, 'omar.ba@siges.sn'),
(6, 'Ndour', 'Fatou', 4, 'fatou.ndour@siges.sn'),
(7, 'Diop', 'Cheikh', 1, 'cheikh.diop@siges.sn'),
(8, 'Sy', 'Awa', 2, 'awa.sy@siges.sn'),
(9, 'Gueye', 'Moussa', 3, 'moussa.gueye@siges.sn'),
(10, 'Sarr', 'Khadija', 4, 'khadija.sarr@siges.sn');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `login` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Professeur','Etudiant') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`login`, `password`, `role`) VALUES
('admin@siges.sn', 'admin123', 'Admin'),
('aissatou.sow@etudiant.sn', 'etudiant123', 'Etudiant'),
('ali.keita@etudiant.sn', 'etudiant123', 'Etudiant'),
('ali.ndiaye@etudiant.sn', 'etudiant123', 'Etudiant'),
('aliou.ndour@etudiant.sn', 'etudiant123', 'Etudiant'),
('alioune.sow@siges.sn', 'prof123', 'Professeur'),
('amadou.ba@gmail.com', 'etudiant123', 'Etudiant'),
('aminata.fall@siges.sn', 'prof123', 'Professeur'),
('aminata.gueye@etudiant.sn', 'etudiant123', 'Etudiant'),
('awa.diallo@etudiant.sn', 'etudiant123', 'Etudiant'),
('awa.diop@etudiant.sn', 'etudiant123', 'Etudiant'),
('awa.sy@siges.sn', 'prof123', 'Professeur'),
('cheikh.diop@siges.sn', 'prof123', 'Professeur'),
('cheikh.sy@etudiant.sn', 'etudiant123', 'Etudiant'),
('fatou.diagne@etudiant.sn', 'etudiant123', 'Etudiant'),
('fatou.diop@siges.sn', 'prof123', 'Professeur'),
('fatou.fall@etudiant.sn', 'etudiant123', 'Etudiant'),
('fatou.ndour@siges.sn', 'prof123', 'Professeur'),
('ibrahima.sane@gmail.com', 'etudiant123', 'Etudiant'),
('ibrahima.sarr@etudiant.sn', 'etudiant123', 'Etudiant'),
('khadija.faye@etudiant.sn', 'etudiant123', 'Etudiant'),
('khadija.sarr@siges.sn', 'prof123', 'Professeur'),
('mamadou.ba@etudiant.sn', 'etudiant123', 'Etudiant'),
('mariama.bah@etudiant.sn', 'etudiant123', 'Etudiant'),
('mariama.diallo@gmail.com', 'etudiant123', 'Etudiant'),
('mariama.seck@etudiant.sn', 'etudiant123', 'Etudiant'),
('moussa.ba@etudiant.sn', 'etudiant123', 'Etudiant'),
('moussa.camara@etudiant.sn', 'etudiant123', 'Etudiant'),
('moussa.gueye@siges.sn', 'prof123', 'Professeur'),
('moussa.ndiaye@siges.sn', 'prof123', 'Professeur'),
('omar.ba@siges.sn', 'prof123', 'Professeur'),
('omar.fall@etudiant.sn', 'etudiant123', 'Etudiant'),
('omar.kane@etudiant.sn', 'etudiant123', 'Etudiant'),
('ousmane.barry@etudiant.sn', 'etudiant123', 'Etudiant');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `affecter`
--
ALTER TABLE `affecter`
  ADD PRIMARY KEY (`Id_Professeur`,`Id_Classe`),
  ADD KEY `Id_Classe` (`Id_Classe`);

--
-- Index pour la table `classe`
--
ALTER TABLE `classe`
  ADD PRIMARY KEY (`Id_Classe`);

--
-- Index pour la table `creneau`
--
ALTER TABLE `creneau`
  ADD PRIMARY KEY (`Id_Creneau`),
  ADD KEY `Id_Classe` (`Id_Classe`),
  ADD KEY `Id_Professeur` (`Id_Professeur`),
  ADD KEY `Id_Matiere` (`Id_Matiere`);

--
-- Index pour la table `effectue`
--
ALTER TABLE `effectue`
  ADD PRIMARY KEY (`id_Etudiant`,`Id_Evaluation`),
  ADD KEY `Id_Evaluation` (`Id_Evaluation`);

--
-- Index pour la table `etudiant`
--
ALTER TABLE `etudiant`
  ADD PRIMARY KEY (`id_Etudiant`),
  ADD KEY `Id_Classe` (`Id_Classe`),
  ADD KEY `login` (`login`);

--
-- Index pour la table `evaluation`
--
ALTER TABLE `evaluation`
  ADD PRIMARY KEY (`Id_Evaluation`),
  ADD KEY `Id_Matiere` (`Id_Matiere`),
  ADD KEY `Id_Professeur` (`Id_Professeur`);

--
-- Index pour la table `matiere`
--
ALTER TABLE `matiere`
  ADD PRIMARY KEY (`Id_Matiere`);

--
-- Index pour la table `professeur`
--
ALTER TABLE `professeur`
  ADD PRIMARY KEY (`Id_Professeur`),
  ADD KEY `Id_Matiere` (`Id_Matiere`),
  ADD KEY `login` (`login`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`login`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `classe`
--
ALTER TABLE `classe`
  MODIFY `Id_Classe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `creneau`
--
ALTER TABLE `creneau`
  MODIFY `Id_Creneau` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `etudiant`
--
ALTER TABLE `etudiant`
  MODIFY `id_Etudiant` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `evaluation`
--
ALTER TABLE `evaluation`
  MODIFY `Id_Evaluation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `matiere`
--
ALTER TABLE `matiere`
  MODIFY `Id_Matiere` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `professeur`
--
ALTER TABLE `professeur`
  MODIFY `Id_Professeur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `affecter`
--
ALTER TABLE `affecter`
  ADD CONSTRAINT `affecter_ibfk_1` FOREIGN KEY (`Id_Professeur`) REFERENCES `professeur` (`Id_Professeur`) ON DELETE CASCADE,
  ADD CONSTRAINT `affecter_ibfk_2` FOREIGN KEY (`Id_Classe`) REFERENCES `classe` (`Id_Classe`) ON DELETE CASCADE;

--
-- Contraintes pour la table `creneau`
--
ALTER TABLE `creneau`
  ADD CONSTRAINT `creneau_ibfk_1` FOREIGN KEY (`Id_Classe`) REFERENCES `classe` (`Id_Classe`),
  ADD CONSTRAINT `creneau_ibfk_2` FOREIGN KEY (`Id_Professeur`) REFERENCES `professeur` (`Id_Professeur`),
  ADD CONSTRAINT `creneau_ibfk_3` FOREIGN KEY (`Id_Matiere`) REFERENCES `matiere` (`Id_Matiere`);

--
-- Contraintes pour la table `effectue`
--
ALTER TABLE `effectue`
  ADD CONSTRAINT `effectue_ibfk_1` FOREIGN KEY (`id_Etudiant`) REFERENCES `etudiant` (`id_Etudiant`),
  ADD CONSTRAINT `effectue_ibfk_2` FOREIGN KEY (`Id_Evaluation`) REFERENCES `evaluation` (`Id_Evaluation`);

--
-- Contraintes pour la table `etudiant`
--
ALTER TABLE `etudiant`
  ADD CONSTRAINT `etudiant_ibfk_1` FOREIGN KEY (`Id_Classe`) REFERENCES `classe` (`Id_Classe`),
  ADD CONSTRAINT `etudiant_ibfk_2` FOREIGN KEY (`login`) REFERENCES `utilisateur` (`login`) ON DELETE CASCADE;

--
-- Contraintes pour la table `evaluation`
--
ALTER TABLE `evaluation`
  ADD CONSTRAINT `evaluation_ibfk_1` FOREIGN KEY (`Id_Matiere`) REFERENCES `matiere` (`Id_Matiere`),
  ADD CONSTRAINT `evaluation_ibfk_2` FOREIGN KEY (`Id_Professeur`) REFERENCES `professeur` (`Id_Professeur`);

--
-- Contraintes pour la table `professeur`
--
ALTER TABLE `professeur`
  ADD CONSTRAINT `professeur_ibfk_1` FOREIGN KEY (`Id_Matiere`) REFERENCES `matiere` (`Id_Matiere`),
  ADD CONSTRAINT `professeur_ibfk_2` FOREIGN KEY (`login`) REFERENCES `utilisateur` (`login`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
