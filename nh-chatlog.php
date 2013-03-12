<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once("sam/php_sam.php");
require_once("class.log.php");

$sRX = 'topic://nh/irc/rx/nottinghack/';
$sTX = 'topic://nh/irc/tx/nottinghack/'; 

$oConn = new SAMConnection();
$oConn->connect(SAM_MQTT, array(SAM_HOST => '192.168.0.1 ', SAM_PORT => 1883));

// connect to the irc MQTT channel

$sSub = $oConn->subscribe($sRX . '+/');

$oLog = "";
$bLogging = false;

while (1) {
	while ($oMsg = $oConn->receive($sSub, array(SAM_WAIT=>500))) {
		if ($bLogging) {
			if ($oMsg->body == "!chatlog end") {
				$bLogging = false;
				sendIRC("Logging complete, saving...");
				if ($oLog->endLog()) {
					sendIRC("Saved to wiki: " . $oLog->getWikiURL());
				}
				else {
					sendIRC("Unable to save to wiki, please access wikitext here: " . $oLog->getPageURL());
				}
				
				continue;
			}
			elseif ($oMsg->body == "!chatlog cancel") {
				// discard log
				unset($oLog);
				$bLogging = false;
				sendIRC("Logging cancelled");
				continue;
			}
			else {
				$sAuthor = str_replace($sRX, "", $oMsg->header->SAM_MQTT_TOPIC);
				$oLog->add($sAuthor, $oMsg->body);
				continue;
			}
		}
		else {
			$aMatches = array();
			if (preg_match("/!chatlog\s*(.*)/", $oMsg->body, $aMatches)) {
				if (!isset($aMatches[1]) or $aMatches[1] == "") {
					$oLog = new Log();
				}
				else {
					$oLog = new Log(trim($aMatches[1]));
				}
				$bLogging = true;
				sendIRC("Logging to " . $oLog->getName());
				sendIRC("If this page exists, it will be overwritten.  Send !chatlog cancel to end logging without saving.");
			}
		}
	}
}


// Debugging
function msg($sMsg) {
	echo($sMsg . "\n");
}

function sendIRC($sMsg) {
	global $oConn, $sTX;
	
	$oMsg = new SAMMessage("ChatLog: " . $sMsg);
	
	$oConn->send($sTX, $oMsg);
}


?>
