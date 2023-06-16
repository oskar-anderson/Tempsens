<?php

declare(strict_types=1);

namespace App\dtoWeb\IndexViewModelChildren;

use App\dtoWeb\Sensor;
use App\dtoWeb\SensorReading;
use DateTimeImmutable;
use Exception;

class AlertMinMax
{
   public DateTimeImmutable $beforeDate;
   /**
    * @var int Duration of the alert chain in minutes with error +- half of sensor readingIntervalMinutes
    */
   public int $duration;
   public int $count;
   public string $content;

   private function __construct(DateTimeImmutable $beforeDate, int $duration, int $count, string $content)
   {
      $this->beforeDate = $beforeDate;
      $this->duration = $duration;
      $this->count = $count;
      $this->content = $content;
   }

   /**
    * @param Sensor $sensor
    * @param SensorReading[] $chain Chain of back to back alerts within the sensor readingIntervalMinutes interval
    * @param string $type "temperature" if the alert is for temperature, "relative humidity" if the alert is for relative humidity
    * @return AlertMinMax
    * @throws Exception
    */
   public static function CreateTempOrRelHumAlert(Sensor $sensor, array $chain, string $type = "temperature"): AlertMinMax
   {
      if (sizeof($chain) === 0) {
         throw new Exception('Invalid input! Array has less than 0 elements!' . var_export($chain, true));
      }
      $before = $chain[0];
      $temps = array_map(fn($x) => $x->temp, $chain);
      $relHums = array_map(fn($x) => $x->relHum, $chain);
      $end = $chain[sizeof($chain) - 1];
      $duration = (int) ceil(($end->dateRecorded->getTimestamp() - $before->dateRecorded->getTimestamp()) / 60);
      if (sizeof($chain) === 1) {
         $duration = (int) ceil($sensor->readingIntervalMinutes / 2);
      }
      switch ($type) {
         case "temperature":
            $temp = AlertMinMax::GetDeviation($temps, ($sensor->minTemp + $sensor->maxTemp) / 2);
            return new AlertMinMax($before->dateRecorded, $duration, sizeof($chain), "Temperature: " . number_format($temp, 1) . " ℃");
         case "relative humidity":
            $relHum = AlertMinMax::GetDeviation($relHums, ($sensor->minRelHum + $sensor->maxRelHum) / 2);
            return new AlertMinMax($before->dateRecorded, $duration, sizeof($chain), "Relative Humidity: " . number_format($relHum, 1) . " %");
         default:
            throw new Exception(`Unknown type: ${$type}`);
      }
   }

   private static function GetDeviation(array $values, float $average): float {
      $lowValue = min($values);
      $highValue = max($values);
      return abs($average - $lowValue) > abs($average - $highValue) ? $lowValue : $highValue;
   }

