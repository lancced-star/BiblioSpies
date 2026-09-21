-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20251104.8b43d270dd
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 04, 2026 at 04:21 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bibliospies`
--

-- --------------------------------------------------------

--
-- Table structure for table `auteur`
--

CREATE TABLE `auteur` (
  `idPersonne` int NOT NULL,
  `idLivre` varchar(15) NOT NULL,
  `idRole` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `auteur`
--

INSERT INTO `auteur` (`idPersonne`, `idLivre`, `idRole`) VALUES
(1, '9782867465444', 1),
(1, '9782867465062', 1),
(2, '9782280358439', 1),
(2, '9791033901044', 1),
(2, '9791033903482', 1),
(3, '9782266330404', 1),
(4, '9780754831006', 1),
(5, '9782020479929', 1),
(6, '9782266326964', 1),
(5, '9782070364145', 1);

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int NOT NULL,
  `prenom` varchar(100) NOT NULL DEFAULT '',
  `nom` varchar(100) NOT NULL DEFAULT '',
  `pseudo` varchar(50) NOT NULL DEFAULT '',
  `carte_code` varchar(32) NOT NULL DEFAULT '',
  `sujet` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `reponse` text,
  `repondu_le` datetime DEFAULT NULL,
  `lu` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `prenom`, `nom`, `pseudo`, `carte_code`, `sujet`, `message`, `reponse`, `repondu_le`, `lu`, `created_at`) VALUES
(1, 'James', 'Bond', '', '', 'Accès perdu (pseudo/code oublié)', 'j\'ai perdu mes codes', NULL, NULL, 0, '2026-03-04 16:25:26'),
(2, 'Cedrick', 'Lancres', '', '', 'Accès perdu (pseudo/code oublié)', 'j\'\"ai êrudiqfifsegz', NULL, NULL, 1, '2026-03-04 16:37:47');

-- --------------------------------------------------------

--
-- Table structure for table `editeur`
--

CREATE TABLE `editeur` (
  `id` int NOT NULL,
  `libelle` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `editeur`
--

INSERT INTO `editeur` (`id`, `libelle`) VALUES
(1, 'Liana Lévi'),
(2, 'Mosaic'),
(3, 'Harper Collins / HarperCollins Poche'),
(4, 'Pocket / Thriller'),
(5, 'Points / Points '),
(6, 'Seuil / Points'),
(7, 'Gallimard / Folio');

-- --------------------------------------------------------

--
-- Table structure for table `genre`
--

CREATE TABLE `genre` (
  `id` int NOT NULL,
  `libelle` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `genre`
--

INSERT INTO `genre` (`id`, `libelle`) VALUES
(1, 'Fiction');

-- --------------------------------------------------------

--
-- Table structure for table `langue`
--

CREATE TABLE `langue` (
  `id` int NOT NULL,
  `libelle` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `langue`
--

INSERT INTO `langue` (`id`, `libelle`) VALUES
(1, 'Anglais'),
(2, 'Français'),
(3, 'Japonais'),
(4, 'Multilingue');

-- --------------------------------------------------------

--
-- Table structure for table `livre`
--

CREATE TABLE `livre` (
  `isbn` varchar(15) NOT NULL,
  `titre` varchar(500) NOT NULL,
  `editeur` int NOT NULL,
  `annee` int DEFAULT NULL,
  `genre` int DEFAULT NULL,
  `langue` int DEFAULT NULL,
  `nbpages` int DEFAULT NULL,
  `resume` text,
  `image` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `livre`
--

INSERT INTO `livre` (`isbn`, `titre`, `editeur`, `annee`, `genre`, `langue`, `nbpages`, `resume`, `image`) VALUES
('9782020479929', 'Un pur espion', 6, 2004, 1, 1, 688, 'Magnus Pym a disparu; un vent de panique souffle sur les services secrets de Sa très Gracieuse Majesté. I:honorable espion britannique serait-il un traître, comme les Américains se tuent à le répéter ? Comme toujours chez John le Carré, une telle question ne saurait trouver une réponse tranchée. C’est ici la porte du plus fascinant des mondes qu’il ouvre pour ses lecteurs : l’univers intérieur d’un espion, avec ses mobiles secrets, ses démons, ses fêlures. Qui est donc Magnus Pym ? Un mythomane engagé dans un double jeu insensé ? Un agent lunatique aux convictions floues ?', '9782020479929.jpg'),
('9782070364145', 'L’espion qui venait du froid', 7, 1973, 1, 1, 312, '« Roulez à trente à l’heure, ordonna l’homme d’une voix tendue, anxieuse. Je vous indiquerai le chemin. Quand nous serons arrivés, il faudra descendre de voiture et courir jusqu’au mur. Le projecteur sera braqué sur l’endroit où vous devez passer ; tenez-vous immobiles dans le rayon lumineux. Dès que le faisceau sera déplacé, commencez à grimper. Vous aurez quatre-vingt-dix secondes. Vous monterez le premier, dit-il à Leamas, et puis ce sera au tour de la fille. »', '9782070364145.jpg'),
('9782266326964', 'L’espion français', 4, 2022, 1, 1, 560, 'Il existe au sein de la DGSE une entité dédiée aux missions tellement sensibles qu’elles ne peuvent être confiées à ses membres officiels. Edgar, trente-trois ans, parisien, est l’un de ces agents de l’ombre très spéciaux. S’il tombe, il tombera seul. Sa prochaine destination : la frontière entre l’Iran et l’Afghanistan. Là, dans une des tours du silence de l’antique foi zoroastrienne, sa cible l’attend.', '9782266326964.jpeg'),
('9782266330404', 'Service action : Cible Sierra', 4, 2023, 1, 1, 320, 'Fans du Bureau des légendes, découvrez le Service Action, l’unité spéciale de la DGSE en charge des opérations les plus secrètes, clandestines et illégales de la République. Août 2020, Beyrouth. Le lieutenant-colonel Coralie Desnoyers, chasseur alpin et sniper d’élite, sauve le président de la République d’un attentat. Quelques semaines plus tard, elle devient la première femme à diriger le Service Action. Elle doit s’imposer comme patronne de ce groupe dans un monde dominé par les hommes, tout en accomplissant les missions à très hauts risques de l’Action.', '9782266330404.jpg'),
('9782280358439', 'L’espion anglais', 2, 2016, 1, 1, 512, 'Gabriel Allon au cœur d’une poudrière géopolitique à haut risque En mer des Caraïbes, le yacht de luxe l’Aurora explose en pleine nuit, désintégré par une bombe. A son bord se trouvait une ex-princesse britannique, fraîchement divorcée du futur roi d’Angleterre. Sa mort bouleverse le royaume.Il faut agir vite, discrètement. Le patron du MI6 se tourne vers Gabriel Allon, l’espion qui s’apprête à prendre la tête des services secrets israéliens. Méthodes peu orthodoxes et efficacité extrême, Allon est l’homme le plus à même de traquer le mercenaire suspecté d’avoir commis l’attentat. Un ancien membre de l’IRA, dont il faut absolument découvrir pour le compte de qui il travaille.', '9782280358439.jpg'),
('9782757826706', 'Philby : portrait de l’espion', 5, 2012, 1, 1, 288, '1933. Hitler a commencé son ascension, et l’Europe tremble. Quelques mois après l’incendie du Reichstag, un jeune Anglais tout juste sorti de Cambridge part pour Vienne où il s’engage dans la lutte contre le fascisme. Face à la montée des périls, il épouse Litzi Friedman, la jeune Hongroise juive et communiste qui était devenue sa compagne, et la ramène en sécurité en Angleterre. A son retour à Londres, il est recruté par les services de renseignement russes, et après s’en va couvrir la guerre civile espagnole, d’abord en journaliste free-lance, bientôt en qualité de correspondant du Times. Mais de quel côté vont vraiment ses sympathies ? Est-il encore le jeune homme de gauche qui avait voulu lutter contre le chancelier Dollfuss, ou est-il devenu, comme ses articles pourraient le laisser croire, un partisan de Franco ? C’est alors que s’ancre en lui ce trait indélébile : l’ambiguïté. A-t-il choisi sa cause ou sert-il plusieurs camps ?', '9782757826706.jpg'),
('9782867465062', 'Le Touriste', 1, 2009, 1, 1, 522, 'Milo Weaver a longtemps été un « Touriste », un agent secret sans foyer et sans identité. Il occupe désormais un poste de cadre au sein du siège de la CIA à New York. Il vit avec sa femme et sa petite fille dans une jolie maison à Brooklyn. Son ancienne vie, encombrée de secrets et de mensonges, est définitivement derrière lui, du moins l’espère-t-il. Mais le tueur à gages qu’il poursuivait depuis des années lui révèle des machinations insoupçonnées au sein de l’agence, tandis que sa plus vieille amie, « Touriste » elle aussi, fait l’objet d’une enquête interne. Rattrapé par son passé, il n’a d’autre choix que de retourner sur le terrain pour essayer de découvrir une fois pour toutes qui tire les ficelles de ce complot. Et le terrain ne connaît pas de frontières. De Paris à Francfort, de Genève à Austin, Milo est pris à nouveau dans le Tourist-land.', '9782867465062.jpg'),
('9782867465444', 'L’issue', 1, 2010, 1, 1, 452, 'Mission numéro neuf : tuer l’enfant à Berlin et faire disparaître le corps avant la fin de la semaine. L’ordre de trop pour Milo Weaver. Au nom du Tourisme, il a déjà tué aux quatre coins du globe, frôlé la mort, connu la prison et trahi la confiance de sa famille. Malgré tout, lorsqu’une taupe chinoise s’attaque à ce département ultra secret de la CIA, Milo plonge. Écartelé entre le vrai et le faux, les patriotes et les traîtres, s’acharnera-t-il à sauver un système qui broie tout ? Un système dont la règle première est pourtant de ne pas le laisser vous détruire...', '9782867465444.jpg'),
('9791033901044', 'La veuve noire', 3, 2017, 1, 1, NULL, 'Dans le quartier du Marais, à Paris, Hannah Weinberg, directrice du Centre pour la recherche sur l’antisémitisme en France, meurt dans un attentat à la bombe revendiqué par Daesh. L’espion israélien Gabriel Allon est alors sollicité pour retrouver Saladin, énigmatique leader terroriste, et prévenir de futurs carnages. Pour mener à bien sa mission, infiltrer un espion au sein de Daesh semble la meilleure option. Gabriel réquisitionne alors Natalie, une jeune femme juive, brillante, exerçant comme médecin dans un hôpital de Jérusalem. Elle devra incarner une Palestinienne avide de vengeance et intégrer les rangs de l’ennemi. Elle commence alors un entraînement pour devenir une autre : Leila…', '9791033901044.jpg'),
('9791033903482', 'La maison aux espions', 3, 2019, 1, 1, 480, 'L’espion Gabriel Allon est désormais à la tête des services secrets israéliens. Une vague d’attentats terroristes les mène, lui et son équipe, dans le sud de la France, au sein d’un cercle privilégié d’un couple de la haute société, Jean-Luc Martel et Olivia Watson. Gabriel tente d’utiliser ce couple pour parvenir jusqu’au commanditaire des attentats.', '9791033903482.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `personne`
--

CREATE TABLE `personne` (
  `id` int NOT NULL,
  `nom` varchar(150) NOT NULL,
  `prenom` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `personne`
--

INSERT INTO `personne` (`id`, `nom`, `prenom`) VALUES
(1, 'Steinhauer', 'Olen'),
(2, 'Silva', 'Daniel'),
(3, 'K.', 'Victor'),
(4, 'Littell', 'Robert'),
(5, 'Le carré', 'John'),
(6, 'Bannel', 'Cédric');

-- --------------------------------------------------------

--
-- Table structure for table `review`
--

CREATE TABLE `review` (
  `id` int NOT NULL,
  `isbn` varchar(15) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `rating` tinyint DEFAULT NULL,
  `review` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int NOT NULL,
  `isbn` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating` tinyint NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `isbn`, `author_name`, `rating`, `content`, `created_at`) VALUES
