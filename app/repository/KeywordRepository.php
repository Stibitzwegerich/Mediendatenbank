<?php

namespace App\Repository;

use App\database\DbConnection;
use App\Repository\MediumRepository;
use App\Model\KeywordModel;

class KeywordRepository
{

    private $conn;
    private $mediumRepository;
    public function __construct()
    {
        $this->mediumRepository = new MediumRepository();
        $this->conn = DbConnection::getInstance()->getConnection();
    }

    public function createKeyword($keyword, $currentUserId)
    {
        $keywordName = $keyword->getName();
        $stmt = $this->conn->prepare("INSERT INTO Schlagworte (Schlagwort_Name, Benutzer_ID) VALUES (?, ?)");
        $stmt->bind_param("si", $keywordName, $currentUserId);
        $stmt->execute();
        $stmt->close();
    }

    public function updateKeywordName($keywordId, $keywordName)
    {
        $stmt = $this->conn->prepare("UPDATE Schlagworte SET Schlagwort_Name = ? WHERE Schlagwort_ID = ?");
        $stmt->bind_param("si", $keywordName, $keywordId);
        $stmt->execute();
        $stmt->close();
    }

    public function deleteKeyword(int $keywordId)
    {
        $stmt = $this->conn->prepare("DELETE FROM SchlagwortMedien WHERE Schlagwort_ID = ?");
        $stmt->bind_param("i", $keywordId);
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("DELETE FROM Schlagworte WHERE Schlagwort_ID = ?");
        $stmt->bind_param("i", $keywordId);
        $stmt->execute();
        $stmt->close();
    }


    public function readAllKeywordsWithAssociations($currentUserId)
    {
        $keywords = [];
        $associations = [];

        $stmt = $this->conn->prepare("SELECT * FROM Schlagworte WHERE Benutzer_ID = ?");
        $stmt->bind_param("i", $currentUserId);
        $stmt->execute();
        $resultKeywords = $stmt->get_result();

        while ($keyword = $resultKeywords->fetch_assoc()) {
            $keywords[] = $keyword;
        }
        $stmt->close();


        foreach ($keywords as $keyword) {
            $keywordId = $keyword['Schlagwort_ID'];
            $stmt = $this->conn->prepare("SELECT * FROM SchlagwortMedien WHERE Schlagwort_ID = ?");
            $stmt->bind_param("i", $keywordId);
            $stmt->execute();
            $resultAssociations = $stmt->get_result();
            while ($association = $resultAssociations->fetch_assoc()) {
                $associations[] = $association;
            }

            $stmt->close();
        }
        return [$keywords, $associations];
    }

    public function assignKeywordToMedia($keywordId, $mediaId)
    {
        $columnName = $this->mediumRepository->idTypeToTableId($mediaId);
        if (!$this->checkIfAssociationExists($keywordId, $mediaId, $columnName)) {
            $stmt = $this->conn->prepare("INSERT INTO SchlagwortMedien (Schlagwort_ID, $columnName) VALUES (?, ?)");
            $stmt->bind_param("is", $keywordId, $mediaId);
            $stmt->execute();
            $stmt->close();
        }
    }

    public function removeKeywordFromMedia($keywordId, $mediaId)
    {
        $columnName = $this->mediumRepository->idTypeToTableId($mediaId);
        $stmt = $this->conn->prepare("DELETE FROM SchlagwortMedien WHERE Schlagwort_ID = ? AND $columnName = ?");
        $stmt->bind_param("is", $keywordId, $mediaId);
        $stmt->execute();
        $stmt->close();
    }

    public function deleteAssociationByKeywordId($keywordId)
    {
        $stmt = $this->conn->prepare("DELETE FROM SchlagwortMedien WHERE Schlagwort_ID = ?");
        $stmt->bind_param("i", $keywordId);
        $stmt->execute();
        $stmt->close();
    }

    public function readKeywordAmountPerUser($userId)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(Schlagwort_ID) FROM Schlagworte WHERE Benutzer_ID = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $amount[] = $row;
        }
        $stmt->close();
        return $amount;
    }

    public function getkeywordsforSentMedia($mediaId)
    {
        $associations = [];
        $tableName = $this->mediumRepository->idTypeToTableId($mediaId);

        $stmt = $this->conn->prepare("SELECT * FROM SchlagwortMedien WHERE $tableName = ?");
        $stmt->bind_param("s", $mediaId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $associations[] = $row;  
        }
        $stmt->close();
        return $associations;
    }

    public function checkIfAssociationExists($keywordId, $mediaId, $columnName)
    {
        $stmt = $this->conn->prepare("SELECT * FROM SchlagwortMedien WHERE Schlagwort_ID = ? AND $columnName = ?");
        $stmt->bind_param("is", $keywordId, $mediaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->num_rows > 0;
    }
}
