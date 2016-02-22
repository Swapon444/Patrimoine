<?php
use \model\Objects as Objects;
use \model\Users as Users;
use \model\Resources as Resources;
use \model\Loans as Loans;

set_time_limit(0);
class ManageItems extends Controller
{
    function ManageItems()
    {
        parent::Controller();
		if(!isset($_SESSION["id"]))
            header(CONNECTION_HEADER);
    }

    //Fonction appelée pour l'affiche par code QR.
    function show($_id)
    {
        if(!empty($_SESSION["id"]))
        {
            if(Objects::isObjectVisibleByUser($_id,$_SESSION["id"]))
            {
                $familyOwner = self::getFamilyOwner($_SESSION["id"]);
                $id = $_id;
                $array = self::loadObjectArray($id,$_SESSION["id"],8);
                $hasParent = ($array[0]["link"]);
                $infos = self::loadObjectInfo($id,$_SESSION["id"]);
                $famille = self::loadFamilyUsers($_SESSION["id"],$id);
                $first = is_null($array[0]["name"]);
                $user = Users::getUser($_SESSION["id"]);
                $completeName = $user["UserInfoFirstName"] . " " . $user["UserInfoLastName"];


                $owned = Users::isUserMod($_SESSION["id"]);
                if(!$owned)
                {
                    $owned = Users::isUserFamilyOwner($_SESSION["id"]);
                    if(!$owned)
                    {
                        $owned = $infos["ownerID"] == $_SESSION["id"];
                    }
                }

                // Chargement des images
                $images = Resources::getImage($id);

                // Encodage des images
                for($i = 0; $i < count($images); $i++)
                {
                    $images[$i]["ImageBlob"] = "data:image;base64," . base64_encode($images[$i]["ImageBlob"]);
                }

                if(count($images) == 0)
                {
                    $currentImage = PUBLIC_ABSOLUTE_PATH . "/assets/no-image.gif";
                    $currentImageId = "";
                }
                else
                {
                    $currentImage = $images[0]["ImageBlob"];
                    $currentImageId = $images[0]["ImageId"];
                }

                $contact = self::loadContactArray($_SESSION["id"]);

                $data = array
                (
                    "PUBLIC_ABSOLUTE_PATH" => PUBLIC_ABSOLUTE_PATH,
                    "SERVER_ABSOLUTE_PATH" => SERVER_ABSOLUTE_PATH,
                    "donnees" => ($array),
                    "user" => $_SESSION["id"],
                    "infos" => ($infos),
                    "contacts" => ($contact),
                    "famille" => ($famille),
                    "first" => ($first),
                    "username" => $completeName,
                    "isSystemAdmin" => $_SESSION["role"] == ROLE_SYSADMIN,
                    "isFamilyAdmin" => $_SESSION["role"] == ROLE_FAMOWNER,
                    "isMod" => $_SESSION["role"] == ROLE_MOD,
                    "currentImage" => $currentImage,
                    "currentImageId" => $currentImageId,
                    "images" => $images,
                    "show" => $hasParent,
                    "owned" => $owned
                );
                $this->renderTemplate(file_get_contents(ITEMS_PAGE), $data);
            }
            else
            {
                header(OBJECTS_HEADER);
            }
        }
        else
        {
            $_SESSION["path"] = $_SERVER["REQUEST_URI"];
            header(CONNECTION_HEADER);
        }
    }

    //Fonction retournant si l'objet est modifiable par l'utilisateur
    function isObjectEditable()
    {
        $infos = self::loadObjectInfo($_POST["object"],$_POST["user"]);
        $owned = Users::isUserMod($_SESSION["id"]);
        if(!$owned)
        {
            $owned = Users::isUserFamilyOwner($_SESSION["id"]);
            if(!$owned)
            {
                $owned = $infos["ownerID"] == $_SESSION["id"];
            }
        }
        if($owned)
        {
            echo "true";
        }
        else
        {
            echo "false";
        }
    }

