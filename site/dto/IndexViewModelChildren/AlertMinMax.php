<?php

declare(strict_types=1);

namespace App\dto\IndexViewModelChildren;

require_once (__DIR__."/../../../vendor/autoload.php");

use App\dto\Sensor;
use App\dto\SensorReading;
use DateTimeImmutable;

class AlertMinMax
{
   public DateTimeImmutable $beforeDate;
   public int $duration;
   public int $count;
   public float $temp;
   public float $hum;

   private function __construct(DateTimeImmutable $beforeDate, int $duration, int $count, float $temp, float $hum)
   {
      $this->beforeDate = $beforeDate;
      $this->duration = $duration;
      $this->count = $count;
      $this->temp = $temp;
      $this->hum = $hum;
   }

   /**
    * @param Sensor $sensor
    * @param SensorReading[] $outOfBoundsGroup
    * @return AlertMinMax
    */
   private static function Create(Sensor $sensor, array $outOfBoundsGroup): AlertMinMax
   {
      $outOfBoundsGroup = array_values($outOfBoundsGroup);
      if (sizeof($outOfBoundsGroup) === 0) {
         die('Invalid input! Array has less than 0 elements!' . var_export($outOfBoundsGroup, true));
      }
      if (sizeof($outOfBoundsGroup) === 1) {
         [$temp, $relHum] = AlertMinMax::GetDeviation($sensor, $outOfBoundsGroup);
         return new AlertMinMax($outOfBoundsGroup[0]->dateRecorded, 0, 1, $temp, $relHum);
      }
      $before = $outOfBoundsGroup[0];
      $end = $outOfBoundsGroup[sizeof($outOfBoundsGroup) - 1];
      [$temp, $relHum] = AlertMinMax::GetDeviation($sensor, $outOfBoundsGroup);
      $result = new AlertMinMax(
         beforeDate: $before->dateRecorded,
         duration: ($end->dateRecorded->getTimestamp() - $before->dateRecorded->getTimestamp()) / 60,
         count: sizeof($outOfBoundsGroup),
         temp: $temp,
         hum: $relHum
      );
      return $result;
   }

   /* @param SensorReading[] $outOfBoundsGroup */
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
    * @param SensorReading[] $ungroupedOutOfBounds
    * @return AlertMinMax[]
    */
   public static function Get(Sensor $sensor, array $ungroupedOutOfBounds): array {
      $outOfBounds = [];
      $outOfBoundsChain = [];

      if (sizeof($ungroupedOutOfBounds) === 0) {
         return [];
      }
      if (sizeof($ungroupedOutOfBounds) === 1) {
         array_push($outOfBounds, AlertMinMax::Create($sensor, $ungroupedOutOfBounds));
         return $outOfBounds;
      }

      // 1 = isPartOfSameChain, 2 = isPartOfChainBreak, 3 = isNotPartOfChain
      // 1    , 1    , 2,   , 3,   , 1
      // 20:58, 21:12, 21:23, 23:45, 00:40, 00:47
      for ($i = 0; $i < sizeof($ungroupedOutOfBounds) - 1; $i++) {
         $rawOutOfBoundBefore = $ungroupedOutOfBounds[$i];
         $rawOutOfBoundAfter = $ungroupedOutOfBounds[$i + 1];

         $isPartOfSameChain = ($rawOutOfBoundAfter->dateRecorded->getTimestamp() -
            $rawOutOfBoundBefore->dateRecorded->getTimestamp())
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
                  array_push($outOfBounds, AlertMinMax::Create($sensor, $outOfBoundsChain));
               }
               break;
            case "isPartOfChainBreak":  // leads to isPartOfSameChain or isNotPartOfChain
               array_push($outOfBounds, AlertMinMax::Create($sensor, $outOfBoundsChain));
               $outOfBoundsChain = [];
               if ($isLast) {
                  array_push($outOfBounds, AlertMinMax::Create($sensor, [$rawOutOfBoundAfter]));
               }
               break;
            case "isNotPartOfChain":  // leads to isPartOfSameChain or isNotPartOfChain
               array_push($outOfBounds, AlertMinMax::Create($sensor, [$rawOutOfBoundBefore]));
               if ($isLast) {
                  array_push($outOfBounds, AlertMinMax::Create($sensor, [$rawOutOfBoundAfter]));
               }
               break;
            default:
               die("Unknown switch case!");
         }
      }
      return $outOfBounds;
   }
}