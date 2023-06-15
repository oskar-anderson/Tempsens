<?php /** @noinspection PhpArrayPushWithOneElementInspection */


namespace App\db;

require_once(__DIR__."/../../vendor/autoload.php");

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensors;
use App\db\migrations\V2\SensorReadingV2;
use App\db\migrations\V2\SensorV2;
use App\domain\Sensor;
use App\domain\SensorReading;
use App\util\Base64;
use App\util\Config;
use App\Util\Console;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Illuminate\Support\Collection;

// Run this from terminal
// php -r "require './Initializer.php'; App\db\Initializer::Initialize();"

// Script class to generate initial database, call from command line
class Initializer
{
   public static function Initialize(): void {
      $console = (new Console(Console::$Linefeed, true));
      $console->WriteLine();
      $name = (new Config())->GetDatabaseName();
      $dropStatement = "DROP DATABASE IF EXISTS {$name};";
      $console->WriteLine($dropStatement);
      $pdo = DbHelper::GetPDO();
      $pdo->exec($dropStatement);
      $createStatement = "CREATE DATABASE IF NOT EXISTS {$name};";
      $console->WriteLine($createStatement);
      $pdo->exec($createStatement);

      $console->WriteLine("Creating tables...");
      DbHelper::CreateTables();
      if ((new Config())->IsDbInitGenerateDbWithSampleData()) {
         $console->WriteLine("Initializing data...");
         Initializer::InitializeData();
      }
      $console->WriteLine("All good!");
   }

   public static function InitializeData(): void
   {
      // $csv = array_map('str_getcsv', file('backupCSV/202104201605-V1_0_0/sensorsV1_0_0.csv'));
      $file = fopen(__DIR__ . "/backupCSV/V2/sensors.csv","r");
      $sensors = Sensor::NewArray();
      for($i = 0; $line = fgetcsv($file, separator: ","); $i++)
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
         $sensorV2 = new SensorV2(
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
         array_push($sensors, $sensorV2->MapToModel());
      }

      fclose($file);

      $file = fopen(__DIR__ . "/backupCSV/V2/sensor-readings.csv","r");
      $sensorReadings = SensorReading::NewArray();
      for($i = 0; $line = fgetcsv($file, separator: ","); $i++)
      {
         // var_dump($line);
         if ($i === 0) continue; // skip first line

         $id = (string) $line[0];
         $sensorId = (string) $line[1];
         $temp = floatval($line[2]);
         $relHum = floatval($line[3]);
         $dateRecorded = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, (string) $line[4], new DateTimeZone('UTC'));
         $dateAdded = (string) $line[5] !== "NULL" ? DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, (string) $line[5], new DateTimeZone('UTC')) : null;
         $sensorReadingV2 = new SensorReadingV2(
            $id,
            $sensorId,
            $temp,
            $relHum,
            $dateRecorded,
            $dateAdded,
         );
         /** @var Sensor|null $sensor */
         $sensor = (new Collection($sensors))->first(fn(Sensor $x) => $x->id === $sensorId);
         if ($sensor === null) {
            throw new Exception('Sensor with id:' . $sensorId . ' does not exist!');
         }
         array_push($sensorReadings, $sensorReadingV2->MapToModel());
      }
      fclose($file);

      $pdo = DbHelper::GetPDO();
      $pdo->beginTransaction();

      $console = new Console(Console::$Linefeed, true);
      $console->WriteLine('Transaction adding table sensors: ' . sizeof($sensors));
      (new DalSensors())->InsertByChunk($sensors, $pdo);

      $console->WriteLine('Transaction adding table sensorReadings: ' . sizeof($sensorReadings));
      (new DalSensorReading())->InsertByChunk($sensorReadings, $pdo);

      $console->WriteLine('Committing transactions...');
      $pdo->commit();
   }
}