    //Fonction chargant les informations d'un objet spécifique, et les mettant dans un tableau associatif.
    function loadObjectInfo($_objectId,$_userId)
    {
        $object = Objects::getObject($_objectId);
        $ownerObj = Users::getUser($object["ObjectOwner"]);
        $owner = $ownerObj["UserInfoFirstName"] . " " . $ownerObj["UserInfoLastName"];
        $warranty = null;
        if(($object["ObjectEndWarranty"]) != "0000-00-00"){
            $warranty = $object["ObjectEndWarranty"];
        }



        $famille = self::loadFamilyUsers($_userId,$_objectId);
        $Contains = Objects::getAllVisibleObjectsInContainer($_objectId, $_userId);
        $container = isset($Contains[0]);
        $contentvalue = 0;
        if($container)
        {
            $contentvalue = Objects::getVisibleObjectContentValue($_objectId,$_userId);
        }
        $array = Array("name" => $object["ObjectName"], "owner" => $owner,"value" => $object["ObjectValue"],
                        "initvalue" => $object["ObjectInitialValue"],"warranty" => $warranty,
                        "summary" => $object["ObjectSummary"],"info" => $object["ObjectInfo"],
                        "public" => $object["ObjectIsPublic"] == 1,"famille" => $famille,"ownerID" => $object["ObjectOwner"],
                        "quantity" => $object["ObjectQuantity"], "totalvalue" => ($object["ObjectInitialValue"] + $contentvalue) * $object["ObjectQuantity"],
                        "container" => $container,"contentvalue" => $contentvalue);
        return $array;
    }

	/*
	 *Fonction retournant tous les contenants racine du chef de famille
	 *Correction 2016
	*/
	function getAllRacineContainer()
	{
		$OwnerId = self::getFamilyOwner($_SESSION["id"]);
		$arrayContainersRacine = array();
		if(!is_null(objects::getRacinesContainers($_SESSION["id"])))
		{
			array_push($arrayContainersRacine , objects::getRacinesContainers($_SESSION["id"]));
		}

		if(!is_null(objects::getRacinesContainers($OwnerId)))
		{
			array_push($arrayContainersRacine ,objects::getRacinesContainers($OwnerId));
		}
		return $arrayContainersRacine;
	}

    /*
     * Fonction retournant un tableau contenant le parent et les enfants de l'objet reçu en paramètre,
     * avec des propriétés pour affichage dans tableaux de navigations.
     */
    function loadObjectArray($_objectId,$_userId,$_nbShow)
    {
        $object = Objects::getObject($_objectId);
        $array = Array();
        $parent = $object["ObjectContainer"];
		$grandparent = Objects::getObject($parent);
		$racineContainer = $grandparent["ObjectId"];
		$grandparent = $grandparent["ObjectContainer"];
        $nomParent ="";

		$enfantsSelected = Objects::getAllVisibleObjectsInContainer($object["ObjectId"], $_userId);
        array_push($array,Array("name" => $object["ObjectName"] , "id" => $object["ObjectId"],"head" => true, "container"=> $enfantsSelected, "link" => false));
        $enfants = Objects::getAllVisibleObjectsInContainer($racineContainer,$_userId);
        $i = 0;
        $more = false;

        foreach($enfants as $enfant){
            if($i < $_nbShow)
            {
				if($object["ObjectId"] != $enfant["ObjectId"])
				{
					$nomEnfant = $enfant["ObjectName"];
					$idEnfant = $enfant["ObjectId"];
					$Contains = Objects::getAllVisibleObjectsInContainer($idEnfant, $_userId);
					$container = isset($Contains[0]);
					if(empty($Contains))
						$Contains = null;
					array_push($array, Array("name" => $nomEnfant, "id" => $idEnfant, "other" => true, "container" => $Contains, "link" => true));
					$i++;
				}
            }
            else
            {
                if(!$more)
                {
                    $more = true;
                    array_push($array, Array("name" => "Plus...", "id" => "-1", "other" => true, "container" => true, "plus" => true, "nb" => $_nbShow, "target" => $_objectId));
                }
            }
        }
        return ($array);
    }

