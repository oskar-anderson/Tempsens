<?php


namespace App\dto\IndexViewModelChildren;


class HandleInputModel
{
   public string $dateFrom;
   public string $dateTo;
   /* @var string[] $selectOptionsRelativeDateFrom */
   public array $selectOptionsRelativeDateFrom;
   public string $dateFromType;
   public SensorCrudBadCreateValues $sensorCrud;

   /* @param  string[] $selectOptionsRelativeDateFrom */
   function __construct(string $dateFrom, string $dateTo, array $selectOptionsRelativeDateFrom, string $dateFromType, SensorCrudBadCreateValues $sensorCrud)
   {
      $this->dateFrom = $dateFrom;
      $this->dateTo = $dateTo;
      $this->selectOptionsRelativeDateFrom = $selectOptionsRelativeDateFrom;
      $this->dateFromType = $dateFromType;
      $this->sensorCrud = $sensorCrud;
   }
}