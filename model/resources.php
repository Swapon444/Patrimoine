<?php

namespace model;
use \repository\Db as Db;
class Resources
{
    //Insérer une image dans la base de données
    static function addImage($_objectId, $_image)
    {
		set_time_limit(0);
		//set_init_time(0);
		// $objectLast = Db::query("SELECT TOP 1 ImageId FROM Images ORDER BY ImageID DESC");
		
		
	  //  $objectLast = Db->prepare("SELECT Last(Tables.ImageId) FROM Images");
	//	$objectLast.execute();
		
		//$imgID = $objectLast->fetch()["ImageId"];
		//echo $imgID."<br>";
		
		//Db::execute("INSERT INTO Images (ImageObject, ImageBlob) VALUES (?, ?)", array($_objectId, $_image));
		
	/*	do 
		{
			sleep(1);
			//$objectLast = Db->prepare("SELECT Last(ImageId) FROM Images");
			//$objectLast.execute();
			$objectNow = Db::query("SELECT Last(ImageId) FROM Images");
			$imgIDNow = $objectNow->fetch()["ImageId"];
			
			echo $imgIDNow."<br>";		
	
		}while ($imgIDNow == $imgID);*/

		$images = Resources::getImage($_POST['objectId']);
		$imgLast = count($images);
				
		Db::execute("INSERT INTO Images (ImageObject, ImageBlob) VALUES (?, ?)", array($_objectId, $_image));
		
	//	sleep(2);
		
		//$imagesNow = Resources::getImage($_objectId);
	//	$imgNow = count($imagesNow);
		
	//	echo $imgLast;
	//	echo $imgNow;
		
		
		do
		{
			sleep(1);
			$imagesNow = Resources::getImage($_objectId);
			$imgNow = count($imagesNow);
		} while($imgLast == $imgNow);


		 return ;
		
		
		/*
        return Db::execute("INSERT INTO Images
        (ImageObject, ImageBlob)
        VALUES (?, ?)", array($_objectId, $_image));*/
		
    }

    //Supprimer une image
    static function deleteImage($_imageId)
    {
        return Db::execute("DELETE FROM Images
        WHERE ImageId = ?", $_imageId);
    }

    //Créer une zone pour une image.
    static function addZone($_imageId, $_objectId, $_x, $_y, $_width, $_height)
    {
        return Db::execute("INSERT INTO Zones
        (ZonePointTo, ZoneImage, ZoneX, ZoneY, ZoneWidth, ZoneHeight) 
        VALUES (?, ?, ?, ?, ?, ?)", array($_objectId, $_imageId, $_x, $_y, $_width, $_height));
    }

    // Supprimer toutes les zones d'une image
    static function deleteZones($_imageId)
    {
        return Db::execute("DELETE FROM zones
        WHERE ZoneImage = ?", $_imageId);
    }

    //Supprimer les zones d'un objet
    static function deleteZonesFromObjets($_objectId)
    {
        return Db::execute("DELETE FROM Zones
        WHERE ZonePointTo = ?", $_objectId);
    }

    //Obtenir les images d'un objet.
    static function getImage($_objectId)
    {
        return Db::query("SELECT *
        FROM Images 
        WHERE ImageObject = ?", $_objectId);
    }

    //Obtenir les zones d'une image.
    static function getZones($_imageId)
    {
        return Db::query("SELECT *
        FROM Zones INNER JOIN Objects ON ZonePointTo = Objects.ObjectId
        WHERE ZoneImage = ?", $_imageId);
    }
}
