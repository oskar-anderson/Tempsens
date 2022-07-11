<?php

namespace App\db\dal;

require_once(__DIR__."/../../../vendor/autoload.php");

use PDO;

interface IDalBase {
    /**
     * Table names will be lowercase in DB
    *  @return string
    */
   public function GetName(): string;

   /**
    *  @return string
    */
    public function SqlCreateTableStmt(): string;

}
