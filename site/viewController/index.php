<?php

namespace App\viewController;

require_once(__DIR__."/../../vendor/autoload.php");

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensorReadingTmp;
use App\db\dal\DalSensors;
use App\dto\AlertMinMax;
use App\dto\IndexViewModel;
use App\dto\IndexViewModelChildren\HandleInputModel;
use App\dto\IndexViewModelChildren\LastSensorReading;
use App\dto\IndexViewModelChildren\Period;
use App\dto\IndexViewModelChildren\SensorCrudBadCreateValues;
use App\dto\IndexViewModelChildren\SensorReadingDTO;
use App\model\sensor;
use App\db\DbHelper;
use App\model\SensorReading;
use App\util\Base64;
use App\util\Config;
use App\util\Helper;
use App\util\InputValidation;
use DateTime;

(new Programm())->main();

class Programm {

   public array $debugs = [];
   public string $pageOutputDateTimeFormat = 'd/m/Y H:i';
   public array $errors = [];

   function main(): void
   {
      $before = microtime(true);

      $sensors = (new DalSensors())->GetAll();
      $input = $this->HandleInput($sensors);

      array_push($this->debugs,'GetSensors and handle input: ' . microtime(true) - $before);
      $before = microtime(true);

      $lastReadingsData = DalSensorReading::GetLastReadingsFromCacheOrDatabase($sensors);
      $lastReadingsView = [];
      foreach ($sensors as  $sensor) {
         $lastReading = $lastReadingsData[$sensor->id];

         $dateRecorded = 'NO DATA';
         $col = 'red';
         if ($lastReading !== null) {
            $lastDate = DateTime::createFromFormat('YmdHi', $lastReading->dateRecorded)->format($this->pageOutputDateTimeFormat);
            $minutesDiff = (
               DateTime::createFromFormat('YmdHi', Helper::GetDateNow())->getTimestamp() -
               DateTime::createFromFormat('YmdHi', $lastReading->dateRecorded)->getTimestamp()
            ) / 60;
            if ($sensor->readingIntervalMinutes - $minutesDiff < 0 && !$sensor->isPortable) {
               $dateRecorded = 'DOWN @' . $lastDate . ' (' . $minutesDiff . ' min ago)';
               $col = 'red';
            }
            if ($sensor->readingIntervalMinutes - $minutesDiff >= 0 && !$sensor->isPortable) {
               $dateRecorded = 'UP @' . $lastDate;
               $col = 'black';
            }
            if ($sensor->isPortable) {
               $dateRecorded = 'Portable sensor';
               $col = 'black';
            }
         }
         $temp = $lastReading !== null ? $lastReading->temp . '℃' : 'NO DATA';
         $relHum = $lastReading !== null ? $lastReading->relHum . '%' : 'NO DATA';
         $lastReadingsView[$sensor->id] = new LastSensorReading(
            dateRecorded: $dateRecorded,
            temp: $temp,
            relHum: $relHum,
            color: $col,
         );
      }

      array_push($this->debugs,'Get Last readings: ' . microtime(true) - $before);
      $before = microtime(true);

      $from = DateTime::createFromFormat('d-m-Y', $input->dateFrom)->format('Ymd') . '0000';
      $to = DateTime::createFromFormat('d-m-Y', $input->dateTo)->format('Ymd') . '2359';

      $sensorReadings = (new DalSensorReading())->GetAllBetween($from, $to);

      array_push($this->debugs,"DalSensorReading->GetAllBetween($from, $to) query: " . microtime(true) - $before);
      $before = microtime(true);

      $sensorReadingsBySensorId = [];
      foreach ($sensors as $sensor) {
         $sensorReadingsOfSensor = array_filter($sensorReadings, function ($obj) use ($sensor) {
            return ($obj->sensorId === $sensor->id);
         });
         foreach ($sensorReadingsOfSensor as $sensorReading) {
            if (! DateTime::createFromFormat('YmdHi', $sensorReading->dateRecorded)) {
               echo $sensorReading->dateRecorded;
            }
         }
         $sensorReadingsBySensorId[$sensor->id] = array_map(function ($x) {
            return new SensorReadingDTO(
               date: DateTime::createFromFormat('YmdHi', $x->dateRecorded)->format($this->pageOutputDateTimeFormat),
               temp: $x->temp,
               relHum: $x->relHum
            );
         }, $sensorReadingsOfSensor);
      }

      array_push($this->debugs,'Map $sensorReadings to sensor: ' . microtime(true) - $before);
      $before = microtime(true);

      $sensorAlertsMinMax = [];
      foreach ($sensors as $sensor) {
         $rawOutOfBounds = array_values(array_filter($sensorReadingsBySensorId[$sensor->id], function($obj) use ($sensor) {
            return ($obj->temp < $sensor->minTemp || $obj->temp > $sensor->maxTemp ||
               $obj->relHum < $sensor->minRelHum || $obj->relHum > $sensor->maxRelHum);
         }));
         $sensorAlertsMinMax[$sensor->id] = AlertMinMax::Get($sensor, $this->pageOutputDateTimeFormat, $rawOutOfBounds);
      }

      array_push($this->debugs,'Combine sensor alerts: ' . microtime(true) - $before);
      $before = microtime(true);

      $goodColors = ['#7F0AAC', '#178152', '#D4D335', '#ED9800', '#F91F2E'];
      $colors = [];
      for ($i = 0; $i < sizeof($sensors) * 2; $i++) {
         if ($i >= sizeof($goodColors)) {
            mt_srand($i * 13 + 1);    // feel free to experiment with the seed
            $val = '#' . str_pad(dechex(mt_rand(0x000000, 0xFFFFFF)), 6, '0', STR_PAD_RIGHT);
            array_push($colors, $val);
         } else {
            array_push($colors, $goodColors[$i]);
         }
      }

      $htmlInjects = (new IndexViewModel())
         ->SetColors($colors)
         ->SetLastReading($lastReadingsView)
         ->SetInput($input)
         ->SetSensorAlertsMinMax($sensorAlertsMinMax)
         ->SetSensorReadingsBySensorId($sensorReadingsBySensorId)
         ->SetSensors($sensors)
         ->SetErrors($this->errors)
         ->SetDefault();

      $this->Render(__DIR__ . "/../view/main/index.php", $htmlInjects);

      foreach ($this->debugs as $debug) {
         $this->DebugToConsole($debug);
      }
   }


