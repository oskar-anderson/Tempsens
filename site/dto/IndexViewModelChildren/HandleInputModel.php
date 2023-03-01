<?php


namespace App\dto\IndexViewModelChildren;


class HandleInputModel
{
   public string $dateFrom;
   public string $dateTo;
   public string $dateFromType;


   function __construct(\DateTimeImmutable $dateFrom, \DateTimeImmutable $dateTo, string $dateFromType)
   {
      $this->dateFrom = $dateFrom->format('d-m-Y');
      $this->dateTo = $dateTo->format('d-m-Y');
      $this->dateFromType = $dateFromType;
   }
}