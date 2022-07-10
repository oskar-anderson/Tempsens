<?php

namespace App\viewController;

require_once(__DIR__."/../../vendor/autoload.php");

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensorReadingTmp;
use App\db\dal\DalSensors;
use App\db\DbHelper;
use App\model\SensorReading;
use DateTime;

// (new Debug())->main();

class Debug
{
   public array $debugs = [];

   function main(): void
   {


      $from = '202006010000';
      $to = '202012010000';
      $iterations = 30;


      $times = [];
      if (false) {
         for ($i = 0; $i < $iterations; $i++) {
            $before = microtime(true);
            (new DalSensorReadingTmp())->GetAllBetweenTest3($from, $to);
            array_push($times, microtime(true) - $before);
         }
      }


      array_push($this->debugs,'Select all avg time ' . array_sum($times) / $iterations);
      array_push($this->debugs,'Select all times ' . var_export($times, true));
      $times = [];

      for ($i = 0; $i < $iterations; $i++) {
         $before = microtime(true);
         $pdo = DbHelper::GetPDO();
         $qry = "SELECT Id, SensorId, " .
            "Temp, RelHum, " .
            "DateRecorded, DateAdded " .
            " FROM " . ' SensorReading' .
            " WHERE DateRecorded >= ? " .
            " AND DateRecorded <= ? " .
            " ORDER BY DateRecorded ASC";
         $stmt = $pdo->prepare($qry);
         $stmt->execute([$from, $to]);
         array_push($times, microtime(true) - $before);
      }
      array_push($this->debugs,'Select all but sensorId avg time ' . array_sum($times) / $iterations);
      array_push($this->debugs,'Select all but sensorId times ' . var_export($times, true));

      foreach ($this->debugs as $debug) {
         echo var_export($debug, true);
         echo '\n';
      }
   }
}