   function HandleInput(array &$sensors): HandleInputModel
   {
      $dateTo = (new DateTime())->format('d-m-Y');
      if (true) {
         $input = '';
         $input = ($input === '' && isset($_GET['To'])) ? $_GET['To'] : $input;
         $input = ($input === '' && isset($_POST['To'])) ? $_POST['To'] : $input;
         if (InputValidation::isDateFormat__d_m_Y($input)) {
            $dateTo = DateTime::createFromFormat('d-m-Y', $input)->format('d-m-Y');
         }
      }

      $dateFromType = 'relative';
      if (true) {
         $input = '';
         $input = $input === '' && isset($_GET['From']) ? $_GET['From'] : $input;
         $input = $input === '' && isset($_POST['From']) ? $_POST['From'] : $input;

         $dateFrom = DateTime::createFromFormat('d-m-Y', $dateTo)->modify("-91 day")->format('d-m-Y');
         if (strlen($input) !== 0 && $input[0] === '-') {
            $val = substr($input, 1);
            if (is_numeric($val)) {
               $dateFrom = DateTime::createFromFormat('d-m-Y', $dateTo)->modify("-" . intval($val) . "day")->format('d-m-Y');
            }
         }
         else if (InputValidation::isDateFormat__d_m_Y($input)) {
            $dateFrom = DateTime::createFromFormat('d-m-Y', $input)->format('d-m-Y');
            $dateFromType = 'absolute';
         }
      }

      $selectOptionsRelativeDateFrom = $this->HandleSelectOptionsRelativeDateFrom($dateFrom, $dateTo);
      $sensorCrud = $this->HandleSensorCrud($sensors);
      $this->UploadReadings($sensors);

      $result = new HandleInputModel($dateFrom, $dateTo, $selectOptionsRelativeDateFrom, $dateFromType, $sensorCrud);
      return $result;
   }

