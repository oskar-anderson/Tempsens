[back to index](../README.md)

# API
Documentation for API endpoints.

## physical-sensor
Official info about the T3510 model sensor from Comet System website and sensor manual:
* [Official site](https://www.cometsystem.com/products/web-sensor-remote-thermometer-hygrometer-with-ethernet-interface/reg-t3510)
* [Official manual](./web-sensor-tx5xx-docs-v28.pdf)

Official info about the M1140 portable model data logger sensor from Comet System website and sensor manual:
* [Official site](https://www.cometsystem.com/products/data-loggers/reg-m1140)

## physical-sensor
Physical sensor API used in Tempsens logserver.

### Insert
Data transfer between the physical sensors and logserver web app using SOAP XML.

Sensor insert API requests can be tested locally using an API testing tool like Postman. Send a `POST` request to URL `http://localhost:80/api/physical-sensor/insert-reading` with request body:

XML request body:
```xml
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
   xmlns:xsd="http://www.w3.org/2001/XMLSchema">
   <soap:Body>
      <InsertTx5xxSample xmlns="http://cometsystem.cz/schemas/soapTx5xx_v2.xsd">
         <passKey>20960050</passKey>
         <device>4145</device>
         <temp>1.4</temp>
         <relHum>91.9</relHum>
         <compQuant>0.3</compQuant>
         <pressure>-9999</pressure>
         <alarms>hi,no,no,no</alarms>
         <compType>Dew point</compType>
         <tempU>C</tempU>
         <pressureU>n/a</pressureU>
         <timer>60</timer>
      </InsertTx5xxSample>
   </soap:Body>
</soap:Envelope>
```

Description:
```
|----------------|-------------------------------------------------------------|
| Tag            | Description                                                 |
|----------------|-------------------------------------------------------------|
| <passKey>      | Device serial number (an eight digit number)                |
|----------------|-------------------------------------------------------------|
| <device>       | Device model identification number:                         |
|                |--------------|----------------------------------------------|
|                | Number       | Device                                       |
|                |--------------|----------------------------------------------|
|                | 4107         | T3511                                        |
|                | 4106         | T4511                                        |
|                | 4129         | T7511                                        |
|                | 4124         | T2514                                        |
|                | 4144         | T0510                                        |
|                | 4145         | T3510                                        |
|                | 4146         | T7510                                        |
|                | 4173         | T0610                                        |
|                | 4174         | T4611                                        |
|                | 4175         | T3610                                        |
|                | 4176         | T3611                                        |
|                | 4177         | T7610                                        |
|                | 4178         | T7611                                        |
|                | 4195         | T7613D                                       |
|----------------|--------------|----------------------------------------------|
| <temp>         | Value of temperature (as decimal place separator is used    |
|                | dot sign). Error signalised by number 9999 or -9999.        |
|----------------|-------------------------------------------------------------|
| <relHum>       | Relative humidity. Error value 9999 or -9999.               |
|----------------|-------------------------------------------------------------|
| <compQuant>    | Computed value/quantity. Error value 9999 or -9999.         |
|----------------|-------------------------------------------------------------|
| <pressure>     | Atmospheric pressure. Error value -9999.                    |
|----------------|-------------------------------------------------------------|
| <alarms>       | State of alarms at measurement channels 1-4. Values:        |
|                | no - alarm is not active                                    |
|                | lo - low alarm is active                                    |
|                | hi - high alarm is active                                   |
|                | Example: no,hi,no,no - high alarm on relative humidity      |
|----------------|-------------------------------------------------------------|
| <compType>     | Computed value/quantity. Values: Absolute humidity,         |
|                | Specific humidity, Mixing proportion, Specific enthalpy,    |
|                | Dew point, n/a                                              |
|----------------|-------------------------------------------------------------|
| <tempU>        | Temperature and Dew point unit. Values: C, F, n/a           |
|----------------|-------------------------------------------------------------|
| <pressureU>    | Atmospheric pressure unit. Values: hPa, PSI, inHg, mBar,    |
|                | oz/in\^2, mmHg, inH2O, kPa, n/a                             |
|----------------|-------------------------------------------------------------|
| <timer>        | SOAP sending interval in [sec].                             |
|----------------|-------------------------------------------------------------|
```


