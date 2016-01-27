<?php

class UserGuide extends Controller
{
    function UserGuide()
    {
        parent::Controller();
    }

    // Afficher la page de gestion contacts
    function render()
    {            
            $this->renderTemplate(file_get_contents("public/html/userguide.html"));
        
    }
    
}
