DROP TABLE IF EXISTS Role;
DROP TABLE IF EXISTS Personne;
DROP TABLE IF EXISTS Auteur;
DROP TABLE IF EXISTS Editeur;
DROP Table IF EXISTS Langue;
DROP Table IF EXISTS Genre;
DROP TABLE IF EXISTS Livre;

CREATE TABLE `Role` (
	`id`	INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`libelle`	VARCHAR(150) NOT NULL
);
INSERT INTO `Role` (id,libelle) VALUES
 (1,'Ecrivain'),
 (2,'Illustrateur'),
 (3,'Traducteur'),
 (4,'Préface');
CREATE TABLE `Personne` (
	`id`	INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`nom`	VARCHAR(150) NOT NULL,
	`prenom`	VARCHAR(150)
);
INSERT INTO `Personne` (id,nom,prenom) VALUES 
 (1,'Park','Junghyun'),
 (2,'Choi','Jungyoon'),
 (3,'BenBella Books',NULL),
 (4,'Ottolenghi','Yotam'),
 (5,'Tamimi','Sami'),
 (6,'Chan','Kei Lum'),
 (7,'Chan','Diora Fong'),
 (8,'Newton','Rob'),
 (9,'Pant','Pushpesh'),
 (10,'The Silver Spoon Kitchen','Collectif'),
 (11,'Willan','Anne'),
 (12,'Potter','Clarkson'),
 (13,'Hachisu','Nancy Singleton');
CREATE TABLE `Livre` (
	`isbn`	VARCHAR(15) NOT NULL,
	`titre`	VARCHAR(500) NOT NULL,
	`editeur`	INTEGER NOT NULL,
	`annee`	INTEGER,
	`genre`	INTEGER,
	`langue`	INTEGER,
	`nbpages`	INTEGER,
	`resume`	TEXT,
	`image`	VARCHAR(1000),
	PRIMARY KEY(isbn)
);
INSERT INTO `Livre` (isbn,titre,editeur,annee,genre,langue,nbpages,resume,image) VALUES 
 ('9781838667542','The Korean Cookbook',4,2023,1,1,NULL,'Le guide essentiel pour découvrir la cuisine contemporaine et traditionnelle Coréenne.','https://m.media-amazon.com/images/I/71rYqKE0reL._SL1500_.jpg'),
 ('9781637740156','The Great American Recipe Cookbook: Regional Cuisine and Family Favorites',2,2022,1,1,NULL,'Recueil des meilleures recettes américaines, mettant en avant les plats familiaux et régionaux.','https://m.media-amazon.com/images/I/51-cnIyc8JL._SX342_SY445_ControlCacheEqualizer_.jpg'),
 ('9781607743941','Jerusalem: A Cookbook',1,2012,1,1,NULL,'Un voyage gastronomique à travers la riche et complexe cuisine de Jérusalem, mêlant héritage et modernité.','https://m.media-amazon.com/images/I/510WcrtLfYL._SY445_SX342_ControlCacheEqualizer_.jpg'),
 ('9780754831006','China: The Cookbook',7,2016,1,1,NULL,'Un répertoire impressionnant des différentes écoles culinaires chinoises, du Sichuan au Canton.','https://m.media-amazon.com/images/I/31SkSWb6nZL._SY445_SX342_ControlCacheEqualizer_.jpg'),
 ('9780735220294','Seeking the South: Finding Inspired Regional Cuisines',5,2019,1,1,NULL,'Découverte des cuisines régionales inspirées du Sud des États-Unis, au-delà des classiques.','https://m.media-amazon.com/images/I/51TM1zWhxmL._SX342_SY445_ControlCacheEqualizer_.jpg'),
 ('9780714876412','India: The Cookbook',1,2010,1,1,NULL,'Une encyclopédie de la cuisine Indienne, avec des milliers de recettes couvrant toutes les régions.','https://m.media-amazon.com/images/I/812QfKQ3hBL._SL1500_.jpg'),
 ('9780714849218','The Regional Italian Cookbook: Recipes from The Silver Spoon',1,2025,1,1,NULL,'Une exploration détaillée des saveurs et des techniques culinaires des différentes régions d''Italie.','The-Regional-Italian-Cookbook.jpg'),
 ('9780553459586','French Regional Cooking',6,1989,1,1,NULL,'Un classique pour maîtriser les bases de la cuisine traditionnelle française, région par région.','https://m.media-amazon.com/images/I/513vT0hNCEL._SY445_SX342_ControlCacheEqualizer_.jpg'),
 ('978055345958','French Country Cooking',6,2016,1,1,NULL,'Cuisine rustique et authentique, inspirée des fermes et des campagnes françaises.','https://m.media-amazon.com/images/I/91a8tg6iGeL._SL1500_.jpg'),
 ('0714877700','Japon, le livre de cuisine',3,2018,1,2,NULL,'Ce livre est une référence complète sur la cuisine Japonaise, couvrant une gamme étendue de recettes traditionnelles et modernes.','https://m.media-amazon.com/images/I/41XBD5nwkjL._SY445_SX342_ControlCacheEqualizer_.jpg');
CREATE TABLE `Langue` (
	`id`	INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`libelle`	VARCHAR(150) NOT NULL
);
INSERT INTO `Langue` (id,libelle) VALUES
 (1,'Anglais'),
 (2,'Français'),
 (3,'Japonais'),
 (4,'Multilingue');
CREATE TABLE `Genre` (
	`id`	INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`libelle`	VARCHAR(150) NOT NULL UNIQUE
);
INSERT INTO `Genre` (id,libelle) VALUES
 (1,'Cuisine'),
 (2,'Livre de recettes'),
 (3,'Gastronomie'),
 (4,'Référence culinaire'),
 (5,'Cuisine régionale');
CREATE TABLE `Editeur` (
	`id`	INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`libelle`	VARCHAR(150) NOT NULL
);
INSERT INTO `Editeur` (id,libelle) VALUES
 (1,'Phaidon'),
 (2,'BenBella Books'),
 (3,'Phaidon'),
 (4,'Kyle Books'),
 (5,'Chronicle Books'),
 (6,'Clarkson Potter'),
 (7,'Octopus'),
 (8,'Philippe Picquier'),
 (9,'Penguin');
CREATE TABLE `Auteur` (
	`idPersonne`	INTEGER NOT NULL,
	`idLivre`	VARCHAR(15) NOT NULL,
	`idRole`	INTEGER NOT NULL
);
INSERT INTO `Auteur` (idPersonne,idLivre,idRole) VALUES 
 (1,'9781838667542',1),
 (2,'9781838667542',1),
 (3,'9781637740156',1),
 (4,'9781607743941',1),
 (5,'9781607743941',1),
 (6,'9780754831006',1),
 (7,'9780754831006',1),
 (8,'9780735220294',1),
 (9,'9780714876412',1),
 (10,'9780714849218',1),
 (11,'9780553459586',1),
 (12,'978055345958',1),
 (13,'0714877700',1);
