<?php
# REST API EXPERIMENTAL 
# use only for publicly available date !
# Matthias Bannert
# maps time series to csv, xlsx and json

# debugging stuff
//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(-1);


date_default_timezone_set('America/New_York');

require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

# initialize the Slim framework
$app = new \Slim\Slim();


#########################
## JSON ROUTE SINGLE TS #
#########################
$app->get('/timeseriesdb/json/:tskey', function ($tskey) {

     $publicDbCon = dbConnect();
     $json_obj = getSingleTs($publicDbCon,$tskey);

     header('Content-Type: application/json');
     echo json_encode($json_obj, JSON_PRETTY_PRINT);


     pg_close($publicDbCon);

});





#########################
## CSV ROUTE SINGLE TS ##
#########################
$app->get('/timeseriesdb/csv/:tskey', function ($tskey) {
    
     $publicDbCon = dbConnect();
     $json_obj = getSingleTs($publicDbCon,$tskey);
     $json_decoded = json_decode($json_obj,true);

     # extract the array that contains the key value pairs... 
     $ts_key = $json_decoded['ts_key'];
     $ts_frequency = $json_decoded['ts_frequency'];
     $ts_data_arr = $json_decoded['ts_data'];

     $filename = $tskey.".csv";     

     // send response headers to the browser
     header( 'Content-Type: text/csv' );
     header( "Content-Disposition: attachment;filename='".$filename."'");
     $fp = fopen('php://output', 'w');

     # use the helper function the create
     # the rows here...      
     $rows = createArrayRows($ts_data_arr);
     foreach ($rows as $row) {
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
     $ts_frequency = $json_decoded['ts_frequency'];
     $ts_data_arr = $json_decoded['ts_data'];
     
     $filename = $tskey.".xlsx";

     
     header('Content-disposition: attachment; filename="'.XLSXWriter::sanitize_filename($filename).'"');
     header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
     header('Content-Transfer-Encoding: binary');
     header('Cache-Control: must-revalidate');
     header('Pragma: public');


 
     $header = array(
      'date'=>'string',
      $tskey=>'string'
      );    
     
     $rows = createArrayRows($ts_data_arr);
     $writer = new XLSXWriter();
     $writer->setAuthor('KOF Swiss Economic Institute');
     $writer->writeSheet($rows,'Sheet1',$header);
     $writer->writeToStdOut();

     exit(0);

     print_r($data);

     pg_close($publicDbCon);

});


$app->run();


# get quarter
function getQuarterByMonth($monthNumber) {
  return floor(($monthNumber - 1) / 3) + 1;
}


# create time series rows
# might need to account for quarterly data here
# at some point, for now it's fine..
# maybe we also need classes and methods here
function createArrayRows($tsdata_array){
     # get keys and values to generate a 
     # mini-arrays that can be used by fputcsv directly... 
     $keys = array_keys($tsdata_array);
     $values = array_values($tsdata_array);

     # create a data array to hold the mini arrays
     # mini arrays = rows

     
     $data = array();
     $length = count($keys);
     for ($i = 0; $i < $length; $i++) {

      $d =  strtotime($keys[$i]);
      $o = date("Y-m",$d);
      #$m = date("m",$d);
      #$mdate = $y." ".(int)$m;
      # echo $mdate."-----";

       $data[$i] = array($o,floatval($values[$i]));
     
     }
     return($data);
}




# DB connection function including db strings
function dbConnect($dbhost = "",
                   $dbport = "5432",
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