    /*
     * Fonction retournant un tableau contenant tous les objets contenant la chaîne recherchée
     */
    function loadSearchArray($_parentId,$_userId,$_search)
    {
        if($_search != "")
        {
            $selected = Objects::getObject($_parentId);
            $array = Array();
            $parent = $selected["ObjectContainer"];
            $nomParent = "";
            if ($parent != null) {
                $parentObject = Objects::getObject($parent);
                $nomParent .= $parentObject["ObjectName"];
                $parentId = $parentObject["ObjectId"];
                array_push($array, Array("name" => $nomParent, "id" => $parentId, "head" => true, "link" => true));
            }
            array_push($array, Array("name" => $selected["ObjectName"], "id" => $selected["ObjectId"], "head" => true, "link" => false));

            $objects = Objects::getAllVisibleObjectsInContainer($_parentId, $_SESSION["id"]);
            $enfants = array();

            $done = false;

            while (!$done) {
                if (count($objects) > 0) {
                    $object = array_pop($objects);
                    //Effectuer l'opération sur l'objet en cours
                    if(strpos(strtolower($object["ObjectName"]),strtolower($_search)) !== false)
                    {
                        array_push($enfants, $object);
                    }

                    //Charger l'objet suivant
                    if (Objects::isObjectContainer($object["ObjectId"])) {
                        $objectsInContainer = Objects::getAllVisibleObjectsInContainer($object["ObjectId"], $_SESSION["id"]);
                        foreach ($objectsInContainer as $objectToAdd) {
                            array_push($objects, $objectToAdd);
                        }
                    }
                } else {
                    $done = true;
                }
            }

            foreach ($enfants as $enfant) {
                $nomEnfant = $enfant["ObjectName"];
                $idEnfant = $enfant["ObjectId"];
                $Contains = Objects::getAllVisibleObjectsInContainer($idEnfant, $_userId);
                $container = isset($Contains[0]);
                array_push($array, Array("name" => $nomEnfant, "id" => $idEnfant, "other" => true, "container" => $container));
            }
            return ($array);
        }
        else
        {
            return self::loadObjectArray($_parentId,$_userId,8);
        }
    }

    /*
     * Fonction retournant un tableau contenant le parent et les enfants de l'objet reçu en paramètre,
     * avec des propriétés pour affichage dans tableau de navigation, pour les déplacements.
     * L'objets "base" sera ignoré lors de la navigation.
     */
    function loadMoveArray($_objectId,$_userId,$_baseId)
    {
        $object = Objects::getObject($_objectId);
        $array = Array();
        $parent = $object["ObjectContainer"];
        $nomParent ="";
        if($parent!=null){
            $parentObject = Objects::getObject($parent);
            $nomParent .=  $parentObject["ObjectName"];
            $parentId = $parentObject["ObjectId"];
            array_push($array,Array("name" => $nomParent , "id" => $parentId, "head" => true,"link" => true));
        }
        array_push($array,Array("name" => $object["ObjectName"] , "id" => $object["ObjectId"],"head" => true, "link" => false));
        $enfants = Objects::getAllVisibleObjectsInContainer($_objectId,$_userId);
        foreach($enfants as $enfant){
            $nomEnfant =  $enfant["ObjectName"];
            $idEnfant = $enfant["ObjectId"];
            if($idEnfant != $_baseId)
            {
                array_push($array,Array("name" => $nomEnfant,"id" => $idEnfant, "other" => true));
            }
        }
        return ($array);
    }

    //Fonction retournant un tableau associatif contenant tous les contacts de l'utilisateur
    function loadContactArray($_userId)
    {
        $contacts = Loans::getContactsByUser($_userId);
        $return = array();
        foreach($contacts as $contact){
            array_push($return,Array("name" => $contact["ContactName"],"id" => $contact["ContactId"]));
        }
        return $return;
    }

