import dayjs from "dayjs";
import SensorReading from "./SensorReading";
import Sensor from "./Sensor";

class IndexModel {
   dateTo = dayjs(document.querySelector('#dataDateTo').innerHTML);
   dateFrom = dayjs(document.querySelector('#dataDateFrom').innerHTML);
   sensorReadingsMap = [];
   sensors = [];

   constructor() {
      let sensorReadingsById = JSON.parse(document.querySelector('#dataSensorReadingsBySensorId').innerHTML);

      let sensorReadingsMap = [];
      for (let [id, sensorReadings] of Object.entries(sensorReadingsById)) {
         let newSensorReadings = [];
         for (let sensorReading of sensorReadings) {
            newSensorReadings.push(new SensorReading(
               dayjs(sensorReading.date),
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