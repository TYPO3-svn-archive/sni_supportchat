/**** check if the supportChat is online ****/

function initOnlineCheck() {
	AjaxChatCheck.checkIfOnline();
}

var AjaxChatCheck = {
	timer:'',
	checkIfOnline:function() {
		this.timer = new Ajax.PeriodicalUpdater(
			"",
			"index.php?eID=tx_snisupportchat_pi1&cmd=checkIfOnline&chatPid="+globChatPid, {
				method:'get',
				onSuccess:function(r) {
					online = r.responseText;
					onlineChat = document.getElementById("tx_snisupportchat_pi1_onlineLogo");
					offlineChat = document.getElementById("tx_snisupportchat_pi1_offlineLogo");
					if(online==1 && onlineChat.className == "hidden") {
						offlineChat.className = "hidden";
						offlineChat.style.display = "none";
						onlineChat.className = "";
						onlineChat.style.display = "inline";
					}
					else {
						if(online==0 && offlineChat.className == "hidden") {
							onlineChat.className = "hidden";
							onlineChat.style.display = "none";
							offlineChat.className = "";
							offlineChat.style.display = "inline";
						}
					}
				},
				frequency: globFreq
			}
		);
	},
	logout:function() {
		if(this.timer) this.timer.stop;
	}
}

function sniSupportchatOpenWindow(url,winName,winParams) {
	var sniTheWindow = window.open(url,winName,winParams);
	if (sniTheWindow) {
		sniTheWindow.focus();
	}
}

