<?php
    namespace model;
    use \repository\Db as Db;
    class Users
    {
        //Retourne toutes les informations sur un utilisateur selon l'identificateur reçu en paramètre
        static function getUser($_userId)
        {
            return Db::queryFirst("SELECT *
                                   FROM Users LEFT JOIN UserInfos ON UserInfo = UserInfoId
                                   WHERE UserId = ?",$_userId);
        }

        //Retourne tous les courriels d'administrateurs
		static function getAllAdminMail()
		{
		    return Db::query("SELECT UserName
			                 FROM Users
							 WHERE UserInfo is NULL");
		}

        // Retourne tous les administrateurs de famille.
        static function getAllFamilyOwners()
        {
            return Db::query("SELECT *
                              FROM Users INNER JOIN UserInfos ON UserInfo = UserInfoId
                              WHERE UserId = UserInfoFamilyOwner");
        }

        static function getAllUser()
        {
            return Db::query("SELECT *
                              FROM Users INNER JOIN UserInfos ON UserInfo = UserInfoId");
        }



        //Retourne toutes les informations sur les membres de la famille du modérateur, dont on a reçu l'idrentificateur en paramètre
        static function getFamilyUsersByOwner($_ownerId)
        {
            return Db::query("SELECT *
                              FROM Users INNER JOIN UserInfos ON UserInfo = UserInfoId
                              WHERE UserInfoFamilyOwner = ?",$_ownerId);
        }
		
		//Retourne l'id du familyOwner selon le userid passé
        static function getFamilyOwnerByUserId($_userId)
        {
            return Db::query("SELECT UserInfoFamilyOwner
                              FROM Users INNER JOIN UserInfos ON UserInfo = UserInfoId
                              WHERE UserId = ?",$_userId);
        }
		
		//Retourne l'utilisateur correspondant au code reçu
		static function getUserIdByCode($_code)
		{
		    $id = Db::queryFirst("SELECT PasswordResetUserId
                                    FROM PasswordReset
                                    WHERE PasswordResetCode = ?",$_code);
            if (empty($id))
            {
                return -1;
            }
            else
            {
                return intval($id[0]);
            }
		}
		
		//Retourne le temps exact que le code a été créé
		static function getCodeDate($_code)
		{
		    $date = Db::queryFirst("SELECT PasswordResetDate
                                    FROM PasswordReset
                                    WHERE PasswordResetCode = ?",$_code);
            if (empty($date))
            {
                return -1;
            }
            else
            {
                return $date[0];
            }
		}
        
        //retourne le token correspondant au id de l'utilisateur 
        static function getTokenByUserId($_user)
        {
            $result = Db::queryFirst("SELECT CookieTokensCode
                                   FROM CookieTokens 
                                   WHERE CookieTokensUserId = ?",$_user);
            return $result[0];
        }
        
        //retourne la date de fin du token voulu
        static function getTokenEndDate($_token)
        {
            $result = Db::queryFirst("SELECT CookieTokensEndDate
                                   FROM CookieTokens 
                                   WHERE CookieTokensCode = ?",$_token);
            return $result[0];
        }
        
        //retourne l'id de l'utilisateur qui posède ce CookieTokens
        static function getUserIdByToken($_token)
        {
            $user =  Db::queryFirst("SELECT CookieTokensUserId
                                   FROM CookieTokens 
                                   WHERE CookieTokensCode = ?",$_token);
                                   
            if (empty($user[0]))
            {
                return -1;
            }
            else
            {
                return $user[0];
            }
        }

        //Retourne l'identificateur de l'utilisateur dont le nom fut reçu en paramètre. REtourne -1 si aucun n'a été trouvé.
        static function getUserIdByName($_name)
        {
            $id = Db::queryFirst("SELECT UserId
                                    FROM Users
                                    WHERE UserName = ?",$_name);
            if (empty($id))
            {
                return -1;
            }
            else
            {
                return intval($id[0]);
            }
        }

        //MEts à jour le mot de passe de l'utilisateur reçu par son identificateur
        static function updatePassword($_userId,$_hash,$_salt)
        {
            return Db::execute("UPDATE Users
                                SET UserHash = ?,
                                UserSalt = ?
                                WHERE UserId = ?", array($_hash, $_salt, $_userId));
        }
        
        //Mettre à jour le username de l'utilisateur.
        static function updateUserName($_userId, $_userName)
        {
            return Db::execute("UPDATE Users
                                SET UserName = ?
                                WHERE UserId = ?", array($_userName, $_userId));
        }
        
        //Mettre à jour le prénom de l'utilisateur.
        static function updateFirstName($_userId, $_firstName)
        {
            $idInfo = Db::queryFirst("SELECT UserInfo
                                      FROM Users
                                      WHERE UserId = ?",$_userId);
            return Db::execute("UPDATE UserInfos
                                SET UserInfoFirstName = ?
                                WHERE UserInfoId = ?", array($_firstName, $idInfo["UserInfo"]));
        }
        
        //Mettre à jour le nom de l'utilisateur.
        static function updateLastName($_userId, $_lastName)
        {
            $idInfo = Db::queryFirst("SELECT UserInfo
                                      FROM Users
                                      WHERE UserId = ?",$_userId);
            return Db::execute("UPDATE UserInfos
                                SET UserInfoLastName = ?
                                WHERE UserInfoId = ?", array($_lastName, $idInfo["UserInfo"]));
        }
        
        //Mettre à jour le téléphone de l'utilisateur.
        static function updateTel($_userId, $_phone)
        {
            $idInfo = Db::queryFirst("SELECT UserInfo
                                      FROM Users
                                      WHERE UserId = ?",$_userId);
            return Db::execute("UPDATE UserInfos
                                SET UserInfoTel = ?
                                WHERE UserInfoId = ?", array($_phone, $idInfo["UserInfo"]));
        }

        //MEts à jour le prénom et le nom de famille de l'utilisateur reçu par son identificateur
        static function updateFirstLastName($_userId,$_firstName,$_lastName)
        {
            $idInfo = Db::queryFirst("SELECT UserInfo
                                      FROM Users
                                      WHERE UserId = ?",$_userId);
            return Db::execute("UPDATE UserInfos
                                SET UserInfoFirstName = ?,
                                UserInfoLastName = ?
                                WHERE UserInfoId = ?",array($_firstName,$_lastName,$idInfo["UserInfo"]));

        }

        //REtourne un booléen représentant si l'utilisateur est modérateur(true) ou non(falsE), par son identificateur.
        static function isUserMod($_userId)
        {

            $result = Db::queryFirst("SELECT UserInfoIsMod
                                      FROM Users INNER JOIN UserInfos ON UserInfo = UserInfoId
                                      WHERE UserId = ?",$_userId);
            
            if(isset($result["UserInfoIsMod"]))
            {
                return $result["UserInfoIsMod"] == 1;
            }
            else
            {
                return false;
            }

        }

        //Retourne si l'utilisateur est un administrateur
        static function isUserAdmin($_userId){

            $result = Db::queryFirst("SELECT UserInfoIsMod
                                        FROM Users INNER JOIN UserInfos ON UserInfo = UserInfoId
                                        WHERE UserId = ?", $_userId);
            return $result[0] == 2;
        }

        //Retourne un booléen représentant si l'utilisateur est le propriétaire d'une famille(True) ou non(false), selon son identificateur
        static function isUserFamilyOwner($_userId)
        {
            $result = Db::queryFirst("SELECT UserInfoFamilyOwner
                                      FROM Users INNER JOIN UserInfos ON UserInfo = UserInfoId
                                      WHERE UserId = ?",$_userId);
            if(isset($result["UserInfoFamilyOwner"]))
            {
                return $result["UserInfoFamilyOwner"] == $_userId;
            }
            else
            {
                return false;
            }
        }
        /* BESOIN */
        //Retourne un booléen représentant si l'utilisateur est activé(true) ou non(false), selon l'identificateur reçu
        static function isUserActivated($_userId)
        {
            $result = Db::queryFirst("SELECT UserInfoStatus
                                      FROM Users INNER JOIN UserInfos ON UserInfo = UserInfoId
                                      WHERE UserId = ?",$_userId);
            if(isset($result["UserInfoStatus"]))
            {
                return $result["UserInfoStatus"] == 1;
            }
            else
            {
                return false;
            }
        }
        
        //Permet de savoir si l'utilisateur existe avec l'email.
        static function isUserExistByMail($_userMail)
        {
            $count = Db::queryFirst("SELECT COUNT(UserId)
            FROM Users
            WHERE UserName = ?", $_userMail);
            
            $recordCount = $count[0];
            
            return ($recordCount > 0);
        }
        /* BESOIN */
        //Active l'utilisateur dont l'identificateur a été reçu en paramètre
        static function activateUser($_userId)
        {
            $idInfo = Db::queryFirst("SELECT UserInfo
                                      FROM Users
                                      WHERE UserId = ?",$_userId);
            return Db::execute("UPDATE UserInfos
                                SET UserInfoStatus = 1
                                WHERE UserInfoId = ?",$idInfo["UserInfo"]);
        }

        //Désactive l'utilisateur dont l'identificateur a été reçu en paramètre
        static function deactivateUser($_userId)
        {
            $idInfo = Db::queryFirst("SELECT UserInfo
                                      FROM Users
                                      WHERE UserId = ?",$_userId);
            return Db::execute("UPDATE UserInfos
                                SET UserInfoStatus = 0
                                WHERE UserInfoId = ?",$idInfo["UserInfo"]);
        }

        //Donne le statut de modérateur à l'utilisateur dont l'identificateur a été reçu en paramètre
        static function grantUserMod($_userId)
        {
            $idInfo = Db::queryFirst("SELECT UserInfo
                                      FROM Users
                                      WHERE UserId = ?",$_userId);
            return Db::execute("UPDATE UserInfos
                                SET UserInfoIsMod = 1
                                WHERE UserInfoId = ?",$idInfo["UserInfo"]);
        }

        //Retire le statut de modérateur à l'utilisateur dont l'identificateur a été reçu en paramètre
        static function revokeUserMod($_userId)
        {
            $idInfo = Db::queryFirst("SELECT UserInfo
                                      FROM Users
                                      WHERE UserId = ?",$_userId);
            return Db::execute("UPDATE UserInfos
                                SET UserInfoIsMod = 0
                                WHERE UserInfoId = ?",$idInfo["UserInfo"]);
        }

        //Supprime l'utilisateur reçu de la base de données
        static function deleteUser($_userId)
        {
            $infoId = Db::queryFirst("SELECT UserInfo
                                        FROM Users
                                        WHERE UserId = ?",$_userId);
            if(self::isUserFamilyOwner($_userId))
            {
                $childs = Db::query("SELECT UserID
                                        FROM users INNER JOIN userinfos ON UserInfo = userinfos.UserInfoId
                                        WHERE UserInfoFamilyOwner = ?", $_userId);
                foreach ($childs as $child)
                {
                    Db::execute("DELETE FROM Users
                          WHERE UserId = ?", $child[0]);
                }
                Db::execute("UPDATE userinfos
                                SET UserInfoFamilyOwner = 1
                                WHERE UserInfoId = ?", $infoId[0]);
            }
            Db::execute("DELETE FROM Users
                                WHERE UserId = ?", $_userId);
            Db::execute("DELETE FROM UserInfos
                                WHERE UserInfoId = ?", $infoId[0]);
        }
        
        //Effacer les tokens de l'utilisateur
        static function deleteCookieToken($_userId)
        {
            Db::execute("DELETE FROM CookieTokens
                         WHERE CookieTokensUserId = ?", $_userId);
        }
        
		//Supprime un code selon l'id du user
		static function deleteCode($_userId)
		{
            Db::execute("DELETE FROM PasswordReset
			                  WHERE PasswordResetUserId =?", $_userId);
		}
		
        //Ajouter un utilisateur régulier à la base de données
        static function addUser($_email,$_phone,$_firstName,$_lastName,$_familyOwner,$_hash,$_salt)
        {
            //Le contrôleur est en charge de vérifié si le nom d'utilisateur est déjà existant
            $userId = Db::createLastID("INSERT INTO Users (UserName,UserHash,UserSalt)
                                    VALUES (?,?,?)",array($_email,$_hash,$_salt));
            $infoId = Db::createLastID("INSERT INTO UserInfos (UserInfoFirstName,UserInfoLastName,UserInfoTel,UserInfoFamilyOwner,UserInfoIsMod,UserInfoStatus)
                                    VALUES (?,?,?,?,0,1) ",array($_firstName,$_lastName,$_phone,$_familyOwner));
            Db::execute("UPDATE Users
                                    SET UserInfo = ?
                                    WHERE UserId = ?",array($infoId, $userId));
        }
        
        //Ajouter un token pour un cookie d'un utilisateur
		static function setCookieToken($_userId, $_cookieToken)
        {
            Db::createLastId("INSERT INTO CookieTokens(CookieTokensUserId,CookieTokensCode,CookieTokensEndDate)
			                  VALUES (?,?,?) ", array($_userId, $_cookieToken, time() + 86400*7));
        }
        
		//Ajouter un code afin de réinitialiser le mot de passe
		static function addCode($_userId, $_code)
		{
            Db::createLastId("INSERT INTO PasswordReset(PasswordResetUserId,PasswordResetCode,PasswordResetDate)
			                  VALUES (?,?,?) ", array($_userId, $_code, date("Y-m-d H:i:s")));
		}

        //Ajouter un propriétaire de famille à la base de données
        static function addFamilyOwner($_email,$_phone,$_firstName,$_lastName,$_hash,$_salt)
        {
            //Le contrôleur est en charge de vérifié si le nom d'utilisateur est déjà existant
            $userId = Db::createLastID("INSERT INTO Users (UserName,UserHash,UserSalt)
                                    VALUES (?,?,?)",array($_email,$_hash,$_salt));
            $infoId = Db::createLastID("INSERT INTO UserInfos (UserInfoFirstName,UserInfoLastName,UserInfoTel,UserInfoFamilyOwner,UserInfoIsMod,UserInfoStatus)
                                    VALUES (?,?,?,?,1,0) ",array($_firstName,$_lastName,$_phone,$userId));
            Db::execute("UPDATE Users
                                    SET UserInfo = ?
                                    WHERE UserId = ?",array($infoId, $userId));
			
			return $userId;
        }

        static function setUserMobileToken($_userId, $_value)
        {
            return Db::execute("UPDATE Users
                SET UserMobileToken = ?
                WHERE UserId = ?", array($_value,$_userId));
        }

        static function getUserIdByMobileToken($_token)
        {
            $result =  Db::queryFirst("SELECT UserId
                FROM Users
                WHERE UserMobileToken = ?", $_token);

            if (!$result)
            {
                return -1;
            }
            return $result["UserId"];
        }
    }
?>
