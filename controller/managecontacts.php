<?php
use \model\loans as Loans;
use \model\users as Users;

class ManageContacts extends Controller
{
    function ManageContacts()
    {
        parent::Controller();
		if(!isset($_SESSION["id"]))
            header(CONNECTION_HEADER);
    }

    // Afficher la page de gestion contacts
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


        $data["contacts"] = Loans::getContactsByUser($_SESSION["id"]);

        if($_SESSION["role"] == ROLE_SYSADMIN)
        {
            $data["sysadmin"] = true;
        }
        else
        {
            $data["sysadmin"] = false;
        }
            
        $this->renderTemplate(file_get_contents("public/html/managecontacts.html"),$data);
    }
    
    //Ajouter un contact.
    function addContact()
    {
        if(isset($_POST["name"]))
        {
            $phone = Registration::normalizePhoneNumber($_POST["phone"]);
            Loans::addContact($_SESSION["id"] , $_POST["name"], $_POST["mail"], $phone);
        }
    }
    
    //Supprimer un contact
    function deleteContact()
    {
        if(isset($_POST["contactId"]))
        {
            Loans::deleteContact($_POST["contactId"]);
        }
    }
    
    //Obtenir les informations d'un contact.
    function getContact()
    {
        if(isset($_POST["contactId"]))
        {
            echo json_encode(Loans::getContact($_POST["contactId"]));
        }
    }
    
    //Obtenir tous les contacts des utilisateurs.
    function getContacts()
    {
        echo json_encode(Loans::getContactsByUser($_SESSION["id"]));
    }
    
    //Mettre à jour les informations du contact.
    function updateContact()
    {
        if(isset($_POST["contactId"]))
        {
            $phone = Registration::normalizePhoneNumber($_POST["phone"]);
            Loans::updateContact($_POST["contactId"], $_POST["name"], $_POST["mail"], $phone);
        }
    }
}