   /* @return string[] */
   function HandleSelectOptionsRelativeDateFrom($dateFrom, $dateTo): array {
      $search = DateTime::createFromFormat('d-m-Y', $dateTo)
         ->diff(DateTime::createFromFormat('d-m-Y', $dateFrom))
         ->days;
      $arr = array_map(function ($obj) {
         return $obj->value;
      }, Period::GetPeriods());
      $daysBefore = $this->IntArrGetClosest($search, $arr);

      $selectOptionsRelativeDateFrom = [];
      $periods = Period::GetPeriods();
      foreach ($periods as $period) {
         $t = $daysBefore === $period->value ?
            "<option value='$period->value' selected>-$period->name</option>" :
            "<option value='$period->value'>-$period->name</option>";
         array_push($selectOptionsRelativeDateFrom, $t);
      }
      return $selectOptionsRelativeDateFrom;
   }

   function HandleSensorCrud(array &$sensors): SensorCrudBadCreateValues {
      $formType = $_POST['formType'] ?? null;
      $id = $_POST['id'] ?? null;
      $name = isset($_POST['name']) ? trim($_POST['name']) : null;
      $serial = isset($_POST['serial']) ? trim($_POST['serial']) : null;
      $model = isset($_POST['model']) ? trim($_POST['model']) : null;
      $ip = isset($_POST['ip']) ? trim($_POST['ip']) : null;
      $location = isset($_POST['location']) ? trim($_POST['location']) : null;
      $isPortable = isset($_POST['isPortable']) ? trim($_POST['isPortable'] === 'Y') : false;
      $minTemp = isset($_POST['minTemp']) ? trim($_POST['minTemp']) : null;
      $maxTemp = isset($_POST['maxTemp']) ? trim($_POST['maxTemp']) : null;
      $minRelHum = isset($_POST['minRelHum']) ? trim($_POST['minRelHum']) : null;
      $maxRelHum = isset($_POST['maxRelHum']) ? trim($_POST['maxRelHum']) : null;
      $readingIntervalMinutes = isset($_POST['readingIntervalMinutes']) ? trim($_POST['readingIntervalMinutes']) : null;
      $auth = isset($_POST['auth']) ? trim($_POST['auth']) : null;


      switch ($formType){
         case 'create':
            $idValid = $id === '';
            break;
         case 'edit':
         case 'delete':
            $idValid = in_array($id, array_map(function ($x) {
               return $x->id;
            }, $sensors));
            break;
         default:
            return new SensorCrudBadCreateValues(null, '');
      }

      if (!$idValid) {
         array_push($this->errors, 'Internal error! Bad sensor id: ' . var_export($id, true));
         return new SensorCrudBadCreateValues(null, '');
      }
      if ($name === null || $serial === null || $model === null || $ip === null
         || $location === null || $isPortable === null || $minTemp === null || $maxTemp === null
         || $minRelHum === null || $maxRelHum === null || $readingIntervalMinutes === null) {
         $str = 'Internal error! Cannot read from values.' .
            sprintf("name: %s, serial: %s, model: %s, ip: %s," .
               "location: %s, isPortable: %s, minTemp: %s, maxTemp: %s, ".
               "minRelHum: %s, maxRelHum: %s, auth: %s",
               var_export($name, true), var_export($serial, true),
               var_export($model, true), var_export($ip, true),
               var_export($location, true), var_export($isPortable, true),
               var_export($minTemp, true), var_export($maxTemp, true),
               var_export($minRelHum, true), var_export($maxRelHum, true),
               var_export($readingIntervalMinutes, true)
            );
         array_push($this->errors, $str);
         return new SensorCrudBadCreateValues(null, '');
      }

      if ($auth !== Config::GetConfig()['webDbPassword']) {
         array_push($this->errors, 'Not authorized to ' . $formType . '!');
         return new SensorCrudBadCreateValues(null, '');
      }

      $errorMessages = [];

      InputValidation::StringInputValidation($errorMessages, $name, 64, 'Name');
      InputValidation::StringInputValidation($errorMessages, $serial, 64, 'Serial');
      InputValidation::StringInputValidation($errorMessages, $model, 64, 'Model');
      InputValidation::StringInputValidation($errorMessages, $ip, 64, 'Ip');
      InputValidation::StringInputValidation($errorMessages, $location, 64, 'Location');

      InputValidation::FloatInputValidation($errorMessages, $minTemp, 'Min Temperature');
      InputValidation::FloatInputValidation($errorMessages, $maxTemp, 'Max Temperature');
      InputValidation::FloatInputValidation($errorMessages, $minRelHum, 'Min Relative Humidity');
      InputValidation::FloatInputValidation($errorMessages, $maxRelHum, 'Max Relative Humidity');

      $isEmpty = $readingIntervalMinutes === '';
      $isInt = ctype_digit($readingIntervalMinutes);
      $tmp = [];
      if ($isEmpty) array_push($tmp, 'No value. ');
      if (!$isInt) array_push($tmp, 'Must be a number. ');
      if ($isEmpty || !$isInt) array_push($errorMessages, 'Reading Interval Minutes is not valid! ' . implode('', $tmp));


      if (sizeof($errorMessages) !== 0) {
         if ($formType === 'create') {
            $createBadValues = new SensorCrudBadCreateValues(
               sensor: new Sensor(
                  id: '',
                  name: $name,
                  serial: $serial,
                  model: $model,
                  ip: $ip,
                  location: $location,
                  isPortable: $isPortable,
                  minTemp: $minTemp,
                  maxTemp: $maxTemp,
                  minRelHum: $minRelHum,
                  maxRelHum: $maxRelHum,
                  readingIntervalMinutes: $readingIntervalMinutes
               ),
               auth: $auth,
            );
            array_push($this->errors, ...$errorMessages);
            return $createBadValues;
         }
         array_push($this->errors, ...$errorMessages);
         return new SensorCrudBadCreateValues(null, '');
      }
      switch ($formType) {
         case 'create':
            $id = Base64::GenerateId();
            $sensor = new Sensor($id, $name, $serial, $model, $ip, $location, $isPortable, $minTemp, $maxTemp, $minRelHum, $maxRelHum, $readingIntervalMinutes);
            $pdo = DbHelper::GetPDO();
            (new DalSensors())->Create($sensor, $pdo);
            break;
         case 'edit':
            $sensor = new Sensor(
               id: $id,
               name: $name,
               serial: $serial,
               model: $model,
               ip: $ip,
               location: $location,
               isPortable: $isPortable,
               minTemp: $minTemp,
               maxTemp: $maxTemp,
               minRelHum: $minRelHum,
               maxRelHum: $maxRelHum,
               readingIntervalMinutes: $readingIntervalMinutes
            );
            (new DalSensors())->Update($sensor);
            break;
         case 'delete':
            (new DalSensors())->Delete($id);
            break;
         default:
            array_push($this->errors, 'Internal error! Unknown value: ' . $formType);
            return new SensorCrudBadCreateValues(null, '');
      }
      $sensors = (new DalSensors())->GetAll();

      return new SensorCrudBadCreateValues(null, '');
   }

