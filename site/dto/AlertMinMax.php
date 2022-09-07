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
    * @param SensorReadingDTO[] $outOfBoundsGroup
    * @return AlertMinMax
    */
   private static function Create(Sensor $sensor, string $outputDateTimeFormat, array $outOfBoundsGroup): AlertMinMax
   {
      $outOfBoundsGroup = array_values($outOfBoundsGroup);
      if (sizeof($outOfBoundsGroup) === 0) {
         die('Invalid input! Array has less than 0 elements!' . var_export($outOfBoundsGroup, true));
      }
      if (sizeof($outOfBoundsGroup) === 1) {
         [$temp, $relHum] = AlertMinMax::GetDeviation($sensor, $outOfBoundsGroup);
         $before = DateTime::createFromFormat($outputDateTimeFormat, $outOfBoundsGroup[0]->date);
         return new AlertMinMax($before, 0, 1, $temp, $relHum);
      }
      $before = $outOfBoundsGroup[0];
      $end = $outOfBoundsGroup[sizeof($outOfBoundsGroup) - 1];
      $before = DateTime::createFromFormat($outputDateTimeFormat, $before->date);
      $end = DateTime::createFromFormat($outputDateTimeFormat, $end->date);
      [$temp, $relHum] = AlertMinMax::GetDeviation($sensor, $outOfBoundsGroup);
      $result = new AlertMinMax(
         beforeDate: $before,
         duration: ($end->getTimestamp() - $before->getTimestamp()) / 60,
         count: sizeof($outOfBoundsGroup),
         temp: $temp,
         hum: $relHum
      );
      return $result;
   }

   /* @param SensorReadingDTO[] $outOfBoundsGroup */
   private static function GetDeviation(Sensor $sensor, array $outOfBoundsGroup): array {
      $hums = array_map(function ($x) {
         return $x->relHum;
      }, $outOfBoundsGroup);
      $temps = array_map(function ($x) {
         return $x->temp;
      }, $outOfBoundsGroup);
      $lowTemp = min($temps);
      $highTemp = max($temps);
      $lowHum = min($hums);
      $highHum = max($hums);
      $avgTemp = ($sensor->minTemp + $sensor->maxTemp) / 2;
      $avgHum = ($sensor->minRelHum + $sensor->maxRelHum) / 2;
      $temp =  abs($avgTemp - $lowTemp) > abs($avgTemp - $highTemp) ? $lowTemp : $highTemp;
      $hum =  abs($avgHum - $lowHum) > abs($avgHum - $highHum) ? $lowHum : $highHum;

      return [$temp, $hum];
   }

   /**
    * @param SensorReadingDTO[] $ungroupedOutOfBounds
    * @return AlertMinMax[]
    */
   public static function Get(Sensor $sensor, string $outputDateTimeFormat, array $ungroupedOutOfBounds): array {
      $outOfBounds = [];
      $outOfBoundsChain = [];

      if (sizeof($ungroupedOutOfBounds) === 0) {
         return [];
      }
      if (sizeof($ungroupedOutOfBounds) === 1) {
         array_push($outOfBounds, AlertMinMax::Create($sensor, $outputDateTimeFormat, $ungroupedOutOfBounds));
         return $outOfBounds;
      }

      // 1 = isPartOfSameChain, 2 = isPartOfChainBreak, 3 = isNotPartOfChain
      // 1    , 1    , 2,   , 3,   , 1
      // 20:58, 21:12, 21:23, 23:45, 00:40, 00:47
      for ($i = 0; $i < sizeof($ungroupedOutOfBounds) - 1; $i++) {
         $rawOutOfBoundBefore = $ungroupedOutOfBounds[$i];
         $rawOutOfBoundAfter = $ungroupedOutOfBounds[$i + 1];

         $isPartOfSameChain = (DateTime::createFromFormat($outputDateTimeFormat, $rawOutOfBoundAfter->date)->getTimestamp() -
               DateTime::createFromFormat($outputDateTimeFormat, $rawOutOfBoundBefore->date)->getTimestamp())
            / 60 <= $sensor->readingIntervalMinutes;
         $isPartOfChainBreak = sizeof($outOfBoundsChain) > 1 && ! $isPartOfSameChain;
         $isNotPartOfChain = sizeof($outOfBoundsChain) === 0 && ! $isPartOfSameChain;
         $state = ["", "isPartOfSameChain", "isPartOfChainBreak", "", "isNotPartOfChain"][(int) $isPartOfSameChain * 1 + (int) $isPartOfChainBreak * 2 + (int) $isNotPartOfChain * 4];
         if (sizeof($outOfBoundsChain) === 1) die("Program logic error! Chain cannot contain singular element!");  // sanity check
         $isLast = $i === sizeof($ungroupedOutOfBounds) - 2;
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
                  array_push($outOfBounds, AlertMinMax::Create($sensor, $outputDateTimeFormat, [$rawOutOfBoundAfter]));
               }
               break;
            case "isNotPartOfChain":  // leads to isPartOfSameChain or isNotPartOfChain
               array_push($outOfBounds, AlertMinMax::Create($sensor, $outputDateTimeFormat, [$rawOutOfBoundBefore]));
               if ($isLast) {
                  array_push($outOfBounds, AlertMinMax::Create($sensor, $outputDateTimeFormat, [$rawOutOfBoundAfter]));
               }
               break;
            default:
               die("Unknown switch case!");
         }
      }
      return $outOfBounds;
   }
}