<?php

namespace App\dto;

class SensorAndLastReading
{
   function __construct(
      public Sensor $sensor,
      public ?SensorReading $sensorReading
   ) {}
}