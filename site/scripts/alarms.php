<?php
// Alarms Notification Script; ver 0.01; 4th January 2021; Indrek Hiie
// #######################################################################################
//  * START *
// #######################################################################################

global $missed;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';  // Load PHPMailer

// cmdline option processing
cmdline();

date_default_timezone_set('Europe/Tallinn');

$datenow0=date("YmdHis");
echo "* timestamp: $datenow0";
if(!$silent) echo "\n";

// TempSense
echo " * MySQL connection: ";
try {
        $db = new PDO(config()['db']['connectUrl'], config()['db']['username'], config()['db']['password']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
        echo "FAILED (" . $e->getMessage() . ")\n";
        exit;
} echo "OK"; if(!$silent) echo "\n";

$process('1', $db);

$timer_0=$timer_start=time();

// ------------------- CLEANUP

unset($db); unset($res);

$timer_end=time();
echo "* timestamp: " . date("YmdHis") . " ";
echo "* total time used: " . ($timer_end-$timer_0) . " sec\n";

// #######################################################################################
//  * END *
// #######################################################################################


function process($days, $db ) {
   global $limit;
   global $silent;
   global $silentall;
   global $datenow0;

   $topic= $days . '_days_watchdog';

      if(!$silent) echo "* $topic started\n";

      $sql="
            select $limit FROM webtemp WHERE 1;
         ";

      //echo $sql."\n";
      $res = $db->query($sql);

      $i=0;
      if(!$res)
      {
         die("Execute query error, because: ". print_r($db->errorInfo(),true) );
      }
      else
         while($row = $res->fetch())
         {
            $i++;
            $res=$row['minduedate'];

            $email=$row['user_email'];
            $name =$row['user_name'];


                  $mailAddr=$email;
                  $mailName=$name;
                  $mailSubject="ALARM: tempsens - ";

                  $mailBody= message($lang,1) . "<br />\n";
                  $mailBody.= message($lang,2) . $cid . "<br />\n";
                  $mailBody.= message($lang,3) . $email . "<br/>\n<br />\n";
                  $mailBody.= message($lang,4) . "<br />\n";
                  $mailBody.= message($lang,5) . "<br />\n";

		  $mailErr = mailer($mailAddr,$mailName,$mailSubject,$mailBody);

                  if($mailErr) {
                     echo "ERROR: Mail '" . $mailErr . "' for user $res03 ($cid), email '$email'!\n";
                  }
                  else echo "OK: User $res03 ($cid) email to '$email' sent ('$lang')! [$res04;$res07;$res1d;$overdue EUR;$res1c days]\n";

               } // if email found...
               else {
                  echo "WARNING: User $res03 ($cid) email not found! [$res04;$res07;$res1d;$overdue EUR; $res1c days]\n";
               }
            } //if debit..
         } //else_while...

      $timer_end=time();
      $lap=$timer_end-$timer_start;
      $speed=round(1000*$lap/$i21,3);
      $datenow=date("YmdHis");
      if(!$silentall) echo "* timestamp: $datenow ** from $i21 -> $j21 overdue matches found, $i1 mails, $l21 lines, $topic done ($lap sec, $speed ms/row)\n";
      //entertoDB_mdimp($db1,$topic,$timer_start,$timer_end,$lap,$i21,$k,$speed,!($limit),$datenow0);
      return $timer_end;

   } //days...
}

function entertoDB_mdimp($db1,$w00,$w01,$w02,$w03,$w04,$w05,$w06,$w07,$w08) {
   $sql1="INSERT INTO `mdimp` (`ctry`,`stage`,`start`,`stop`,`duration`,`records`,`err_records`,`speed`,`status`,`dactdate`)";
   $sql1=$sql1." VALUES ('EE','$w00','$w01','$w02','$w03','$w04','$w05','$w06','$w07','$w08');";
   //echo $sql1."\n";
   if (!$res1 = $db1->query($sql1)) {
      echo "MySQL insert error: $sql1 \n";
   }
}

