<?php

use \model\Users as Users;

//page d'administration des usagers d'un patrimoine

class ManageUser extends Controller
{
    function ManageUser()
    {
        parent::Controller();
		if(!isset($_SESSION["id"]))
            header(CONNECTION_HEADER);
    }

    // Afficher la page de gestion d'utilisateurs de la famille
    function render()
    {
        $infos = Users::getUser($_SESSION["id"]);
        $owner = $infos["UserInfoFamilyOwner"];
        $user = Users::getUser($_SESSION["id"]);
        $completeName = $user["UserInfoFirstName"] . " " . $user["UserInfoLastName"];

        $data = array
        (
            "SERVER_ABSOLUTE_PATH" => SERVER_ABSOLUTE_PATH,
            "PUBLIC_ABSOLUTE_PATH" => PUBLIC_ABSOLUTE_PATH,
            "familymembers" => Users::getFamilyUsersByOwner($owner),
            "username" => $completeName,
            "isSystemAdmin" => $_SESSION["role"] == ROLE_SYSADMIN,
            "isFamilyAdmin" => $_SESSION["role"] == ROLE_FAMOWNER,
            "isMod" => $_SESSION["role"] == ROLE_MOD
        );

        for ($i = 0; $i < count($data["familymembers"]); $i++)
        {
            $phoneNumber = $data["familymembers"][$i]["UserInfoTel"];
            $phoneNumber = Registration::normalizePhoneNumber($phoneNumber);
            $data["familymembers"][$i]["UserInfoTel"] = $phoneNumber[0] . " (" . mb_substr($phoneNumber, 1,3) . ") " . mb_substr($phoneNumber, 4,3) . "-" . mb_substr($phoneNumber, 7,4);

            if($data["familymembers"][$i]["UserInfoIsMod"] == 1)
            {
                // Utilisateur activé
                $data["familymembers"][$i]["UserInfoIsMod"] = "checked";
            }
            else
            {
                // Utilisateur désactivé
                $data["familymembers"][$i]["UserInfoIsMod"] = "";
            }
            $data["familymembers"][$i]["CanMod"] = !($data["familymembers"][$i]["UserInfoFamilyOwner"] == $data["familymembers"][$i]["UserId"]);
        }
        
        if($_SESSION["role"] == "sysAdmin")
                $data["sysadmin"] = true;
            else
                $data["sysadmin"] = false;
        $this->renderTemplate(file_get_contents(PUBLIC_ABSOLUTE_PATH."/html/manageuser.html"),$data);
    }

    function setFamilyModActivation()
    {
        if(isset($_POST["id"]))
        {
            if(Users::isUserMod($_POST["id"]))
            {
                Users::revokeUserMod($_POST["id"]);
            }
            else
            {
                Users::grantUserMod($_POST["id"]);
            }
        }
    }


    function addFamilyMember()
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
                $ownerId = Users::getFamilyOwnerByUserId($_SESSION["id"]);
                Users::addUser($_POST["UserName"], $_POST["UserInfoTel"], $_POST["UserInfoFirstName"], $_POST["UserInfoLastName"],$ownerId[0][0], $crypt, $salt);
                $userId = Users::getUserIdByName($_POST["UserName"]);
                $user = Users::getUser($userId);
                $phoneNumber = $user["UserInfoTel"];
                $phoneNumber = Registration::normalizePhoneNumber($phoneNumber);
                $user["UserInfoTel"] = $phoneNumber[0] . " (" . mb_substr($phoneNumber, 1,3) . ") " . mb_substr($phoneNumber, 4,3) . "-" . mb_substr($phoneNumber, 7,4);
                echo json_encode($user);
            }
        }
    }

    function deleteFamilyMember()
    {
        if(isset($_POST["id"]))
        {
            Users::deleteUser($_POST["id"]);
        }
    }

    //retourne les membre d'un patrimoine
    function getFamilyMember()
    {
        if(isset($_POST["familyMemberId"]))
        {
            echo json_encode(Users::getUser($_POST["familyMemberId"]));
        }
    }

    //ajuste l'usager avec les données passeés, retourne un objet JSON avec les nouvelles valeures
    function editFamilyMember()
    {
        if(isset($_POST["UserId"]) && isset($_POST["UserName"]) && isset($_POST["UserInfoFirstName"]) && isset($_POST["UserInfoLastName"]) && isset($_POST["UserInfoTel"]))
        {
            //TODO: Verif si email déjà en utilisation, si oui alors modification annulé
            Users::updateFirstLastName($_POST["UserId"],$_POST["UserInfoFirstName"],$_POST["UserInfoLastName"]);
            Users::updateTel($_POST["UserId"],$_POST["UserInfoTel"]);
            Users::updateUserName($_POST["UserId"],$_POST["UserName"]);

            $user = Users::getUser($_POST["UserId"]);

            $phoneNumber = $user["UserInfoTel"];
            $phoneNumber = Registration::normalizePhoneNumber($phoneNumber);
            $user["UserInfoTel"] = $phoneNumber[0] . " (" . mb_substr($phoneNumber, 1,3) . ") " . mb_substr($phoneNumber, 4,3) . "-" . mb_substr($phoneNumber, 7,4);

            echo json_encode($user);
        }
    }
}