    //Retourne un json des objets de navigation, pour utilisation par AJAX
    function getTableau()
    {
        echo json_encode(self::loadObjectArray($_POST["object"],$_POST["user"],$_POST["nb"]));
    }

    //Retourne un json des informations de l'objet en cours, pour utilisation par AJAX
    function getInfos()
    {
        echo json_encode(self::loadObjectInfo($_POST["object"],$_SESSION["id"]));
    }

    //Retourne un json des information pour le code qr de l'objet en cours.
    function getInfosQrCode()
    {
        echo json_encode(self::loadObjectInfoQrCode($_POST["object"], $_POST["userId"]));
    }

    //Retourne un json du tableau de navigation de déplacement, pour utilisation par AJAX.
    function getMoveTab()
    {
        echo json_encode(self::loadMoveArray($_POST["object"],$_POST["user"],$_POST["base"]));
    }

    //Retourne un json des objets de navigation, pour utilisation par AJAX, selon la chaîne de recherche
    function getSearch()
    {
        echo json_encode(self::loadSearchArray($_POST["object"],$_POST["user"],$_POST["search"]));
    }

    //Charge les informations QR d'un objet
    function loadObjectInfoQrCode($_objectId,$_userId)
    {
        $object = Objects::getObject($_objectId);
        if(Objects::isObjectContainer($_objectId))
        {
            $children = Objects::getAllObjectsNameInContainer($_objectId,$_userId);
            $array = Array("name" => $object["ObjectName"],"children" => $children);
        }
        else
            $array = Array("name" => $object["ObjectName"],"children" => "aucun");

        return $array;
    }

    //Charge les membres d'une famille, ainsi que les exceptions de ceux-ci face à l'objet sélectionné..
    function loadFamilyUsers($_userId,$_objectId)
    {
        $array = Array();
        $owner = self::getFamilyOwner($_userId);
        $famille = Users::getFamilyUsersByOwner($owner);
        foreach($famille as $user){
            $firstName = $user["UserInfoFirstName"];
            $lastName = $user["UserInfoLastName"];
            $id = $user["UserId"];
            $exception = Objects::isUserExempt($_objectId,$id);
            if ($id != $_userId){
                array_push($array,Array("name" => $firstName . " " . $lastName,"id" => $id,"exception" => $exception));
            }
        }
        return $array;
    }

    //Retourne le propriétaire de la famille du membre reçu en param;etre.
    private function getFamilyOwner($_userId)
    {
        $infos = Users::getUser($_userId);
        $owner = $infos["UserInfoFamilyOwner"];
        return $owner;
    }

    // Afficher la page de gestion d'objets
    function render()
    {
        //Obtenir l'objet de base du user en cours
        $familyOwner = (int)self::getFamilyOwner($_SESSION["id"]);
        $objet = Objects::getFirstRacine($familyOwner);
		$id = -1;
		if(!empty($objet) && !is_null($objet))
		{
			$id = (int)$objet[0][0];
		}

        $array = self::loadObjectArray($id,$_SESSION["id"],50);
        $infos = self::loadObjectInfo($id,$_SESSION["id"]);
        $famille = self::loadFamilyUsers($_SESSION["id"],$id);
        $first = is_null($array[0]["name"]);
        $user = Users::getUser($_SESSION["id"]);
        $completeName = $user["UserInfoFirstName"] . " " . $user["UserInfoLastName"];
        $owned = Users::isUserMod($_SESSION["id"]);
        if(!$owned)
        {
            $owned = Users::isUserFamilyOwner($_SESSION["id"]);
            if(!$owned)
            {
                $owned = $infos["ownerID"] == $_SESSION["id"];
            }
        }

        // Chargement des images
        $images = Resources::getImage($id);

        // Encodage des images
        for($i = 0; $i < count($images); $i++)
        {
            $images[$i]["ImageBlob"] = "data:image;base64," . base64_encode($images[$i]["ImageBlob"]);
        }

        if(count($images) == 0)
        {
            $currentImage = PUBLIC_ABSOLUTE_PATH . "/assets/no-image.gif";
            $currentImageId = "";
        }
        else
        {
            $currentImage = $images[0]["ImageBlob"];
            $currentImageId = $images[0]["ImageId"];
        }

        $contact = self::loadContactArray($_SESSION["id"]);
        $data = array
        (
            "PUBLIC_ABSOLUTE_PATH" => PUBLIC_ABSOLUTE_PATH,
            "SERVER_ABSOLUTE_PATH" => SERVER_ABSOLUTE_PATH,
            "donnees" => ($array),
            "user" => $_SESSION["id"],
            "infos" => ($infos),
            "contacts" => ($contact),
            "famille" => ($famille),
            "first" => ($first),
            "username" => $completeName,
            "isSystemAdmin" => $_SESSION["role"] == ROLE_SYSADMIN,
            "isFamilyAdmin" => $_SESSION["role"] == ROLE_FAMOWNER,
            "isMod" => $_SESSION["role"] == ROLE_MOD,
            "currentImage" => $currentImage,
            "currentImageId" => $currentImageId,
            "images" => $images,
            "owned" => $owned
        );
		$this->renderTemplate(file_get_contents(ITEMS_PAGE), $data);
	}

