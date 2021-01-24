<?php
   session_start();

   global $parms;
   global $baseurl;
   global $datenow;
   $baseurl="index.php";

   $per_a = [
      ['0', '-1 day', '1 day','1'],
      ['1', '-1 week', '1 week','7'],
      ['2', '-2 weeks', '2 weeks', '14'],
      ['3', '-1 month', '1 month', '30'],
      ['4', '-3 months', '3 months', '60'],
      ['5', '-6 months', '6 months', '180'],
      ['6', '-1 year', '1 year', '365'],
      ['7', '-10 years', '10 years', '3650']
   ];

   date_default_timezone_set('Europe/Tallinn');
   $datenow=date("YmdHi");

   require("functions.inc.php");

   // Temperature DB
   try {
     $db1 = new PDO(Config()['db']['connectUrl'], Config()['db']['username'], Config()['db']['password']);
     $db1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   } catch (PDOException $e) {
     echo "FAILED (" . $e->getMessage() . ")\n";
     exit;
   }

   // ---> export - file export
   if (isset($_GET['export'])) { $export = preg_replace ('/[^0-9]/', '', $_GET['export'] ); }
     else if (isset($_POST['export'])){ $export = preg_replace ('/[^0-9]/', '', $_POST['export'] ); } else { $export=0; }

   // ---> id - sensor select
   if (isset($_GET['id'])) { $id = preg_replace ('/[^0-9]/', '', $_GET['id'] ); }
     else if (isset($_POST['id'])){ $id = preg_replace ('/[^0-9]/', '', $_POST['id'] ); } else { $id=1; }

   // ---> inter - interval select
   if (isset($_GET['inter'])) { $inter = preg_replace ('/[^0-9]/', '', $_GET['inter'] ); }
     else if (isset($_POST['inter'])){ $inter = preg_replace ('/[^0-9]/', '', $_POST['inter'] ); } else { $inter=1; }

   // ---> per - period select
   if (isset($_GET['per'])) { $per = preg_replace ('/[^0-9]/', '', $_GET['per'] ); }
     else if (isset($_POST['per'])){ $per = preg_replace ('/[^0-9]/', '', $_POST['per'] ); } else { $per=1; }
   $peri=$per_a[$per][1];

   // ---> from - startdate
   if (isset($_GET['dateFrom'])) { $dateFrom = preg_replace ('/[^0-9-]/', '', $_GET['dateFrom'] ); }
     else if (isset($_POST['dateFrom'])){ $dateFrom = preg_replace ('/[^0-9-]/', '', $_POST['dateFrom'] ); } else { $dateFrom = date('d-m-Y', strtotime("+0 day",strtotime($peri))); }

   // ---> to - enddate
   if (isset($_GET['dateTo'])) { $dateTo = preg_replace ('/[^0-9-]/', '', $_GET['dateTo'] ); }
     else if (isset($_POST['dateTo'])){ $dateTo = preg_replace ('/[^0-9-]/', '', $_POST['dateTo'] ); } else { $dateTo = date('d-m-Y', strtotime('+1 day')); }

  if(!isset($parms)) $parms = getParms($db1);
  $sen=getSensors($db1);
  //print_r($sen);
  $sensors = count($sen);
  //$table = 'webtemp';

  foreach ($sen as $nam) {
     if ($id == $nam[0]) { $sen_id=$nam[6];}
  }
  //echo $sen_id . "<br />";

  $graph=getGraph($sen[$sen_id][5], $id, $inter, $dateFrom, $dateTo, $db1);
  //print_r($graph);

   if($export)
   {

      $filename = $graph[1][3] ."_". date('Y-m-d',strtotime($dateFrom)) ."_". date('Y-m-d',strtotime($dateTo)) .".csv";

      header('Content-Description: File Transfer');
      header('Content-Type: application/csv');
      header('Content-Disposition: attachment; filename='.$filename);
      header('Content-Transfer-Encoding: binary');
      //header('Expires: 0');
      //header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      //header('Pragma: public');
      //header('Content-Length: ' . filesize($file));
      //ob_clean();
      //flush();
      foreach ($graph as $row) {
         echo date('Y/m/d H:i',$row[0]) . "," . $row[4] . "," . $row[5] . "\n";
      }
      exit();
   }

?>
