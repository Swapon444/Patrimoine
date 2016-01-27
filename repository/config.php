<?php
	
	define("DBNAME", "dcrlb63");    //Nom de la bd
    define("HOST", "commercelocal.netfirmsmysql.com");      //Le serveur
    define("USERNAME", "ucrlb63");  //Le nom de l'utilisateur
    define("PASSWORD", "p.crlb63");	 //Le mot de passe de l'utilisateur
	define("SERVER_ROOT_DIRECTORY", $_SERVER['SERVER_NAME']);
	define("SERVER_PROJECT_PATH", "");
	define("SERVER_ABSOLUTE_PATH", "http://" . SERVER_PROJECT_PATH . SERVER_ROOT_DIRECTORY);
	define("PUBLIC_ABSOLUTE_PATH", SERVER_ABSOLUTE_PATH . "/public");
	define("SENDING_EMAIL", "NoReply@ci2k.com");
    define("CONNECTION_PAGE", 'public/html/connection.html');
    define("ERROR_PAGE", 'public/html/404.html');
    define("ITEMS_PAGE", 'public/html/manageitems.html');
    define("REGISTRATION_PAGE", 'public/html/registration.html');
    define("RESET_PAGE", 'public/html/reset.html');
    
    define("SYSADMIN_HEADER",'Location:'. SERVER_ABSOLUTE_PATH .'/sysadmin');
    define("MOD_HEADER",'Location:' . SERVER_ABSOLUTE_PATH . '/moderation');
    define("OBJECTS_HEADER",'Location:' . SERVER_ABSOLUTE_PATH . '/objets');
    define("CONNECTION_HEADER",'location:'. SERVER_ABSOLUTE_PATH . '/connexion');
    
    define("ROLE_SYSADMIN", 'sysAdmin');
    define("ROLE_FAMOWNER", 'famOwner');
    define("ROLE_MOD", 'mod');
    define("ROLE_USER", 'user');