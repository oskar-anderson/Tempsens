<?php

namespace App\dto;

use JsonSerializable;

abstract class AbstractValidModel implements JsonSerializable
{
   protected array $fieldIsValidFuncArr = [];
   protected array $isFieldReqArr = [];
   protected bool $isValidCache = false;

   public function __construct(public string $childName)
   {
   }

   public function isValid(): array {
      foreach ($this->isFieldReqArr as $i => $isRequiredToCheck) {
         if (! $isRequiredToCheck) {
            continue;
         }

         $isValid = $this->fieldIsValidFuncArr[$i][0]();
         if (! $isValid) {
            return [
               "result" => false,
               "message" => $this->fieldIsValidFuncArr[$i][1],
            ];
         }
      }
      $this->isValidCache = true;
      return [
         "result" => true,
         "message" => "",
      ];
   }

   public function dieWhenInvalid() {
      if ($this->isValidCache) {
         return $this;
      }
      $check = $this->isValid();
      if (! $check["result"]) {
         die("{$this->childName} is invalid! {$check['message']}");
      }
      return $this;
   }

   /**
    * Type hinting trick, use PHPDoc return value type hinting
    *  @return null[]
    */
   public static abstract function NewArray(): array;

   /**
    * Makes classes with private properties serializable with json_encode().
    * You can serialize the parent along with the child if you really want.
    * array_merge(parent::jsonSerializeParent(), get_object_vars($this));
    * @return array
    */
   public function jsonSerializeParent(): array
   {
      return get_object_vars($this);
   }

   public abstract function jsonSerialize();
}