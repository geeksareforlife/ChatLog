<?php

class Wiki {
	
	private $_sEndpoint = 'http://wiki.nottinghack.org.uk/api.php';
	private $_sCookie = 'cookie.tmp';
	
	private $_aLogin;
	
	private $_sUserAgent;
	
	private $_bLoggedIn = false;
	
	public function __construct($sUserAgent = 'Nottinghack Test Wiki API') {
		$aLogin = array(	
						'username'	=>	"Holly533Mhz",
					   );
		require_once("wikipass.php"); //$aLogin['password']
		$this->_aLogin = $aLogin;
		
		$this->_sUserAgent = $sUserAgent;
		
		// Login to the wiki
		try {
			$sToken = $this->login();
			$this->login($sToken);
			$this->_bLoggedIn = true;
		} catch (Exception $e) {
			$this->_bLoggedIn = false;
		}
	}
	
	public function checkLogin() {
		return $this->_bLoggedIn;
	}
	
	public function addPage($sTitle, $sWikitext) {
		return $this->editPage($sTitle, $sWikitext);
	}
	
	public function editFullPage($sTitle, $sWikitext) {
		return $this->editPage($sTitle, $sWikitext);
	}
	
	// vPost can be either a string or an array
	// If array, posted as multipart/form-data
	private function httpRequest($sURL, $vPost="") {
		$oCH = curl_init();
		
		// Set the options for the cURL
		curl_setopt($oCH, CURLOPT_USERAGENT, $this->_sUserAgent);
		curl_setopt($oCH, CURLOPT_URL, ($sURL));
		curl_setopt($oCH, CURLOPT_ENCODING, "UTF-8" );
		curl_setopt($oCH, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($oCH, CURLOPT_COOKIEFILE, $this->_sCookie);
		curl_setopt($oCH, CURLOPT_COOKIEJAR, $this->_sCookie);
		
		// Are we posting?
		if (!empty($vPost)) {
			curl_setopt($oCH, CURLOPT_POSTFIELDS, $vPost);
		}
		
		//UNCOMMENT TO DEBUG TO output.tmp
		//curl_setopt($oCH, CURLOPT_VERBOSE, true); // Display communication with server
		//$fp = fopen("output.tmp", "w");
		//curl_setopt($ch, CURLOPT_STDERR, $fp); // Display communication with server
		
		$sXML = curl_exec($oCH);
		
		if (!$sXML) {
			throw new Exception("Error getting data from server ($sUrl): " . curl_error($oCH));
		}
		
		curl_close($oCH);
		
		return $sXML;
	}
	
	private function login($sToken='') {
		$sURL = $this->_sEndpoint . "?action=login&format=xml";
		
		$sPost = 'action=login&lgname=' . $this->_aLogin['username'] . '&lgpassword=' . $this->_aLogin['password'];
		if (!empty($sToken)) {
			$sPost .= '&lgtoken=' . $sToken;
		}
		
		$sXML = $this->httpRequest($sURL, $sPost);
		
		if (empty($sXML)) {
			throw new Exception("No data received from server. Check that API is enabled.");
		}
		
		$oXML = simplexml_load_string($sXML);
		
		if (!empty($sToken)) {
			//Check for successful login
			$sExp = "/api/login[@result='Success']";
			$aResult = $oXML->xpath($sExp);
			
			if(!count($aResult)) {
				throw new Exception("Login failed");
			}
		}
		else {
			$sExp = "/api/login[@token]";
			$aResult = $oXML->xpath($sExp);
			
			if(!count($aResult)) {
				throw new Exception("Login token not found in XML");
			}
		}
		
		return $aResult[0]->attributes()->token;
	}
	
	private function editPage($sTitle, $sWikitext) {
		// we need to get an edit token
		$sURL = $this->_sEndpoint . '?action=query&format=xml&prop=info|revisions&inprop=url&intoken=edit&titles=' . urlencode($sTitle);
		
		$sXML = $this->httpRequest($sURL);
		
		if (empty($sXML)) {
			return false;
		}
		
		$oXML = simplexml_load_string($sXML);
		
		$sExp = "/api/query/pages/page";
		$aResult = $oXML->xpath($sExp);
		
		if (count($aResult) != 1) {
			return false;
		}
		
		$sEditToken = $aResult[0]->attributes()->edittoken;
		$sPageURL = (string) $aResult[0]->attributes()->fullurl;
		
		// Prepare the Edit call
		
		$sURL = $this->_sEndpoint . '?action=edit&format=xml';
		
		$aPost = array(
					   'action'	=>	'edit',
					   'format'	=>	'xml',
					   'title'	=>	$sTitle,
					   'bot'	=>	1,
					   'text'	=>	$sWikitext,
					   'token'	=>	$sEditToken,
					   );
		
		$sXML = $this->httpRequest($sURL, $aPost);
		
		if (empty($sXML)) {
			return false;
		}
		
		$oXML = simplexml_load_string($sXML);
		
		$sExp = "/api/edit[@result='Success']";
		$aResult = $oXML->xpath($sExp);
		
		if(!count($aResult)) {
			return false;
		}
		
		return $sPageURL;
	}
	
}

?>
