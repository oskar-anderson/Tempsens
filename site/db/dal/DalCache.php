<?php

namespace App\db\dal;

use App\db\DbHelper;
use App\model\Cache;
use App\model\SensorReading;
use App\util\Base64;
use DateTimeImmutable;
use Exception;
use PDO;

class DalCache extends AbstractDalBase
{
   public static function  getLastSensorReadingType(): string { return "LastSensorReadings"; }

   public function GetTableName(): string { return "Cache"; }

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
    * DO NOT USE!!! Only here because inheritance! All different cache keys will implement their own!
    */
   public function Map(array $value): array
   {
      throw new Exception("Will not be implemented!");
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
    *  @param PDO $pdo
    */
   public function DeleteByKey(string $key, PDO $pdo): void
   {
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
      $pdo->beginTransaction();
      $this->DeleteByKey($object->getType(), $pdo);
      $this->Insert([$object], $pdo);
      $pdo->commit();
   }


   /* @param SensorReading[] $sensorReadings assoc array */
   public static function SaveSensorReadings(array $sensorReadings): void
   {
      (new DalCache())->Update(
         (new Cache(true, true, true))->
         setId(Base64::GenerateId())->
         setType(DalCache::getLastSensorReadingType())->
         setContent($sensorReadings)
      );
   }

   /**
    * @return SensorReading[] assoc array
    * @throws Exception
    */
   public static function ReadSensorReadings(): array {
      // DB query
      $qry = "SELECT Key0, Content " .
         " FROM " . (new DalCache())->GetDatabaseNameDotTableName() .
         " WHERE Key0 = ? LIMIT 1" .
         ";";
      $pdo = DbHelper::GetPDO();
      $stmt = $pdo->prepare($qry);
      $stmt->execute([DalCache::getLastSensorReadingType()]);

      if ($stmt->rowCount() !== 1) {
         throw new Exception("Internal error! No SensorReadings row in " . (new DalCache())->GetDatabaseNameDotTableName() . "!");
      }
      $dalCache = $stmt->fetch();

      // Mapping
      $lastReadingBySensorIdAssocArr = SensorReading::NewArray();
      if (DalCache::getLastSensorReadingType() === $dalCache['Key0']) {

         $content = json_decode($dalCache['Content'], true);
         foreach ($content as $key => $item) {
            if ($item === null) {
               $lastReadingBySensorIdAssocArr[$key] = null;
            } else {
               // stupid PHP. Can you cast to SensorReadings directly, without?
               $sensorReading = new SensorReading(
                  $item["id"],
                  $item["sensorId"],
                  $item["temp"],
                  $item["relHum"],
                  DateTimeImmutable::createFromFormat('YmdHi', $item["dateRecorded"]),
                  $item["dateAdded"] === null ? null : DateTimeImmutable::createFromFormat('YmdHi', $item["dateAdded"])
               );
               $lastReadingBySensorIdAssocArr[$key] = $sensorReading;
            }
         }
      }
      return $lastReadingBySensorIdAssocArr;
   }
}