    /**
     * Télécharger et stocker sous forme de blob l'image reçue
     */
    function uploadImage()
    {
        if(!empty($_FILES) && $_FILES['file']['size'] > 0)
        {
            if($_FILES['file']['type'] == 'image/jpeg' || $_FILES['file']['type'] == 'image/png')
            {
                $tmpName = $_FILES['file']['tmp_name'];
                $fileSize = $_FILES['file']['size'];
                $fileType = $_FILES['file']['type'];

                $fp = fopen($tmpName, 'r');
                $content = fread($fp, filesize($tmpName));
                fclose($fp);

                Resources::addImage($_POST["objectId"], $content);


				sleep(3);

            }
        }
    }

    /**
     * Obtient une image encodée en base64 à partir d'un id d'image reçu en post
     *
     * TODO SÉCURITÉ : Il faudra faire de la validation afin de vérifier si l'image appartient à l'utilisateur
     *
     **/
    function getEncodedImagesById()
    {

        $images = Resources::getImage($_POST['objectId']);

		$tabImage = null;

        for($i = 0; $i < count($images); $i++)
        {
            $tabImage[$i]["ImageBlob"] = "data:image;base64," . base64_encode($images[$i]["ImageBlob"]);
			$tabImage[$i]["ImageId"] = $images[$i]["ImageId"];
        }

        echo json_encode($tabImage);

    }

    /**
     * Supprime une image
     *
     * TODO SÉCURITÉ : Il faudra faire de la validation afin de vérifier si l'image appartient à l'utilisateur
     *
     **/
    function deleteImage()
    {
        $imageId = $_POST["imageId"];
        Resources::deleteImage($imageId);
    }

    /**
     * Ajouter une nouvelle zone à une image
     *
     * Note : Pour des raisons de facilité d'implémentation, j'ai décidé de recréer à chaque fois toutes les zones
     *        comme ça je peux prendre au complet les tableaux de données reçus sans me soucier de vérifier ceux qui
     *        existent et sont soummis à des opérations CRUD.
     */
     function addZone()
    {
        $imageId = $_POST["imageId"];
        $zones = $_POST["zones"];
        $objectsId = $_POST["txtZoneName"];

        // Suppression de toutes les zones associées à l'image
        Resources::deleteZones($imageId);

        // Insérer les nouvelles zones
        for($i = 0; $i < count($zones); $i++)
        {
            Resources::addZone($imageId, $objectsId[$i], $zones[$i]["x"], $zones[$i]["y"], $zones[$i]["w"], $zones[$i]["h"]);
        }
    }

    function getZones()
    {
        $imageId = $_POST["imageId"];
        echo json_encode(Resources::getZones($imageId));
    }

    // Obtient en JSON qui représente la liste des objets d'un conteneur
    function getVisibleObjects()
    {
        $objects = Objects::getAllVisibleObjectsInContainer($_POST["objectId"],$_SESSION["id"]);
        echo json_encode($objects);
    }