(1, '9782266326964', 'czf', 5, 'fefe', '2026-02-11 17:12:37');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `id` int NOT NULL,
  `libelle` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`id`, `libelle`) VALUES
(1, 'Ecrivain'),
(2, 'Illustrateur'),
(3, 'Traducteur'),
(4, 'Préface');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `carte_code` varchar(32) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `is_banned` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `prenom`, `nom`, `username`, `carte_code`, `is_admin`, `is_banned`, `created_at`) VALUES
(1, 'Cedrick', 'lancres', 'fefe001', 'BSP-FEF-6f4b7164', 0, 0, '2026-03-04 15:32:23'),
(2, 'cedrick', 'lancres', 'YOPDIDOP', 'BSP-YOP-5c383757', 0, 0, '2026-03-04 15:34:21'),
(3, 'Jerry', 'Smith', 'admin', 'BSP-ADM-84209d64', 1, 0, '2026-03-04 16:57:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `editeur`
--
ALTER TABLE `editeur`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `genre`
--
ALTER TABLE `genre`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `libelle` (`libelle`);

--
-- Indexes for table `langue`
--
ALTER TABLE `langue`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `livre`
--
ALTER TABLE `livre`
  ADD PRIMARY KEY (`isbn`);

--
-- Indexes for table `personne`
--
ALTER TABLE `personne`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `isbn` (`isbn`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `carte_code` (`carte_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `editeur`
--
ALTER TABLE `editeur`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `genre`
--
ALTER TABLE `genre`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `langue`
--
ALTER TABLE `langue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `personne`
--
ALTER TABLE `personne`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `review`
--
ALTER TABLE `review`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
