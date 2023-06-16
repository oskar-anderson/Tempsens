<?php


namespace App\webApp\view\partial;

use App\dtoWeb\Sensor;
use Ramsey\Uuid\Uuid;

class SensorCrudPartialCreateEdit
{
   public static function GetHtml(?Sensor $sensor): string
   {
      $format = <<<EOT
         <div style="display: none">
            <span>Id</span>
            <input autocomplete="off" required="required" name="id" id="idOf%s" type="text" value="%s">
         </div>
         <div class="twoByTwo">
            <span>Name</span>
            <input autocomplete="off" required="required" maxlength="64" name="name" id="nameOf%s" type="text" value="%s">
         </div>
         <div class="twoByTwo">
            <span>Serial</span>
            <input autocomplete="off" required="required" maxlength="64" name="serial" id="serialOf%s" type="text" value="%s">
         </div>
         <div class="twoByTwo">
            <span>Model</span>
            <input autocomplete="off" required="required" maxlength="64" name="model" id="modelOf%s" type="text" value="%s">
         </div>
         <div class="twoByTwo">
            <span>IP</span>
            <input autocomplete="off" required="required" maxlength="64" name="ip" id="ipOf%s" type="text" value="%s">
         </div>
         <div class="twoByTwo">
            <span>Location</span>
            <input autocomplete="off" required="required" maxlength="64" name="location" id="locationOf%s" type="text" value="%s">
         </div>
         <div class="twoByTwo">
            <span>Is Portable</span>
            <input autocomplete="off" required="required" name="isPortable" id="isPortableOf%s" type="checkbox" %s value="%s">
         </div>
         <div class="twoByTwo">
            <span>Min Temp</span>
            <input autocomplete="off" required="required" name="minTemp" id="minTempOf%s" type="number" value="%s">
         </div>
         <div class="twoByTwo">
            <span>Max Temp</span>
            <input autocomplete="off" required="required" name="maxTemp" id="maxTempOf%s" type="number" value="%s">
         </div>
         <div class="twoByTwo">
            <span>Min Relative Humidity</span>
            <input autocomplete="off" required="required" name="minRelHum" id="minRelHumOf%s" type="number" value="%s">
         </div>
         <div class="twoByTwo">
            <span>Max Relative Humidity</span>
            <input autocomplete="off" required="required" name="maxRelHum" id="maxRelHumOf%s" type="number" value="%s">
         </div>
         <div class="twoByTwo">
            <span>Reading Interval Minutes</span>
            <input autocomplete="off" required="required" name="readingIntervalMinutes" id="readingIntervalMinutesOf%s" type="number" value="%s">
         </div>
      EOT;


      $idAppend = 'newSensor';
      $id = Uuid::uuid4()->toString();
      $name = '';
      $serial = '';
      $model = '';
      $ip = '';
      $location = '';
      $isPortableCheck = '';
      $minTemp = '';
      $maxTemp = '';
      $minRelHum = '';
      $maxRelHum = '';
      $readingIntervalMinutes = '';
      if ($sensor !== null) {
         $idAppend = $sensor->id;
         $id = htmlspecialchars($sensor->id);
         $name = htmlspecialchars($sensor->name);
         $serial = htmlspecialchars($sensor->serial);
         $model = htmlspecialchars($sensor->model);
         $ip = htmlspecialchars($sensor->ip);
         $location = htmlspecialchars($sensor->location);
         $isPortableCheck = $sensor->isPortable ? 'checked' : $isPortableCheck;;
         $minTemp = htmlspecialchars($sensor->minTemp);
         $maxTemp = htmlspecialchars($sensor->maxTemp);
         $minRelHum = htmlspecialchars($sensor->minRelHum);
         $maxRelHum = htmlspecialchars($sensor->maxRelHum);
         $readingIntervalMinutes = htmlspecialchars($sensor->readingIntervalMinutes);
      }


      $result = sprintf($format,
         $idAppend, $id,
         $idAppend, $name,
         $idAppend, $serial,
         $idAppend, $model,
         $idAppend, $ip,
         $idAppend, $location,
         $idAppend, $isPortableCheck, 'Y',
         $idAppend, $minTemp,
         $idAppend, $maxTemp,
         $idAppend, $minRelHum,
         $idAppend, $maxRelHum,
         $idAppend, $readingIntervalMinutes,
      );
      return $result;
   }

}