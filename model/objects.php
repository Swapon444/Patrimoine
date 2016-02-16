<?php
    namespace model;
    use \repository\Db as Db;
    class Objects
    {
    
        //Obtenir les informations de l'objet, selon le ID en paramètre.
        static function getObject($_objectId)
        {
            return Db::queryFirst("SELECT *
            FROM Objects
            WHERE ObjectId = ?", $_objectId);
        }
        
        //Obtenir tous les objets qui sont dans un conteneur.
        static function getAllObjectsInContainer($_containerId)
        {
            if ($_containerId == null)
            {
                return Db::query("SELECT *
                FROM Objects
                WHERE ObjectContainer IS NULL
                ORDER BY ObjectName",array());
            }
            else
            {
                return Db::query("SELECT *
                FROM Objects
                WHERE ObjectContainer = ?
                ORDER BY ObjectName", $_containerId);
            }
        }
        
        //Obtenir le nom de tous les objets qui sont dans un conteneur.
        static function getAllObjectsNameInContainer($_containerId,$_user)
        {
            $objects = Db::query("SELECT *
            FROM Objects
            WHERE ObjectContainer = ?
            ORDER BY ObjectName", $_containerId);
            $collection = "";
            for ($i = 0; $i < count($objects); $i++)
            {
                if (self::isObjectVisibleByUser($objects[$i]["ObjectId"], $_user))
                {
                    $collection = $collection . $objects[$i]["ObjectName"] . "\n";
                }
            }

            return $collection;
        }
        
        //Obtenir tous les objets qui sont dans un conteneur selon les droits d'un utilisateur.
        static function getAllVisibleObjectsInContainer($_containerId, $_userId)
        {
            $objectsInContainer = self::getAllObjectsInContainer($_containerId);
            $visibleObjects = array();
            
            foreach($objectsInContainer as $object)
            {
                if(self::isObjectVisibleByUser($object["ObjectId"], $_userId))
                {
                    array_push($visibleObjects, $object);
                }
            }
            
            return $visibleObjects;
        }
        
        //Obtenir tous les objets d'une famille.
        static function getObjectsByFamily($_familyId)
        {
            return Db::query("SELECT ObjectId, ObjectOwner, ObjectName, ObjectValue, ObjectInitialValue, ObjectEndWarranty, ObjectContainer, ObjectInfo, ObjectSummary, ObjectGPS, ObjectIsLent, ObjectIsPublic, ObjectQuantity
            FROM (Objects INNER JOIN Users ON ObjectOwner = UserId) 
            INNER JOIN UserInfos ON  UserInfo = UserInfoId 
            WHERE UserInfoFamilyOwner = ?", $_familyId);
        }
        
        //Obtenir la valeur d'un objet.
        static function getObjectValue($_objectId)
        {
            $return = Db::queryFirst("SELECT ObjectInitialValue
            FROM Objects
            WHERE ObjectId = ?",$_objectId);
            return $return[0];
        }

        //Obtenir la quantité d'un objet.
        static function getObjectQuantity($_objectId)
        {
            $return = Db::queryFirst("SELECT ObjectQuantity
            FROM Objects
            WHERE ObjectId = ?",$_objectId);
            return $return[0];
        }

        //Obtenir la valeur du contenu d'un conteneur par l'utilisateur passé
        static function getVisibleObjectContentValue($_objectId,$_userId)
        {
            $objects = self::getAllVisibleObjectsInContainer($_objectId,$_userId);

            $done = false;

            $totalValue = 0;

            while(!$done)
            {
                if(count($objects) > 0)
                {
                    $object = array_pop($objects);

                    $totalValue = $totalValue + self::getObjectValue($object["ObjectId"]) * $object["ObjectQuantity"];

                    if(self::isObjectContainer($object["ObjectId"]))
                    {
                        $objectsInContainer = self::getAllVisibleObjectsInContainer($object["ObjectId"],$_userId);

                        foreach($objectsInContainer as $objectToAdd)
                        {
                            for($i = 1; $i <= $object["ObjectQuantity"];$i++)
                            {
                                array_push($objects, $objectToAdd);
                            }
                        }
                    }
                }
                else
                {
                    $done = true;
                }
            }

            return $totalValue;
        }
        
        //Pour savoir si l'objet est visible par un utilisateur.
        static function isObjectVisibleByUser($_objectId, $_userId)
        {
            //Vérifier si l'objet est prêté
            if(self::isObjectLent($_objectId))
            {
                return false;
            }
            else
            {
                //Vérifier si l'user est un admin
                $result = Db::queryFirst("SELECT UserInfo
                                            FROM Users
                                            WHERE UserId = ?", $_userId);
                if($result[0] == null)
                {
                    //Est Admin
                    return true;
                }
                else
                {
                    //Trouver l'ID de la famille
                    $result = Db::queryFirst("SELECT UserInfoFamilyOwner
                                                FROM Users INNER JOIN UserInfos ON UserInfo = UserInfoID
                                                WHERE UserId = ?", $_userId);
                    $familyOwner = $result[0];
                    //Vérifier si l'objet appartient à la famille du user
                    $objectFromFamily = false;
                    $objectsFromFamily = self::getObjectsByFamily($familyOwner);
                    foreach($objectsFromFamily as $object)
                    {
                        if($object[0] == $_objectId)
                        {
                            $objectFromFamily = true;
                        }
                    }
                    if($objectFromFamily)
                    {
                        //Vérifier si l'utilisateur est modérateur
                        $result = Db::queryFirst("SELECT UserInfoIsMod
                                                    FROM Users INNER JOIN UserInfos ON UserInfo = UserInfoID
                                                    WHERE UserId = ?", $_userId);
                        if ($result[0] == 1)
                        {
                            return true;
                        }
                        else
                        {
                            //Vérifier si l'user possède l'objet
                            $result = self::getObject($_objectId);
                            if($result["ObjectOwner"] == $_userId)
                            {
                                return true;
                            }
                            else
                            {
                                $objectPublic = self::isObjectPublic($_objectId);
                                //VÉrifier si l'utilisateur a les droits de voir l'objet.
                                $Count = Db::queryFirst("SELECT COUNT(RightExceptionId)
                                                        FROM RightExceptions
                                                        WHERE RightExceptionObject = ?
                                                        AND RightExceptionUser = ?", array($_objectId, $_userId));

                                $recordCount = $Count[0];

                                $userInRight = $recordCount <= 0;

                                if ($objectPublic)
                                {
                                    return $userInRight;
                                }
                                else
                                {
                                    return !$userInRight;
                                }
                            }
                        }
                    }
                    else
                    {
                        //l'objet n'appartient pas à la famille
                        return false;
                    }

                }
            }
        }
        
        //Pour savoir si l'objet est public.
        static function isObjectPublic($_objectId)
        {
            $result = Db::queryFirst("SELECT ObjectIsPublic
            FROM Objects
            WHERE ObjectId = ?", $_objectId);
            return $result[0];
        }

        //Vérifie si l'utilisateur reçu a une exception sur l'objet reçu
        static function isUserExempt($_objectId,$_userId){
            $recordCount = Db::queryFirst("SELECT COUNT(RightExceptionId)
                                        FROM RightExceptions
                                        WHERE RightExceptionUser = ? AND RightExceptionObject = ?",
                                        array($_userId,$_objectId));
            return $recordCount[0] > 0;
        }
        
        //Pour savoir si l'objet est un conteneur.
        static function isObjectContainer($_objectId)
        {
            $count = Db::queryFirst("SELECT COUNT(ObjectId)
            FROM Objects
            WHERE ObjectContainer = ?", $_objectId);
            $recordCount = $count[0];
            return ($recordCount > 0);
        }
        
        //Pour savoir si l'objet a été prêté.
        static function isObjectLent($_objectId)
        {
            $count = Db::queryFirst("SELECT ObjectIsLent
            FROM Objects
            WHERE ObjectId = ?", $_objectId);
            $recordCount = $count[0];
            return ($recordCount > 0);
        }
        
        //Mettre à jours tous les informations d'un objet.
        static function updateObject($_objectId, $_name, $_value, $_initialValue, $_endWarranty, $_info, $_summary, $_quantity)
        {
            return Db::execute("UPDATE Objects 
            SET ObjectName = ?,
            ObjectValue = ?,
            ObjectInitialValue = ?,
            ObjectEndWarranty = ?,
            ObjectInfo = ?,
            ObjectSummary = ?,
            ObjectQuantity = ?
            WHERE ObjectId = ?", array($_name, $_value, $_initialValue, $_endWarranty, $_info, $_summary, $_quantity, $_objectId));
        }

        //Mettre à jour la visibilité d'un objet
        static function updatePublic($_objectId, $_public)
        {
            return Db::execute("UPDATE Objects
                                SET ObjectIsPublic = ?
                                WHERE ObjectID = ?",array($_public,$_objectId));
        }
        
        //Mettre à jour les coordonnées GPS d'un objet.
        static function updateGPS($_objectId, $_gps)
        {
            return Db::execute("UPDATE Objects
            SET ObjectGPS = ? 
            WHERE ObjectId = ?", array($_gps, $_objectId));
        }

        //Changer le conteneur d'un objet
        static function updateContainer($_objectId, $_target)
        {
            return Db::execute("UPDATE Objects
                                SET ObjectContainer = ?
                                WHERE ObjectId = ?",array($_target,$_objectId));
        }
        
        //Ajouter un objet dans la base de données.
        static function addObject($_name, $_owner, $_container, $_value, $_initialValue, $_endWarranty, $_info, $_summary, $_isPublic, $_quantity)
        {
            return Db::createLastID("INSERT INTO Objects
            (ObjectOwner,
            ObjectContainer,
            ObjectName, 
            ObjectValue,
            ObjectInitialValue,
            ObjectEndWarranty,
            ObjectInfo,
            ObjectSummary,
            ObjectIsPublic,
            ObjectQuantity)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array($_owner, $_container,  $_name, $_value, $_initialValue, $_endWarranty,  $_info, $_summary, $_isPublic, $_quantity));
        }

        //Ajouter une exception à un objet.
        static function addException($_objectId, $_userId)
        {
            return Db::createLastID("INSERT INTO RightExceptions
                (RightExceptionUser, RightExceptionObject)
                VALUES (?, ?)", array($_userId, $_objectId));
        }
        
        //Supprimer un objet.
        static function deleteObject($_objectId)
        {
            return Db::execute("DELETE FROM Objects
            WHERE ObjectId = ?", $_objectId);
        }

        //Supprimer un droit spécifique d'un objet
        static function deleteRight($_objectId,$_userId)
        {
            return Db::execute("DELETE FROM RightExceptions
                                WHERE RightExceptionObject = ? AND RightExceptionUser = ?",Array($_objectId,$_userId));
        }
		
		//Obtient le id du conteneur principale
		static function getRacinesContainersId($_userid)
		{
			return Db::query("SELECT ObjectId FROM Objects WHERE ObjectContainer is NULL AND ObjectOwner =?",$_userid);
		}
    }

?>
