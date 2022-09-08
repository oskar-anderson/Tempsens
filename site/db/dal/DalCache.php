<?php

namespace App\db\dal;

use App\db\DbHelper;
use App\model\Cache;
use App\model\SensorReading;
use Exception;
use PDO;

class DalCache extends AbstractDalBase
{
   public static function  getLastSensorReadingType(): string {
      return "LastSensorReadings";
   }

   public function GetTableName(): string
   {
      return "Cache";
   }

   public function SqlCreateTableStmt(): string
   {
      // Key0 is just named like that to avoid using sql keywords, nothing to do with enumeration
      $result = "create table " . $this->GetDatabaseNameDotTableName() .
         " ( " .
         "Id VARCHAR(64) NOT NULL PRIMARY KEY, " .
         "Key0 TEXT(64) NOT NULL, " .
         "Content TEXT NOT NULL " .
         " ) DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin;";
      return $result;
   }

   /**
    *  @param array $value
    *  @return SensorReading[]
    */
   public function Map(array $value): array
   {
      $arr = SensorReading::NewArray();
      if (DalCache::getLastSensorReadingType() === $value['Key0']) {

         $content = json_decode($value['Content'], true);
         foreach ($content as $key => $item) {
            if ($item === null) {
               $arr[$key] = null;
            } else {
               // stupid PHP. Can you cast to SensorReadings directly, without?
               $sensorReading = new SensorReading(
                  $item["id"],
                  $item["sensorId"],
                  $item["temp"],
                  $item["relHum"],
                  $item["dateRecorded"],
                  $item["dateAdded"]
               );
               $arr[$key] = $sensorReading;
            }
         }
      }
      return $arr;
   }

   /**
    *  @param Cache[] $objects
    *  @param PDO $pdo
    */
   protected function Insert(array $objects, PDO $pdo): void
   {
      $qry = "INSERT INTO " . $this->GetDatabaseNameDotTableName() . " ( " .
         "Id, " .
         "Key0, " .
         "Content ) " .
         " VALUES " . $this->getPlaceHolders(numberOfQuestionMarks: 3, numberOfRows: sizeof($objects)) . ";";
      $stmt = $pdo->prepare($qry);
      $params = [];
      foreach ($objects as $object) {
         array_push($params, $object->getId(), $object->getType(), $object->GetContent());
      }
      $stmt->execute($params);
   }

   /**
    * @param string $key
    * @return SensorReading[]|false
    * @throws Exception No such field
    */
   public function GetByKeyFirstOrFalse(string $key): array|bool
   {
      $qry = "SELECT Key0, Content " .
         " FROM " . $this->GetDatabaseNameDotTableName() .
         " WHERE Key0 = ? LIMIT 1" .
         ";";
      $pdo = DbHelper::GetPDO();
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$key]);

      if ($stmt->rowCount() === 0) {
         // throw new Exception("No field with $key in " . $this->GetDatabaseNameDotTableName() . "!");
         return false;
      }
      return $this->Map($stmt->fetch());
   }

   /**
    *  @param string $id
    */
   public function Delete(string $id): void
   {
      $pdo = DbHelper::GetPDO();
      $qry = "DELETE FROM " . $this->GetDatabaseNameDotTableName() . " WHERE Id = ?";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$id]);
   }

   /**
    *  @param string $key
    */
   public function DeleteByKey(string $key): void
   {
      $pdo = DbHelper::GetPDO();
      $qry = "DELETE FROM " . $this->GetDatabaseNameDotTableName() . " WHERE Key0 = ?";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$key]);
   }

   /**
    *  @param Cache $object
    */
   public function Update($object): void
   {
      $pdo = DbHelper::GetPDO();
      $qry = "UPDATE " . $this->GetDatabaseNameDotTableName() . " SET " .
         "Content = ? " .
         "WHERE Key0 = ?";
      $stmt = $pdo->prepare($qry);
      $stmt->execute([$object->getContent(), $object->getType() ]);
   }


}