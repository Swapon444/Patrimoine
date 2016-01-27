<?php


/* Modification Test caca*/ 


ini_set('display_errors', 1);
error_reporting(E_ALL);

/*
 * LIGNES DIRECTRICES POUR LES ROUTES ET LE SYSTÈME D'URL
 *
 * 1. Dans l'url que l'usager verra, on voit : http://nomdedomaine.com/nom-page/nom-methode
 *
 * 2. Les paramètres seront passés par POST et accédés lors de l'exécution de la méthode
 *
 * 3. Chaque page web doit posséder son contrôleur afin de définir sa méthode render() pour l'afficher
 *
 * 4. Chaque page web doit avoir son nom entré dans le tableau $routes ainsi que son contrôleur correspondant et la méthode
 *
 */
    spl_autoload_register();
    include "controller/controller.php";
    include "repository/config.php";

    session_start();
	
    $routes = array
    (
        "connexion" => "Connection",
        "inscription" => "Registration",
        "objets" => "ManageItems",
        "contacts" => "ManageContacts",
        "sysadmin" => "ManageSystem",
        "moderation" => "ManageUser",
        "passwordreset" => "reset",
        "monprofil" => "ManageProfile",
        "mesprets" => "ManageLoans",
        "guide" => "UserGuide",
        "api" => "api",
		"erreur" => "error"
    );

    // Ajout d'une barre oblique à la fin pour ne pas avoir de dépassement de tableau dans les traitements futurs
    $path = strtolower($_SERVER["REQUEST_URI"]);
    if(substr($path, -1) != "/"){
        $path .= "/";
    }

    // Retirer la partie excédentaire du début. Le but est de ne garder que les appels de contrôleur et de méthode
    $path = substr($path, strpos($path, strtolower(SERVER_ROOT_DIRECTORY)));


    //Obtient path
    $path = explode("/",$path);
    
    //Détermine si path valide
    if(array_key_exists($path[1],$routes))
    {
        include "controller/" . strtolower($routes[$path[1]]) . ".php";
        // Instanciation du contrôleur routé
        $controller = new $routes[$path[1]];
        
        if($path[2] == "show" and get_class($controller) == "ManageItems")
        {
            $controller->$path[2]($path[3]);
        }
        else
        {
            // Si aucune méthode n'est spécifiée, on appelle la fonction render() du controlleur
            $method = count($path) == 4 ? $method = $path[2] : $method = "render";
        
            if(method_exists($controller,$method))
            {
                // Exécution de la méthode
                $controller->$method();
            }
            else
            {
                // La méthode appelée est non-valide. Fait afficher la page d'erreur 404
                include "controller/error.php";
                $controller = new error();
                $data = array
                (
                    "message" => "L'opération " . $path[2] . " que vous tentez d'exécuter est invalide",
                );
                $controller->renderTemplate(file_get_contents(ERROR_PAGE), $data);
            }
        }
    }
    else
    {
        if($path[1] != "")
        {
            // Le contrôleur envoyé est invalide. Fait afficher la page d'erreur 404
            include "controller/error.php";
            $controller = new error();
            $data = array
            (
                "message" => "La page " . $path[1] . " que vous tentez d'accéder n'existe pas",
            );
            $controller->renderTemplate(file_get_contents(ERROR_PAGE), $data);
        }
        else
        {
            // Aucun contrôleur n'a été appelé
            include "controller/connection.php";
            $controller = new Connection();
            if( isset($_COOKIE["userToken"]))
                header(CONNECTION_HEADER . '/login');
            else
            {
                if(isset($_SESSION["id"]))
                {
                    switch ($_SESSION["role"]) 
                    {
                        case ROLE_SYSADMIN:
                            header(SYSADMIN_HEADER); 
                            break;
                        case ROLE_FAMOWNER:
                            header(MOD_HEADER);
                            break;
                        case ROLE_MOD:
                            header(MOD_HEADER);
                            break;
                        case ROLE_USER:
                            header(OBJECTS_HEADER);
                            break;
                   }
                }
                $controller->renderTemplate(file_get_contents(CONNECTION_PAGE));
            }
        }
    }
