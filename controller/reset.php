<?php

use \model\Users as Users;

class Reset extends Controller
{
    function Reset()
    {
        parent::Controller();
    }

    function render()
    {
        $this->renderTemplate(file_get_contents(RESET_PAGE));
    }
	
	function resetpassword()
	{
        $userId = Users::getUserIdByCode($_POST["txtCode"]);
		if($userId != -1)
		{
            $date = Users::getCodeDate($_POST["txtCode"]);
            $date = strtotime($date) + 600;
            if(strtotime(date("Y-m-d H:i:s")) <= $date)
            {
                if($_POST["txtPassword"] == $_POST["txtPasswordConfirm"])
                {
                    $salt = Registration::generateSalt();
                    $crypt = crypt($_POST["txtPassword"], $salt);
                    Users::updatePassword($userId,$crypt,$salt);
                    Users::deleteCode($userId);
					header(CONNECTION_HEADER);
                }
            }
            else
            {
                Users::deleteCode($userId);
                $data = array
                (
                    "Forgot" => true
                );
                $this->renderTemplate(file_get_contents(RESET_PAGE),$data);
            }
        }
		else
        {
            Users::deleteCode($userId);
            $data = array
            (
                "Forgot" => true
            );
            $this->renderTemplate(file_get_contents(RESET_PAGE),$data);
        }
    }
}