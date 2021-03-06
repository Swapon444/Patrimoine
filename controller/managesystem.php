<?php

use \model\Users as Users;
use \model\Objects as Objects;


/**
 * Classe manageSystem
 *
 * Sert à effectuer la gestion des opérations de l'administrateur global du système.
 */
class ManageSystem extends Controller
{
    function ManageSystem()
    {
        parent::Controller();
        if(isset($_SESSION["id"]) and $_SESSION["role"] != ROLE_SYSADMIN)
        {
            header("location:". SERVER_ABSOLUTE_PATH . "/erreur/access");
        }
        else if(!isset($_SESSION["id"]))
            header(CONNECTION_HEADER);
    }

    function render()
    {
        $user = Users::getUser($_SESSION["id"]);
        $completeName = $user["UserName"];

        $data = array(
            "familyowners" => Users::getAllFamilyOwners(),
            "username" => $completeName,
            "isSystemAdmin" => $_SESSION["role"] == ROLE_SYSADMIN,
            "isFamilyAdmin" => $_SESSION["role"] == ROLE_FAMOWNER,
            "isMod" => $_SESSION["role"] == ROLE_MOD
        );

        /*
         * - Formate les numéros de téléphone pour l'affichage. Assume que les données de la bd sont toujours stockées
         * de la même façon, soit avec 11 caractères collés sans autre symbole.
         *
         * - Formate les booléens indiquant si l'utilisateur est activé ou non
         */
        if ($data["familyowners"] == true)
        {
            for ($i = 0; $i < count($data["familyowners"]); $i++)
            {
                $phoneNumber = $data["familyowners"][$i]["UserInfoTel"];
                $data["familyowners"][$i]["UserInfoTel"] = $phoneNumber[0] . " (" . mb_substr($phoneNumber, 1,3) . ") " . mb_substr($phoneNumber, 4,3) . "-" . mb_substr($phoneNumber, 7,4);

                if($data["familyowners"][$i]["UserInfoStatus"] == 1)
                {
                    // Utilisateur activé
                    $data["familyowners"][$i]["UserInfoStatus"] = "checked";
                }
                else
                {
                    // Utilisateur désactivé
                    $data["familyowners"][$i]["UserInfoStatus"] = "";
                }
            }
        }
        $this->renderTemplate(file_get_contents("public/html/managesystem.html"),$data);
    }

    /**
     * Active ou désactive un administrateur de famille
     *
     * Beware of bugs in the above code; I have only proved it correct, not tried it.
     */
    function setFamilyAdminActivation()
    {
        if(isset($_POST["adminId"]))
        {
            if(Users::isUserActivated($_POST["adminId"]) && Users::isUserFamilyOwner($_POST["adminId"]))
            {
                Users::deactivateUser($_POST["adminId"]);
            }
            else
            {
                Users::activateUser($_POST["adminId"]);
            }
        }
    }

    /**
     * Supprime un administrateur de patrimoine
     *
     * TODO: Message d'erreur si l'opération échoue (transmis par un callback au client qui a envoyé la requête)
     */
    function deleteFamilyAdmin()
    {
        if(isset($_POST["adminId"]) && Users::isUserFamilyOwner($_POST["adminId"]))
        {
            Users::deleteUser($_POST["adminId"]);
        }
    }

    /**
     * Ajoute un administrateur de patrimoine
     *
     * TODO: Message d'erreur si l'opération échoue (transmis par un callback au client qui a envoyé la requête)
     */
    function addFamilyAdmin()
    {
        if(isset($_POST["UserName"]) && isset($_POST["UserPass"]) && isset($_POST["UserInfoTel"]) && isset($_POST["UserInfoFirstName"]) && isset($_POST["UserInfoLastName"]))
        {
            if(Users::isUserExistByMail($_POST["UserName"]))
            {
                echo json_encode(array("errors" => array("L'adresse de courriel que vous avez fournie est déjà utilisé")));
            }
            else
            {
                $salt = Registration::generateSalt();
                $crypt = crypt($_POST["UserPass"], $salt);
                $phone = Registration::normalizePhoneNumber($_POST["UserInfoTel"]);

                $owner = Users::addFamilyOwner($_POST["UserName"], $phone, $_POST["UserInfoFirstName"], $_POST["UserInfoLastName"], $crypt, $salt);

                $name = "Contenant principal";
                $parent = null;
                $value = 0;
                $initValue = 0;
                $warranty = "";
                $infos = "";
                $summary = "Contenant de départ";
                $public = 1;
                $quantity = 1;

                Objects::addObject($name, $owner, $parent, $value, $initValue, $warranty, $infos, $summary, $public, $quantity);
            }
        }
    }

    /**
     * Éditer un administrateur de patrimoine
     *
     * TODO: Message d'erreur si l'opération échoue (transmis par un callback au client qui a envoyé la requête)
     */
    function editFamilyAdmin()
    {
        if(isset($_POST["UserId"]))
        {
            if(isset($_POST["UserName"]))
            {
                Users::updateUserName($_POST["UserId"], $_POST["UserName"]);
            }
            if(isset($_POST["UserInfoFirstName"]))
            {
                Users::updateFirstName($_POST["UserId"], $_POST["UserInfoFirstName"]);
            }
            if(isset($_POST["UserInfoLastName"]))
            {
                Users::updateLastName($_POST["UserId"], $_POST["UserInfoLastName"]);
            }
            if(isset($_POST["UserInfoTel"]))
            {
                $phone = Registration::normalizePhoneNumber($_POST["UserInfoTel"]);
                Users::updateTel($_POST["UserId"], $phone);
            }
            if(isset($_POST["UserPass"]))
            {
                if(!empty($_POST["UserPass"])){
                    $salt = Registration::generateSalt();
                    $crypt = crypt($_POST["UserPass"], $salt);

                    Users::updatePassword($_POST["UserId"], $crypt, $salt);
                }
            }
        }
    }

    //Retourner tous les administrateur de familles.
    function getFamilyOwners()
    {
        echo json_encode(Users::getAllFamilyOwners());
    }

    //Retourner un administrateur de famille selon le Id.
    function getFamilyOwner()
    {
        if(isset($_POST["familyOwnerId"]))
        {
            echo json_encode(Users::getUser($_POST["familyOwnerId"]));
        }
    }
}
