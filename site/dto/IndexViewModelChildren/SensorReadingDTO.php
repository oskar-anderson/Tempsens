<?php

namespace App\dto\IndexViewModelChildren;


use App\dto\AbstractValidModel;
use DateTimeImmutable;

class SensorReadingDTO extends AbstractValidModel
{
   private ?DateTimeImmutable $date = null;
   private ?float $temp = null;
   private ?float $relHum = null;


   public function __construct(bool $isDateReq, bool $isTempReq, bool $isRelHumReq)
   {
      parent::__construct("SensorReadingDTO");
      $this->isFieldReqArr = [$isDateReq, $isTempReq, $isRelHumReq];
      // Arrow Functions PHP 7.4
      $this->fieldIsValidFuncArr = [
         [fn() => $this->date !== null, "SensorReadingDTO date must be defined!"],
         [fn() => $this->temp !== null, "SensorReadingDTO temp must be defined!"],
         [fn() => $this->relHum !== null, "SensorReadingDTO relHum must be defined!"]
      ];
   }

   public function setDate(DateTimeImmutable $date): static { $this->isValidCache = false; $this->date = $date; return $this;}
   public function getDate(): DateTimeImmutable { $this->dieWhenInvalid(); return $this->date; }

   public function setTemp(float $temp): static { $this->isValidCache = false; $this->temp = $temp; return $this;}
   public function getTemp(): float { $this->dieWhenInvalid(); return $this->temp; }

   public function setRelHum(float $relHum): static { $this->isValidCache = false; $this->relHum = $relHum; return $this;}
   public function getRelHum(): float { $this->dieWhenInvalid(); return $this->relHum; }

   /**
    * Type hinting trick
    *  @return SensorReadingDTO[]
    */
   public static function NewArray(): array
   {
      return [];
   }

   public function jsonSerialize(): array
   {
      $this->dieWhenInvalid();

      return [
         "date" => $this->getDate()->format("YmdHi"),
         "temp" => $this->getTemp(),
         "relHum" => $this->getRelHum()
      ];
   }
}