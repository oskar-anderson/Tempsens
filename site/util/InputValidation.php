<?php


namespace App\util;


class InputValidation
{

   public static function StringInputValidation(array &$errorMessages, string $input, int $maxLength, $name)
   {
      $badChars = str_split(''); // '><|"\'\\/?:*\`'
      $isBadChars = sizeof(array_intersect(str_split($input), $badChars)) !== 0;
      $isEmpty = $input === '';
      $isTooBig = strlen($input) >= $maxLength;
      $tmp = [];
      if ($isBadChars) {
         array_push($tmp, 'Cannot contain special characters (' . htmlspecialchars(implode($badChars)) . '). ');
      }
      if ($isEmpty) {
         array_push($tmp, 'No value. ');
      }
      if ($isTooBig) {
         array_push($tmp, 'Length must be less than ' . $maxLength . '.');
      }
      if ($isBadChars || $isEmpty || $isTooBig) {
         array_push($errorMessages, $name . ' is not valid! ' . implode('', $tmp));
      }
   }

   public static function FloatInputValidation(array &$errorMessages, string $input, $name): void
   {
      $isEmpty = $input === '';
      $isNumeric = is_numeric($input);
      $tmp = [];
      if ($isEmpty) array_push($tmp, 'No value. ');
      if (!$isNumeric) array_push($tmp, 'Must be a number. ');
      if ($isEmpty || !$isNumeric) array_push($errorMessages, $name . ' is not valid! ' . implode('', $tmp));
   }

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