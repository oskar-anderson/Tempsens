<?php

namespace App\db\migrations\V1_0_0;

class SensorV1_0_0 {

    // PHP 8: Constructor property promotion - pretty nice
    function __construct(
        public string $id,
        public string $name,
        public string $serial,
        public string $model,
        public string $ip,
        public string $location,
        public bool $isPortable,
        public float $minTemp,
        public float $maxTemp,
        public float $minRelHum,
        public float $maxRelHum,
        public int $readingIntervalMinutes
        )
    { }
}