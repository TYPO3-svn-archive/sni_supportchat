<?php

class tx_snisupportchat_ajax {
	var $chatsPid; // the page - id where the chats are stored
	var $cmd = "getAll"; // what to do
	var $lastRowArray = Array(); // the last Row Array / key => chatUid, value => lastRow
	var $uid = 0; // a single chat uid to handle
	var $logging = 1; // display log messages
	var $lastLogRow = 0; // the last row uid of log messages
			
	function init() {
		global $BE_USER,$TYPO3_DB;		
		$this->chatsPid = $BE_USER->userTS["sni_supportchat."]["chatsPid"];
		if($BE_USER->userTS["sni_supportchat."]["showLogBox"]!="") {
			$this->logging = $BE_USER->userTS["sni_supportchat."]["showLogBox"];
		}
		$this->lastRowArray = t3lib_div::_GP("lastRowArray");
		if(t3lib_div::_GP("cmd")) {
			$this->cmd = t3lib_div::_GP("cmd");
		}
		if(intval(t3lib_div::_GP("lastLogRow"))) {
			$this->lastLogRow = intval(t3lib_div::_GP("lastLogRow"));
		}
		$this->uid = intval(t3lib_div::_GP("chatUid")) ? intval(t3lib_div::_GP("chatUid")) : 0;
		$chatMarket = new chatMarket($this->logging,$this->lastLogRow);
		$chatMarket->initChat($this->chatsPid,$BE_USER->user["uid"],1);
		switch($this->cmd) {
			case 'doAll':
				// get all chats,messages,time,be_user 
				$msgToSend = t3lib_div::_GP("msgToSend");
				$lockChats = t3lib_div::_GP("lockChat");
				$destroyChats = t3lib_div::_GP("destroyChat");
				$xmlArray = Array(
					"fromDoAll" => Array(
						"time" => $chatMarket->renderTstamp(time()),
						"chats" => $chatMarket->doAll($this->lastRowArray,$msgToSend,$lockChats,$destroyChats),
						"log" => $chatMarket->getLogMessages(),
						"lastLogRow" => $chatMarket->lastLogRow,
						"beUsers" => $chatMarket->getBeUsers()
					)
				);
				$xml = tx_chat_functions::convert2xml($xmlArray);
				$chatMarket->printResponse($xml); 
			break;	
		}			
		return ;
	}
	
	/**
	 * Set Response if no Be-User is logged in
	 * @return XML
	 */
	function noAccess() {
		$xmlArray = Array(
			"fromNoAccess" => Array(
				"time" => tx_chat_functions::renderTstamp(time()),
			)
		);
	}

}

	require_once ('conf.php');
	require_once ($BACK_PATH.'init.php');
	require_once(PATH_typo3.'sysext/lang/lang.php');
	require_once(t3lib_extMgm::extPath('sni_supportchat').'lib/class.tx_chatMarket.php');

	define(TYPO3_PROCEED_IF_NO_USER,1);	  
	$LANG = t3lib_div::makeInstance('language');
	$LANG->init($BE_USER->uc['lang']);
	$LANG->includeLLFile('EXT:sni_supportchat/mod1/locallang.xml');
	$SOBE = new tx_snisupportchat_ajax();
	if($BE_USER->user["uid"]) {			
		$SOBE->init();
	}	
	else {
		$SOBE->noAccess();
	}
			
?>
