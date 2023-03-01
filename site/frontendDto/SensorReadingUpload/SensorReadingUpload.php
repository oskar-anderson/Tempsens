<?php

namespace App\frontendDto\SensorReadingUpload;

class SensorReadingUpload
{
   /** @var SensorReadingUploadReadings[] */
   public array $sensorReadings;
   public string $sensorId;
   public string $auth;

   /**
    * @param SensorReadingUploadReadings[] $sensorReadings
    * @param string $sensorId
    * @param string $auth
    */
   function __construct(
      array $sensorReadings,
      string $sensorId,
      string $auth,
   ) {
      $this->sensorReadings = $sensorReadings;
      $this->sensorId = $sensorId;
      $this->auth = $auth;
   }
}