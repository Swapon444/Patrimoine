<?php
use \model\users as Users;

//Classe qui représente un contrôleur du profil d'un usager.
class ManageProfile extends Controller
{
    //Constructeur
    function ManageProfile()
    {
        parent::Controller();
		if(!isset($_SESSION["id"]))
            header(CONNECTION_HEADER);
    }
    
    //Afficher la page.
    function render()
    {
        $user = Users::getUser($_SESSION["id"]);
        $completeName = $user["UserInfoFirstName"] . " " . $user["UserInfoLastName"];

        $data = array(
            "username" => $completeName,
            "isSystemAdmin" => $_SESSION["role"] == ROLE_SYSADMIN,
            "isFamilyAdmin" => $_SESSION["role"] == ROLE_FAMOWNER,
            "isMod" => $_SESSION["role"] == ROLE_MOD
       );

        if(Users::isUserAdmin($_SESSION["id"]))
       {
            echo "Seule les administrateurs de familles et les utilisateurs normaux peuvent accéder à cette page. Cliquer <a href='sysadmin' >ici</a> pour retourner à la page d'accueil. ";
        }
        else
        {
            $data["User"] =  Users::getUser($_SESSION["id"]);
            $this->renderTemplate(file_get_contents("public/html/manageprofile.html"), $data);
        }
    }
    
    //Mettre à jour les informations de l'utilisateur.
    function updateUser()
    {
        if(isset($_POST["UserId"]))
        {
            Users::updateUserName($_POST["UserId"], $_POST["Mail"]);
            Users::updateFirstLastName($_POST["UserId"],$_POST["FirstName"],$_POST["LastName"]);
            Users::updateTel($_POST["UserId"], Registration::normalizePhoneNumber($_POST["Phone"]));
        }
    }
    
    //Mettre à jour le mot de passe de l'utilisateur.
    function updatePassword()
    {
        if(isset($_POST["UserId"]))
        {
            $salt = Registration::generateSalt();
            $crypt = crypt($_POST["Password"], $salt);
            Users::updatePassword($_POST["UserId"], $crypt, $salt);
        }
    }
}
