<?php

namespace App\Repository;

use App\Database\DbConnection;

class MediumRepository
{
    private $conn;

    public function __construct()
    {
        $this->conn = DbConnection::getInstance()->getConnection();
    }

    public function createPhotoMedium($id, $fileName, $filePath, $fileType, $fileSize, $uploadDate,  $resolution, $userId)
    {
        $stmt = $this->conn->prepare("INSERT INTO Fotos (Foto_ID, Titel, Dateipfad, Typ, Dateigröße, Hochlade_datum, Auflösung, Benutzer_ID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssi", $id, $fileName, $filePath, $fileType, $fileSize, $uploadDate, $resolution, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function createVideoMedium($id, $fileName, $filePath, $fileType, $fileSize, $uploadDate,  $resolution, $duration, $userId)
    {
        $stmt = $this->conn->prepare("INSERT INTO Videos (Video_ID, Titel, Dateipfad, Typ, Dateigröße, Hochlade_datum, Auflösung, Dauer, Benutzer_ID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssi", $id, $fileName, $filePath, $fileType, $fileSize, $uploadDate, $resolution, $duration, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function createAudioBookMedium($id, $fileName, $filePath, $fileType, $fileSize, $uploadDate,  $speaker, $duration, $userId)
    {
        $stmt = $this->conn->prepare("INSERT INTO Hörbücher (Hörbuch_ID, Titel, Dateipfad, Typ, Dateigröße, Hochlade_datum, Sprecher, Dauer, Benutzer_ID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssi", $id, $fileName, $filePath, $fileType, $fileSize, $uploadDate, $speaker, $duration, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function createEbookMedium($id, $fileName, $filePath, $fileType, $fileSize, $uploadDate,  $author, $pages, $userId)
    {
        $stmt = $this->conn->prepare("INSERT INTO Ebooks (ebook_ID, Titel, Dateipfad, Typ, Dateigröße, Hochlade_datum, Autor, Seitenzahl, Benutzer_ID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssii", $id, $fileName, $filePath, $fileType, $fileSize, $uploadDate, $author, $pages, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function updateMedium($id, $title)
    {
        $mediaType = $this->getMediaTypeById($id);
        $tableName = $this->nameConverterDbName($mediaType);
        $idQuery = $this->nameConverterId($tableName);
        $stmt = $this->conn->prepare("UPDATE $tableName SET Titel = ? WHERE $idQuery  = ?");
        $stmt->bind_param("ss", $title, $id);
        $stmt->execute();
    }

    public function readAllMedia($currentUserId, $direction, $sortingParamter, $searchParameter) //sort asc/desc/size/date
    {
        $mediaTypes = ['Fotos', 'Videos', 'Hörbücher', 'Ebooks'];
        $results = [];

        foreach ($mediaTypes as $type) {

            $query = "SELECT * FROM $type WHERE Benutzer_ID = ?";

            if ($searchParameter) {
                $query .= " AND Titel LIKE ?";
            }

            $query .= " ORDER BY $sortingParamter $direction";
            $stmt = $this->conn->prepare($query);

            if ($searchParameter) {
                $searchParameter = "%" . $searchParameter . "%";
                $stmt->bind_param("ss", $currentUserId, $searchParameter);
            } else {
                $stmt->bind_param("s", $currentUserId);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $mediaData = [];
            while ($row = $result->fetch_assoc()) {
                $mediaData[] = $row;
            }
            $results[$type] = $mediaData;
            $stmt->close();
        }

        return $results;
    }

    public function readMediaAmountPerUser($userId)
    {
        $mediaTypes = ['Fotos', 'Videos', 'Hörbücher', 'Ebooks'];
        $results = [];

        foreach ($mediaTypes as $type) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM $type WHERE Benutzer_ID = ?");
            $stmt->bind_param("s", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $mediaData = [];
            while ($row = $result->fetch_assoc()) {
                $mediaData[] = $row;
            }
            $results[$type] = $mediaData;
            $stmt->close();
        }

        return $results;
    }
    public function getMediaTypeById($id)
    {
        $tables = ['Fotos', 'Videos', 'Hörbücher', 'Ebooks'];
        foreach ($tables as $table) {
            if($table == 'Hörbücher') {
                $idQuery = "Hörbuch_ID";
            } else {
                $idQuery = rtrim($table, 's') . "_ID";
            }
            $query = "SELECT Typ FROM $table WHERE $idQuery = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $stmt->close();
                $mediumtype = $row['Typ'];
               return $mediumtype;
            }
            $stmt->close();
        }
        return null;
    }

    public function deleteMedium($id)
    {
        $tablename = $this->nameConverterDbName($this->getMediaTypeById($id));
        $idQuery = $this->nameConverterId($tablename);
        $stmt = $this->conn->prepare("DELETE FROM SchlagwortMedien  WHERE $idQuery = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("DELETE FROM $tablename WHERE $idQuery = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $stmt->close();
    }

    public function getAllMediaIdsPerUser($userId)
    {
        $mediaTypes = ['Fotos', 'Videos', 'Hörbücher', 'Ebooks'];
        $results = [];

        foreach ($mediaTypes as $type) {
            $tableId = $this->nameConverterId($type);
            $stmt = $this->conn->prepare("SELECT $tableId FROM $type WHERE Benutzer_ID = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $mediaData = [];
            while ($row = $result->fetch_assoc()) {
                $mediaData[] = $row;
            }
            $results[$type] = $mediaData;
            $stmt->close();
        }

        return $results;
    }

    public function getAllMediaIdsForUserPureId($userId)
    {
        $mediaTypes = ['Fotos', 'Videos', 'Hörbücher', 'Ebooks'];
        $results = [];
    
        foreach ($mediaTypes as $type) {
            $tableId = $this->nameConverterId($type);
            $stmt = $this->conn->prepare("SELECT $tableId FROM $type WHERE Benutzer_ID = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $mediaData = [];
            while ($row = $result->fetch_assoc()) {
                $mediaData[] = $row[$tableId];
            }
            $results = array_merge($results, $mediaData);
            $stmt->close();
        }
    
        return $results;
    }

    public function nameConverterDbName($medium) //from english to db table name
    {
        switch ($medium) {
            case 'photo':
                return 'Fotos';
            case 'video':
                return 'Videos';
            case 'audiobook':
                return 'Hörbücher';
            case 'ebook':
                return 'Ebooks';
        }
    }
    public function nameConverterId($medium) //from db table name to id
    {
        switch ($medium) {
            case 'Fotos':
                return 'Foto_ID';
            case 'Videos':
                return 'Video_ID';
            case 'Hörbücher':
                return 'Hörbuch_ID';
            case 'Ebooks':
                return 'ebook_ID';
        }
    }
    
    public function idTypeToTableId($mediumId) {
        $medium = $this->getMediaTypeById($mediumId);
       return $this->nameConverterId($this->nameConverterDbName($medium));
    }
}
