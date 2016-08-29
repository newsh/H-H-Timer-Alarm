<?php
date_default_timezone_set('Europe/Berlin');
define ('BOT_TOKEN', ''); //Your BOT_TOKEN here
define ('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');
define ( 'DSN', 'mysql:host=' );//Ip of db here
define ( 'dbname', '' ); //DB name
define ( 'username', '' ); //DB username
define ( 'password', '' ); //DB password

function exec_curl_request($handle) {
  $response = curl_exec($handle);

  if ($response === false) {
    $errno = curl_errno($handle);
    $error = curl_error($handle);
    error_log("Curl returned error $errno: $error\n");
    curl_close($handle);
    return false;
  }

  $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
  curl_close($handle);

  if ($http_code >= 500) {
    // do not wat to DDOS server if something goes wrong
    sleep(10);
    return false;
  } else if ($http_code != 200) {
    $response = json_decode($response, true);
    error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
    if ($http_code == 401) {
      throw new Exception('Invalid access token provided');
    }
    return false;
  } else {
    $response = json_decode($response, true);
    if (isset($response['description'])) {
      error_log("Request was successfull: {$response['description']}\n");
    }
    $response = $response['result'];
  }

  return $response;
}
function apiRequest($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  foreach ($parameters as $key => &$val) {
    // encoding to JSON array parameters, for example reply_markup
    if (!is_numeric($val) && !is_string($val)) {
      $val = json_encode($val);
    }
  }
  $url = API_URL.$method.'?'.http_build_query($parameters);
  $url = str_replace('%25','%',$url); // quick fix to enable newline in messages. '%' musn't be replaced by http encoding to '%25'
  $handle = curl_init($url);

  
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);

  return exec_curl_request($handle);
}
function addJob($chat_id, $timerName, $timerEnd, $timerDuration) {
	$db = new PDO ( DSN . ';dbname=' . dbname, username, password );
	$stmt = $db->prepare("INSERT INTO jobs (chatId, timerName, timerEnd, timerStamp) VALUES (:chatId, :timerName, :timerEnd, :timerStamp)");
	$stmt->bindValue(':chatId', $chat_id);
	$stmt->bindValue(':timerName', $timerName);
	$stmt->bindValue(':timerEnd', $timerEnd);
	$stmt->bindValue(':timerStamp', md5($timerName.$timerDuration.$chat_id));
	$stmt->execute();

}
function deleteJob($chat_id, $timerName, $timerDuration) {
	$db = new PDO ( DSN . ';dbname=' . dbname, username, password );
	$stmt = $db->prepare("DELETE FROM jobs WHERE timerStamp LIKE :timerStamp");
	$stmt->bindValue(':timerStamp', md5($timerName.$timerDuration.$chat_id));
	$stmt->execute();
}


$jsonString = $_POST['message']; //Get incoming message from client
$obj = json_decode($jsonString,true); //Create object from json String

$timerName = $obj['timerName'];
$timerDuration = $obj['duration']/1000; //Convert 'Duration' from milli seconds to seconds
$timerCommand = $obj['command'];
$chat_id = $obj['chat_id'];

$timerEnd = date('Y-m-d H:i:s', strtotime('+'.$timerDuration.' seconds')); //Calculate timer's end by adding duration to current time

if($timerCommand == "add")
	addJob($chat_id, $timerName, $timerEnd, $timerDuration);
else if($timerCommand == "delete" || $timerCommand == "stop")
	deleteJob($chat_id, $timerName, $timerDuration);



?>