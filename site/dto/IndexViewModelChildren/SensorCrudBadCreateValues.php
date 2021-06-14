<?php


namespace App\dto\IndexViewModelChildren;


use App\model\Sensor;

class SensorCrudBadCreateValues
{
   function __construct(
      public ?Sensor $sensor,
      public string $auth,
   ) {}
}