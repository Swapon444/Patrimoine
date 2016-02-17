<?php

namespace model;
use \repository\Db as Db;
class Resources
{
    //Insérer une image dans la base de données
    static function addImage($_objectId, $_image)
    {
        return Db::execute("INSERT INTO Images
        (ImageObject, ImageBlob)
        VALUES (?, ?)", array($_objectId, $_image));
		
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
