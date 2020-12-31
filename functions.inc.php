<?php

   function Config()
   {
      global $configData;
      if (! isset($configData)) {
         $configData = require ("config.php");
      }
      return $configData;
   }

  function getParms($db1)
  {
    $parms=array();
    $qry = "SELECT `label`, `value` FROM `parms` WHERE '1' ORDER BY `id`;";
    // echo $qry;
    if (!$res = $db1->query($qry)) {
       echo "Parameters query error!<br />\n";
    }
    else while($values = $res->fetch()) $parms[$values['label']]=$values['value'];

    return $parms;
  }

  function getSensors($db1)
  {
    $qry = "SELECT * FROM `sensors` WHERE `active`='1'; ";
    //echo $qry;
    $sen = array();
    if (! $res = $db1->query($qry)) {
        echo "Sensors query error!\n";
    } else {
        $i=0;
        while ($values = $res->fetch()) {
            $i++;
            $sen[$i][0]=$values["id"];
            $sen[$i][1]=$values["name"];
            $sen[$i][2]=$values["serial"];
            $sen[$i][3]=$values["ip"];
            $sen[$i][4]=$values["desc"];
            $sen[$i][5]=$values["portable"];
            $sen[$i][6]=$i;
        }
    }
    return $sen;
  }

  function getGraph($portable, $id, $inter, $dateFrom, $dateTo, $db1) {
     if($portable) $table="portable"; else $table="webtemp";
     $qry = "SELECT * from `$table` as a1, `sensors` as a2
                WHERE a1.`passKey`=a2.`serial` AND a2.`id` = '$id'
                   AND a1.`id` mod '$inter'*15 = '0'
                   AND a1.`dactdate` >= DATE_FORMAT(STR_TO_DATE('$dateFrom','%d-%m-%Y'),'%Y%m%d')
                   AND a1.`dactdate` <= DATE_FORMAT(STR_TO_DATE('$dateTo','%d-%m-%Y'),'%Y%m%d')
             ;";
     //echo $qry;
     $graph = array();

     if (! $res = $db1->query($qry)) {
       echo "Query error!\n";
     } else {
        $i=0;
        while($values = $res->fetch()) {
           $i++;
           $graph[$i][0] = strtotime($values['dactdate']);
           $graph[$i][1] = $values['dactdate'];
           $graph[$i][2] = date('Ymdhi',$graph[$i][0]);
           $graph[$i][3] = $values['name'];
           $graph[$i][4] = $values['temp'];
           $graph[$i][5] = $values['relHum'];
        }
     }
  return $graph;
  }

  function getGlobalAlert($portable, $scope, $db1) {
    global $parms;

    if($portable) $table="portable"; else $table="webtemp";
    $qry = "SELECT a1.`dactdate`, a1.`temp`, a2.`name` from `$table` AS a1
            INNER JOIN `sensors` AS a2 ON a1.`passKey`=a2.`serial`
            WHERE a1.`passKey`=a2.`serial` AND `temp` <= '". $parms['alert_mintemp'] . "' OR `temp` >= '" . $parms['alert_maxtemp'] ."' ";
    if($scope!='all') $qry .= "AND a2.`id`='$scope' ";
    $qry .= "ORDER BY a1.`id` DESC LIMIT 6";

    $disp2=$qry;
    $disp="";

    if (! $res = $db1->query($qry)) {
      $disp = "Alert query error!<br />\n";
    } else {
      while ($values = $res->fetch()) {
        $t = strtotime($values['dactdate']);
        $name = $values['name'];
        $disp .= "      " . $values['temp'] . "°&nbsp;&nbsp;&nbsp;@" . date('d-m-Y  H:i',$t) . "&nbsp;&nbsp;&nbsp;Sensor:&nbsp;$name<br />\n";
      }
    }
  return $disp;
  }

  function getTop($sel, $portable, $scope, $db1) {
    if($portable) $table="portable"; else $table="webtemp";
    switch ($sel)
    {
      case 'min':
        $qry = "SELECT MIN(`temp`) AS gettop FROM `$table` AS a1
            INNER JOIN `sensors` AS a2 ON a1.`passKey`=a2.`serial`
            WHERE a1.`passKey`=a2.`serial`";
        if($scope!='all') $qry .= "AND a2.`id`='$scope' ";
        $qry.=";";
        break;
      case 'max':
        $qry = "SELECT MAX(`temp`) AS gettop FROM `$table` AS a1
            INNER JOIN `sensors` AS a2 ON a1.`passKey`=a2.`serial`
            WHERE a1.`passKey`=a2.`serial`";
        if($scope!='all') $qry .= "AND a2.`id`='$scope' ";
        $qry.=";";
        break;
      case 'avg':
        $qry = "SELECT ROUND(AVG(`temp`),1) AS gettop FROM `$table` AS a1
            INNER JOIN `sensors` AS a2 ON a1.`passKey`=a2.`serial`
            WHERE a1.`passKey`=a2.`serial`";
        if($scope!='all') $qry .= "AND a2.`id`='$scope' ";
        $qry.=";";
        break;
      default:
        $qry = 0;
        break;
    }
    $disp2=$qry;
    $disp="";

    if($qry) {
      if (! $res = $db1->query($qry)) {
        $disp = "Query error!<br />\n";
      }
      else {
        while ($values = $res->fetch()) {
          $top = $values['gettop'];
          $disp .= $top . "ºC<br />\n";
        }
      }
    }
    return $disp;
  }

?>

