<?php
date_default_timezone_set('Europe/Berlin');
define ('BOT_TOKEN', ''); //Your BOT_TOKEN
define ( 'DSN', 'mysql:host=' );//Ip of db
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
  }
  return $response;
}


$db = new PDO ( DSN . ';dbname=' . dbname, username, password );
$result = $db->query('SELECT * FROM jobs');

while($row = $result->fetch(PDO::FETCH_ASSOC)) {
	
	$time = strtotime($row['timerEnd']);
	$time = date("Y-m-d H:i",$time);
	if($time<=date("Y-m-d H:i")) {

		$db->exec("DELETE FROM jobs WHERE id LIKE ".$row['id']);

		$url = 'https://api.telegram.org/bot' . BOT_TOKEN . '/sendMessage?chat_id='.$row['chatId'].'&text='.$row['timerName'].'+has+finished!';
		$handle = curl_init($url);
		exec_curl_request($handle);
	}
}


?>