<?php
/** @noinspection DuplicatedCode */

use App\dto\IndexViewModel;
use App\util\Helper;
use App\view\partial\SensorCrudPartialCreateEdit;

/* @var IndexViewModel $model */

$dateFromType = $model->input->dateFromType;
$dateFrom = $model->input->dateFrom;
$dateTo = $model->input->dateTo;
$sensorCrud = $model->input->sensorCrud;
$selectOptionsRelativeDateFrom = $model->input->selectOptionsRelativeDateFrom;

$sensors = $model->sensors;
$lastReadingsView = $model->lastReadingsView;
$sensorReadingOutOfBounds = $model->sensorAlertsMinMax;
$sensorReadingsBySensorId = $model->sensorReadingsBySensorId;
$colors = $model->colors;
$errors = $model->errors;

?>

<!DOCTYPE HTML>
<html lang="en">
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
   <meta name="viewport" content="width=device-width, initial-width, initial-scale=1.0">
   <meta name="robots" content="index,nofollow"/>
   <meta name="keywords" content="Sensors"/>
   <meta name="description" content="Sensors"/>
   <base href="http://localhost/myApps/Tempsens/site/"/>
   <title>Sensor</title>

   <link rel="icon" href="static/gfx/favicon3.png" type="image/png"/>

   <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css"/>
   <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"/>
   <link rel="stylesheet" type="text/css" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css"/>

   <link rel="stylesheet" type="text/css" media="screen" href="static/css/reset.css"/>
   <link rel="stylesheet" type="text/css" media="screen" href="static/css/main-layout.css"/>

   <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
   <script type="text/javascript" src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
   <script type="text/javascript" src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
   <style>
      .dateFromOption {
         display: grid;
         align-items: center;
         grid-template-columns: 20px auto auto;
         height: var(--row_height);
         transition: all 300ms ease-in;
      }

      .graphOptions {
         display: grid;
         grid-template-columns: 4fr 3fr 2fr;
         margin-bottom: 1em;
      }

      .graphOptionsSelect {
         background: var(--color-green-ll-3);
         color: white;
      }

      .zebra-overview, .zebra-graph-settings {
         background: var(--light_grey_02);
      }

      .zebra-overview:nth-of-type(4n), .zebra-overview:nth-of-type(4n - 3), .zebra-graph-settings:nth-of-type(2n) {
         background: var(--light_grey_01);
      }

      /* #region radio */
      [type="radio"] {
         z-index: -1;
         position: absolute;
         opacity: 0;
      }

      [type="radio"]:checked ~ label {
         background-color: white;
      }

      [type="radio"]:not(:checked) ~ label {
         cursor: pointer;
      }

      label > .radio-dot {
         position: relative;
         display: inline-flex;
         width: 20px;
         height: 20px;
         border-radius: 20px;
         background: var(--light_grey_03);
      }

      [type="radio"]:checked ~ label > .radio-dot {
         background-color: #6FA773;
      }

      /* White ball inside checked radio */
      [type="radio"]:checked ~ label .radio-dot:after {
         content: "";
         position: absolute;
         top: 50%;
         left: 50%;
         transform: translate(-50%, -50%);
         width: 6px;
         height: 6px;
         border-radius: 10px;
         background-color: #fff;
      }

      /* #endregion radio */


   </style>
   <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

   <!-- date parsing -->
   <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/dayjs@1.10.4/dayjs.min.js"></script>
   <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/dayjs@1.10.4/plugin/customParseFormat.js"></script>

</head>
<body style="min-height: 100vh; min-width: 900px; margin:0; display: grid; grid-template-rows: 1fr auto">

