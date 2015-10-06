<?php
# REST API EXPERIMENTAL 
# use only for publicly available date !
# Matthias Bannert
# maps time series to csv, xlsx and json

# debugging stuff
//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(-1);

require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

# initialize the Slim framework
$app = new \Slim\Slim();

#########################
## CSV ROUTE SINGLE TS ##
#########################
$app->get('/timeseriesdb/csv/:tskey', function ($tskey) {
    
     $publicDbCon = dbConnect();
     $json_obj = getSingleTs($publicDbCon,$tskey);
     $json_decoded = json_decode($json_obj,true);

     # extract the array that contains the key value pairs... 
     $ts_key = $json_decoded['ts_key'];
     $ts_data_arr = $json_decoded['ts_data'];
     # get keys and values to generate a 
     # mini-arrays that can be used by fputcsv directly... 
     $keys = array_keys($ts_data_arr);
     $values = array_values($ts_data_arr);

     # create a data array to hold the mini arrays
     # mini arrays = rows
     $data = array();
     $length = count($keys);
     for ($i = 0; $i < $length; $i++) {
     $data[$i] = array($keys[$i],$values[$i]);
     }

     // send response headers to the browser
     header( 'Content-Type: text/csv' );
     header( 'Content-Disposition: attachment;filename=out.csv');
     $fp = fopen('php://output', 'w');

     foreach ($data as $row) {
        fputcsv($fp, $row);
     }

     fclose($fp);

     // print_r($data);

     pg_close($publicDbCon);

});


#########################
## XLSX ROUTE SINGLE TS #
#########################
$app->get('/timeseriesdb/xlsx/:tskey', function ($tskey) {
     # thanks https://github.com/mk-j/PHP_XLSXWriter/
     include_once("xlsxwriter/xlsxwriter.class.php");

     $publicDbCon = dbConnect();
     $json_obj = getSingleTs($publicDbCon,$tskey);
     $json_decoded = json_decode($json_obj,true);

     # extract the array that contains the key value pairs... 
     $ts_key = $json_decoded['ts_key'];
     $ts_data_arr = $json_decoded['ts_data'];
     # get keys and values to generate a 
     # mini-arrays that can be used by fputcsv directly... 
     $keys = array_keys($ts_data_arr);
     $values = array_values($ts_data_arr);

     # create a data array to hold the mini arrays
     # mini arrays = rows
     $data = array();
     $length = count($keys);
     for ($i = 0; $i < $length; $i++) {
     $data[$i] = array($keys[$i],floatval($values[$i]));
     }

     $filename = $tskey.".xlsx";
     header('Content-disposition: attachment; filename="'.XLSXWriter::sanitize_filename($filename).'"');
     header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
     header('Content-Transfer-Encoding: binary');
     header('Cache-Control: must-revalidate');
     header('Pragma: public');
 
     $header = array(
      'date'=>'string',
      'value'=>'string'
      );    

     $writer = new XLSXWriter();
     $writer->setAuthor('timeseriesdb API');
     $writer->writeSheet($data,'Sheet1',$header);
     $writer->writeToStdOut();

     exit(0);

     // print_r($data);

     pg_close($publicDbCon);

});


$app->run();


# DB connection function including db strings
function dbConnect($dbhost = "",
                   $dbport = "",
                   $dbname = "",
                   $dbuser = "",
                   $dbpass = ""){

     $connection_string = "host=$dbhost port=$dbport dbname=$dbname user=$dbuser password=$dbpass";
     $dbcon = pg_connect($connection_string);

     return($dbcon);

}


# get a single time series from db and 
# return it as a PHP StdClass Object
# the Object contains A JSON Object 
# will information including
# ts_key, ts_data and ts_frequency
function getSingleTs($conObj,$param){
     $str = pg_escape_string($param);
     $query = "SELECT ts_json_records FROM pblc_timeseries.v_timeseries_json WHERE ts_key = '{$str}'";

     $result = pg_query($conObj, $query);
     $series = pg_fetch_object($result);
     $json_obj = $series -> ts_json_records;

     return($json_obj);
}


?>