   /**
    * Groups the input array into sub arrays based on $isPartOfSameChainCallback callback marking values as similar
    * @param array $sortedUngroupedAlertValues T value that has DateTimeImmutable property that can be compared in the callback
    * @param callable $isPartOfSameChainCallback takes in 2 T arguments and returns a boolean
    * @return array[] grouped alerts
    * @throws Exception
    */
   public static function GroupAlerts(array $sortedUngroupedAlertValues, callable $isPartOfSameChainCallback): array {
      $result = [];
      $chain = [];

      if (sizeof($sortedUngroupedAlertValues) === 0) {
         return [];
      }
      if (sizeof($sortedUngroupedAlertValues) === 1) {
         array_push($result, $sortedUngroupedAlertValues);
         return $result;
      }

      // 1 = isPartOfSameChain, 2 = isPartOfChainBreak, 3 = isNotPartOfChain
      // 1    , 1    , 2,   , 3,   , 1
      // 20:58, 21:12, 21:23, 23:45, 00:40, 00:47
      for ($i = 0; $i < sizeof($sortedUngroupedAlertValues) - 1; $i++) {
         $start = $sortedUngroupedAlertValues[$i];
         $next = $sortedUngroupedAlertValues[$i + 1];

         $isPartOfSameChain = $isPartOfSameChainCallback($start, $next);
         $isPartOfChainBreak = sizeof($chain) > 1 && ! $isPartOfSameChain;
         $isNotPartOfChain = sizeof($chain) === 0 && ! $isPartOfSameChain;
         $state = match (true) {
            $isPartOfSameChain => "isPartOfSameChain",
            $isPartOfChainBreak => "isPartOfChainBreak",
            $isNotPartOfChain => "isNotPartOfChain",
            default => ""
         };
         if (sizeof($chain) === 1) throw new Exception("Program logic error! Chain cannot contain singular element!");  // sanity check
         $isLast = $i === sizeof($sortedUngroupedAlertValues) - 2;

         switch ($state) {
            case "isPartOfSameChain":  // leads to isPartOfSameChain or isPartOfChainBreak
               if (sizeof($chain) === 0){
                  array_push($chain, $start);
               }
               array_push($chain, $next);
               if ($isLast) {
                  array_push($result, $chain);
               }
               break;
            case "isPartOfChainBreak":  // leads to isPartOfSameChain or isNotPartOfChain
               array_push($result, $chain);
               $chain = [];
               if ($isLast) {
                  array_push($result, [$next]);
               }
               break;
            case "isNotPartOfChain":  // leads to isPartOfSameChain or isNotPartOfChain
               array_push($result, [$start]);
               if ($isLast) {
                  array_push($result, [$next]);
               }
               break;
            default:
               throw new Exception("Unknown switch case!");
         }
      }
      return $result;
   }

   /**
    * Creates buckets from start to end date on sensor expected interval and returns the buckets with no value as alerts
    * @param int $readingIntervalMinutes bucket creation interval
    * @param SensorReading[] $sensorReadings sorted array of sensorReadings by $dateRecorded
    * @param DateTimeImmutable $from from date to start creating buckets
    * @param DateTimeImmutable $to to date to stop making buckets
    * @return AlertMinMax[] chain of continues buckets with no value array
    */
   public static function GetMissingValuesAsAlerts(int $readingIntervalMinutes, array $sensorReadings, DateTimeImmutable $from, DateTimeImmutable $to): array
   {
      $start = $from;
      /** @var DateTimeImmutable[][] $buckets */
      $buckets = [];
      for ($i = 1; ; $i++) {
         $next = $start->modify("+" . $readingIntervalMinutes . "minutes");
         if ($next >= $to) break;
         array_push($buckets, [$start, $next, 0]);
         $start = $next;
      }
      $lowestElementIndex = 0;
      for ($i = 0; $i < sizeof($buckets); $i++) {
         $bucketNumberOfElements = 0;
         $bucketStart = $buckets[$i][0];
         $bucketEnd = $buckets[$i][1];

         // this is the same optimization step used in JS filterSortedArrayValuesBetweenDates function
         while ($lowestElementIndex < sizeof($sensorReadings)) {
            $reading = $sensorReadings[$lowestElementIndex];
            if ($bucketEnd <= $reading->dateRecorded) {
               break;
            }
            if ($reading->dateRecorded >= $bucketStart) {
               $bucketNumberOfElements++;
            }
            $lowestElementIndex++;
         }
         $buckets[$i][2] = $bucketNumberOfElements;
      }
      $alertChainArr = AlertMinMax::GroupAlerts(
         array_values(array_filter($buckets, fn($x) => $x[2] === 0)),
         function ($start, $next) use ($readingIntervalMinutes) {
            return ($next[0]->getTimestamp() - $start[0]->getTimestamp()) / 60 <= $readingIntervalMinutes;
         });
      $missingChains = [];
      foreach ($alertChainArr as $alertChain) {
         $missingChains[] = new AlertMinMax(
            $alertChain[0][0],
            ($alertChain[sizeof($alertChain) - 1][1]->getTimestamp() - $alertChain[0][0]->getTimestamp()) / 60,
            sizeof($alertChain),
            "Missing"
         );
      }
      return $missingChains;
   }
}