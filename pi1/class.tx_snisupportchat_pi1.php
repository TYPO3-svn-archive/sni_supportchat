<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Georg Sch�nweger <Georg.Schoenweger@gmail.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Plugin 'Support Chat' for the 'sni_supportchat' extension.
 *
 * @author	Georg Schönweger <Georg.Schoenweger@gmail.com>
 */

require_once(t3lib_extMgm::extPath('sni_supportchat').'lib/class.tx_chat.php');
require_once(PATH_tslib.'class.tslib_pibase.php');

class tx_snisupportchat_pi1 extends tslib_pibase {
	var $prefixId = 'tx_snisupportchat_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_snisupportchat_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'sni_supportchat';	// The extension key.
	var $pi_checkCHash = TRUE;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();		
		$this->templateCode = $this->cObj->fileResource($this->conf["templateFile"]);
		$this->checkPids = $this->checkForOnlineOfflinePages(true);
		$cmd = $this->piVars["cmd"];
		switch ($cmd) {
			case 'openChat':
				//get sessionId and check if JS is enabled
				$sessionId = $this->checkJS();
				// write something to the session so the session cookie (and id) is not re-created on every browser request (needed since new session handling see bug http://bugs.typo3.org/view.php?id=10205)
   	            $GLOBALS['TSFE']->fe_user->setKey("ses","sni_supportchat","1"); 
				if($sessionId) {
					$chatIsOnline = tx_chat_functions::checkIfChatIsOnline($this->checkPids);
					if($chatIsOnline[$this->conf["chatPluginPid"]]) {
//						tx_chat_functions::destroyInactiveChats($this->conf["timeToInactivateChatIfNoMessages"],$this->conf["chatsPid"]);
				        $chat = new chat();
				        $chat->initChat($this->conf["chatsPid"],"");
						$chat->destroyInactiveChats($this->conf["timeToInactivateChatIfNoMessages"]);
						$this->addJsInHeader($sessionId,$chatUid);
						$content = $this->generateChatBox();											
					}
					else {
						$content = $this->showChatIsOfflineMessage();	
					}
				}
				else 
					$content = $this->noJsOrCookie();
			break;
			default:
				$this->addJsInHeaderForCheckIfChatIsOnline();
				$content = $this->showSupportButton();
		}
		if(trim($this->conf["_CSS_DEFAULT_STYLE"]))	{	
			$GLOBALS['TSFE']->additionalHeaderData['tx_snisupportchat_pi1_css'] = '<style type="text/css">'.$this->conf["_CSS_DEFAULT_STYLE"].'</style>';
		}	
		return $this->pi_wrapInBaseClass($content);
	}
	
	/**
	 * Render template with message that the Chat is offline
	 *
	 * @return HTML-Output
	 */
	function showChatIsOfflineMessage() {
		$out = $this->cObj->getSubpart($this->templateCode, '###CHAT_BOX_OFFLINE###');
		$markerArray = Array(
			"###TITLE###" => $this->pi_getLL("chat-offline-title"),
			"###MESSAGE###" => $this->pi_getLL("chat-offline-message"),
		);
		$content = $this->cObj->substituteMarkerArrayCached($out,$markerArray);
		return ($content);
	}
	
	/**
	 * generates the ChatBox 
	 * @return HTML-Output
	 */
	function generateChatBox() {
		$out = $this->cObj->getSubpart($this->templateCode, '###CHAT_BOX###');
		$markerArray = Array(
			"###TITLE###" => $this->pi_getLL("chatbox-welcome"),
			"###TITLE_ID###" => 'chatboxTitle',
			"###CHATBOX_STYLE###" => "display: none;",
			"###SEND_LABEL###" => $this->pi_getLL("chatbox-sendmessage"),
			"###MESSAGE_LABEL###" => $this->pi_getLL("chatbox-messagelabel"),
			"###CHAT_BOX_ID###" => "snisupportchatbox",
			"###TEXTBOX_ID###" => "sniTextbox",
			"###SEND_ID###" => "sniSendMessage",		
			"###MESSAGE###" => "",
			"###CLOSE_ID###" => "sniChatClose",
			"###CLOSE_LABEL###" => $this->pi_getLL("chatbox-close"),
			"###ERROR###" => $this->pi_getLL("noJsOrCookies-text"), 
		);
		$content = $this->cObj->substituteMarkerArrayCached($out,$markerArray);		
		return($content);
	}
	
	/**
	 * Adds the JS Code for the SupportLogo to the Header
	 *
	 */
	function addJsInHeaderForCheckIfChatIsOnline() {
		if($this->conf["usePrototype"] || $this->conf["useMootools"]) {
			if($this->conf["addPrototype"] && $this->conf["usePrototype"]) {
				$GLOBALS['TSFE']->additionalHeaderData['tx_snisupportchat_pi1'] = '<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath('sni_supportchat').'js/prototype.js"></script>';
			}
			if($this->conf["addMootools"] && $this->conf["useMootools"]) {
				$GLOBALS['TSFE']->additionalHeaderData['tx_snisupportchat_pi1'] = '<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath('sni_supportchat').'js/mootools-1.2.4-core-yc.js"></script>';
			}
			$jsCheckPids = $this->checkForOnlineOfflinePages();
			if($jsCheckPids) {
				if($this->conf["usePrototype"]) {
					$GLOBALS['TSFE']->additionalHeaderData['tx_snisupportchat_pi1'] .= '<script type="text/javascript" src="typo3conf/ext/sni_supportchat/js/sni_supportchatIsOnline.js"></script>';
					$onLoad = '
						Event.observe(window, "load", initOnlineCheck);
					';
				}
				else {
					$GLOBALS['TSFE']->additionalHeaderData['tx_snisupportchat_pi1'] .= '<script type="text/javascript" src="typo3conf/ext/sni_supportchat/js/sni_supportchatIsOnline_Mootools.js"></script>';
					$onLoad = '
						window.addEvent("domready",initOnlineCheck)
					';
				}
			}	
			else {
				// no online/offline check in FE
				$onLoad = '';
			}
			$GLOBALS['TSFE']->additionalHeaderData['tx_snisupportchat_pi1'] .= '
				<script type="text/javascript">
				/*<![CDATA[*/
				<!--
					var globFreq = '.$this->conf["checkIfChatIsOnline"].';
					var checkPids = "'.$jsCheckPids.'";				
					'.$onLoad.'
				// -->
				/*]]>*/
				</script>
			';
		}	
		return;
	}
	
	/**
	 * Adds the JS - AJAX Code in the <head> section of the template
	 * Includes the needed JS files.
	 * @param String $sessionId: The identification for the Server
	 * @param Int $chatUid: The chat-uid for this Surfer
	 * @return nothing
	 */
	function addJsInHeader($sessionId,$chatUid) {
		$pid = $this->conf["chatsPid"] ? $this->conf["chatsPid"] : $GLOBALS["TSFE"]->id;
		$lang = intval(t3lib_div::_GET("L")) ? "&L=".intval(t3lib_div::_GET("L")) : "";
		$freq = $this->conf["getMessagesInSeconds"]*1000;
		$chatUsername = $GLOBALS["TSFE"]->fe_user->user["uid"] ? ($GLOBALS["TSFE"]->fe_user->user["first_name"] ? ($GLOBALS["TSFE"]->fe_user->user["first_name"]." ".$GLOBALS["TSFE"]->fe_user->user["last_name"]) : addslashes($GLOBALS["TSFE"]->fe_user->user["name"])) : addslashes($this->pi_getLL("chat-username"));
		$GLOBALS['TSFE']->additionalHeaderData['tx_snisupportchat_pi1'] = '
			<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath('sni_supportchat').'js/mootools-1.2.4-core-yc.js"></script>
            <script type="text/javascript" src="'.t3lib_extMgm::siteRelPath('sni_supportchat').'js/mootools-1.2.4.2-more.js"></script>
            <script type="text/javascript" src="'.t3lib_extMgm::siteRelPath('sni_supportchat').'js/smilies.js"></script>
			<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath('sni_supportchat').'js/sni_supportchat.js"></script>		
			<script type="text/javascript">
			/*<![CDATA[*/
			<!--
				var chatUid = 0;
				var globFreqMessages = '.$freq.';
				var globPid = '.$pid.';
				var globLang = "'.$lang.'";
				var fe_user_name = "'.$GLOBALS["TSFE"]->fe_user->user["name"].'";
				var timeFormated = "'.strftime($this->conf["strftime"],time()).'";
				var diffLang = {
					\'chatboxTitleBeUserOk\': \''.addslashes($this->pi_getLL("chatbox-title-be-user-ok")).'\',
					\'chatboxWelcome\': \''.addslashes($this->pi_getLL("chatbox-welcome")).'\',
					\'chatUsername\': \''.$chatUsername.'\',
					\'systemByeBye\': \''.addslashes($this->pi_getLL("system-chat-byebye")).'\',
					\'systemSupportlerJoinedChat\': \''.addslashes($this->pi_getLL("system-supportler-joined-chat")).'\',
					\'systemSupportlerLeavedChat\': \''.addslashes($this->pi_getLL("system-supportler-leaved-chat")).'\',
					\'chatWelcome\': \''.addslashes($this->pi_getLL("chatbox-entry-welcome")).'\',
					\'system\': \''.addslashes($this->pi_getLL("system-name")).'\',
					\'chatTimeout\': \''.addslashes($this->pi_getLL("chatTimeout")).'\',
					\'chatDestroyedByAdmin\': \''.addslashes($this->pi_getLL("chatDestroyedByAdmin")).'\',
					\'chatNoAccess\': \''.addslashes($this->pi_getLL("chatNoAccess")).'\'
				};
				window.addEvent("domready", function() {
					initChat();
				}); 
			// -->
			/*]]>*/
			</script>
		';
		return;
	}
	
	/**
	 * Shows the Support Button - 2 ways - online or offline Logo and Link from TS
	 * @return HTML-Outut
	 */
	function showSupportButton() {
		// check if Chat is online or offline (if page where chat is stored is hidden or not)
		$chatIsOnline = tx_chat_functions::checkIfChatIsOnline($this->checkPids);
		$out = $this->cObj->getSubpart($this->templateCode, '###SHOW_SUPPORT_LOGO###');	
		// get the offline Variant
		$image = '<img src="'.tx_chat_functions::getPath($this->conf["offlineLogo"]).'" alt="Support Chat Offline" title="Support Chat Offline" />';
		$markerArray = Array(
			"###TITLE###" => $this->pi_getLL("support-logo-header"),
			"###IMAGE###" => $this->cObj->stdWrap($image,$this->conf["offlineLogo."]["stdWrap."]),
			"###STATUS_MSG###" => $this->pi_getLL("status_msg_offline")
		);
		if($chatIsOnline[$this->conf["chatPluginPid"]]) { 
			$onlineClass = "";
			$offlineClass = 'class="hidden"';			
		}
		else {
			$onlineClass = 'class="hidden"';
			$offlineClass = "";			
		} 
		$offline = '<div '.$offlineClass.' id="tx_snisupportchat_pi1_offlineLogo_'.$this->conf["chatPluginPid"].'">'.$this->cObj->substituteMarkerArrayCached($out,$markerArray).'</div>';
		// get the online Variant
		$image = '<img src="'.tx_chat_functions::getPath($this->conf["onlineLogo"]).'" alt="Support Chat Online" title="Support Chat Online" />';
/*		$params = Array(
			"tx_snisupportchat_pi1[cmd]" => "openChat" 
		); */
		// typolink does not create the link if page is hidden
		// $openChatLink = $this->pi_getPageLink($this->conf["chatPluginPid"],'',$params);
		$openChatLink = '/index.php?id='.$this->conf["chatPluginPid"].'&tx_snisupportchat_pi1[cmd]=openChat';
		$markerArray = Array(
			"###TITLE###" => $this->pi_getLL("support-logo-header"),
			"###IMAGE###" => '<a href="'.$this->pi_getPageLink($this->conf["chatNotSupportedPage"]).'" onclick="sniSupportchatOpenWindow(\''.$openChatLink.'\',\'snisupportchatwindow\',\''.$this->conf["chatWindowJsParams"].'\'); return false;" target="_blank">'.$image.'</a>',
			"###STATUS_MSG###" => $this->pi_getLL("status_msg_online")
		);		
		$online = '<div '.$onlineClass.' id="tx_snisupportchat_pi1_onlineLogo_'.$this->conf["chatPluginPid"].'">'.$this->cObj->substituteMarkerArrayCached($out,$markerArray).'</div>';
		$content = $online.$offline;		
		return ($content);
	}

	/**
	* @return comma separated list of pages which this plugin instance should check if offline or online
	*/
	function checkForOnlineOfflinePages($forceThisUid=false) {
		if(trim($this->conf["checkIfChatIsOnlinePids"])) {
            if(trim($this->conf["checkIfChatIsOnlinePids"]) == "this") {
                $checkPids = $this->conf["chatPluginPid"];
            }
            else {
                $checkPids = trim($this->conf["checkIfChatIsOnlinePids"]);
            }
		}
		else {
			$checkPids = 0;
		}
		if($forceThisUid) {
			$checkPids = $this->conf["chatPluginPid"];
		}
		return ($checkPids);
	}

	/**
	 * Checks if the Surfer has JS enabled and if a sessionID exists
	 * @return SessionId or Zero if no javascript or no sessionId
	 */
	function checkJS() {
		if(!$GLOBALS['TSFE']->fe_user->id || t3lib_div::_GET("noJs"))		
			return(0);
		else
			return($GLOBALS['TSFE']->fe_user->id);	
	}
	
	/**
	 * Shows an error message that Cookies and Javascript must be enabled
	 * return HTML-Output
	 */
	function noJsOrCookie() {
		$out = $this->cObj->getSubpart($this->templateCode, '###NO_JS_OR_COOKIES_ENABLED###');
		$markerArray = Array(
			"###TITLE###" => $this->pi_getLL("noJsOrCookies-title"),
			"###TEXT###" => $this->pi_getLL("noJsOrCookies-text"),
		);
		$content = $this->cObj->substituteMarkerArrayCached($out,$markerArray);
		return ($content);
	}
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sni_supportchat/pi1/class.tx_snisupportchat_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sni_supportchat/pi1/class.tx_snisupportchat_pi1.php']);
}

?>