<div>
   <div>
      <?php if (sizeof($errors) !== 0) { ?>
         <div class="alert alert-danger alert-dismissible fade show" style="margin-bottom: 2em" role="alert">
            <h4>Errors:</h4>
            <ol>
               <?php foreach ($errors as $error) { ?>
                  <li><?php echo $error; ?></li>
               <?php } ?>
            </ol>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span>
            </button>
         </div>
      <?php } ?>

      <div class="🎯">


         <div style="width: min(90%, 50em); display: grid; grid-template-columns: 5fr 2fr 5fr; margin-top: 2em;">
            <div>

               <div>
                  <input type="radio" onclick="handleRadioClick(this);" name="dateFromType" id="typeAbsolute"
                     <?php if ($dateFromType === 'absolute') {
                        echo "checked";
                     } ?>
                         value="absolute"/>
                  <label class="dateFromOption" for="typeAbsolute">
                     <span class="radio-dot"></span>
                     <span style="margin-left: 20px">From Date</span>
                     <span class="child-input 🎯" style="--xpos: end;">
                        <input name="absoluteDateFrom" type="text" id="absoluteDateFrom"
                               value="<?php echo $dateFrom; ?>" <?php echo $dateFromType !== 'absolute' ? 'disabled' : ''; ?> />
                     </span>
                  </label>
               </div>

               <div>
                  <input type="radio" onclick="handleRadioClick(this);" name="dateFromType" id="typeRelative"
                     <?php if ($dateFromType === 'relative') {
                        echo "checked";
                     } ?>
                         value="relative"/>
                  <label class="dateFromOption" for="typeRelative">
                     <span class="radio-dot"></span>
                     <span style="margin-left: 20px">From Relative</span>
                     <span class="child-input 🎯" style="--xpos: end;">
                        <select name="relativeDateFrom" id="relativeDateFrom" class="active"
                           <?php echo $dateFromType !== 'relative' ? 'disabled' : ''; ?>
                        >
                           <?php
                           foreach ($selectOptionsRelativeDateFrom as $item) {
                              echo $item;
                           }
                           ?>
                        </select>
                     </span>
                  </label>
               </div>
            </div>

            <div class="🎯" style="--xpos: center; --ypos: center">
               <button id="btnLoad" class="button">Load</button>
            </div>
            <div class="🎯" style="height: var(--row_height);">
               <label for="dateTo" class="🎯" style="--xpos: start;">To Date</label>
               <input type="text" name="dateTo" id="dateTo" value="<?php echo $dateTo; ?>"/>
            </div>


         </div>
      </div>
   </div>
   <div style="margin-top: 3em; margin-left: clamp(2%, 6%, 10%); margin-right: clamp(2%, 6%, 10%);">
      <div style="margin-bottom: 1em">

         <div>
               <span class="h2">
                  <span>Overview</span>
                  <span type="button" data-toggle="collapse" data-target="#collapseHelp" aria-expanded="false">
                     <i style="font-size: 0.55em" class="bi bi-question-circle"></i>
                  </span>
               </span>
         </div>

         <div class="collapse" id="collapseHelp">
            <div style="margin-bottom: 1em;">

               <p>
                  Click on sensor to:
               </p>
               <ul style="margin-bottom: 12px">
                  <li>Show alert info</li>
                  <li>Export data</li>
                  <li>Upload data (only for portable sensors)</li>
                  <li>Edit sensor fields (requires password, will not affect sensor's own configuration)</li>
               </ul>
               <p>
                  Exported and imported data is semicolon delimited CSV.
                  It is worth noting that Excel reads files using the computer regional settings and will
                  likely display semicolon delimited files incorrectly.
                  Excel uses delimiter from user PC
                  <code>Control Panel\Clock and Region -> Region -> Additional settings -> List separator</code>
                  (this should be changed from <code>,</code> to <code>;</code>). The exported data format
                  itself is correct.
               </p>

               <p>
                  For portable sensors reading data can be added to database by parsing CSV files.
                  Look for upload option under the specific portable sensor.
                  Order of columns can be changed.
                  Column separator is <code>;</code>.
                  Double separator <code>;;</code> skips in between column.
                  Any empty values and error values are skipped.
                  Allowed column values are: <code>date</code>, <code>temp</code>, <code>relHum</code>.
               </p>
               <p>
                  Sensors alarms will combine all continues alarms under one parent alarm.
                  Parent alarm will show starting time, duration, count of sub alarms and sub alarm deviation
                  (largest offset from average allowed value) of temperature and relative humidity.
                  Alarm duration is approximate depending on sensor reading interval.
               </p>
            </div>


            <div style="margin-bottom: 1em; background: var(--light_grey_02);">
               <div
                  style="display: grid; grid-auto-rows: auto; grid-template-columns: 30px 10em; width: min-content;"
                  class="collapse-and-change-icon" data-toggle="collapse" data-target="#collapseSensorCreate"
                  aria-expanded="false">
                  <div style="padding-left: 4px" type="button">
                     <i class="bi bi-caret-right"></i>
                  </div>
                  <div>Create new sensor</div>
               </div>
               <form action="" method="post" autocomplete="off"
                     style="padding: 0.4em 30px 0.4em 30px; margin-top: 1.2em" class="collapse"
                     id="collapseSensorCreate">
                  <div
                     style="display: grid; grid-template-columns: 1fr 1fr; grid-gap: 0.4em 3em; padding-bottom: 0.8em">
                     <?php echo SensorCrudPartialCreateEdit::GetHtml($sensorCrud->createBadValues->sensor ?? null) ?>
                  </div>
                  <div>
                     <span>Auth</span>
                     <input type="password" name="auth"
                            value="<?php echo htmlspecialchars($sensorCrud->createBadValues->auth ?? ''); ?>">
                     <button class="button" type="submit" name="formType" value="create">Create</button>
                  </div>
               </form>
            </div>

         </div>
      </div>

      <div class="wrapper zebra">
         <div
            style="--ypos: center;--xpos: start; height: 2em; display: grid; grid-auto-rows: auto; grid-template-columns: 30px 5fr 6fr 2fr 2fr 2fr 2fr 2fr 2fr 2fr;">
            <div class="🎯 table-head" style="padding-left: 4px">#</div>
            <div class="🎯 table-head">Sensor</div>
            <div class="🎯 table-head">Latest</i></div>
            <div class="🎯 table-head">Alerts</div>
            <div class="🎯 table-head"><i class="bi bi-thermometer-half">℃<br> avg</i></div>
            <div class="🎯 table-head"><i class="bi bi-thermometer-half">℃<br> min</i></div>
            <div class="🎯 table-head"><i class="bi bi-thermometer-half">℃<br> max</i></div>
            <div class="🎯 table-head"><i class="bi bi-droplet-half">%<br> avg</i></div>
            <div class="🎯 table-head"><i class="bi bi-droplet-half">%<br> min</i></div>
            <div class="🎯 table-head"><i class="bi bi-droplet-half">%<br> max</i></div>
         </div>
         <?php
         foreach ($sensors as $i => $sensor):?>

            <div class="🎯 collapse-and-change-icon zebra-overview" style="display: grid; grid-auto-rows: auto;
                           grid-template-columns: 30px 5fr 6fr 2fr 2fr 2fr 2fr 2fr 2fr 2fr;
                           --ypos: center; --xpos: start;" data-toggle="collapse"
                 data-target="<?php echo "#collapseSensorInfoIdx_" . $i ?>" aria-expanded="false">
               <span style="padding-left: 4px" type="button">
                  <i class="bi bi-caret-right"></i>
               </span>
               <?php
               $temps = array_map(fn($obj) => $obj->temp, $sensorReadingsBySensorId[$sensor->id]);
               $tempAvg = sizeof($temps) === 0 ? 'NULL' : number_format(array_sum($temps) / sizeof($temps), 1);
               $tempMax = sizeof($temps) === 0 ? 'NULL' : number_format(max($temps), 1);
               $tempMix = sizeof($temps) === 0 ? 'NULL' : number_format(min($temps), 1);

               $hums = array_map(fn($obj) => $obj->relHum, $sensorReadingsBySensorId[$sensor->id]);
               $humAvg = sizeof($hums) === 0 ? 'NULL' : number_format(array_sum($hums) / sizeof($hums), 1);
               $humMax = sizeof($hums) === 0 ? 'NULL' : number_format(max($hums), 1);
               $humMix = sizeof($hums) === 0 ? 'NULL' : number_format(min($hums), 1);
               ?>
               <div><?php echo htmlspecialchars($sensor->name) ?></div>
               <div>
                  <div
                     style="height: 1em; margin-bottom: 6px; overflow: hidden; font-size: 13px; color: <?php echo $lastReadingsView[$sensor->id]->color ?>">
                     <?php echo htmlspecialchars($lastReadingsView[$sensor->id]->dateRecorded); ?>
                  </div>
                  <div style="--xpos: center; --ypos: center; display: grid; grid-auto-rows: auto;
                        grid-template-columns: auto auto;">
                     <div>
                        <i class="bi bi-thermometer-half"></i><?php echo $lastReadingsView[$sensor->id]->temp; ?>
                     </div>
                     <div>
                        <i class="bi bi-droplet-half"></i><?php echo $lastReadingsView[$sensor->id]->relHum; ?>
                     </div>
                  </div>
               </div>
               <div><?php echo sizeof($sensorReadingOutOfBounds[$sensor->id]) ?></div>
               <div><?php echo $tempAvg ?></div>
               <div><?php echo $tempMix ?></div>
               <div><?php echo $tempMax ?></div>
               <div><?php echo $humAvg ?></div>
               <div><?php echo $humMix ?></div>
               <div><?php echo $humMax ?></div>
            </div>

            <div class="collapse zebra-overview" id="<?php echo "collapseSensorInfoIdx_" . $i ?>"
                 style="border-top: 1px dotted black; padding: 0.4em 20px">
               <nav>
                  <div class="nav nav-tabs">
                     <button class="nav-link active button" data-toggle="tab"
                             data-target="#nav-alerts-<?php echo $i ?>">Alerts
                     </button>
                     <button class="nav-link button" data-toggle="tab"
                             data-target="#nav-details-<?php echo $i ?>">Details
                     </button>
                     <button class="nav-link button" data-toggle="tab"
                             data-target="#nav-general-<?php echo $i ?>">General
                     </button>
                     <button class="nav-link button" data-toggle="tab"
                             data-target="#nav-export-<?php echo $i ?>">Export
                     </button>
                     <button class="nav-link button" data-toggle="tab"
                             data-target="#nav-import-<?php echo $i ?>">Import
                     </button>
                  </div>
               </nav>
               <div class="tab-content">

                  <!--  ALERTS  -->
                  <div class="tab-pane active" id="nav-alerts-<?php echo $i ?>" role="tabpanel">
                     <h3 class="h3">Alerts</h3>
                     <div style="margin-bottom: 1.4em">
                        <p>Number of alerts: <?php echo sizeof($sensorReadingOutOfBounds[$sensor->id]); ?></p>
                        <?php if (sizeof($sensorReadingOutOfBounds[$sensor->id]) !== 0) { ?>
                           <table style="background: var(--light_grey_03)">
                              <thead>
                              <tr>
                                 <th>#</th>
                                 <th>Timestamp</th>
                                 <th>Duration (min)</th>
                                 <th>Readings count</th>
                                 <th>Deviation Temp (℃)</th>
                                 <th>Deviation Hum (%)</th>
                              </tr>
                              </thead>
                              <tbody>
                              <?php
                              $arr = $sensorReadingOutOfBounds[$sensor->id];
                              foreach ($arr as $j => $item) { ?>
                                 <tr>
                                    <td><?php echo $j + 1 ?></td>
                                    <td><?php echo $item->beforeDate ?></td>
                                    <td><?php echo $item->duration ?></td>
                                    <td><?php echo $item->count ?></td>
                                    <td><?php echo $item->temp ?></td>
                                    <td><?php echo $item->hum ?></td>
                                 </tr>
                              <?php } ?>
                              </tbody>
                           </table>
                        <?php } ?>
                     </div>

                  </div>

                  <!--  DETAILS  -->
                  <div class="tab-pane" id="nav-details-<?php echo $i ?>" role="tabpanel">
                     <h3 class="h3">Details</h3>
                     <form action="" method="post">
                        <div
                           style="display: grid; grid-template-columns: 1fr 1fr; grid-gap: 0.4em 3em; padding-bottom: 0.8em">
                           <?php echo SensorCrudPartialCreateEdit::GetHtml($sensor) ?>
                        </div>
                        <div style="padding-top: 0.4em">
                           <div>
                              <span>Auth</span>
                              <input type="password" name="auth">
                              <button class="button" type="submit" name="formType" value="edit">Save</button>
                              <button class="button button-danger" type="submit" name="formType"
                                      value="delete">Delete
                              </button>
                           </div>
                        </div>
                     </form>
                  </div>

                  <!--  GENERAL  -->
                  <div class="tab-pane" id="nav-general-<?php echo $i ?>" role="tabpanel">
                     <h3 class="h3">General</h3>
                     <div style="margin-bottom: 0.4em">
                        <span>Sensor interface:</span>
                        <a href="<?php echo htmlspecialchars($sensor->ip) ?>"
                           target="_blank"><?php echo htmlspecialchars($sensor->ip) ?></a>
                     </div>
                  </div>

                  <!--  EXPORT -->
                  <div class="tab-pane" id="nav-export-<?php echo $i ?>" role="tabpanel">
                     <h3 class="h3">Export</h3>
                     <button style="margin-bottom: 0.4em" class="js-export-btn button"
                             data-filename='<?php echo htmlspecialchars($sensor->name . '_' . $dateFrom . '_' . $dateTo . '.csv'); ?>'
                             data-sensorId='<?php echo $sensor->id; ?>'>Export
                     </button>
                  </div>

                  <!--  IMPORT  -->
                  <div class="tab-pane" id="nav-import-<?php echo $i ?>" role="tabpanel">
                     <h3 class="h3">Import</h3>
                     <div style="margin-bottom: 0.8em">
                        <?php if ($sensor->isPortable) { ?>

                           <div>
                              <div>
                                 <span>Upload column order</span>
                                 <input type="text"
                                        id="csvParseExpressionSensorId_<?php echo $sensor->id; ?>"
                                        value="date;temp;relHum">
                              </div>
                              <div>
                                 <button type="button" class="button csvFile"
                                         data-sensorId="<?php echo $sensor->id; ?>">Upload
                                 </button>
                              </div>
                           </div>
                        <?php } else { ?>
                           <p>Sensor is not portable! Cannot import!</p>
                        <?php } ?>
                     </div>
                  </div>


               </div>
            </div>
         <?php endforeach; ?>
      </div>

      <div style="margin-top: 2em;" class="h2">Graph</div>
      <div class="h4">Settings</div>
      <div style="display: grid; grid-template-columns: 6fr 6fr">

         <div>
            <table style="min-width: 320px; width: 75%">
               <thead>
               <tr>
                  <th>Name</th>
                  <th>Color</th>
                  <th><i class="bi bi-thermometer-half"></i></th>
                  <th><i class="bi bi-droplet-half"></i></th>
               </tr>
               </thead>
               <tbody id="sensorDrawingSettings">
               <?php
               foreach ($sensors as $i => $sensor):
                  ?>
                  <tr class="zebra-graph-settings">
                     <td style="display: none" class="sensorId"><?php echo $sensor->id ?></td>
                     <td class="sensorName"><?php echo htmlspecialchars($sensor->name) ?></td>
                     <td style="display: flex">
                        <input class="colorSelectTemp" type="color" style="width: 100%; padding: 0"
                               value="<?php echo $colors[$i]; ?>">
                        <input class="colorSelectHum" type="color" style="width: 100%; padding: 0"
                               value="<?php echo $colors[$i + sizeof($sensors)]; ?>">
                     </td>
                     <td><input class="tempSelect" type="checkbox"></td>
                     <td><input class="relHumSelect" type="checkbox"></td>
                  </tr>
               <?php endforeach; ?>
               </tbody>
            </table>
         </div>

         <div style="display: flex; flex-direction:column; justify-content: space-between;">
            <div>
               <div class="graphOptions">
                  <p>Interval</p>
                  <select id="graphOptionsIntervalSelect" class="graphOptionsSelect">
                     <option value="15" selected>15 min</option>
                     <option value="30">30 min</option>
                     <option value="120">2 hour</option>
                     <option value="360">6 hour</option>
                     <option value="1440">1 day</option>
                  </select>
               </div>
               <div class="graphOptions">
                  <p>Interval Strategy</p>
                  <select id="graphOptionsStrategySelect" class="graphOptionsSelect">
                     <option value="median">Take Median</option>
                     <option value="average">Take Average</option>
                     <option value="deviation">Take Deviation</option>
                  </select>
               </div>
               <div class="graphOptions">
                  <p>No Value As 0</p>
                  <div class="🎯" style="--xpos: end">
                     <input id="graphOptionsLoudNoValue" type="checkbox">
                  </div>
               </div>
            </div>

            <div style="display: flex; justify-content: end;">
               <button id="ChartDrawButton" style="width: 9em" class="button">Draw</button>
            </div>
         </div>

      </div>
      <div style="margin-top: 2em;" class="h4">Result</div>
      <div style="height: 540px">
         <p id="chartErr">No sensor selected</p>
         <div id="chartDiv" style="display: none">
            <button id="saveImgBtn" type="button" class="button">Save Image</button>
            <div id="chart" style="width: 100%; height: 500px;"></div>
            <div style="display: none" id="chartAsPictureDiv"></div>
         </div>
      </div>
   </div>
</div>
<footer class="footer">
   Javascript failed!
</footer>
<!-- Modal -->
<div class="modal fade" id="JsonModal" tabindex="-1" role="dialog">
   <div class="modal-dialog" role="document">
      <div class="modal-content modal-dialog" role="document">
         <div class="modal-header" style="display: block">
            <h5 class="modal-title">Confirm data?</h5>
            <span id="modalMsg"></span>
            <button style="position: absolute; top: 16px; right: 16px;" type="button" class="close"
                    data-dismiss="modal" aria-label="Close">
               <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body" style="height: 40em; overflow-y: auto">
               <pre id="csvDataDisplay"></pre>
         </div>

         <div style="padding: .75rem; border-top: 1px solid #dee2e6;">
            <label for="import-form-password-input">Auth</label>
            <input id="import-form-password-input" type="password">
            <button id="import-form-submit-btn" class="button">Submit</button>
         </div>
      </div>
   </div>
</div>
<!-- end Modal -->

</body>

<script type="text/javascript">

   dayjs.extend(window.dayjs_plugin_customParseFormat);


   function TriggerPortableSensorDataCsvUpload(csvFileUploadBtn) {
      let id = csvFileUploadBtn.getAttribute('data-sensorId');
      let reader = new FileReader();
      reader.onload = function (event) {
         let csv = event.target.result;
         let delimiter = ';';
         let headers = document.getElementById('csvParseExpressionSensorId_' + id).value.split(delimiter);
         let arr, skipCount;
         try {
            [arr, skipCount] = csvToJsonArray(csv, delimiter, headers);
         } catch (e) {
            console.error(e);
            return false;
         }
         document.getElementById('modalMsg').innerText = `Total data rows count: ${arr.length + skipCount}, skipped ${skipCount}`;
         document.getElementById('csvDataDisplay').innerText = JSON.stringify(arr, null, 2);
         document.getElementById('csvDataDisplay').style.display = 'block';
         document.querySelector('#import-form-submit-btn').onclick = () => {
            let pass = document.getElementById('import-form-password-input').value;
            // prevents browser remember password prompt.
            // How does it know that the input is connected with the post request ???
            document.getElementById('import-form-password-input').remove();
            post("", {
               "jsonData": JSON.stringify(arr),
               "csvSensorId": id,
               "csvAuth": pass
               }, "post"
            );
         }
         $('#JsonModal').modal('show');
      }
      let input = document.createElement('input');
      input.type = 'file';
      input.accept = '.csv';


      input.addEventListener("change", () => {
         reader.readAsText(input.files[0]);
      })
      input.click();
   }


   function LoadButtonEvent() {
      let dateTo = document.getElementById('dateTo').value;

      let type = document.querySelector('input[name="dateFromType"]:checked').value;
      let dateFrom = "";
      switch (type) {
         case 'absolute':
            dateFrom = document.getElementById('absoluteDateFrom').value;
            break;
         case 'relative':
            dateFrom = '-' + document.getElementById('relativeDateFrom').value;
            break;
         default:
            console.error('Unknown type:', type);
            break;
      }
      post("", {"From": dateFrom, "To": dateTo}, "get");
   }


   /**
    * sends a request to the specified url from a form. this will change the window location.
    * Taken from Rakesh Pai, https://stackoverflow.com/questions/133925/javascript-post-request-like-a-form-submit?rq=1
    * @param {string} path the path to send the post request to
    * @param {object} params the parameters to add to the url
    * @param {string} [method=post] the method to use on the form
    */
   function post(path, params, method = 'post') {
      const form = document.createElement('form');
      form.method = method;
      form.action = path;

      for (const key in params) {
         const hiddenField = document.createElement('input');
         hiddenField.type = 'hidden';
         hiddenField.name = key;
         hiddenField.value = params[key];

         form.appendChild(hiddenField);
      }
      document.body.appendChild(form)
      form.submit();
   }

   function getEOL() {
      let aPlatform = navigator.platform.toLowerCase();
      if (aPlatform.indexOf('win') !== -1) return '\r\n'
      if (aPlatform.indexOf('mac') !== -1) return '\r'
      return '\n'
   }

   function csvToJsonArray(csv, delimiter, headers) {
      let allowedHeaders = ['date', 'temp', 'relHum'];
      if (!allowedHeaders.every(x => headers.includes(x))) {
         throw `Missing field, fields [${allowedHeaders.join(',')}] must be defined in [${headers.join(',')}]!`;
      }
      let eol = getEOL();
      // skip 1. row (the header)
      let rows = csv.slice(csv.indexOf(eol) + eol.length).split(eol);
      if (rows.length === 0) {
         throw 'No rows!';
      }
      rows.pop(); // remove newline

      let result = [];
      let skipCount = 0;
      let nRowNumber = 0;
      for (let row of rows) {
         nRowNumber++;
         let obj = {};
         let values = row.split(delimiter);
         if (values.length !== headers.length) {
            throw `Row: ${nRowNumber}. Header and data column count mismatch!`;
         }
         if (values.length < 3) {
            throw `Row: ${nRowNumber}. Row has less than 3 columns. Row value: ${row} parsed as [${values.join(',')}]!`;
         }
         for (let i = 0; i < headers.length; i++) {
            let header = headers[i];
            let value = values[i];

            if (header === '') {
               continue;
            }

            if (value.slice(0, 5) === 'Error' || value === '') {
               skipCount++;
               break;
            }
            switch (header) {
               case 'date':
                  // format from sensor
                  let date = dayjs(value, 'DD/MM/YYYY HH:mm:ss');
                  if (!date.isValid()) {
                     // format from page export
                     date = dayjs(value, 'DD/MM/YYYY HH:mm');
                  }
                  if (!date.isValid()) {
                     throw `Row: ${nRowNumber}, Invalid date format! Must be DD/MM/YYYY HH:mm:ss or DD/MM/YYYY HH:mm!`
                  }
                  obj[header] = date.format('DD-MM-YYYY HH:mm')
                  break;
               case 'relHum':
               case 'temp':
                  let fValue = parseFloat(value);
                  if (isNaN(fValue)) throw `Row: ${nRowNumber}. Cannot parse number ${value}`;
                  obj[header] = parseFloat(fValue.toFixed(1));
                  break;
               default:
                  throw 'Unexpected header ' + header + '!';
            }
            if (i === headers.length - 1) {
               result.push(obj);
            }
         }
      }
      return [result, skipCount];
   }

   function drawChart(indexModel, dateFrom, dateTo) {
      let chartEle = document.getElementById("chart");
      let data = new google.visualization.DataTable();

      // let t0 = performance.now();

      let step = parseInt(document.getElementById('graphOptionsIntervalSelect').value);
      let strategy = document.getElementById('graphOptionsStrategySelect').value;
      // undefined value is ignored in graph
      let graphDefaultValue = document.getElementById('graphOptionsLoudNoValue').checked ? 0 : undefined;

      let sensors = indexModel.GetSensorsWithSettings()
      if (true) {
         // Google charts can handle it built in so this is actually unnecessary, but it does look nicer
         let chartDiv = document.getElementById('chartDiv');
         let chartErr = document.getElementById('chartErr');
         if (sensors.every(x => !x.sensorSettings.isTemp && !x.sensorSettings.isRelHum)) {
            chartDiv.style.display = 'none';
            chartErr.innerText = 'No sensor selected';
            return;
         }
         chartDiv.style.display = 'block';
         chartErr.innerText = '';
      }

      /* Example of xAxisTimesToSensors.
         [
            { date; [{temp, relHum}, {temp, relHum} {temp, relHum}] },
            { 9:15; [{24.1, 15}, {24.5, 15.7} {24.8, 16.7}] },
            { 9:30; [{21.6, 14.3}, {22, 15.1} {22.4, 15.7}] }
         ]
       */
      let xAxisTimesToSensors = [];
      for (let i = 0; ; i++) {
         let nextDate = dateFrom.add(step * i, 'minutes');
         if (nextDate >= dateTo) break;
         xAxisTimesToSensors.push({
            'date': nextDate,
            'data': []
         });
      }

      // Make sure temp operations always come before relHum operations.
      // They are done multiple times in different places.
      // Perhaps this design can be improved.
      for (let sensor of sensors) {
         let tempRangeAvg = (sensor.maxTemp + sensor.minTemp) / 2;
         let humRangeAvg = (sensor.maxRelHum + sensor.minRelHum) / 2;

         let low = 0;
         for (let i = 0; i < xAxisTimesToSensors.length - 1; i++) {
            let sensorDataBetweenDates = {
               'temp': null,
               'relHum': null,
            };
            if (!sensor.sensorSettings.isTemp && !sensor.sensorSettings.isRelHum) {
               // This is an optimization.
               // This value should never be used, but it makes 2d array creation easier as the data is accessed by index.
               xAxisTimesToSensors[i].data.push(sensorDataBetweenDates);
               continue;
            }

            let before = xAxisTimesToSensors[i].date;
            let after = xAxisTimesToSensors[i + 1].date;

            let tmp = FilterSortedArrayValuesBetweenDates(sensor.sensorReadings, before, after, low);
            let currentRows = tmp.result;
            low = tmp.low;

            let doStrategy = function (strategy, arr, graphDefaultValue, sensorRangeAvg) {
               let getMedianValue = function (arr, graphDefaultValue) {
                  let valOrUndefined = arr[Math.floor(arr.length / 2)];
                  return valOrUndefined === undefined ? graphDefaultValue : valOrUndefined;
               }
               let getAverageValue = function (arr, graphDefaultValue) {
                  return arr.length === 0 ? graphDefaultValue : arr.reduce((a, b) => a + b) / arr.length;
               }
               let getDeviationValue = function (arr, graphDefaultValue, sensorRangeAvg) {
                  return arr.length === 0 ? graphDefaultValue :
                     arr.sort(
                        (a, b) => Math.abs(a - sensorRangeAvg) - Math.abs(b - sensorRangeAvg)
                     )[arr.length - 1]
               }
               switch (strategy) {
                  case 'median':
                     return getMedianValue(arr, graphDefaultValue);
                  case 'average':
                     return getAverageValue(arr, graphDefaultValue);
                  case 'deviation':
                     return getDeviationValue(arr, graphDefaultValue, sensorRangeAvg);
                  default:
                     console.error('Unknown value!');
                     return;
               }
            }

            if (sensor.sensorSettings.isTemp) {
               sensorDataBetweenDates.temp = doStrategy(strategy, currentRows.map(x => x.temp), graphDefaultValue, tempRangeAvg);
            }
            if (sensor.sensorSettings.isRelHum) {
               sensorDataBetweenDates.relHum = doStrategy(strategy, currentRows.map(x => x.relHum), graphDefaultValue, humRangeAvg);
            }

            xAxisTimesToSensors[i].data.push(sensorDataBetweenDates);
         }
      }
      // We needed one extra date for data manipulation. The last data is empty
      xAxisTimesToSensors.pop();

      // add header columns
      data.addColumn('string', 'date');
      for (let sensor of sensors) {
         if (sensor.sensorSettings.isTemp) {
            data.addColumn('number', sensor.name + ' temperature (℃)');
            data.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
         }
         if (sensor.sensorSettings.isRelHum) {
            data.addColumn('number', sensor.name + ' relative humidity (%)');
            data.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
         }
      }

      // add data columns
      let rows = [];
      for (let xAxisTime of xAxisTimesToSensors) {
         let row = [];
         row.push(xAxisTime.date.add(step / 2, 'minutes').format('DD.MM'))
         for (let y = 0; y < sensors.length; y++) {
            let sensor = sensors[y];
            const addTooltipFunc = function (valueAndSymbol) {
               return `<div style="height: 50px; width: 14em">
										<p><b>${xAxisTime.date.format('HH:mm, DD.MM.YYYY')}</b></p>
               					<p style="margin-top: 6px">${encodeURI(sensor.name)}: <b>${valueAndSymbol}</b></p>
               				</div>`;
            };
            let cell = xAxisTime.data[y];
            if (sensor.sensorSettings.isTemp) {
               row.push(cell.temp);
               row.push(addTooltipFunc(cell.temp + '℃'));
            }
            if (sensor.sensorSettings.isRelHum) {
               row.push(cell.relHum);
               row.push(addTooltipFunc(cell.relHum + '%'));
            }
         }
         rows.push(row);
      }
      data.addRows(rows);

      let colors = [];
      for (let sensor of sensors) {
         if (sensor.sensorSettings.isTemp) {
            colors.push(sensor.sensorSettings.colorTemp);
         }
         if (sensor.sensorSettings.isRelHum) {
            colors.push(sensor.sensorSettings.colorRelHum)
         }
      }

      let options = {
         title: dateFrom.format('DD-MM-YYYY HH:mm') + ' - ' + dateTo.format('DD-MM-YYYY HH:mm'),
         fontSize: 13,
         vAxis: {
            // is it possible to add a callback? I only found ticks, but they mess automatic zooming
            // ticks: [{v:16, f:'banana'}, {v:18, f:'apple'}, {v:20, f:'mango'}, {v:22, f:'orange'}, {v:24, f:'apricot'}]
            title: 'Temperature (℃) and Relative Humidity (%)',
         },
         tooltip: {isHtml: true},
         colors: colors,
         legend: {position: 'right'},
      };
      let chart = new google.visualization.LineChart(chartEle);

      google.visualization.events.addListener(chart, 'ready', function () {
         document.getElementById('chartAsPictureDiv').innerHTML = `<img id="chartAsPictureImg" src="${chart.getImageURI()}">`;
      })

      chart.draw(data, options);
   }

   function SaveChartImg(dateFrom, dateTo) {
      let base64 = document.getElementById('chartAsPictureImg').src;
      let fileName = 'tempsens ' + dateFrom + '-' + dateTo;
      ExportBase(base64, fileName)
   }

   function ExportBase(encodedUri, filename) {
      let link = document.createElement("a");
      // document.body.appendChild(link); // This might be needed in some browsers, currently not needed.
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", filename)
      link.click();
   }

   function FilterSortedArrayValuesBetweenDates(sensorReadings, before, after, low) {
      // this would be more readable, but much slower duo to searching the entire array
      // sensorReadings.filter(x => before <= x.date && after > x.date);

      let result = [];
      while (low < sensorReadings.length) {
         let obj = sensorReadings[low];
         if (after <= obj.date) {
            break;
         }
         if (before <= obj.date) {
            result.push(obj);
         }
         low++;
      }
      return {
         'result': result,
         'low': low
      };
   }

   function GetDrawingSettings(_sensorId) {
      let table = document.getElementById('sensorDrawingSettings');
      for (let row of table.children) {
         let sensorId = row.querySelector('.sensorId').innerHTML;
         if (sensorId !== _sensorId) continue;
         let colorTemp = row.querySelector('.colorSelectTemp').value;
         let colorRelHum = row.querySelector('.colorSelectHum').value;
         let isTemp = row.querySelector('.tempSelect').checked;
         let isRelHum = row.querySelector('.relHumSelect').checked;

         return new SensorSettings(colorTemp, colorRelHum, isTemp, isRelHum);
      }
      return null;
   }

   class SensorSettings {
      constructor(colorTemp, colorRelHum, isTemp, isRelHum) {
         this.colorTemp = colorTemp;
         this.colorRelHum = colorRelHum;
         this.isTemp = isTemp;
         this.isRelHum = isRelHum;
      }
   }

   class Sensor {
      constructor(id, name, sensorReadings, maxTemp, minTemp, maxRelHum, minRelHum, sensorSettings) {
         this.id = id;
         this.name = name;
         this.sensorReadings = sensorReadings;
         this.maxTemp = maxTemp;
         this.minTemp = minTemp;
         this.maxRelHum = maxRelHum;
         this.minRelHum = minRelHum;
         this.sensorSettings = sensorSettings;
      }
   }

   class SensorReading {
      constructor(date, temp, relHum) {
         this.date = date;
         this.temp = temp;
         this.relHum = relHum;
      }
   }


   function handleRadioClick(dateFromType) {
      const absoluteDateFromInput = document.getElementById("absoluteDateFrom");
      const relativeDateFromInput = document.getElementById("relativeDateFrom");

      const relative = "relative";
      const absolute = "absolute";
      if (!dateFromType.value in [relative, absolute]) {
         console.error("Unexpected value: " + dateFromType.value)
         return;
      }
      if (absoluteDateFromInput.disabled && dateFromType.value !== absolute ||
         relativeDateFromInput.disabled && dateFromType.value !== relative) {
         return;
      }
      absoluteDateFromInput.disabled = dateFromType.value !== absolute;
      relativeDateFromInput.disabled = dateFromType.value !== relative;
   }

   function HandleCollapseSymbolChange() {
      document.querySelectorAll('.collapse-and-change-icon').forEach(x => x.onclick = () => {
         let targetElement = document.querySelector(x.getAttribute("data-target"))
         if (targetElement.classList.contains("collapsing")) {
            // prevents the icon from going out of sync from the collapse state by multiple rapid clicks
            return;
         }
         // $(x.find('i')).toggleClass('bi bi-caret-down bi bi-caret-right');
         x.querySelector('i').classList.toggle('bi-caret-down');
         x.querySelector('i').classList.toggle('bi-caret-right');
      });
   }


   function ExportCSV(filename, sensorReadings) {
      let data = [];
      data.push(['Timestamp', 'Temperature (*C)', 'Relative Humidity (%)'])
      for (let sensorReading of sensorReadings) {
         let row = [sensorReading.date.format("DD/MM/YYYY HH:mm"), sensorReading.temp, sensorReading.relHum];
         data.push(row)
      }
      let eol = getEOL();
      let csvContent = "data:text/csv;charset=utf-8," + data.map(e => e.join(";")).join(eol);
      let encodedUri = encodeURI(csvContent);

      ExportBase(encodedUri, filename)
   }


   class IndexModel {
      dateTo = dayjs('<?php echo $dateTo . ', 23:59' ?>', 'DD-MM-YYYY, HH:mm');
      dateFrom = dayjs('<?php echo $dateFrom . ', 00:00' ?>', 'DD-MM-YYYY, HH:mm');
      sensorReadingsMap = [];
      sensorsWithoutSettings = [];

      constructor() {
         let sensorReadingsById = JSON.parse('<?php echo Helper::EchoJson($sensorReadingsBySensorId); ?>');

         let sensorReadingsMap = [];
         for (let [id, sensorReadings] of Object.entries(sensorReadingsById)) {
            let newSensorReadings = [];
            for (let sensorReading of sensorReadings) {
               newSensorReadings.push(new SensorReading(
                  dayjs(sensorReading.date, 'DD/MM/YYYY HH:mm'),
                  parseFloat(sensorReading.temp),
                  parseFloat(sensorReading.relHum)));
            }
            sensorReadingsMap.push(
               {
                  key: id,
                  value: newSensorReadings
               });
         }
         let sensorsOld = JSON.parse('<?php echo Helper::EchoJson($sensors); ?>');
         let sensors = [];
         for (let sensor of sensorsOld) {
            sensors.push(new Sensor(
               sensor.id,
               sensor.name,
               sensorReadingsMap.find(x => x.key === sensor.id).value,
               sensor.maxTemp,
               sensor.minTemp,
               sensor.maxRelHum,
               sensor.minRelHum,
               null
            ));
         }
         this.sensorsWithoutSettings = sensors;
         this.sensorReadingsMap = sensorReadingsMap;
      }

      GetSensorsWithSettings() {
         let sensors = [];
         for (let sensor of this.sensorsWithoutSettings) {
            sensors.push(new Sensor(
               sensor.id,
               sensor.name,
               this.sensorReadingsMap.find(x => x.key === sensor.id).value,
               sensor.maxTemp,
               sensor.minTemp,
               sensor.maxRelHum,
               sensor.maxRelHum,
               GetDrawingSettings(sensor.id)
            ));
         }
         return sensors;
      }
   }

   async function main() {
      let indexModel = new IndexModel();
      fetch("view/partial/FooterPartial.html").then(x => x.text().then(res => document.querySelector(".footer").innerHTML = res));
      document.querySelector('#saveImgBtn').onclick = () => SaveChartImg(
         indexModel.dateFrom.format("DD-MM-YYYY"),
         indexModel.dateTo.format("DD-MM-YYYY")
      );
      document.querySelectorAll('.csvFile').forEach(x => x.onclick = () => TriggerPortableSensorDataCsvUpload(x));
      document.querySelectorAll('.js-export-btn').forEach(x => x.onclick = () =>
         ExportCSV(
            x.getAttribute("data-filename"),
            indexModel.sensorReadingsMap.find(y => y.key === x.getAttribute("data-sensorId")).value
         )
      );
      document.querySelector('#btnLoad').onclick = LoadButtonEvent;
      // we need dates to be in this format for GET request
      ['#dateTo', '#absoluteDateFrom'].forEach(item => $(item).datepicker({dateFormat: "dd-mm-yy"}));
      document.querySelector('#ChartDrawButton').onclick = () => {
         google.charts.load("current", {'packages': ["corechart", "line"]});
         google.charts.setOnLoadCallback(() => drawChart(
            indexModel,
            indexModel.dateFrom,
            indexModel.dateTo
         ));
      };
      HandleCollapseSymbolChange()
   }

   main();

</script>
</html>
