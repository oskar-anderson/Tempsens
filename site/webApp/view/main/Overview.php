<?php
/** @noinspection DuplicatedCode */

declare(strict_types=1);

use App\dtoWeb\IndexViewModel;
use App\util\Helper;
use App\webApp\view\partial\SensorCrudPartialCreateEdit;

/* @var IndexViewModel $model */

$dateFromType = $model->input->dateFromType;
$dateFrom = $model->input->dateFrom;
$dateTo = $model->input->dateTo;
$periods = $model->periods;

$sensors = $model->sensors;
$sensorReadingOutOfBounds = $model->sensorAlertsMinMax;
$sensorReadingsBySensorId = $model->sensorReadingsBySensorId;

?>
<!--suppress HtmlUnknownTarget -->
<!DOCTYPE HTML>
<html lang="en">
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <meta name="robots" content="index,nofollow"/>
   <meta name="keywords" content="Sensors"/>
   <meta name="description" content="Sensors"/>

   <title>Sensor</title>
   <!-- IDE might complain about links not resolving, but they are resolved relative to the viewController dir not the view dir -->
   <link rel="icon" href="../webApp/static/gfx/favicon3.png" type="image/png"/>

   <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css"/>
   <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"/>
   <link rel="stylesheet" type="text/css" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css"/>

   <link rel="stylesheet" type="text/css" media="screen" href="../webApp/static/css/reset.css"/>
   <link rel="stylesheet" type="text/css" media="screen" href="../webApp/static/css/main-layout.css"/>

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

      .zebra-overview, .zebra-graph-settings {
         background: var(--light_grey_02);
      }

      .zebra-overview:nth-of-type(4n), .zebra-overview:nth-of-type(4n - 3), .zebra-graph-settings:nth-of-type(2n) {
         background: var(--light_grey_01);
      }

   </style>
   <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

   <!-- date parsing -->
   <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/dayjs@1.10.4/dayjs.min.js"></script>
   <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/dayjs@1.10.4/plugin/customParseFormat.js"></script>

</head>
<body style="min-height: 100vh; min-width: 900px;">
<div style="display: grid; grid-template-rows: 1fr auto;">

