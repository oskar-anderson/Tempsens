<?php

declare(strict_types=1);

namespace App\model;

use App\dto\AbstractValidModel;

class Cache extends AbstractValidModel
{
   // access only through getters
   private ?string $id = null;
   private ?string $type = null;
   private ?string $content = null;

   public function __construct(bool $isIdReq, bool $isTypeReq, bool $isContentReq)
   {
      parent::__construct("Cache");
      $this->isFieldReqArr = [$isIdReq, $isTypeReq, $isContentReq];
      // Arrow Functions PHP 7.4
      $this->fieldIsValidFuncArr = [
         [fn() => $this->id !== null, "Cache id must be defined!"],
         [fn() => $this->type !== null, "Cache type must be defined!"],
         [fn() => $this->content !== null, "Cache content must be defined!"]
      ];
   }

   public function setId(string $id): static { $this->isValidCache = false; $this->id = $id; return $this; }

   public function setType(string $type): static { $this->isValidCache = false; $this->type = $type; return $this; }

   public function setContent(mixed $content): static { $this->isValidCache = false; $this->content = json_encode($content); return $this;}

   public function getId(): string { $this->dieWhenInvalid(); return $this->id; }

   public function getType(): string { $this->dieWhenInvalid(); return $this->type; }

   public function getContent(): string { $this->dieWhenInvalid(); return $this->content; }


   /**
    * @return Cache[]
    */
   public static function NewArray(): array
   {
      return [];
   }

   public function jsonSerialize(): array
   {
      $this->dieWhenInvalid();
      return get_object_vars($this);
   }
}