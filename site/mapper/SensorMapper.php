<?php

namespace App\mapper;

use App\frontendDto\Sensor\Sensor_v1;
use App\domain\Sensor;

class SensorMapper
{
   function MapFrontToDomain(Sensor_v1 $x): Sensor {
      return new Sensor(
         id: $x->id,
         name: $x->name,
         serial: $x->serial,
         model: $x->model,
         ip: $x->ip,
         location: $x->location,
         isPortable: $x->isPortable,
         minTemp: $x->minTemp,
         maxTemp: $x->maxTemp,
         minRelHum: $x->minRelHum,
         maxRelHum: $x->maxRelHum,
         readingIntervalMinutes: $x->readingIntervalMinutes
      );
   }
}