<?php

declare(strict_types=1);

namespace App\viewController;

require_once(__DIR__."/../../vendor/autoload.php");

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensors;
use App\db\DbHelper;
use App\dto\IndexViewModel;
use App\dto\IndexViewModelChildren\AlertMinMax;
use App\dto\IndexViewModelChildren\HandleInputModel;
use App\dto\IndexViewModelChildren\LastSensorReading;
use App\dto\IndexViewModelChildren\Period;
use App\frontendDto\Sensor\SensorWithAuth_v1;
use App\frontendDto\SensorReadingUpload\SensorReadingUpload;
use App\frontendDto\SensorReadingUpload\SensorReadingUploadReadings;
use App\mapper\SensorMapper;
use App\model\SensorReading;
use App\util\Base64;
use App\util\Config;
use App\util\Helper;
use App\util\InputValidation;
use DateTimeImmutable;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


class Overview {

   public array $debugs = [];

   public function Index(Request $request, Response $response, $args): Response
   {
      $start = microtime(true);
      $before = microtime(true);

      $input = $this->HandleInput();
      $sensors = (new DalSensors())->GetAll();

      array_push($this->debugs,'Get sensors and handle input: ' . microtime(true) - $before);
      $before = microtime(true);

      $lastReadingsData = DalSensorReading::GetLastReadingsFromCacheOrDefault($sensors);
      $lastReadingsView = [];
      foreach ($sensors as $sensor) {
         $lastReading = $lastReadingsData[$sensor->id] ?? null;

         $dateRecorded = 'NO DATA';
         $col = 'red';
         if ($lastReading !== null) {
            $lastDate = $lastReading->getDateRecordedAsDateTime()->format('d/m/Y H:i');
            $minutesDiff = floor((
               Helper::GetDateNowAsDateTime()->getTimestamp() -
               $lastReading->getDateRecordedAsDateTime()->getTimestamp()
            ) / 60);
            if ($sensor->readingIntervalMinutes - $minutesDiff < 0 && !$sensor->isPortable) {
               $dateRecorded = 'DOWN @' . $lastDate;
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

      array_push($this->debugs,'Get last readings: ' . microtime(true) - $before);
      $before = microtime(true);

      $from = DateTimeImmutable::createFromFormat('d-m-Y', $input->dateFrom);
      $to = DateTimeImmutable::createFromFormat('d-m-Y', $input->dateTo);
      $periods = Period::GetPeriodOptions($from, $to);


      $sensorReadingsBySensorId = [];
      foreach ($sensors as $sensor) {
         $sensorReadingsBySensorId[$sensor->id] = [];
      }
      $sensorReadingsBySensorId = array_merge($sensorReadingsBySensorId, (new DalSensorReading())->GetAllBetween($from->format('Ymd') . '2359', $to->format('Ymd') . '2359'));

      array_push($this->debugs,"GetAllBetween(" . $from->format('Ymd') . '2359' . ", " . $to->format('Ymd') . '2359') .  " query (count: " . (new Collection($sensorReadingsBySensorId))->map(function ($readings) { return count($readings); })->sum() . "): " . microtime(true) - $before;
      $before = microtime(true);

      $sensorAlertsMinMax = [];
      foreach ($sensors as $sensor) {
         $rawOutOfBounds = array_values(array_filter($sensorReadingsBySensorId[$sensor->id], fn($x) =>
            $x->getTemp() < $sensor->minTemp || $x->getTemp() > $sensor->maxTemp ||
            $x->getRelHum() < $sensor->minRelHum || $x->getRelHum() > $sensor->maxRelHum
         ));
         $sensorAlertsMinMax[$sensor->id] = AlertMinMax::Get($sensor, $rawOutOfBounds);
      }

      array_push($this->debugs,'Group chaining sensor alerts: ' . microtime(true) - $before);
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
         ->SetPeriods($periods)
         ->SetDefault();

      $content = Helper::Render(__DIR__ . "/../view/main/overview.php", $htmlInjects);
      array_push($this->debugs,'PHP generating page: ' . microtime(true) - $before);
      array_push($this->debugs,'PHP total: ' . microtime(true) - $start);
      $content .= '<script type=text/javascript>' . join("", array_map(fn($x) => 'console.log(' . json_encode($x) . ');', $this->debugs)) . '</script>';
      $response->getBody()->write($content);
      return $response->withStatus(200);
   }

   function HandleInput(): HandleInputModel
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

      $result = new HandleInputModel($dateFrom, $dateTo, $dateFromType);
      return $result;
   }

   function CreateSensor(Request $request, Response $response, $args): Response {
      $body = $request->getBody()->getContents();  // $request->getParsedBody() does not work for some reason
      $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
      /** @var SensorWithAuth_v1 $model */
      $model = $serializer->deserialize($body, SensorWithAuth_v1::class, 'json');

      if (sizeof($model->sensor->validate()) !== 0) {
         $response->getBody()->write("Bad request or invalid data!");
         return $response->withStatus(400);
      }
      if ($model->auth !== (new Config())->GetWebDbPassword()) {
         $response->getBody()->write("Not authorized!");
         return $response->withStatus(401);
      }
      $domainSensor = (new SensorMapper())->MapFrontToDomain($model->sensor);
      $pdo = DbHelper::GetPDO();
      (new DalSensors())->InsertByChunk([$domainSensor], $pdo);
      $response->getBody()->write("All good!");
      return $response->withStatus(200);
   }

   function UpdateSensor(Request $request, Response $response, $args): Response {
      $body = $request->getBody()->getContents();  // $request->getParsedBody() does not work for some reason
      $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
      /** @var SensorWithAuth_v1 $model */
      $model = $serializer->deserialize($body, SensorWithAuth_v1::class, 'json');

      if (sizeof($model->sensor->validate()) !== 0) {
         $response->getBody()->write("Bad request or invalid data!");
         return $response->withStatus(400);
      }
      if ($model->auth !== (new Config())->GetWebDbPassword()) {
         $response->getBody()->write("Not authorized!");
         return $response->withStatus(401);
      }
      $domainSensor = (new SensorMapper())->MapFrontToDomain($model->sensor);
      (new DalSensors())->Update($domainSensor);
      $response->getBody()->write("All good!");
      return $response->withStatus(200);
   }

   function DeleteSensor(Request $request, Response $response, $args): Response {
      $model = json_decode($request->getBody()->getContents());
      $id = $model->id ?? null;
      $auth = $model->auth ?? null;

      if ($id === null || $auth === null) {
         $response->getBody()->write("Bad request");
         return $response->withStatus(400);
      };
      if ($auth !== (new Config())->GetWebDbPassword()) {
         $response->getBody()->write("Not authorized!");
         return $response->withStatus(401);
      }

      (new DalSensors())->Delete($id);
      $response->getBody()->write("All good!");
      return $response->withStatus(200);
   }

   function UploadReadings(Request $request, Response $response, $args): Response {
      $body = $request->getBody()->getContents();  // $request->getParsedBody() does not work for some reason
      $serializer = new Serializer([new ObjectNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);
      /** @var SensorReadingUpload $model */
      $model = $serializer->deserialize($body, SensorReadingUpload::class, 'json');
      // Deserializer is a piece of shit and will not hydrate array objects

      /** @var SensorReadingUploadReadings[] $sensorReadingDeserialized */
      $sensorReadingDeserialized = [];
      foreach ($model->sensorReadings as $sensorReading) {
         $sensorReadingDeserialized[] = new SensorReadingUploadReadings(DateTimeImmutable::createFromFormat("d-m-Y H:i:s", $sensorReading['date']), $sensorReading['temp'], $sensorReading['relHum']);
      }
      $model->sensorReadings = $sensorReadingDeserialized;

      if ($model === null || sizeof($model->sensorReadings) === 0) {
         $response->getBody()->write("Bad request or invalid data!");
         return $response->withStatus(400);
      }
      $sensor = (new DalSensors())->GetFirstOrDefault($model->sensorId);
      if ($sensor === null) {
         $response->getBody()->write('Error! Sensor with id not found! Id: ' . $model->sensorId);
         return $response->withStatus(400);
      }

      if ($model->auth !== (new Config())->GetWebDbPassword()) {
         $response->getBody()->write('Not authorized!');
         return $response->withStatus(401);
      }

      $newSensorReadings = array_map(function ($row) use ($sensor) {
         return new SensorReading(
            id: Base64::GenerateId(),
            sensorId: $sensor->id,
            temp: $row->temp,
            relHum: $row->relHum,
            dateRecorded: $row->date,
            dateAdded: Helper::GetDateNowAsDateTime()
         );
      }, $model->sensorReadings);

      $existingSensorReadingsDateRecordedArr = array_map(
         fn($x) => $x->getDate()->format('YmdHis'),
         (new DalSensorReading())->GetAllWhereSensorId($model->sensorId)
      );

      /** @var SensorReading[] $duplicateDateTimes */
      $duplicateDateTimes = array_filter($newSensorReadings, function($sensorReadingNew) use ($existingSensorReadingsDateRecordedArr) {
         return in_array($sensorReadingNew->dateRecorded, $existingSensorReadingsDateRecordedArr, true);
      });

      if (sizeof($duplicateDateTimes) !== 0) {
         $duplicateDateStrings = array_map(
            fn($duplicateDateTime) => $duplicateDateTime->getDateRecordedAsDateTime()->format('d/m/Y H:i:s'),
            $duplicateDateTimes);
         $response->getBody()->write('Duplicate entry dates: ' . join(', ', $duplicateDateStrings));
         return $response->withStatus(400);
      }

      $pdo = DbHelper::GetPDO();
      $pdo->beginTransaction();
      (new DalSensorReading())->InsertByChunk($newSensorReadings, $pdo);
      $pdo->commit();

      DalSensorReading::ResetCache();
      $response->getBody()->write('Success!');
      return $response->withStatus(200);
   }
}
