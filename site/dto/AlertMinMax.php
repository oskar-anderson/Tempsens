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

   /* @param SensorReadingDTO[] $outOfBoundsTmp */
   private static function Create(Sensor $sensor, string $dateTimeFormat, array $outOfBoundsTmp): AlertMinMax
   {
      $outOfBoundsTmp = array_values($outOfBoundsTmp);
      if (sizeof($outOfBoundsTmp) < 2) {
         die('Invalid input! Array has less than 2 elements!' . var_export($outOfBoundsTmp, true));
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
      $outOfBoundsTmp = [];

      if (sizeof($rawOutOfBounds) === 1) {
         [$temp, $relHum] = AlertMinMax::GetDeviation($sensor, $rawOutOfBounds);
         $before = DateTime::createFromFormat($outputDateTimeFormat, $rawOutOfBounds[0]->date);
         $alertMinMax = new AlertMinMax(
            beforeDate: $before,
            duration: 0,
            count: 1,
            temp: $temp,
            hum: $relHum
         );
         array_push($outOfBounds, $alertMinMax);
      }
      if (sizeof($rawOutOfBounds) < 2) {
         return $outOfBounds;
      }
      for ($i = 0; true; $i++) {
         $rawOutOfBoundBefore = $rawOutOfBounds[$i];
         $rawOutOfBoundAfter = $rawOutOfBounds[$i + 1];

         if (
            (DateTime::createFromFormat($outputDateTimeFormat, $rawOutOfBoundAfter->date)->getTimestamp() -
            DateTime::createFromFormat($outputDateTimeFormat, $rawOutOfBoundBefore->date)->getTimestamp())
            / 60 <= $sensor->readingIntervalMinutes
         ) {
            if (sizeof($outOfBoundsTmp) === 0){
               array_push($outOfBoundsTmp, $rawOutOfBoundBefore);
            }
            array_push($outOfBoundsTmp, $rawOutOfBoundAfter);
            if ($i === sizeof($rawOutOfBounds) - 2) {
               array_push($outOfBounds, AlertMinMax::Create($sensor, $outputDateTimeFormat, $outOfBoundsTmp));
               break;
            }
            continue;
         }
         if (sizeof($outOfBoundsTmp) > 1) {
            array_push($outOfBounds, AlertMinMax::Create($sensor, $outputDateTimeFormat, $outOfBoundsTmp));
            $outOfBoundsTmp = [];
            continue;
         }
         $addAlertMinMaxFunc = function($rawOutOfBound, &$arr) use ($outputDateTimeFormat, $sensor) {
            [$temp, $relHum] = AlertMinMax::GetDeviation($sensor, [$rawOutOfBound]);
            $before = DateTime::createFromFormat($outputDateTimeFormat, $rawOutOfBound->date);
            array_push($arr, new AlertMinMax($before, 0, 1, $temp, $relHum));
         };
         $addAlertMinMaxFunc($rawOutOfBoundBefore, $outOfBounds);
         if ($i === sizeof($rawOutOfBounds) - 2) {
            $addAlertMinMaxFunc($rawOutOfBoundAfter, $outOfBounds);
            break;
         }
      }
      return $outOfBounds;
   }
}