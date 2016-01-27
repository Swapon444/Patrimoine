<?php

use \model\users as Users;
use \model\loans as Loans;

//Classe qui représente un contrôleur qui s'occupe des prêts.
class ManageLoans extends Controller
{
    function ManageLoans()
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
            "Loans" => Loans::getLoansWithInfoByUserId($_SESSION["id"]),
            "username" => $completeName,
            "isSystemAdmin" => $_SESSION["role"] == ROLE_SYSADMIN,
            "isFamilyAdmin" => $_SESSION["role"] == ROLE_FAMOWNER,
            "isMod" => $_SESSION["role"] == ROLE_MOD
        );

        $this->renderTemplate(file_get_contents("public/html/manageloans.html"), $data);
    }
    
    //Prolonger la date du prêt.
    function extendDate()
    {
        if(isset($_POST["LoanId"]))
        {
            Loans::updateLoanExpDate($_POST["LoanId"], $_POST["LoanExpDate"]);
        }
    }
    
    //Supprimer un prêt
    function deleteLoan()
    {
        if(isset($_POST["LoanId"]))
        {
            Loans::deleteLoan($_POST["LoanId"]);
        }
    }
    
    //Obtenir les prêts de l'usager
    function getLoans()
    {
        if(isset($_SESSION["id"]))
        {
            echo json_encode(Loans::getLoansWithInfoByUserId($_SESSION["id"]));
        }
    }
}
