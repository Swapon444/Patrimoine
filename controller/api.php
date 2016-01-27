<?php

use \model\Users as Users;
use \model\Objects as Objects;
use \model\Resources as Resources;

class api extends Controller
{
    function api()
    {
        parent::Controller();
    }

    // Reçoit Email et Mot de passe en POST
    // Renvoie le token de connexion
    function login()
    {
        if (isset($_POST["Password"]) && isset($_POST["Email"]))
        {
            $userId = Users::getUserIdByName($_POST["Email"]);

            if ($userId != -1)
            {
                $user = Users::getUser($userId);

                if (crypt($_POST["Password"], $user["UserSalt"]) == $user["UserHash"])
                {
                    $token = self::generateToken();
                    Users::setUserMobileToken($userId, $token);
                    echo $token;
                }
            }
        }
        else if (isset($_POST["Token"]) && $_POST["Token"] != null)
        {
            $userId = Users::getUserIdByMobileToken($_POST["Token"]);
            if ($userId != -1)
            {
                echo $_POST["Token"];
            }
        }
    }

    // Détruire le token et la session en GET
    function logout()
    {
        if (isset($_POST["Token"]))
        {
            $userId = Users::getUserIdByMobileToken($_POST["Token"]);
            Users::setUserMobileToken($userId, null);
        }
    }

    // Obtenir un objet dans l'arbre, selon son Id en POST
    function objects()
    {
        if (isset($_POST["Token"]) && isset($_POST["ObjectId"]) && $_POST["Token"] != null)
        {
            $userId = Users::getUserIdByMobileToken($_POST["Token"]);

            if ($userId != -1)
            {
                if ($_POST["ObjectId"] == null)
                {
                    $object = new stdClass();
                    $object->CurrentObject = null;
                    $object->ParentObject = null;
                    $object->ChildObjects = Objects::getAllVisibleObjectsInContainer(null, $userId);

                    echo JSON_ENCODE($object);
                }
                else
                {
                    $object = new stdClass();
                    $object->CurrentObject = Objects::getObject($_POST["ObjectId"]);
                    $object->ParentObject = $object->CurrentObject["ObjectContainer"] == null ? null : Objects::getObject($object->CurrentObject["ObjectContainer"]);
                    $object->ChildObjects = Objects::getAllVisibleObjectsInContainer($_POST["ObjectId"], $userId);

                    echo JSON_ENCODE($object);
                }
            }
        }
    }

    // Ajouter une photo à un objet dont l'id est passé en POST
    function addImage()
    {
        if (isset($_POST["ObjectId"]) && isset($_POST["Image"]))
        {
            Resources::addImage($_POST["ObjectId"], base64_decode($_POST["Image"]));
        }
    }
    
	function utf8ize($d) {
        if (is_array($d)) 
		{
            foreach ($d as $k => $v) 
			{
                $d[$k] = $this->utf8ize($v);
            }
		}
		else if(is_object($d))
		{
			foreach ($d as $k => $v) 
				$d->$k = $this->utf8ize($v);
        } 
		else
		{
            return utf8_encode($d);
        }
        return $d;
    }
	
    function getImages()
    {
        if (isset($_POST["ObjectId"]))
        {
            $images = Resources::getImage($_POST["ObjectId"]);
            for($i = 0; $i < count($images); $i++)
            {
                $images[$i]["ImageBlob"] = base64_encode($images[$i]["ImageBlob"]);
            }
            
            $object = new stdClass();
            $object->Images = $images;
            
            echo JSON_ENCODE($this->utf8ize($object));
        }
    }

    // Met à jour le champ ObjectGPS de l'objet passé en paramètre en PUT
    function addGps()
    {
        if (isset($_POST["ObjectId"]) && isset($_POST["Location"]))
        {
            Objects::updateGPS($_POST["ObjectId"], $_POST["Location"]);
        }
    }

    private static function generateToken($length = 256)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ./$";
        $result = "";
        for ($i = 0; $i < $length; $i++)
        {
            $result .= $chars[rand(0, strlen($chars) - 1)];
        }

        return $result;
    }
}
