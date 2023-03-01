<?php


namespace App\dto;


use App\dto\IndexViewModelChildren\AlertMinMax;
use App\dto\IndexViewModelChildren\HandleInputModel;
use App\dto\IndexViewModelChildren\LastSensorReading;
use App\dto\IndexViewModelChildren\Period;
use App\dto\IndexViewModelChildren\SensorReadingDTO;
use App\model\Sensor;

class IndexViewModel
{
   /* @var HandleInputModel $input */
   public HandleInputModel $input;
   /* @var Sensor[] $sensors */
   public array $sensors;
   /* @var LastSensorReading[] $lastReadingsView */
   public array $lastReadingsView;
   /* @var AlertMinMax[][] $sensorAlertsMinMax */
   public array $sensorAlertsMinMax;
   /* @var SensorReadingDTO[][] $sensorReadingsBySensorId */
   public array $sensorReadingsBySensorId;
   /* @var string[] $colors */
   public array $colors;
   /* @var Period[] $periods */
   public array $periods;

   function __construct()
   {

   }
   /* @param Sensor[] $sensors */
   function SetSensors(array $sensors): IndexViewModel {
      $this->sensors = $sensors;
      return $this;
   }
   /* @param LastSensorReading[] $lastReadings $ */
   function SetLastReading(array $lastReadings): IndexViewModel {
      $this->lastReadingsView = $lastReadings;
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
   function SetDefault(): IndexViewModel {
      $this->periods = Period::GetPeriods();
      return $this;
   }

}