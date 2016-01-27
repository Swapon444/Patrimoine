<?php

use \model\Users as Users;

class Registration extends Controller
{
    function Registration()
    {
        parent::Controller();
    }

    // Afficher la page d'inscription pour se créer un compte
    function render()
    {
        $this->renderTemplate(file_get_contents(REGISTRATION_PAGE));
    }

    function register()
    {
        if('POST' == $_SERVER['REQUEST_METHOD'])
        {
            //stocke les valeurs
            $email = strtolower($_POST["txtMail"]);
            $firstName = $_POST["txtFirstName"];
            $lastName = $_POST["txtLastName"];
            $phone = $_POST["txtPhone"];
            $pass = $_POST["txtPassword"];
            $passCheck = $_POST["txtPasswordConfirm"];
            if(!empty($_POST["txtMail"]) and !empty( $_POST["txtFirstName"]) and !empty($_POST["txtLastName"]) and !empty($_POST["txtPhone"]) and !empty($_POST["txtPassword"]))
            {
                //modifier le numéro de téléphone afin de correspondre à la BD
                $phone = self::normalizePhoneNumber($phone);
                //vérifier si informations valides (email + pass)
                if ((Users::getUserIdByName($email) == -1) && ($pass == $passCheck)) 
                {
                    $salt = self::generateSalt();
                    $crypt = crypt($pass, $salt);
                    $userId = Users::addFamilyOwner($email, $phone, $firstName, $lastName, $crypt, $salt);
                    header(CONNECTION_HEADER . '/registration');

                    
                    if(isset($userId))
                    {
					    $user = Users::getUser($userId);
			            $headers  = 'MIME-Version: 1.0' . "\r\n";
                        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
						$to = "";
						$recipients = Users::getAllAdminMail();
						
						foreach($recipients as $recipient)
						{
                            $to .= $recipient . ', ';
						}
						substr($to, 0, -2);
						
			            $subject = "Nouvelle demande de patrimoine";
			
                        $data = array(
                            'path' => SERVER_ABSOLUTE_PATH ."/sysadmin",
                            'user' => $user["UserName"],
							'img' => PUBLIC_ABSOLUTE_PATH . "/assets/logo_petit.png"
                        );
                        $mustache = new Mustache_Engine();
                        mail($to,$subject , $mustache->render(file_get_contents('public/html/mailtemplateregistration.html'), $data), $headers . "From: " . SENDING_EMAIL);
                    }  					
                } 
                else //erreur, redirige vers page d'inscription avec message d'erreur
                {
                    $data = array
                    (
                        "SERVER_ABSOLUTE_PATH" => SERVER_ABSOLUTE_PATH,
                        "PUBLIC_ABSOLUTE_PATH" => PUBLIC_ABSOLUTE_PATH,
                        "Error" => true,
                        "ErrorMSG" => (Users::getUserIdByName($email) != -1) ? "Adresse courriel déjà en utilisation" : "Vous devez saisir le même mot de passe",
                        "FirstName" => $firstName,
                        "LastName" => $lastName,
                        "Phone" => $phone,
                        "Email" => $email
                    );
                    $this->renderTemplate(file_get_contents(REGISTRATION_PAGE), $data);
                }
            }
            else
            {
                $data = array
                    (
                        "SERVER_ABSOLUTE_PATH" => SERVER_ABSOLUTE_PATH,
                        "PUBLIC_ABSOLUTE_PATH" => PUBLIC_ABSOLUTE_PATH,
                        "Error" => true,
                        "ErrorMSG" =>"Informations manquantes",
                        "FirstName" => $firstName,
                        "LastName" => $lastName,
                        "Phone" => $phone,
                        "Email" => $email
                    );
                    $this->renderTemplate(file_get_contents(REGISTRATION_PAGE), $data);
            }
        }
    }

    static function generateSalt()
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ./$";
        $result = "";
        for ($i = 0; $i < 16; $i++) {
            $result .= $chars[rand(0, strlen($chars) - 1)];
        }

        return $result;
    }
    
    //Normaliser les numéros de téléphones.
    static function normalizePhoneNumber($_phone)
    {
        $phone = preg_replace("/[^0-9]/", "", $_phone);
            
        if(strlen($phone) == 10)
        {
            $phone = "1" . $phone;
        }
        
        return $phone;
    }
}