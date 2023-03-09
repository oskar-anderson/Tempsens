<?php


namespace App\dtoWeb;


use App\dtoWeb\IndexViewModelChildren\AlertMinMax;
use App\dtoWeb\IndexViewModelChildren\HandleInputModel;
use App\dtoWeb\IndexViewModelChildren\Period;

class IndexViewModel
{
   /**
    * @param HandleInputModel $input
    * @param SensorAndLastReading[] $sensors
    * @param AlertMinMax[][] $sensorAlertsMinMax
    * @param SensorReading[][] $sensorReadingsBySensorId
    * @param Period[] $periods
    */
   function __construct(
      public HandleInputModel $input,
      public array $sensors,
      public array $sensorAlertsMinMax,
      public array $sensorReadingsBySensorId,
      public array $periods
   )
   {}
}