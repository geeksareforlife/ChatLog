<?php
require_once('class.wiki.php');

class Log {
	
	private $_sWikiFailDir = '/home/instrumentation/public_html/chatlog/';
	private $_sWikiFailUrl = 'http://lspace.nottinghack.org.uk/chatlog/';
	
	private $_sWikiPage;
	
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
		
		// Keep the log here in case wiki fails.
		$this->writeLogFile();
		
		$oWiki = new Wiki('ChatLog/1.0 (https://github.com/geeksareforlife/ChatLog; james@geeksareforlife.com)');
		
		if ($oWiki->checkLogin()) { 
			$this->_sWikiPage = $oWiki->addPage($this->_sLogPage, $this->_sLog);
			return true;
		}
		else {
			return false;
		}
		
	}
	
	function writeLogFile() {
		file_put_contents($this->_sWikiFailDir . $this->_sLogPage . ".txt", $this->_sLog);
	}
	
	function getWikiURL() {
		return $this->_sWikiPage;
	}
	
	function getPageURL() {
		return $this->_sWikiFailUrl . rawurlencode($this->_sLogPage) . ".txt";
	}
}

?>
