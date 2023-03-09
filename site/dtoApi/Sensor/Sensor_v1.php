<?php

namespace App\dtoApi\Sensor;

class Sensor_v1
{
   function __construct(
      public string $id,
      public string $name,
      public string $serial,
      public string $model,
      public string $ip,
      public string $location,
      public bool   $isPortable,
      public int    $minTemp,
      public int    $maxTemp,
      public int    $minRelHum,
      public int    $maxRelHum,
      public int    $readingIntervalMinutes
   ) {}

   function validate(): array {
      $errors = [];
      if (strlen($this->id) > 64) {
         $errors[] = "Id field must be less than 64 characters long";
      }
      if (strlen($this->id) < 1) {
         $errors[] = "Id field must be at least 1 characters long";
      }

      if (strlen($this->name) > 64) {
         $errors[] = "Name field must be less than 64 characters long";
      }
      if (strlen($this->name) < 1) {
         $errors[] = "Name field must be at least 1 characters long";
      }

      if (strlen($this->serial) > 64) {
         $errors[] = "Serial field must be less than 64 characters long";
      }
      if (strlen($this->serial) < 1) {
         $errors[] = "Serial field must be at least 1 characters long";
      }

      if (strlen($this->model) > 64) {
         $errors[] = "Model field must be less than 64 characters long";
      }
      if (strlen($this->model) < 1) {
         $errors[] = "Model field must be at least 1 characters long";
      }

      if (strlen($this->ip) > 64) {
         $errors[] = "IP field must be less than 64 characters long";
      }
      if (strlen($this->ip) < 1) {
         $errors[] = "IP field must be at least 1 characters long";
      }

      if (strlen($this->location) > 64) {
         $errors[] = "Location field must be less than 64 characters long";
      }
      if (strlen($this->location) < 1) {
         $errors[] = "Location field must be at least 1 characters long";
      }
      return $errors;
   }
}