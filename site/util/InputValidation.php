<?php


namespace App\util;


class InputValidation
{
   public static function isDateFormat__d_m_Y(string $input): bool
   {
      $dmY = explode("-", $input);
      if (sizeof($dmY) !== 3) {
         return false;
      }
      $isDaysValid = false;
      $isMonthsValid = false;
      $isYearsValid = false;

      if (strlen($dmY[0]) === 2) {
         if (str_contains("0123", $dmY[0][0]) && str_contains("0123456789", $dmY[0][1])) {
            $isDaysValid = true;
         }
      }

      if (strlen($dmY[1]) === 2) {
         if (str_contains("01", $dmY[1][0]) && str_contains("0123456789", $dmY[1][1])) {
            $isMonthsValid = true;
         }
      }

      if (is_numeric($dmY[2])) {
         $isYearsValid = true;
      }

      return $isDaysValid && $isMonthsValid && $isYearsValid;
   }
}