    // Ajouter un objet à la base de données
    function addItem()
    {
        $parent = $_POST["parent"];
        if($parent == '' || $parent == null)
        {
            $ownerId = users::getFamilyOwnerByUserId($_SESSION['id']);
			$parent = objects::getRacinesContainersId((int)($ownerId[0]["UserInfoFamilyOwner"]));
			$parent = (int)$parent[0]["ObjectId"];
        }
        $name = $_POST["name"];
        $initValue = $_POST["initValue"];
        $value = $_POST["value"];
        $warranty = $_POST["warranty"];
        if($warranty == "0000-00-00"){
            $warranty = null;
        }
        $summary = $_POST["summary"];
        $infos = $_POST["infos"];
        $publicItem = $_POST["publicItem"];
        $exceptions = $_POST["exceptions"];
        $quantity = $_POST["quantity"];
        $owner = $_POST["owner"];
        if ($publicItem != "false")
        {
            $public = 1;
        }
        else
        {
            $public = 0;
        }
        $object = Objects::addObject($name,$owner,$parent,$value,$initValue,$warranty,$infos,$summary,$public,$quantity);
        $exceptions = json_decode($exceptions,true);
        foreach($exceptions as $exception)
        {
            if($exception["state"] == 1)
            {
                $user = $exception["id"];
                Objects::addException($object,$user);
            }
        }
    }

    //Modifie un objet dans la base de données
    function editItem()
    {
        $id = $_POST["object"];
        $name = $_POST["name"];
        $initValue = $_POST["initValue"];
        $value = $_POST["value"];
        $warranty = $_POST["warranty"];
        if($warranty == "0000-00-00"){
            $warranty = null;
        }
        $summary = $_POST["summary"];
        $infos = $_POST["infos"];
        $publicItem = $_POST["publicItem"];
        $exceptions = $_POST["exceptions"];
        $quantity = $_POST["quantity"];
        if ($publicItem != "false")
        {
            $public = 1;
        }
        else
        {
            $public = 0;
        }
        Objects::updateObject($id,$name,$value,$initValue,$warranty,$infos,$summary,$quantity);
        Objects::updatePublic($id,$public);
        $exceptions = json_decode($exceptions,true);
        var_dump($exceptions);
        foreach($exceptions as $exception)
        {
            $user = $exception["id"];
            if($exception["state"] == "1")
            {
                //Vérifier si l'utilisateur à déjà une exception
                if(!Objects::isUserExempt($id,$user))
                {
                    Objects::addException($id, $user);
                }
            }
            else
            {
                if(Objects::isUserExempt($id,$user))
                {
                    Objects::deleteRight($id,$user);
                }
            }
        }
    }

    //Supprime un objet de la base de données et retourne l'id du parent
    function deleteItem()
    {
        $item = $_POST["object"];
        $infos = Objects::getObject($item);
        $parent = $infos["ObjectContainer"];
        Objects::deleteObject($item);
        echo $parent;
    }