function cmdline() {

   global $u14;
   global $u21;
   global $u24;
   global $u30;
   global $u_limit;
   global $u_period;

   global $limit;
   global $silent;
   global $silentall;

   $u14=$u21=$u24=$u30=0;
   $u_period=$u_limit=$u_silent=$u_silentall=0;

   $longopts  = array(
      "14",
      "21",
      "24",
      "30",
      "all",
      "period",
      "limit",
      "silent",
      "silentall"
   );

   $opts = getopt("",$longopts);

   // Handle command line arguments
   foreach (array_keys($opts) as $opt) switch ($opt) {
      case '14':
         $u14=true;
         //$something = $opts['product'];
         break;
      case '21':
         $u21=true;
         break;
      case '24':
         $u24=true;
         break;
      case '30':
         $u30=true;
         break;
      case 'all':
         $u14=true;
         $u21=true;
         $u24=true;
         break;
      case 'period':
         $u_period=true;
         break;
      case 'limit':
         $u_limit=true;
         break;
      case 'silent':
         $u_silent=true;
         break;
      case 'silentall':
         $u_silentall=true;
         break;
   }

   if($u_silent) $silent = true; else $silent = false;
   if($u_silentall) { $silentall = true; $silent = true; } else $silentall = false;

   if($u14 & $u21 & $u24) {
      if(!$silent) echo "Processing: everything\n";
   }
   else
      if(!$silent)
         if($u14 | $u21 | $u24 | $u30) {
            echo "Processing: [ ";
            if($u14) echo "14 days ";
            if($u21) echo "21 days ";
            if($u24) echo "24 days ";
            if($u30) echo "30 days ";
            echo "]\n";
         }
         else echo "Use: php overdue-ee.php [--14|--21|--24|--30|--all] [--period|--limit|--silent|--silentall]\n";

   if($u_period) if(!$silent) echo "NB! Period handling mode: on\n";
   if($u_limit)  { if(!$silent) echo "limit mode: 2 rows limit\n"; $limit = 'TOP 2 '; } else $limit ='';

}

function time_passed($secs)
   {
   $bit = array(
      'y' => $secs / 31556926 % 12,
      'w' => $secs / 604800 % 52,
      'd' => $secs / 86400 % 7,
      'h' => $secs / 3600 % 24,
      'm' => $secs / 60 % 60,
      's' => $secs % 60
      );

   foreach($bit as $k => $v)
      if($v > 0)$ret[] = $v . $k;

   return join(' ', $ret);
   }

function escape($value)
   {
       $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
       $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");

       return str_replace($search, $replace, $value);
   }

function config()
   {
      global $configData;
      if (! isset($configData)) {
         $configData = require ("./config/config.php");
      }
      return $configData;
   }

function mailer($mailAddr, $mailName, $mailSubject, $mailBody)
   {
      $aeg = date('d-m-Y H:i',time());

      $mail = new PHPMailer(true);
      try {
      //Server settings
         $mail->CharSet = 'UTF-8';
         $mail->Encoding = 'base64';
         $mail->SMTPDebug = 0;  //set '2' for debugging
         $mail->isSMTP();
         $mail->Host = config()['smtp']['host'];
         $mail->SMTPAuth = config()['smtp']['auth'];
         $mail->Username = config()['smtp']['username'];
         $mail->Password = config()['smtp']['password'];
         $mail->SMTPSecure = 'tls';
         $mail->Port = 587;

         $mail->setFrom(config()['smtp']['email'], config()['smtp']['name']);

         $mailBody="<style type='text/css'><!--\n
            table { width: 100%; border-collapse: collapse; }\n
            table th { border: 1px #b9bbbe solid; padding: 8px; background: #f0f0f0; font-weight: bold; text-align: left; }\n
            table td { border: 1px #b9bbbe solid; padding: 8px; text-align: left; }\n
            -->\n</style>\n" . $mailBody;

$mailAddr = "indrek.hiie@sirowa.com";
$mailName = "Indrek Hiie";

         $mail->addAddress($mailAddr, $mailName);

//         $mail->addAddress('recipient2@example.com');
//         $mail->addReplyTo('noreply@example.com', 'noreply');
//         $mail->addCC('cc@example.com');
         $mail->addBCC(config()['dev']['email'], config()['dev']['name']);
//         Attachments
//         $mail->addAttachment('/backup/myfile.tar.gz');

         //Content
         $mail->isHTML(true);
         $mail->Subject = $mailSubject;
         $mail->Body    = $mailBody;

         $mail->send();
         return 0;
      } catch (Exception $e) {
         return $mail->ErrorInfo;
      }
   }

function message($lang, $part)
   {

   $msg = array();

   switch ($lang) {
      case 'et':
      case 'ET':
         $msg[1]="Armas kolleeg,<br />\n";
         break;
      default:
         $msg[1]="Dear Colleague,<br />\n";
         break;
   }
   return $msg[$part];
}

?>
