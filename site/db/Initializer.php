<?php


namespace App\db;

require_once(__DIR__."/../../vendor/autoload.php");

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensorReadingTmp;
use App\db\dal\DalSensors;
use App\db\migrations\v1\MigrateSensorReading;
use App\model\Sensor;
use PDO;
use PDOException;

Initializer::Initialize('tempsens20210530');

// Script class to generate initial database, call from command line
class Initializer
{
   public static function Initialize(string $name) {
      try {
         $pdo = new PDO('mysql:host=localhost', 'root', '');
         $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      } catch (PDOException $e) {
         die("FAILED (" . $e->getMessage() . ")\n");
      }
      echo "New PDO mysql:host=localhost \n";
      $stmt = "CREATE DATABASE IF NOT EXISTS " . $name . ";";
      echo $stmt . "\n";
      $pdo->exec($stmt);

      DbHelper::DropTables();
      DbHelper::CreateTables();
      Initializer::InitializeData();

   }

   public static function InitializeData() {
      $sensors = [
         new Sensor(
            id:"00000001x0000x00000001",
            name: "EE01-Pharma",
            serial: "18967632",
            model: "T3510",
            ip: "http://10.37.2.15",
            location: "Salve 2c 3. korruse ravimiladu",
            isPortable: false,
            minTemp: 15, maxTemp: 25,
            minRelHum: 20, maxRelHum: 60,
            readingIntervalMinutes: 15,
         ),
         new Sensor(
            id:"00000001x0000x00000002",
            name:"EE02-Z_Ladu-U",
            serial: "20960014",
            model: "T3510",
            ip: "http://10.37.2.16",
            location: "Salve 2c 1. korruse lao keskel ülal",
            isPortable: false,
            minTemp: 15, maxTemp: 25,
            minRelHum: 20, maxRelHum: 60,
            readingIntervalMinutes: 15,
         ),
         new Sensor(
            id:"00000001x0000x00000003",
            name:"EE03-Z_Ladu-D",
            serial: "20960015",
            model: "T3510",
            ip: "http://10.37.2.17",
            location: "Salve 2c 1. korruse lao tagasein",
            isPortable: false,
            minTemp: 15, maxTemp: 25,
            minRelHum: 20, maxRelHum: 60,
            readingIntervalMinutes: 15,
         ),
         new Sensor(
            id:"00000001x0000x00000004",
            name:"EE04-Labeling",
            serial: "20960047",
            model: "T3510",
            ip: "http://10.37.2.18",
            location: "Salve 2c 1. korruse kleepsuruum",
            isPortable: false,
            minTemp: 15, maxTemp: 25,
            minRelHum: 20, maxRelHum: 60,
            readingIntervalMinutes: 15,
         ),
         new Sensor(
            id:"00000001x0000x00000005",
            name:"EE05-D_Ladu",
            serial:"20960050",
            model:"T3510",
            ip:"http://10.37.2.19",
            location: "Salve 2c Bepulsaar",
            isPortable: false,
            minTemp: 15, maxTemp: 25,
            minRelHum: 20, maxRelHum: 60,
            readingIntervalMinutes: 15,
         ),
         new Sensor(
            id:"00000001x0000x00000006",
            name:"EE07-Portable",
            serial: "19260003",
            model: "M1140",
            ip: "http://10.37.2.14",
            location: "Liikumises",
            isPortable: true,
            minTemp: 0, maxTemp: 100,
            minRelHum: 0, maxRelHum: 100,
            readingIntervalMinutes: 30,
         ),
      ];

      $sensorReadings = MigrateSensorReading::GetUpSensorReading(DbHelper::GetPdoByKey("db_local_dev"), $sensors);

      $pdo = DbHelper::GetDevPDO();
      $pdo->beginTransaction();

      echo "Adding data sensors" . "\n";
      foreach ($sensors as $sensor) {
         (new DalSensors())->Create($sensor, $pdo);
      }

      echo "Adding data sensorReadings" . "\n";
      foreach ($sensorReadings[0] as $sensorReading) {
         (new DalSensorReading())->Create($sensorReading, $pdo);
      }
      foreach ($sensorReadings[1] as $sensorReading) {
         (new DalSensorReadingTmp())->Create($sensorReading, $pdo);
      }

      $pdo->commit();

      DalSensorReading::GetLastReadingsCacheIfNotExistUpdate($sensors);


   }
}