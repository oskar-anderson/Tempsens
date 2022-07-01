<?php


namespace App\dto;

require_once (__DIR__."/../../vendor/autoload.php");

use App\dto\IndexViewModelChildren\SensorReadingDTO;
use App\model\Sensor;
use DateTime;

class AlertMinMax
{
   public string $beforeDate;
   public string $duration;
   public int $count;
   public float $temp;
   public float $hum;

   private function __construct(DateTime $beforeDate, int $duration, int $count, float $temp, float $hum)
   {
      $this->beforeDate = $beforeDate->format('d/m/Y H:i');
      $this->duration = $duration;
      $this->count = $count;
      $this->temp = $temp;
      $this->hum = $hum;
   }

   /**
    * @param Sensor $sensor
    * @param string $outputDateTimeFormat
    * @param SensorReadingDTO $rawOutOfBound
    * @return AlertMinMax
    */
   public static function CreateNonChainingAlert(Sensor $sensor, string $outputDateTimeFormat, $rawOutOfBound): AlertMinMax
   {
         [$temp, $relHum] = AlertMinMax::GetDeviation($sensor, [$rawOutOfBound]);
         $before = DateTime::createFromFormat($outputDateTimeFormat, $rawOutOfBound->date);
         return new AlertMinMax($before, 0, 1, $temp, $relHum);
   }

   /* @param SensorReadingDTO[] $outOfBoundsTmp */
   private static function Create(Sensor $sensor, string $dateTimeFormat, array $outOfBoundsTmp): AlertMinMax
   {
      $outOfBoundsTmp = array_values($outOfBoundsTmp);
      if (sizeof($outOfBoundsTmp) == 0) {
         die('Invalid input! Array has less than 0 elements!' . var_export($outOfBoundsTmp, true));
      }
      if (sizeof($outOfBoundsTmp) == 1) {
         return AlertMinMax::CreateNonChainingAlert($sensor, $dateTimeFormat, $outOfBoundsTmp[0]);
      }
      $before = $outOfBoundsTmp[0];
      $end = $outOfBoundsTmp[sizeof($outOfBoundsTmp) - 1];
      $before = DateTime::createFromFormat($dateTimeFormat, $before->date);
      $end = DateTime::createFromFormat($dateTimeFormat, $end->date);
      [$temp, $relHum] = AlertMinMax::GetDeviation($sensor, $outOfBoundsTmp);
      $result = new AlertMinMax(
         beforeDate: $before,
         duration: ($end->getTimestamp() - $before->getTimestamp()) / 60,
         count: sizeof($outOfBoundsTmp),
         temp: $temp,
         hum: $relHum
      );
      return $result;
   }

   /* @param SensorReadingDTO[] $outOfBoundsTmp */
   public static function GetDeviation(Sensor $sensor, array $outOfBoundsTmp): array {
      $hums = array_map(function ($x) use ($sensor) {
         return $x->relHum;
      }, $outOfBoundsTmp);
      $temps = array_map(function ($x) use ($sensor) {
         return $x->temp;
      }, $outOfBoundsTmp);
      $lowTemp = min($temps);
      $highTemp = max($temps);
      $lowHum = min($hums);
      $highHum = max($hums);
      $avgTemp = ($sensor->minTemp + $sensor->maxTemp) / 2;
      $avgHum = ($sensor->minRelHum + $sensor->maxRelHum) / 2;
      $temp =  abs($avgTemp) - abs($lowTemp) > abs($avgTemp) -  abs($highTemp) ? $lowTemp : $highTemp;
      $hum =  abs($avgHum) - abs($lowHum) > abs($avgHum) -  abs($highHum) ? $lowHum : $highHum;

      return [$temp, $hum];
   }

   /**
    * @param SensorReadingDTO[] $rawOutOfBounds
    * @return AlertMinMax[]
    */
   public static function Get(Sensor $sensor, string $outputDateTimeFormat, array $rawOutOfBounds): array {
      $outOfBounds = [];
      $outOfBoundsChain = [];

      if (sizeof($rawOutOfBounds) == 0) {
         return [];
      }
      if (sizeof($rawOutOfBounds) === 1) {
         array_push($outOfBounds, AlertMinMax::CreateNonChainingAlert($sensor, $outputDateTimeFormat, $rawOutOfBounds[0]));
         return $outOfBounds;
      }

      // 1 = isPartOfSameChain, 2 = isPartOfChainBreak, 3 = isNotPartOfChain
      // 1    , 1    , 2,   , 3,   , 1
      // 20:58, 21:12, 21:23, 23:45, 00:40, 00:47
      for ($i = 0; $i < sizeof($rawOutOfBounds) - 1; $i++) {
         $rawOutOfBoundBefore = $rawOutOfBounds[$i];
         $rawOutOfBoundAfter = $rawOutOfBounds[$i + 1];

         $isPartOfSameChain = (DateTime::createFromFormat($outputDateTimeFormat, $rawOutOfBoundAfter->date)->getTimestamp() -
               DateTime::createFromFormat($outputDateTimeFormat, $rawOutOfBoundBefore->date)->getTimestamp())
            / 60 <= $sensor->readingIntervalMinutes;
         $isPartOfChainBreak = sizeof($outOfBoundsChain) > 1 && ! $isPartOfSameChain;
         $isNotPartOfChain = sizeof($outOfBoundsChain) == 0 && ! $isPartOfSameChain;
         $state = ["", "isPartOfSameChain", "isPartOfChainBreak", "isNotPartOfChain"][(int) $isPartOfSameChain * 1 + (int) $isPartOfChainBreak * 2 + (int) $isNotPartOfChain * 3];
         if (sizeof($outOfBoundsChain) == 1) die("Program logic error! Chain cannot contain singular element!");  // sanity check
         $isLast = $i === sizeof($rawOutOfBounds) - 2;
         switch ($state) {
            case "isPartOfSameChain":  // leads to isPartOfSameChain or isPartOfChainBreak
               if (sizeof($outOfBoundsChain) === 0){
                  array_push($outOfBoundsChain, $rawOutOfBoundBefore);
               }
               array_push($outOfBoundsChain, $rawOutOfBoundAfter);
               if ($isLast) {
                  array_push($outOfBounds, AlertMinMax::Create($sensor, $outputDateTimeFormat, $outOfBoundsChain));
               }
               break;
            case "isPartOfChainBreak":  // leads to isPartOfSameChain or isNotPartOfChain
               array_push($outOfBounds, AlertMinMax::Create($sensor, $outputDateTimeFormat, $outOfBoundsChain));
               $outOfBoundsChain = [];
               if ($isLast) {
                  array_push($outOfBounds, AlertMinMax::CreateNonChainingAlert($sensor, $outputDateTimeFormat, $rawOutOfBoundAfter));
               }
               break;
            case "isNotPartOfChain":  // leads to isPartOfSameChain or isNotPartOfChain
               array_push($outOfBounds, AlertMinMax::CreateNonChainingAlert($sensor, $outputDateTimeFormat, $rawOutOfBoundBefore));
               if ($isLast) {
                  array_push($outOfBounds, AlertMinMax::CreateNonChainingAlert($sensor, $outputDateTimeFormat, $rawOutOfBoundAfter));
               }
               break;
            default:
               die("Unknown switch case!");
         }
      }
      return $outOfBounds;
   }
}