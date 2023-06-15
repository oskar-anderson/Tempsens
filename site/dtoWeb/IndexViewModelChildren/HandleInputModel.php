<?php


namespace App\dtoWeb\IndexViewModelChildren;


use DateTimeImmutable;

class HandleInputModel
{
   public DateTimeImmutable $dateFrom;
   public DateTimeImmutable $dateTo;
   public string $dateFromType;


   function __construct(\DateTimeImmutable $dateFrom, \DateTimeImmutable $dateTo, string $dateFromType)
   {
      $this->dateFrom = $dateFrom;
      $this->dateTo = $dateTo;
      $this->dateFromType = $dateFromType;
   }
}