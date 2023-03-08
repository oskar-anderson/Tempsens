<?php


namespace App\dto;


use App\dto\IndexViewModelChildren\AlertMinMax;
use App\dto\IndexViewModelChildren\HandleInputModel;
use App\dto\IndexViewModelChildren\Period;

class IndexViewModel
{
   /* @var HandleInputModel $input */
   public HandleInputModel $input;
   /* @var SensorAndLastReading[] $sensors */
   public array $sensors;
   /* @var AlertMinMax[][] $sensorAlertsMinMax */
   public array $sensorAlertsMinMax;
   /* @var \App\dto\SensorReading[][] $sensorReadingsBySensorId */
   public array $sensorReadingsBySensorId;
   /* @var string[] $colors */
   public array $colors;
   /* @var Period[] $periods */
   public array $periods;

   function __construct()
   {

   }

   /* @param SensorAndLastReading[] $sensors $ */
   function SetSensorsAndLastReadings(array $sensors): IndexViewModel {
      $this->sensors = $sensors;
      return $this;
   }
   function SetSensorAlertsMinMax(array $sensorReadingOutOfBounds): IndexViewModel {
      $this->sensorAlertsMinMax = $sensorReadingOutOfBounds;
      return $this;
   }
   function SetSensorReadingsBySensorId(array $sensorReadingsBySensorId): IndexViewModel {
      $this->sensorReadingsBySensorId = $sensorReadingsBySensorId;
      return $this;
   }
   /* @param string[] $colors */
   function SetColors(array $colors): IndexViewModel {
      $this->colors = $colors;
      return $this;
   }
   function SetInput(HandleInputModel $input): IndexViewModel {
      $this->input = $input;
      return $this;
   }
   /* @param Period[] $periods */
   function SetPeriods(array $periods): IndexViewModel {
      $this->periods = $periods;
      return $this;
   }

}