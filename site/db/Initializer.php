<?php /** @noinspection PhpArrayPushWithOneElementInspection */


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


Initializer::Initialize('tempsens20210530');
// Initializer::InitializeData();

// Script class to generate initial database, call from command line
class Initializer
{
   public static function Initialize(string $name) {
      Console::WriteLine();
      $pdo = DbHelper::GetPDO();
      $dropStatement = "DROP DATABASE IF EXISTS {$name};";
      Console::WriteLine($dropStatement, true);
      $pdo->exec($dropStatement);
      $createStatement = "CREATE DATABASE IF NOT EXISTS {$name};";
      Console::WriteLine($createStatement, true);
      $pdo->exec($createStatement);

      Console::WriteLine("Creating tables...", true);
      DbHelper::CreateTables();
      Console::WriteLine("Initialisizing data...", true);
      Initializer::InitializeData();
      Console::WriteLine("All good!", true);
   }

   public static function InitializeData() {
      // $csv = array_map('str_getcsv', file('backupCSV/202104201605-V1_0_0/sensorsV1_0_0.csv'));
      $file = fopen("backupCSV/202104201605-V1_0_0/sensors.csv","r");
      $sensors = Sensor::NewArray();
      for($i = 0; $line = fgetcsv($file, separator: ";"); $i++)
      {
         // var_dump($line);
         if ($i === 0) continue; // skip first line

         $id = (string) $line[0];
         $name = (string) $line[1];
         $serial = (string) $line[2];
         $model = (string) $line[3];
         $ip = (string) $line[4];
         $location = (string) $line[5];
         $isPortable = $line[6] != "false"; // don't use boolval
         $minTemp = floatval($line[7]);
         $maxTemp = floatval($line[8]);
         $minRelHum = floatval($line[9]);
         $maxRelHum = floatval($line[10]);
         $readingIntervalMinutes = intval($line[11]);
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
         array_push($sensors, $sensor_V1_0_0->MapToModel());
      }
      
      fclose($file);

      $file = fopen("backupCSV/202104201605-V0_3_4/sensor-readings.csv","r");
      $sensorReadings = SensorReading::NewArray();
      for($i = 0; $line = fgetcsv($file, separator: ";"); $i++)
      {
         // var_dump($line);
         if ($i === 0) continue; // skip first line

         $id = (string) $line[0];
         $passKey = (string) $line[1];
         $device = (string) $line[2];
         $temp = floatval($line[3]);
         $relHum = floatval($line[4]);
         $compQuant = floatval($line[5]);
         $pressure = floatval($line[6]);
         $alarms = (string) $line[7];
         $compType = (string) $line[8];
         $tempU = (string) $line[9];
         $pressureU = (string) $line[10];
         $timer = intval($line[11]);
         $dactdate = (string) $line[12];
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
         array_push($sensorReadings, $sensorReadingV0_3_4->GetUp($sensor->id, $sensor->isPortable)->MapToModel());
      }
      fclose($file);
      
      $pdo = DbHelper::GetPDO();
      $pdo->beginTransaction();

      Console::WriteLine('Transaction adding sensors: ' . sizeof($sensors), true);
      foreach ($sensors as $sensor) {
         (new DalSensors())->Create($sensor, $pdo);
      }

      Console::WriteLine('Transaction adding sensorReadings: ' . sizeof($sensorReadings), true);
      foreach ($sensorReadings as $sensorReading) {
         (new DalSensorReading())->Create($sensorReading, $pdo);
      }

      $pdo->commit();

      DalSensorReading::GetLastReadingsFromCacheOrDatabase($sensors);
   }
}