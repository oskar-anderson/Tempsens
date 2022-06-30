<?php

namespace App\db\dal;

require_once(__DIR__."/../../../vendor/autoload.php");

use PDO;

interface IDalBase {
    /**
    *  @return string
    */
   public function GetName(): string;

   /**
    *  @return string
    */
    public function SqlCreateTableStmt(): string;

}