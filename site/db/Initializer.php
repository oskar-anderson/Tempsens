<?php /** @noinspection PhpArrayPushWithOneElementInspection */


namespace App\db;

require_once(__DIR__."/../../vendor/autoload.php");

use App\db\dal\DalCache;
use App\db\dal\DalSensorReading;
use App\db\dal\DalSensorReadingTmp;
use App\db\dal\DalSensors;
use App\db\migrations\V0_3_4\MigrateSensorReading;
use App\db\migrations\V0_3_4\SensorReadingV0_3_4;
use App\db\migrations\V0_3_4\SensorV0_3_4;
use App\db\migrations\V1_0_0\SensorV1_0_0;
use App\db\migrations\V1_0_0\SensorReadingV1_0_0;
use App\model\Cache;
use App\model\Sensor;
use App\model\SensorReading;
use App\model\SensorReadingTmp;
use App\util\Base64;
use App\util\Config;
use App\Util\Console;

// Run this from terminal
// php -r "require './Initializer.php'; App\db\Initializer::Initialize();"

// Script class to generate initial database, call from command line
class Initializer
{
   public static function Initialize(): void {
      $console = (new Console(Console::$Linefeed, true));
      $console->WriteLine();
      $pdo = DbHelper::GetPDO();
      $name = (new Config())->GetDatabaseName();
      $dropStatement = "DROP DATABASE IF EXISTS {$name};";
      $console->WriteLine($dropStatement);
      $pdo->exec($dropStatement);
      $createStatement = "CREATE DATABASE IF NOT EXISTS {$name};";
      $console->WriteLine($createStatement);
      $pdo->exec($createStatement);

      $console->WriteLine("Creating tables...");
      DbHelper::CreateTables();
      $console->WriteLine("Initializing data...");
      Initializer::InitializeData();
      $console->WriteLine("All good!");
   }

   public static function InitializeData(): void
   {
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
      $debugSensorReadings = SensorReadingTmp::NewArray();
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
         $sensorReadingV1_0_0 = $sensorReadingV0_3_4->GetUp($sensor->id, $sensor->isPortable)->MapToModel();
         array_push($sensorReadings, $sensorReadingV1_0_0);
         $sensorReadingTmp = new SensorReadingTmp(
            $sensorReadingV1_0_0->id, $sensorReadingV1_0_0->sensorId, $sensorReadingV1_0_0->relHum,
            $sensorReadingV1_0_0->temp, $sensorReadingV1_0_0->dateRecorded, $sensorReadingV1_0_0->dateAdded,
            $sensorReadingV1_0_0->id, $sensorReadingV1_0_0->sensorId, $sensorReadingV1_0_0->dateRecorded
         );
         array_push($debugSensorReadings, $sensorReadingTmp);
      }
      fclose($file);

      $pdo = DbHelper::GetPDO();
      $pdo->beginTransaction();

      $console = new Console(Console::$Linefeed, true);
      $console->WriteLine('Transaction adding table sensors: ' . sizeof($sensors));
      (new DalSensors())->InsertByChunk($sensors, $pdo);

      $console->WriteLine('Transaction adding table sensorReadings: ' . sizeof($sensorReadings));
      (new DalSensorReading())->InsertByChunk($sensorReadings, $pdo);

      $cache = [(new Cache(true, true, true))->
         setId(Base64::GenerateId())->
         setType(DalCache::getLastSensorReadingType())->
         setContent([])
      ];
      $console->WriteLine('Transaction adding table cache: ' . sizeof($cache));
      (new DalCache())->InsertByChunk($cache, $pdo);

      $console->WriteLine('Transaction adding debug tables... ');
      $console->WriteLine('Transaction adding table sensorReadingsTmp: ' . sizeof($debugSensorReadings));
      (new DalSensorReadingTmp())->InsertByChunk($debugSensorReadings, $pdo);

      $pdo->commit();

      DalSensorReading::GetLastReadingsFromCacheOrDatabase($sensors);
   }
}
