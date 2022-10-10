<?php

declare(strict_types=1);

namespace App\viewController;

require_once(__DIR__."/../../vendor/autoload.php");

use App\db\dal\DalSensorReading;
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
use App\util\Console;
use App\util\Helper;
use App\util\InputValidation;
use DateTimeImmutable;

(new Programm())->main();

class Programm {

   public array $debugs = [];
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
         $lastReading = $lastReadingsData[$sensor->id] ?? null;

         $dateRecorded = 'NO DATA';
         $col = 'red';
         if ($lastReading !== null) {
            $lastDate = $lastReading->getDateRecordedAsDateTime()->format('d/m/Y H:i');
            $minutesDiff = (
               Helper::GetDateNowAsDateTime()->getTimestamp() -
               $lastReading->getDateRecordedAsDateTime()->getTimestamp()
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

      $from = DateTimeImmutable::createFromFormat('d-m-Y', $input->dateFrom)->format('Ymd') . '0000';
      $to = DateTimeImmutable::createFromFormat('d-m-Y', $input->dateTo)->format('Ymd') . '2359';


      $sensorReadingsBySensorId = [];
      foreach ($sensors as $sensor) {
         $sensorReadingsBySensorId[$sensor->id] = [];
      }
      $sensorReadingsBySensorId = array_merge($sensorReadingsBySensorId, (new DalSensorReading())->GetAllBetween($from, $to));

      array_push($this->debugs,"DalSensorReading->GetAllBetween($from, $to) query: " . microtime(true) - $before);
      $before = microtime(true);

      $sensorAlertsMinMax = [];
      foreach ($sensors as $sensor) {
         $rawOutOfBounds = array_values(array_filter($sensorReadingsBySensorId[$sensor->id], fn($x) =>
            $x->getTemp() < $sensor->minTemp || $x->getTemp() > $sensor->maxTemp ||
            $x->getRelHum() < $sensor->minRelHum || $x->getRelHum() > $sensor->maxRelHum
         ));
         $sensorAlertsMinMax[$sensor->id] = AlertMinMax::Get($sensor, $rawOutOfBounds);
      }

      array_push($this->debugs,'Combine sensor alerts: ' . microtime(true) - $before);
      $before = microtime(true);

      $colors = [];
      for ($i = 0; $i < sizeof($sensors) * 2; $i++) {
         mt_srand($i * 13 + 1);    // feel free to experiment with the seed
         $val = '#' . str_pad(dechex(mt_rand(0x000000, 0xFFFFFF)), 6, '0', STR_PAD_RIGHT);
         array_push($colors, $val);
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

      Helper::Render(__DIR__ . "/../view/main/index.php", $htmlInjects);

      foreach ($this->debugs as $debug) {
         Console::DebugToConsole($debug, true);
      }
   }


   function HandleInput(array &$sensors): HandleInputModel
   {
      $dateTo = Helper::GetDateNowAsDateTime();
      $inputDateTo = $_GET['To'] ?? '';
      if (InputValidation::isDateFormat__d_m_Y($inputDateTo)) {
         $dateTo = DateTimeImmutable::createFromFormat('d-m-Y', $inputDateTo);
      }


      $dateFrom = $dateTo->modify("-91 day");
      $dateFromType = 'relative';

      $inputDateFrom = $_GET['From'] ?? '';
      if (strlen($inputDateFrom) > 0 && $inputDateFrom[0] === '-' && is_numeric(substr($inputDateFrom, 1))) {
         $dateFrom = $dateTo->modify($inputDateFrom . "day");
      } else if (InputValidation::isDateFormat__d_m_Y($inputDateFrom)) {
         $dateFrom = DateTimeImmutable::createFromFormat('d-m-Y', $inputDateFrom);
         $dateFromType = 'absolute';
      }

      $selectOptionsRelativeDateFrom = $this->HandleSelectOptionsRelativeDateFrom($dateFrom, $dateTo);
      $sensorCrud = $this->HandleSensorCrud($sensors);
      $this->UploadReadings($sensors);

      $result = new HandleInputModel($dateFrom->format('d-m-Y'), $dateTo->format('d-m-Y'), $selectOptionsRelativeDateFrom, $dateFromType, $sensorCrud);
      return $result;
   }

   /**
    * @param $dateFrom DateTimeImmutable
    * @param $dateTo DateTimeImmutable
    * @return string[]
    */
   public function HandleSelectOptionsRelativeDateFrom(DateTimeImmutable $dateFrom, DateTimeImmutable $dateTo): array {
      $daysBefore = $this->IntArrGetClosest(
         $dateTo->diff($dateFrom)->days,
         array_map(
            fn($x) => $x->value,
            Period::GetPeriods()
         )
      );

      $periods = Period::GetPeriods();
      return array_map(
         fn($period) => $daysBefore === $period->value ?
            "<option value='$period->value' selected>-$period->name</option>" :
            "<option value='$period->value'>-$period->name</option>",
         $periods);
   }

   function HandleSensorCrud(array &$sensors): SensorCrudBadCreateValues {
      $formType = $_POST['formType'] ?? null;
      $id = $_POST['id'] ?? null;
      $name = isset($_POST['name']) ? trim($_POST['name']) : null;
      $serial = isset($_POST['serial']) ? trim($_POST['serial']) : null;
      $model = isset($_POST['model']) ? trim($_POST['model']) : null;
      $ip = isset($_POST['ip']) ? trim($_POST['ip']) : null;
      $location = isset($_POST['location']) ? trim($_POST['location']) : null;
      $isPortable = isset($_POST['isPortable']) && trim($_POST['isPortable']) === 'Y';
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
            $idValid = in_array($id, array_map(fn($x) => $x->id, $sensors));
            break;
         default:
            return new SensorCrudBadCreateValues(null, '');
      }

      if (!$idValid) {
         array_push($this->errors, 'Internal error! Bad sensor id: ' . var_export($id, true));
         return new SensorCrudBadCreateValues(null, '');
      }
      if ($name === null || $serial === null || $model === null || $ip === null
         || $location === null || $isPortable || $minTemp === null || $maxTemp === null
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

      if ($auth !== (new Config())->GetWebDbPassword()) {
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
            (new DalSensors())->InsertByChunk([$sensor], $pdo);
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

      if ($auth !== (new Config())->GetWebDbPassword()) {
         array_push($this->errors, 'Not authorized!');
         return false;
      }

      $jsonDataInput = json_decode($jsonDataInput);
      if (sizeof($jsonDataInput) === 0) {
         return false;
      }

      $newSensorReadings = SensorReading::NewArray();
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
         $dateDateTime = DateTimeImmutable::createFromFormat('d-m-Y H:i', $date);
         if ($dateDateTime === false) {
            array_push($this->errors, 'Error! Date cannot be read! Date: ' . $date);
            return false;
         }

         $sensorReading = new SensorReading(
            id: Base64::GenerateId(),
            sensorId: $sensorId,
            temp: $temp,
            relHum: $relHum,
            dateRecorded: $dateDateTime,
            dateAdded: Helper::GetDateNowAsDateTime()
         );

         array_push($newSensorReadings, $sensorReading);
      }

      $sensorReadingsByDateRecorded = array_map(
         fn($x) => $x->getDate(),
         (new DalSensorReading())->GetAllWhereSensorId($sensorId)
      );
      $duplicateDateTimes = array_filter($newSensorReadings, fn($sensorReadingNew) =>
         in_array($sensorReadingNew->dateRecorded, $sensorReadingsByDateRecorded, true)
      );

      if (sizeof($duplicateDateTimes) !== 0) {
         $duplicateDateStrings = array_map(
            fn($duplicateDateTime) => $duplicateDateTime->getDateRecordedAsDateTime()->format('d/m/Y H:i'),
            $duplicateDateTimes);
         array_push($this->errors, 'Duplicate entry dates: ' . join(', ', $duplicateDateStrings));
         return false;
      }

      $pdo = DbHelper::GetPDO();
      $pdo->beginTransaction();
      (new DalSensorReading())->InsertByChunk($newSensorReadings, $pdo);
      $pdo->commit();

      DalSensorReading::ResetCache($sensors);
      return true;
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
}