    /**
     * Génère un rapport qui liste tous les objets à partir d'un objet racine spécifié
     */
    function generateReport()
    {
        $objectId = $_POST["object"];
        $objects = Objects::getAllVisibleObjectsInContainer($objectId,$_SESSION["id"]);
        $sum = Objects::getObjectValue($objectId) * Objects::getObjectQuantity($objectId);

        for($i = 0; $i < count($objects); $i++)
        {
            $objects[$i]["ObjectIsLent"] = $objects[$i]["ObjectIsLent"] == 0 ? "Non" : "Oui";

            if($objects[$i]["ObjectEndWarranty"] == "0000-00-00")
            {
                $objects[$i]["ObjectEndWarranty"] = "-";
            }

            $sum += ($objects[$i]["ObjectInitialValue"] * $objects[$i]["ObjectQuantity"]) * Objects::getObjectQuantity($objectId);
            $sum += Objects::getVisibleObjectContentValue($objects[$i]["ObjectId"],$_SESSION["id"]) * Objects::getObjectQuantity($objectId);
            $objects[$i]["ObjectContentValue"] = Objects::getVisibleObjectContentValue($objects[$i]["ObjectId"],$_SESSION["id"]);
            $objects[$i]["ObjectTotalValue"] = ($objects[$i]["ObjectInitialValue"]+$objects[$i]["ObjectContentValue"]) * $objects[$i]["ObjectQuantity"];
        }

        $container = Objects::getObject($objectId);

        $user = Users::getUser($_SESSION["id"]);

        $data = array(
            "containerValue" => Objects::getObjectValue($objectId),
            "name" => $user["UserInfoFirstName"] . " " . $user["UserInfoLastName"],
            "date" => date("Y-m-j"),
            "objects" => $objects,
            "total" => (Objects::getVisibleObjectContentValue($objectId,$_SESSION["id"]) + Objects::getObjectValue($objectId)) * Objects::getObjectQuantity($objectId),
            "container" => $container["ObjectName"],
            "containerId" => $container["ObjectId"],
            "containerQuantity" => $container["ObjectQuantity"]
        );

        $this->renderTemplate(file_get_contents("public/html/report.html"), $data);
    }

    /**
     * Génère un rapport qui liste tous les objets à partir d'un objet racine spécifié, ainsi que leurs enfants et enfants d'enfant etc.
     */
    function generateReportAll()
    {
        $objectId = $_POST["object"];
        $objects = Objects::getAllVisibleObjectsInContainer($objectId,$_SESSION["id"]);
        $ObjectArr = [];
        $ObjContainer = [];
        $sum = Objects::getObjectValue($objectId) * Objects::getObjectQuantity($objectId);

        $Item = selft::getOneContent($objectId,$objects, $ObjectArr, $ObjContainer, 0);

        $user = Users::getUser($_SESSION["id"]);

        $data = array(
            "name" => $user["UserInfoFirstName"] . " " . $user["UserInfoLastName"],
            "date" => date("Y-m-j"),
            "container" => $Item
        );

        $this->renderTemplate(file_get_contents("public/html/report.html"), $data);
    }

    function getOneContent($_ObjetId, $_VisibleObj,$_ObjectArr, $_Objcontainer, $_nb){
        $sum = Objects::getObjectValue($objectId) * Objects::getObjectQuantity($objectId);

        for($i = 0; $i < count($_VisibleObj); $i++)
        {
            $_VisibleObj[$i]["ObjectIsLent"] = $_VisibleObj[$i]["ObjectIsLent"] == 0 ? "Non" : "Oui";

            if($_VisibleObj[$i]["ObjectEndWarranty"] == "0000-00-00")
            {
                $_VisibleObj[$i]["ObjectEndWarranty"] = "-";
            }

            $sum += ($_VisibleObj[$i]["ObjectInitialValue"] * $_VisibleObj[$i]["ObjectQuantity"]) * Objects::getObjectQuantity($objectId);
            $sum += Objects::getVisibleObjectContentValue($_VisibleObj[$i]["ObjectId"],$_SESSION["id"]) * Objects::getObjectQuantity($objectId);
            $_VisibleObj[$i]["ObjectContentValue"] = Objects::getVisibleObjectContentValue($_VisibleObj[$i]["ObjectId"],$_SESSION["id"]);
            $_VisibleObj[$i]["ObjectTotalValue"] = ($_VisibleObj[$i]["ObjectInitialValue"]+$_VisibleObj[$i]["ObjectContentValue"]) * $_VisibleObj[$i]["ObjectQuantity"];
        }


        $container = Objects::getObject($objectId);
        $_Objcontainer[$_nb] = array(
                                "containerValue" => Objects::getObjectValue($_ObjetId)
                                "container" => $container["ObjectName"],
                                "containerId" => $container["ObjectId"],
                                "containerQuantity" => $container["ObjectQuantity"],
                                "total" => (Objects::getVisibleObjectContentValue($_ObjetId,$_SESSION["id"]) + Objects::getObjectValue($_ObjetId)) * Objects::getObjectQuantity($_ObjetId),
                                "objects" => $_VisibleObj);

        for($i = 0; $i < count($objects);$i++){
            $objects = Objects::getAllVisibleObjectsInContainer($_VisibleObj[$i]["ObjectId"],$_SESSION["id"]);

            $tempo = self::getOneContent($_VisibleObj[$i]["ObjectId"],$objects,$_ObjectArr,$_Objcontainer,$_nb + 1);

            $_Objcontainer.push($tempo);
        }
        return $_Objcontainer;
    }


