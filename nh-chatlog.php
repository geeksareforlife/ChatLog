<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once("sam/php_sam.php");

$sRX = 'topic://nh/irc/rx/nottinghack/';
$sTX = 'topic://nh/irc/tx/nottinghack/'; 

$oConn = new SAMConnection();
$oConn->connect(SAM_MQTT, array(SAM_HOST => '192.168.0.1 ', SAM_PORT => 1883));

// connect to the irc MQTT channel

$sSub = $oConn->subscribe($sRX . '+/');

$oLog = "";
$bLogging = false;





/*while (1) {
	while ($oMsg = $oConn->receive($sSub, array(SAM_WAIT=>500))) {
		if ($bLogging) {
			if ($oMsg->body == "!chatlog end") {
				$oLog->endLog();
				$bLogging = false;
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
			}
		}
	}
}*/


function msg($sMsg) {
	echo($sMsg . "\n");
}

function sendIRC($sMsg) {
	global $oConn, $sTX;
	
	$oMsg = new SAMMessage($sMsg);
	
	$oConn->send($sTX, $oMsg);
}


class Log {
	
	private $_sLogPage;
	
	private $_aColours;
	private $_iColourID;
	
	private $_aUserMaps;
	private $_aIgnoreNames;
	
	private $_sLog;

	function __construct($sLogPage = "ChatLog") {
		
		$this->_sLogPage = date("Y-m-d") . " " . $sLogPage;
		
		$this->_aIgnoreNames = array("nh-holly");
		
		$this->_aColours = array("#00C322", "#FF1300", "#3E13AF", "#FFD700", "#CD0074", "#FFAA00", "#9FEE00", "#009999", "#A64B00", "#D8005F");
		$this->_iColourID = 0;
		$this->_aUserMaps = array();
		
		$this->_sLog = '{| class="wikitable"' . "\n";
		$this->_sLog .= "!Time!!Name!!Minute\n";
	}
	
	function getName() {
		return $this->_sLogPage;
	}
	
	function add($sName, $sMsg) {
		if (!isset($this->_aUserMaps[$sName])) {
			if ($this->_iColourID < count($this->_aColours)) {
				$this->_aUserMaps[$sName] = $this->_aColours[$this->_iColourID];
				$this->_iColourID++;
			}
			else {
				$this->_aUserMaps[$sName] = "000000";
			}
		}
		
		$this->_sLog .= "|-\n";
		$this->_sLog .=  '|<span style="color: #AAAAAA">' . date("H:i") . '</span>||<span style="color: ' . $this->_aUserMaps[$sName] . '">' . $sName . '</span>||' . $sMsg . "\n";
	}
	
	function endLog() {
		$this->_sLog .= "|}\n";
		msg($this->_sLog);
	}
} 
?>