   /* @param Sensor[] $sensors */
   function UploadReadings(array $sensors): bool  {
      $jsonDataInput = $_POST['jsonData'] ?? null;
      $sensorId = $_POST['csvSensorId'] ?? null;
      $auth = $_POST['csvAuth'] ?? null;

      if ($jsonDataInput === null || $sensorId === null || $auth === null) {
         return false;
      }
      $sensor = null;
      foreach ($sensors as $x) {
         if ($x->id === $sensorId) {
            $sensor = $x;
         }
      }
      if ($sensor === null) {
         array_push($this->errors, 'Error! Sensor with id not found! Id: ' . $sensorId);
         return false;
      }

      if ($auth !== Config::GetConfig()['webDbPassword']) {
         array_push($this->errors, 'Not authorized!');
         return false;
      }

      $jsonDataInput = json_decode($jsonDataInput);
      if (sizeof($jsonDataInput) === 0) {
         return false;
      }

      $sensorReadings = [];
      $lastDateSensorReading = null;
      foreach ($jsonDataInput as $row) {
         $temp = $row->temp;
         if (! is_numeric($temp)) {
            array_push($this->errors, 'Error! Temperature cannot be read! Temp: ' . $temp);
            return false;
         }
         $relHum = $row->relHum;
         if (! is_numeric($relHum)) {
            array_push($this->errors, 'Error! Relative humidity cannot be read! RelHum: ' . $relHum);
            return false;
         }

         $date = $row->date;
         $tmpDate = DateTime::createFromFormat('d-m-Y H:i', $date);
         if ($tmpDate === false) {
            array_push($this->errors, 'Error! Date cannot be read! Date: ' . $date);
            return false;
         }
         $date = $tmpDate->format('YmdHi');

         $sensorReading = new SensorReading(
            id: Base64::GenerateId(),
            sensorId: $sensorId,
            temp: $temp,
            relHum: $relHum,
            dateRecorded: $date,
            dateAdded: Helper::GetDateNow()
         );
         if ($lastDateSensorReading === null) {
            $lastDateSensorReading = $sensorReading;
         }
         if ($sensorReading->dateRecorded > $lastDateSensorReading->dateRecorded) {
            $lastDateSensorReading = $sensorReading;
         }

         array_push($sensorReadings, $sensorReading);
      }

      $dups = [];
      $sensorReadingsDupCheckArr = (new DalSensorReading())->GetAllWhereSensorId($sensorId);
      $sensorReadingsByDateRecorded = [];
      foreach ($sensorReadingsDupCheckArr as $sensorReadingDb) {
         $sensorReadingsByDateRecorded[$sensorReadingDb->dateRecorded] = true;
      }
      foreach ($sensorReadings as $sensorReadingNew) {
         if (isset($sensorReadingsByDateRecorded[$sensorReadingNew->dateRecorded])) {
            array_push($dups,
               DateTime::createFromFormat('YmdHi', $sensorReadingNew->dateRecorded)
                  ->format($this->pageOutputDateTimeFormat)
            );
         }
      }

      if (sizeof($dups) !== 0) {
         array_push($this->errors, 'Duplicate entry dates: ' . join(', ', $dups));
         return false;
      }

      $pdo = DbHelper::GetDevPDO();
      $pdo->beginTransaction();
      foreach ($sensorReadings as $sensorReading) {
         (new DalSensorReading())->Create($sensorReading, $pdo);
      }
      $pdo->commit();

      DalSensorReading::ResetCache($sensors);
      return true;
   }

   function DebugToConsole($data)
   {
      // Buffering to solve problems frameworks, like header() in this and not a solid return.
      ob_start();

      $output = 'console.log(' . json_encode($data) . ');';
      $output = '<script type=text/javascript>' . $output . '</script>';
      echo $output;
   }

   function IntArrGetClosest(int $needle, array $arr) : int|null {
      $search = null;
      foreach ($arr as $item) {
         if ($search === null || abs($needle - $search) > abs($item - $needle)) {
            $search = $item;
         }
      }
      return $search;
   }

   /** @noinspection PhpUnusedParameterInspection */
   function Render($fileName, $htmlInjects) {
      require($fileName);
   }
}
