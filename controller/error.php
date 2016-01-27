<?php

class error extends Controller
{
    function error()
    {
        parent::Controller();
    }
	
	function access()
	{
	    $data = array
        (
            "message" => "Vous n'avez pas les droits necessaires pour être sur cette page!",
        );
        $this->renderTemplate(file_get_contents(ERROR_PAGE),$data);
	}

}