<div>
   <div>
      <div class="error-alert"></div>

      <div class="🎯">


         <div style="width: min(90%, 50em); display: grid; grid-template-columns: 5fr 2fr 5fr; margin-top: 2em;">
            <div>

               <div>
                  <label class="dateFromOption" for="typeAbsolute">
                     <input type="radio" name="dateFromType" id="typeAbsolute"
                        <?= $dateFromType === 'absolute' ? 'checked' : '' ?>
                            value="absolute"/>
                     <span style="margin-left: 20px">From Date</span>
                     <span class="child-input 🎯" style="--xpos: end;">
                        <input name="absoluteDateFrom" type="text" id="absoluteDateFrom"
                               value="<?= $dateFrom ?>" <?= $dateFromType !== 'absolute' ? 'disabled' : ''; ?> />
                     </span>
                  </label>
               </div>

               <div>
                  <label class="dateFromOption" for="typeRelative">
                     <input type="radio" name="dateFromType" id="typeRelative"
                        <?= $dateFromType === 'relative' ? "checked" : ''?>
                            value="relative"/>
                     <span style="margin-left: 20px">From Relative</span>
                     <span class="child-input 🎯" style="--xpos: end;">
                        <select name="relativeDateFrom" id="relativeDateFrom" class="active"
                           <?= $dateFromType !== 'relative' ? 'disabled' : ''; ?>
                        >
                           <?php foreach ($periods as $item) { ?>
                              <option value='<?= $item->value ?>' <?= $item->isSelected ? "selected" : '' ?>>-<?= $item->name ?></option>
                           <?php } ?>

                        </select>
                     </span>
                  </label>
               </div>
            </div>

            <div class="🎯" style="--xpos: center; --ypos: center">
               <button id="btnLoad" class="button">Load</button>
            </div>
            <div class="🎯" style="height: var(--row_height);">
               <label for="dateTo" style="width: 100%;">To Date</label>
               <input type="text" name="dateTo" id="dateTo" value="<?= $dateTo; ?>"/>
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
                  Portable sensor reading data can be added to database by parsing CSV files.
                  Order of parsed CSV file columns can be changed.
                  Column separator is <code>;</code>.
                  Double separator <code>;;</code> skips in between column.
                  Any empty values and error values are skipped.
                  Allowed column values are: <code>date</code>, <code>temp</code>, <code>relHum</code>.
               </p>
               <p>
                  Sensor alarms will combine all continues alarms under one parent alarm.
                  Parent alarm will show starting time, duration and alarm deviation
                  (the largest offset from average allowed value) of temperature and relative humidity.
                  Alarm duration is approximate - accuracy depends on sensor reading interval.
               </p>
            </div>


            <div style="margin-bottom: 1em; background: var(--light_grey_02);">
               <div style="display: grid; grid-auto-rows: auto; grid-template-columns: 30px 10em; width: min-content;" class="collapse-and-change-icon" data-toggle="collapse" data-target="#collapseSensorCreate" aria-expanded="false">
                  <div style="padding-left: 4px" type="button">
                     <i class="bi bi-caret-right"></i>
                  </div>
                  <div>Create new sensor</div>
               </div>
               <form action="" autocomplete="off" style="padding: 0.4em 30px 0.4em 30px; margin-top: 1.2em" class="collapse" id="collapseSensorCreate">
                  <div style="display: grid; grid-template-columns: 1fr 1fr; grid-gap: 0.4em 3em; padding-bottom: 0.8em">
                     <?= SensorCrudPartialCreateEdit::GetHtml(null) ?>
                  </div>
                  <div>
                     <span>Auth</span>
                     <input type="password" name="auth" value="">
                     <button id="collapseSensorCreateSubmitBtn" class="button" type="button">Create</button>
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
         foreach ($sensors as $i => $sensor):
            $lastReading = $sensor->sensorReading;
            $sensor = $sensor->sensor;
            ?>


            <div class="🎯 collapse-and-change-icon zebra-overview" style="display: grid; grid-auto-rows: auto;
                           grid-template-columns: 30px 5fr 6fr 2fr 2fr 2fr 2fr 2fr 2fr 2fr;
                           --ypos: center; --xpos: start;" data-toggle="collapse"
                 data-target="<?= "#collapseSensorInfoIdx_" . $i ?>" aria-expanded="false">
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
               <div><?= htmlspecialchars($sensor->name) ?></div>
               <?php

               $dateRecorded = 'NO DATA';
               $col = 'red';
               if ($lastReading !== null) {
                  $lastDate = $lastReading->dateRecorded->format('d/m/Y H:i');
                  $minutesDiff = floor((
                     Helper::GetUtcNow()->getTimestamp() -
                     $lastReading->dateRecorded->getTimestamp()
                  ) / 60);
                  if ($sensor->readingIntervalMinutes - $minutesDiff < 0 && !$sensor->isPortable) {
                     $dateRecorded = 'DOWN @' . $lastDate;
                     $col = 'red';
                  }
                  if ($sensor->readingIntervalMinutes - $minutesDiff >= 0 && !$sensor->isPortable) {
                     $dateRecorded = 'UP @' . $lastDate;
                     $col = 'black';
                  }
                  if ($sensor->isPortable) {
                     $dateRecorded = 'Portable sensor';
                     $col = 'black';
                  }
               }
               $temp = $lastReading !== null ? $lastReading->temp . '℃' : 'NO DATA';
               $relHum = $lastReading !== null ? $lastReading->relHum . '%' : 'NO DATA';

               ?>
               <div>
                  <div
                     style="height: 1em; margin-bottom: 6px; overflow: hidden; font-size: 13px; color: <?php echo $col ?>">
                     <?= htmlspecialchars($dateRecorded); ?>
                  </div>
                  <div style="--xpos: center; --ypos: center; display: grid; grid-auto-rows: auto;
                        grid-template-columns: auto auto;">
                     <div>
                        <i class="bi bi-thermometer-half"></i><?= $temp; ?>
                     </div>
                     <div>
                        <i class="bi bi-droplet-half"></i><?= $relHum; ?>
                     </div>
                  </div>
               </div>
               <div>
                  <?= sizeof($sensorReadingOutOfBounds[$sensor->id]) ?>
               </div>
               <div><?= $tempAvg ?></div>
               <div><?= $tempMix ?></div>
               <div><?= $tempMax ?></div>
               <div><?= $humAvg ?></div>
               <div><?= $humMix ?></div>
               <div><?= $humMax ?></div>
            </div>

            <div class="collapse zebra-overview" id="<?= "collapseSensorInfoIdx_" . $i ?>"
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
                           <table>
                              <thead>
                              <tr>
                                 <th>Timestamp</th>
                                 <th>Duration (min)</th>
                                 <th>Content</th>
                              </tr>
                              </thead>
                              <tbody>
                              <?php
                              $arr = $sensorReadingOutOfBounds[$sensor->id];
                              foreach ($arr as $j => $item) { ?>
                                 <tr style="border-bottom: 1px solid #394960;">
                                    <td><?php echo $item->beforeDate->format("d/m/Y H:i") ?></td>
                                    <td title="Readings count: <?php echo $item->count ?>"><?php echo $item->duration ?></td>
                                    <td>
                                       <?php echo $item->content ?>
                                    </td>
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
                        <div style="display: grid; grid-template-columns: 1fr 1fr; grid-gap: 0.4em 3em; padding-bottom: 0.8em">
                           <?php echo SensorCrudPartialCreateEdit::GetHtml($sensor) ?>
                        </div>
                        <div style="padding-top: 0.4em">
                           <div>
                              <span>Auth</span>
                              <input type="password" name="auth">
                              <button class="button sensorCrudActionSaveBtn" type="button">Save</button>
                              <button class="button sensorCrudActionDeleteBtn button-danger" type="button">Delete</button>
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
                  $sensor = $sensor->sensor;
                  ?>
                  <tr class="zebra-graph-settings">
                     <td style="display: none" class="sensorId"><?php echo $sensor->id ?></td>
                     <td class="sensorName"><?php echo htmlspecialchars($sensor->name) ?></td>
                     <td style="display: flex">
                        <input class="colorSelectTemp" type="color" style="width: 100%; padding: 0"
                               value="#000000">
                        <input class="colorSelectHum" type="color" style="width: 100%; padding: 0"
                               value="#000000">
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
                  <select id="graphOptionsIntervalSelect">
                     <option value="15" selected>15 min</option>
                     <option value="30">30 min</option>
                     <option value="120">2 hour</option>
                     <option value="360">6 hour</option>
                     <option value="1440">1 day</option>
                  </select>
               </div>
               <div class="graphOptions">
                  <p>Interval Strategy</p>
                  <select id="graphOptionsStrategySelect">
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
<div style="display: none">
   <div id="dataSensor"><?php echo json_encode($sensors); ?></div>
   <div id="dataSensorReadingsBySensorId"><?php echo json_encode($sensorReadingsBySensorId); ?></div>
   <div id="dataDateTo"><?php echo $dateTo . ', 23:59:59' ?></div>
   <div id="dataDateFrom"><?php echo $dateFrom . ', 00:00:00' ?></div>
</div>
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

</div>
</body>

<script type="module">

   dayjs.extend(window.dayjs_plugin_customParseFormat);
   let baseApi = window.location.protocol + '//' + window.location.host + "/api"

   function handleSensorCreate() {
      let submitBtn = document.querySelector('#collapseSensorCreateSubmitBtn');
      submitBtn.addEventListener('click', async (e) => {
         e.preventDefault();
         let form = submitBtn.closest('form');
         if (! form.reportValidity()) {
            return;
         }
         let formData = getFormSensor(form);
         console.log("Sending a post request: ", baseApi + "/v1/sensor/create")
         let response = await fetch(baseApi + "/v1/sensor/create", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json'},
            body: JSON.stringify(formData)
         })
         let msg = await response.text();
         console.log(`${msg}`);
         if (response.ok) {
            location.reload();
         } else {
            displayError(msg);
         }
      })
   }

   function handleSensorUpdate() {
      let submitBtns = document.querySelectorAll('.sensorCrudActionSaveBtn');
      for (let submitBtn of submitBtns) {
         submitBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            let form = submitBtn.closest('form');
            if (! form.reportValidity()) {
               return;
            }
            let formData = getFormSensor(form);
            console.log("Sending a post request: ", baseApi + "/v1/sensor/update")
            let response = await fetch(baseApi + "/v1/sensor/update", {
               method: 'POST',
               headers: { 'Content-Type': 'application/json'},
               body: JSON.stringify(formData)
            })
            let msg = await response.text();
            console.log(`${msg}`);
            if (response.ok) {
               location.reload();
            } else {
               displayError(msg);
            }
         })
      }
   }

   function getFormSensor(form) {
      let id = form.querySelector('[name="id"]').value;
      let name = form.querySelector('[name="name"]').value;
      let serial = form.querySelector('[name="serial"]').value;
      let model = form.querySelector('[name="model"]').value;
      let ip = form.querySelector('[name="ip"]').value;
      let location = form.querySelector('[name="location"]').value;
      let isPortable = form.querySelector('[name="isPortable"]').value === 'Y';
      let minTemp = form.querySelector('[name="minTemp"]').value;
      let maxTemp = form.querySelector('[name="maxTemp"]').value;
      let minRelHum = form.querySelector('[name="minRelHum"]').value;
      let maxRelHum = form.querySelector('[name="maxRelHum"]').value;
      let readingIntervalMinutes = form.querySelector('[name="readingIntervalMinutes"]').value;
      let auth = form.querySelector('[name="auth"]').value;
      return {
         auth: auth,
         sensor: {
            id: id,
            name: name,
            serial: serial,
            model: model,
            ip: ip,
            location: location,
            isPortable: isPortable,
            minTemp: minTemp,
            maxTemp: maxTemp,
            minRelHum: minRelHum,
            maxRelHum: maxRelHum,
            readingIntervalMinutes: readingIntervalMinutes
         }
      };
   }

   function handleSensorDelete() {
      let submitBtns = document.querySelectorAll('.sensorCrudActionDeleteBtn');
      for (let submitBtn of submitBtns) {
         submitBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            let form = submitBtn.closest('form');
            if (! form.reportValidity()) {
               return;
            }
            let id = form.querySelector('input[name="id"]').value;
            let auth = form.querySelector('input[name="auth"]').value;
            let formData = {
               id: id,
               auth: auth
            };
            console.log("Sending a post request: ", baseApi + "/v1/sensor/delete")
            let response = await fetch(baseApi + "/v1/sensor/delete", {
               method: 'POST',
               headers: { 'Content-Type': 'application/json'},
               body: JSON.stringify(formData)
            })
            let msg = await response.text();
            console.log(`${msg}`);
            if (response.ok) {
               location.reload();
            } else {
               displayError(msg);
            }
         })
      }
   }


   function triggerPortableSensorDataCsvUpload(csvFileUploadBtn) {
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
            displayError(e);
            return false;
         }
         document.getElementById('modalMsg').innerText = `CSV file contained ${arr.length + skipCount} rows, skipped ${skipCount} rows.`;
         document.getElementById('csvDataDisplay').innerText = JSON.stringify(arr, null, 2);
         document.getElementById('csvDataDisplay').style.display = 'block';
         document.querySelector('#import-form-submit-btn').onclick = async () => {
            let pass = document.getElementById('import-form-password-input').value;
            console.log("Sending a post request: ", baseApi + "/v1/sensor-reading/upload")
            let response = await fetch(baseApi + "/v1/sensor-reading/upload", {
               method: 'POST',
               headers: { 'Content-Type': 'application/json'},
               body: JSON.stringify({
                  sensorReadings: arr,
                  sensorId: id,
                  auth: pass
               })
            })
            let msg = await response.text();
            console.log(`${msg}`);
            if (response.ok) {
               location.reload();
            } else {
               displayError(msg);
               $('#JsonModal').modal('hide');
            }
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


   function loadButtonEvent() {
      let dateTo = document.getElementById('dateTo').value;
      let type = document.querySelector('input[name="dateFromType"]:checked').value;
      if (! ['absolute', 'relative'].includes(type)) throw `Unknown type: ${type}`;
      let dateFrom = type === 'absolute' ? document.getElementById('absoluteDateFrom').value : '-' + document.getElementById('relativeDateFrom').value;
      window.location.href = window.location.protocol + '//' + window.location.host + `/overview?From=${dateFrom}&To=${dateTo}`;
   }


   function csvToJsonArray(csv, delimiter, headers) {
      let allowedHeaders = ['date', 'temp', 'relHum'];
      if (!allowedHeaders.every(x => headers.includes(x))) {
         throw `Missing field, fields [${allowedHeaders.join(',')}] must be defined in [${headers.join(',')}]!`;
      }
      let rows = csv.split('\n');
      if (rows.length <= 1) {
         throw 'No rows!';
      }
      rows.shift(); // remove title row
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
                  let date = dayjs(value, 'DD/MM/YYYY HH:mm:ss');
                  if (!date.isValid()) {
                     throw `Row: ${nRowNumber}, Invalid date format! Must be DD/MM/YYYY HH:mm:ss!`
                  }
                  obj[header] = date.format('DD-MM-YYYY HH:mm:ss')
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

      let sensors = indexModel.sensors.filter(x => {
         let settings = getDrawingSettings(x.id);
         return settings.isTemp || settings.isRelHum
      })

      let chartDiv = document.getElementById('chartDiv');
      let chartErr = document.getElementById('chartErr');
      if (sensors.length === 0) {
         chartDiv.style.display = 'none';
         chartErr.innerText = 'No sensor selected';
         return;
      }
      chartDiv.style.display = 'block';
      chartErr.innerText = '';

      const getBuckets = (dateFrom, dateTo) => {
         let buckets = [];
         let bucketStartDate = dateFrom;  // doing this saves 50ms on large graphs opposed to doing dateFrom.add(step * (i - 1), 'minutes');
         for (let i = 1; ; i++) {
            let bucketNextDate = dateFrom.add(step * i, 'minutes');
            if (bucketNextDate >= dateTo) break;
            buckets.push({
               startDateTime: bucketStartDate,
               endDateTime: bucketNextDate,
               row: []  // will match graphLines length
            });
            bucketStartDate = bucketNextDate;
         }
         return buckets;
      }
      let buckets = getBuckets(dateFrom, dateTo);

      for (let sensor of sensors) {
         let sensorReadings = indexModel.sensorReadingsMap.find(x => x.key === sensor.id).value;
         let tempRangeAvg = (sensor.maxTemp + sensor.minTemp) / 2;
         let humRangeAvg = (sensor.maxRelHum + sensor.minRelHum) / 2;

         let drawingSettings = getDrawingSettings(sensor.id);
         if (!drawingSettings.isRelHum && !drawingSettings.isTemp) continue;
         let low = 0;
         for (let bucket of buckets) {
            let doStrategy = function (strategy, arr, graphDefaultValue, sensorRangeAvg) {
               let getMedianValue = function (arr, graphDefaultValue) {
                  let valOrUndefined = arr[Math.floor(arr.length / 2)];
                  return valOrUndefined === undefined ? graphDefaultValue : valOrUndefined;
               }
               let getAverageValue = function (arr, graphDefaultValue) {
                  if (arr.length === 0) return graphDefaultValue;
                  let average = arr.reduce((a, b) => a + b) / arr.length;
                  // rounded to 1 decimal point
                  return Math.round(average * 10) / 10;
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
                     throw "Unknown value!";
               }
            }

            let tmp = filterSortedArrayValuesBetweenDates(sensorReadings, bucket.startDateTime, bucket.endDateTime, low);
            let currentRows = tmp.result;
            low = tmp.low;


            if (drawingSettings.isTemp) {
               let tempValue = doStrategy(strategy, currentRows.map(x => x.temp), graphDefaultValue, tempRangeAvg);
               bucket.row.push(tempValue);
            }
            if (drawingSettings.isRelHum) {
               let relHumValue = doStrategy(strategy, currentRows.map(x => x.relHum), graphDefaultValue, humRangeAvg);
               bucket.row.push(relHumValue);
            }
         }
      }

      let graphLines = [];
      for (let sensor of sensors) {
         let drawingSettings = getDrawingSettings(sensor.id);
         if (buckets.length === 0) break;
         if (drawingSettings.isTemp) { // order of temp and humidity data insertion must be same as bucket insertion order
            graphLines.push({
               sensorName: encodeURI(sensor.name),
               unit: "℃",
               graphLineColor: drawingSettings.colorTemp,
               label: `${sensor.name} temperature (℃)`
            });
         }
         if (drawingSettings.isRelHum) {
            graphLines.push({
               sensorName: encodeURI(sensor.name),
               unit: "%",
               graphLineColor: drawingSettings.colorRelHum,
               label: `${sensor.name} relative humidity (%)`
            });
         }
      }

      // add header columns
      data.addColumn('datetime');  // This column is for the x-axis
      for (let lineData of graphLines) {
         data.addColumn('number', lineData.label);  // This column is the data, label is used in legend for each line
         data.addColumn({ type: 'string', role: 'tooltip', p: { html: true } });  // This column is for overriding HTML tooltips for hovering cursor over line
      }

      // add data columns
      let rows = [];
      for (let bucket of buckets) {
         let row = [];
         row.push(bucket.startDateTime.add(step / 2, 'minutes').toDate())
         for (let i = 0; i < graphLines.length; i++) {
            row.push(bucket.row[i]);
            row.push(
               `<div class="p-2">
                  <p>
                     <b>${bucket.startDateTime.format('HH:mm, DD-MM-YYYY')}</b>
                  </p>
                  <p style="white-space: nowrap;" class="mt-3">
                     <i style="color: ${graphLines[i].graphLineColor}" class="bi bi-circle-fill"></i>
                     ${graphLines[i].sensorName}:
                     <b>${bucket.row[i]} ${graphLines[i].unit}</b>
                  </p>
               </div>`
            );
         }
         rows.push(row);
      }
      data.addRows(rows);

      let colors = graphLines.map(graphLine => graphLine.graphLineColor);

      let options = {
         title: `Sirowa sensor measurements`,
         fontSize: 13,
         vAxis: {
            title: 'Temperature (℃) and Relative Humidity (%)',
         },
         hAxis: {
            title: `Datetime (${dateFrom.format('DD-MM-YYYY, HH:mm')} - ${dateTo.format('DD-MM-YYYY, HH:mm')})`,
            minorGridlines: { // will hide non month vertical gridlines
               color: 'transparent'
            },
            format: "dd. MMM"
         },
         tooltip: {isHtml: true},
         colors: colors,  // hexadecimal color values for every line in order
         legend: {position: 'right'},
      };
      let chart = new google.visualization.LineChart(chartEle);

      google.visualization.events.addListener(chart, 'ready', function () {
         document.getElementById('chartAsPictureDiv').innerHTML = `<img id="chartAsPictureImg" src="${chart.getImageURI()}" alt="chart">`;
      })

      chart.draw(data, options);
   }

   function saveChartImg(dateFrom, dateTo) {
      let base64 = document.getElementById('chartAsPictureImg').src;
      let fileName = 'tempsens ' + dateFrom + '-' + dateTo;
      exportBase(base64, fileName)
   }

   function exportBase(encodedUri, filename) {
      let link = document.createElement("a");
      // document.body.appendChild(link); // This might be needed in some browsers, currently not needed.
      link.setAttribute("href", encodedUri);
      link.setAttribute("download", filename)
      link.click();
   }

   function filterSortedArrayValuesBetweenDates(sensorReadings, before, after, low) {
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
         result: result,
         low: low
      };
   }

   function getDrawingSettings(_sensorId) {
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
      constructor(id, name, maxTemp, minTemp, maxRelHum, minRelHum) {
         this.id = id;
         this.name = name;
         this.maxTemp = maxTemp;
         this.minTemp = minTemp;
         this.maxRelHum = maxRelHum;
         this.minRelHum = minRelHum;
      }
   }

   class SensorReading {
      constructor(date, temp, relHum) {
         this.date = date;
         this.temp = temp;
         this.relHum = relHum;
      }
   }


   function handleRadioClick(e) {
      let dateFromType = e.target;
      const absoluteDateFromInput = document.getElementById("absoluteDateFrom");
      const relativeDateFromInput = document.getElementById("relativeDateFrom");

      const relative = "relative";
      const absolute = "absolute";
      if (! [relative, absolute].includes(dateFromType.value)) {
         displayError("Unexpected value: " + dateFromType.value);
         return;
      }
      if (absoluteDateFromInput.disabled && dateFromType.value !== absolute ||
         relativeDateFromInput.disabled && dateFromType.value !== relative) {
         return;
      }
      absoluteDateFromInput.disabled = dateFromType.value !== absolute;
      relativeDateFromInput.disabled = dateFromType.value !== relative;
   }

   function handleCollapseSymbolChange() {
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
         let row = [sensorReading.date.format("DD/MM/YYYY HH:mm:ss"), sensorReading.temp, sensorReading.relHum];
         data.push(row)
      }
      let csvContent = "data:text/csv;charset=utf-8," + data.map(e => e.join(";")).join('\n');
      let encodedUri = encodeURI(csvContent);

      exportBase(encodedUri, filename)
   }

   Math.seed = function(s) {
      // https://stackoverflow.com/questions/521295/seeding-the-random-number-generator-in-javascript
      let mask = 0xffffffff;
      let m_w  = (123456789 + s) & mask;
      let m_z  = (987654321 - s) & mask;

      return function() {
         m_z = (36969 * (m_z & 65535) + (m_z >>> 16)) & mask;
         m_w = (18000 * (m_w & 65535) + (m_w >>> 16)) & mask;

         let result = ((m_z << 16) + (m_w & 65535)) >>> 0;
         result /= 4294967296;
         return result;
      }
   }

   function randomHexColor(getRandom) {
      // https://stackoverflow.com/questions/1484506/random-color-generator
      return '#' + (getRandom().toString(16) + "000000").substring(2,8);
   }


   class IndexModel {
      dateTo = dayjs(document.querySelector('#dataDateTo').innerHTML, 'DD-MM-YYYY, HH:mm:ss');
      dateFrom = dayjs(document.querySelector('#dataDateFrom').innerHTML, 'DD-MM-YYYY, HH:mm:ss');
      sensorReadingsMap = [];
      sensors = [];

      constructor() {
         let sensorReadingsById = JSON.parse(document.querySelector('#dataSensorReadingsBySensorId').innerHTML);

         let sensorReadingsMap = [];
         for (let [id, sensorReadings] of Object.entries(sensorReadingsById)) {
            let newSensorReadings = [];
            for (let sensorReading of sensorReadings) {
               newSensorReadings.push(new SensorReading(
                  dayjs(sensorReading.date, 'YYYYMMDDHHmmss'),
                  parseFloat(sensorReading.temp),
                  parseFloat(sensorReading.relHum)));
            }
            sensorReadingsMap.push(
               {
                  key: id,
                  value: newSensorReadings
               });
         }
         let sensorsOld = JSON.parse(document.querySelector('#dataSensor').innerHTML);
         let sensors = [];
         for (let sensorWithLastReading of sensorsOld) {
            let sensor = sensorWithLastReading.sensor;
            sensors.push(new Sensor(
               sensor.id,
               sensor.name,
               sensor.maxTemp,
               sensor.minTemp,
               sensor.maxRelHum,
               sensor.minRelHum
            ));
         }
         this.sensors = sensors;
         this.sensorReadingsMap = sensorReadingsMap;
      }
   }

   function displayError(error) {
      document.querySelector('.error-alert').innerHTML = `
         <div class="alert alert-danger alert-dismissible fade show m-2" role="alert">
            <h4>Error: </h4>
            <p>${error}</p>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span>
            </button>
         </div>
         `;
      window.scrollTo(0, 0);
   }

   async function main() {
      let indexModel = new IndexModel();
      let footerResponse = await fetch("../webApp/view/partial/FooterPartial.html");
      document.querySelector(".footer").innerHTML = await footerResponse.text()
      document.querySelector('#typeAbsolute').onclick = (e) => handleRadioClick(e);
      document.querySelector('#typeRelative').onclick = (e) => handleRadioClick(e);
      document.querySelector('#saveImgBtn').onclick = () => saveChartImg(
         indexModel.dateFrom.format("DD-MM-YYYY"),
         indexModel.dateTo.format("DD-MM-YYYY")
      );
      let getRandom = Math.seed(9);
      document.querySelectorAll('.colorSelectTemp').forEach(x => x.value = randomHexColor(getRandom));
      document.querySelectorAll('.colorSelectHum').forEach(x => x.value = randomHexColor(getRandom));
      document.querySelectorAll('.csvFile').forEach(x => x.onclick = () => triggerPortableSensorDataCsvUpload(x));
      document.querySelectorAll('.js-export-btn').forEach(x => x.onclick = () =>
         ExportCSV(
            x.getAttribute("data-filename"),
            indexModel.sensorReadingsMap.find(y => y.key === x.getAttribute("data-sensorId")).value
         )
      );
      handleSensorCreate();
      handleSensorUpdate();
      handleSensorDelete();
      document.querySelector('#btnLoad').onclick = loadButtonEvent;
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
      handleCollapseSymbolChange()
   }

   await main();

</script>
</html>
