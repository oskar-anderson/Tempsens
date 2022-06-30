<?php


namespace App\db;

require_once(__DIR__."/../../vendor/autoload.php");

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensorReadingTmp;
use App\db\dal\DalSensors;
use App\db\migrations\V0_3_4\MigrateSensorReading;
use App\db\migrations\V0_3_4\SensorReadingV0_3_4;
use App\db\migrations\V0_3_4\SensorV0_3_4;
use App\db\migrations\V1_0_0\SensorV1_0_0;
use App\db\migrations\V1_0_0\SensorReadingV1_0_0;
use App\model\Sensor;
use App\model\SensorReading;
use PDO;
use PDOException;
use App\Util\Console;


// Initializer::Initialize('tempsens20210530');


// Script class to generate initial database, call from command line
class Initializer
{
   public static function Initialize(string $name) {
      Console::WriteLine();
      $pdo = DbHelper::GetPdoByKey('db_local_dev');
      $dropStatement = "DROP DATABASE IF EXISTS {$name};";
      Console::WriteLine($dropStatement, true);
      $pdo->exec($dropStatement);
      $createStatement = "CREATE DATABASE IF NOT EXISTS {$name};";
      Console::WriteLine($createStatement, true);
      $pdo->exec($createStatement);

      Console::WriteLine("Creating tables...", true);
      DbHelper::CreateTables();
      // Console::WriteLine("Initialisizing data...", true);
      // Initializer::InitializeData();
   }

   public static function InitializeData() {
      $file = fopen("backupCSV/202104201605-V1_0_0/sensors.csv","r");
      $sensors = [];
      for($i = 0; ! feof($file); $i++)
      {
         $line = fgetcsv($file, separator: ";");
         if ($i === 0) continue; // skip first line

         $id = $line[0];
         $name = $line[1];
         $serial = $line[2];
         $model = $line[3];
         $ip = $line[4];
         $location = $line[5];
         $isPortable = $line[6];
         $minTemp = $line[7];
         $maxTemp = $line[8];
         $minRelHum = $line[9];
         $maxRelHum = $line[10];
         $readingIntervalMinutes = $line[11];
         $sensor_V1_0_0 = new SensorV1_0_0(
            $id, 
            $name, 
            $serial, 
            $model, 
            $ip, 
            $location, 
            $isPortable, 
            $minTemp,
            $maxTemp, 
            $minRelHum, 
            $maxRelHum, 
            $readingIntervalMinutes
         );
         array_push($sensors, $sensor_V1_0_0);
      }
      
      fclose($file);

      $file = fopen("backupCSV/202104201605-V0_3_4/sensor-readings.csv","r");
      $sensorReadings = [];
      for($i = 0; ! feof($file); $i++)
      {
         $line = fgetcsv($file, separator: ";");
         if ($i === 0) continue; // skip first line

         $id = $line[0];
         $passKey = $line[1];
         $device = $line[2];
         $temp = $line[3];
         $relHum = $line[4];
         $compQuant = $line[5];
         $pressure = $line[6];
         $alarms = $line[7];
         $compType = $line[8];
         $tempU = $line[9];
         $pressureU = $line[10];
         $timer = $line[11];
         $dactdate = $line[12];
         $sensorReadingV0_3_4 = new SensorReadingV0_3_4(
            $id, 
            $passKey, 
            $device, 
            $temp, 
            $relHum, 
            $compQuant, 
            $pressure, 
            $alarms, 
            $compType, 
            $tempU, 
            $pressureU, 
            $timer,
            $dactdate
         );
         $sensor = SensorReading::GetSensorBySerial($sensors, $passKey);
         array_push($sensorReadings, $sensorReadingV0_3_4->GetUp($sensor->id, $sensor->portable));
      }
      fclose($file);
      
      $pdo = DbHelper::GetDevPDO();
      $pdo->beginTransaction();

      foreach ($sensors as $sensor) {
         (new DalSensors())->Create($sensor, $pdo);
      }

      foreach ($sensorReadings as $sensorReadingV1_0_0) {
         (new DalSensorReading())->Create($sensorReadingV1_0_0, $pdo);
      }

      $pdo->commit();

      DalSensorReading::GetLastReadingsFromCacheOrDatabase($sensors);
   }
}