    /*
     * Génère un rapport complet à partir d'un objet racine spécifié
     */
    function generateFullReport()
    {
        $objectId = $_POST["object"];
        $objects = Objects::getAllVisibleObjectsInContainer($objectId,$_SESSION["id"]);
        $toRender = array();

        $done = false;
        $level = 0;
        $levelstack = array(count($objects));
        $sum = Objects::getObjectValue($objectId);

        while(!$done)
        {
            if(count($objects) > 0)
            {
                $levelstack[$level]--;
                $object = array_pop($objects);
                /*$object["ObjectContentValue"] = Objects::getVisibleObjectContentValue($object["ObjectId"],$_SESSION["id"]);
                $object["ObjectTotalValue"] = ($object["ObjectInitialValue"]+$object["ObjectContentValue"]) * $object["ObjectQuantity"];*/
                $object["ObjectTotalValue"] = $object["ObjectInitialValue"] * $object["ObjectQuantity"];
                if(isset($object["quantity"]))
                {
                    $object["quantity"] = $object["ObjectQuantity"] * $object["quantity"];
                }
                else
                {
                    $object["quantity"] = $object["ObjectQuantity"];
                }
                $object["level"] = $level * 25;
                //Effectuer l'opération sur l'objet en cours
                array_push($toRender,$object);
                $sum += $object["ObjectInitialValue"] * $object["quantity"];

                //Charger l'objet suivant
                if(Objects::isObjectContainer($object["ObjectId"]))
                {
                    $level++;
                    $objectsInContainer = Objects::getAllVisibleObjectsInContainer($object["ObjectId"],$_SESSION["id"]);
                    foreach($objectsInContainer as $objectToAdd)
                    {
                        $objectToAdd["quantity"] = $object["quantity"];
                        array_push($objects, $objectToAdd);
                    }
                    $levelstack[$level] = count($objectsInContainer);

                }
                while($levelstack[$level] == 0 && $level > 0)
                {
                    $level--;
                }
            }
            else
            {
                $done = true;
            }
        }

        $container = Objects::getObject($objectId);


        $user = Users::getUser($_SESSION["id"]);

        $data = array(
            "containerValue" => Objects::getObjectValue($objectId),
            "name" => $user["UserInfoFirstName"] . " " . $user["UserInfoLastName"],
            "date" => date("Y-m-j"),
            "objects" => $toRender,
            "total" => (Objects::getVisibleObjectContentValue($objectId,$_SESSION["id"]) + Objects::getObjectValue($objectId)) * Objects::getObjectQuantity($objectId),
            "container" => $container["ObjectName"],
            "containerId" => $container["ObjectId"],
            "containerQuantity" => $container["ObjectQuantity"]
        );

        $this->renderTemplate(file_get_contents("public/html/fullreport.html"), $data);
    }

    //Déplace un objet(item) dans un emplacement spécifique(target)
    function moveItem()
    {
        $item = $_POST["item"];
        $target = $_POST["target"];
        Resources::deleteZonesFromObjets($item);
        Objects::updateContainer($item,$target);
    }

    //Crée un prêt sur on objet
    function lendItem()
    {
        $object = $_POST["object"];
        $contact = $_POST["contact"];
        $initDate = date("Y-m-d");
        $dateEnd = $_POST["dateEnd"];
        Loans::addLoan($object,$contact,$initDate,$dateEnd);
    }
}
