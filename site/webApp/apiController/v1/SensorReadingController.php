<?php

namespace App\webApp\apiController\v1;

use App\db\dal\DalSensorReading;
use App\db\dal\DalSensors;
use App\db\DbHelper;
use App\domain\Sensor;
use App\domain\SensorReading;
use App\dtoApi\SensorReadingUpload\SensorReadingUpload;
use App\dtoApi\SensorReadingUpload\SensorReadingUploadReadings;
use App\util\Config;
use App\util\Helper;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SensorReadingController
{
   function Insert(Request $request, Response $response, $args): Response {
      $xml_string = $request->getBody()->getContents();
      $xmlStringParsable = str_replace('soap:', '', $xml_string);    // https://stackoverflow.com/questions/4194489/how-to-parse-soap-xml
      $requestDataObject = @simplexml_load_string($xmlStringParsable); // add @ before function call to prevent warning message from being added to response
      if (! $requestDataObject) {
         $response->getBody()->write("Failed to parse XML!");
         return $response->withStatus(400);
      }
      $serial = (string) $requestDataObject->Body->InsertTx5xxSample->passKey;
      $temp = (float) $requestDataObject->Body->InsertTx5xxSample->temp;
      $relHum = (float) $requestDataObject->Body->InsertTx5xxSample->relHum;

      /** @var Sensor|null $sensor */
      $sensor = collect((new DalSensors())->GetAll())->first(fn(Sensor $x) => $x->serial === $serial);
      if ($sensor === null) {
         $response->getBody()->write('Error! Sensor with serial:' . $serial . ' does not exist!');
         return $response->withStatus(400);
      }
      $id = Uuid::uuid4()->toString();
      $reading = new SensorReading(
         id: $id,
         sensorId: $sensor->id,
         temp: $temp,
         relHum: $relHum,
         dateRecorded: Helper::GetUtcNow(),
         dateAdded: null
      );
      $db = DbHelper::GetPDO();
      (new DalSensorReading)->InsertByChunk([$reading], $db);

      $response->getBody()->write($id);
      return $response;
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
         $sensorReadingDeserialized[] = new SensorReadingUploadReadings(DateTimeImmutable::createFromFormat("d-m-Y H:i:s", $sensorReading['date'], new \DateTimeZone("Europe/Tallinn")), $sensorReading['temp'], $sensorReading['relHum']);
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
            id: Uuid::uuid4()->toString(),
            sensorId: $sensor->id,
            temp: $row->temp,
            relHum: $row->relHum,
            dateRecorded: $row->date,
            dateAdded: Helper::GetUtcNow()
         );
      }, $model->sensorReadings);

      $existingSensorReadingsDateRecordedArr = array_map(
         fn($x) => $x->dateRecorded->format('YmdHis'),
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

      $response->getBody()->write('Success!');
      return $response->withStatus(200);
   }
}