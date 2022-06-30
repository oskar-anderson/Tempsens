<?php

namespace App\db\migrations\V1_0_0;

class SensorReadingV1_0_0 {

    // PHP 8: Constructor property promotion - pretty nice
    function __construct(
        public string $id, 
        public string $sensorId, 
        public float $temp, 
        public float $relHum, 
        public string $dateRecorded, 
        public ?string $dateAdded)
    { }
}