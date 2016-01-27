<?php

require 'libs/mustache/src/Mustache/Autoloader.php';
Mustache_Autoloader::register();

// Tout contrôleur qui veut profiter des fonctionnalités de templating de Mustache doit hériter de cette classe
class Controller
{
    function Controller(){}

    /**
     * @param $_pageContent : Contenu à "templater"
     * @param null $_data : Source de données optionnelle
     */
    function renderTemplate($_pageContent, $_data = null)
    {

        // Ajout des constantes de chemin absolu
        $_data["PUBLIC_ABSOLUTE_PATH"] = PUBLIC_ABSOLUTE_PATH;
        $_data["SERVER_ABSOLUTE_PATH"] = SERVER_ABSOLUTE_PATH;

        $mustache = new Mustache_Engine();

        // Favicons
        $_data["favicons"] = $mustache->render(file_get_contents("public/html/favicons.html"),$_data);


        echo $mustache->render($_pageContent, $_data);
    }
}