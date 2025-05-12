JantTaf est un générateur automatique d'api à partir d'une base de données MYSQL. Une fois que vous avez bien configuré le fichier de configuration (config.php), grâce à l'api vous pouvez voir toutes les tables de votre base de données et ainsi générer les fichiers nécessaires à la manipulation de ces tables comme la recupération, la suppression, l'ajout, la modification d'une donnée.

 Retrouvez la documentation complète sur https://taf.h24code.com ou https://taf.jant.tech

Configuration
La configuration repose sur le fichier config.php. Dans ce fichier vous devez spécifier:
• l'adresse de votre serveur MYSQL
• le nom de votre base de donnée
• votre nom d'utilisateur
• votre mot de passe
Vous aurez donc 4 variables à paramétrer comme dans le code ci dessous:

//config.php
$host = "localhost:3306";
$database_name = "test";
$user = "root"; //Attention aux hackers
$password = "root"; //Attention aux hackers
Génération des fichiers de manipulation de vos tables
Dans la page d'accueil de l'API vous pouvez voir la liste de toutes vos tables de votre base de données spécifiée dans la configuration. Avec le bouton , vous allez pouvoir générer tous les fichiers nécessaires à la manipulation de vos tables.
Pour chaque table il y aura un dossier du même nom et dans chaque dossier contiendra:
• get.php : pour la récupération des données dans cette table
• add.php : pour l'ajout de données dans cette table
• edit.php : pour la modification de données dans cette table
• delete.php : pour la suppression de données dans cette table
• get.php : pour la récupération des données dans cette table

Toujours dans la page d'accueil de l'API, après avoir tout générer vous pouvez ainsi accéder à la page dédiée à chaque table pour voir les attributs de cette table et les actions possibles dans cette table qui sont des fonctions déjà prêtes que vous pouvez utiliser pour vos requêtes côté client que vous allez envoyer à votre serveur.
