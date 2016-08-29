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
function processMessage($message) {
	if (isset($message['text'])) {
		$chat_id = $message['chat']['id'];
		$text = $message['text'];

		if (strpos($text, "/start") === 0) {
			apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Hello, this is BrodgarBot!', 'reply_markup' => array(
				'keyboard' => array(array('Show Timers', 'Show my Chat ID')),
				'one_time_keyboard' => false,
				'resize_keyboard' => true))
			);
		} 
		else if ($text === "Show Timers" || $text === "/showtimers") {
		
			$db = new PDO ( DSN . ';dbname=' . dbname, username, password );
			$allTimers = $db->prepare("SELECT * FROM jobs WHERE chatId LIKE :chat_id ORDER BY timerEnd DESC");
			$allTimers->bindValue(':chat_id', $chat_id);
			$allTimers->execute();
			
			if ($allTimers->rowCount() > 0) {
				
				while($row = $allTimers->fetch(PDO::FETCH_ASSOC)) {
					
					$dateNow = new DateTime(date("Y-m-d H:i:s")); //Get current time.
					$endTime = new DateTime($row['timerEnd']); //Get timers end time.
					$timeDif = $dateNow->diff($endTime); //Calculate difference of two DateTime objects

					//Build string containing all timers.
					$listOfAllTimers .= $timeDif->format("%D:%H:%I:%S".' - '.$row['timerName']).'%0A';
					
				}
				
			apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => $listOfAllTimers)); //Send all timers to user.
			}
			else 
				apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'No timers are running!'));
		}else if ($text === '/showmychatid' || $text === 'Show my Chat ID') {  //'Show my Chat ID'
			apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Your personal Chat ID is: '.$chat_id));
		}
		else;//User sends some other, not recognized text.
  } else; //User sends something other than text.
}

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (isset($update["message"])) {
  processMessage($update["message"]);
}
if (!$update) {
  // receive wrong update, must not happen
  exit;
}

?>
		