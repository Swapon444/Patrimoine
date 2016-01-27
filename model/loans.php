<?php
namespace model;
use \repository\Db as Db;
class Loans
{
    //Ajoute un nouvel emprunt
    static function addLoan($_objectId, $_contactId, $_loanDate, $_loanExpDate)
    {
        if(Db::execute("INSERT INTO Loans (LoanObject,LoanContact,LoanDate,LoanExpDate)
            VALUES(?,?,?,?)", array($_objectId, $_contactId, $_loanDate, $_loanExpDate)))
        {
            return Db::execute("UPDATE Objects
                                SET ObjectIsLent = 1
                                WHERE ObjectID = ?",$_objectId);
        }
        else
        {
            return false;
        }
    }

    //Ajoute un nouveau contact
    static function addContact($_userId, $_contactName, $_contactMail, $_contactTel)
    {
        return Db::execute("INSERT INTO Contacts (ContactUser,ContactName,ContactMail,ContactTel)
            VALUES(?,?,?,?)", array($_userId, $_contactName, $_contactMail, $_contactTel));
    }
    
    //Retourner les prêts avec leurs informations d'un utilisateur.
    static function getLoansWithInfoByUserId($_userId)
    {
        return Db::query("SELECT LoanId, LoanDate, LoanExpDate, ContactName, ContactMail, ContactTel, ObjectName
            FROM ((Contacts INNER JOIN Loans ON ContactId = LoanContact)
            INNER JOIN Objects ON LoanObject = ObjectId)
            WHERE ObjectOwner= ?", $_userId);
    }
    
    //Retourner les contacts associés à un utilisateur
    static function getContactsByUser($_userId)
    {
        return Db::query("SELECT *
        FROM Contacts
        WHERE ContactUser = ?", $_userId);
    }

    //Retourne tous les champs d'un emprunt
    static function getLoan($_loanId)
    {
        return Db::queryFirst("SELECT *
            FROM Loans
            WHERE LoanId = ?", $_loanId);
    }

    //Retourne tous les champs d'un contact
    static function getContact($_contactId)
    {
        return Db::queryFirst("SELECT *
            FROM Contacts
            WHERE ContactId = ?", $_contactId);
    }

    //Met à jour tous les champs d'un contact
    static function updateContact($_contactId, $_contactName, $_contactMail, $_contactTel)
    {
        return Db::execute("UPDATE Contacts
            SET ContactName = ?,
            ContactMail = ?,
            ContactTel = ?
            WHERE ContactId = ?", array($_contactName, $_contactMail, $_contactTel, $_contactId));
    }
    
    //Mettre à jour la date d'expiration du prêts
    static function updateLoanExpDate($_loanId, $_loanExpDate)
    {
        return Db::execute("UPDATE Loans
            SET LoanExpDate = ?
            WHERE LoanId = ?", array($_loanExpDate, $_loanId));
    }

    //Supprime un contact
    static function deleteContact($_contactId)
    {
        return Db::execute("DELETE FROM Contacts
            WHERE ContactId = ?", $_contactId);
    }

    //Supprime un emprunt
    static function deleteLoan($_loanId)
    {
        $loan = self::getLoan($_loanId);
        $objectId = $loan["LoanObject"];
        if(Db::execute("DELETE FROM Loans
            WHERE LoanId = ?", $_loanId))
        {
            return Db::execute("UPDATE Objects
                                SET ObjectIsLent = 0
                                WHERE ObjectID = ?",$objectId);
        }
        else
        {
            return false;
        }
    }
}

?>
