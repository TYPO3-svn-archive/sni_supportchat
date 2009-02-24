/**** check if the supportChat is online ****/

function initOnlineCheck() {
	var theChecker = new checker(globChatPid,globFreq);
}

var checker = new Class({
	"initialize": function(globChatPid,frequency) {
		this.pid = globChatPid;
		this.frequency = globFreq*1000;
		this.timer = null; 
		this.request = new Request({
			"url": "index.php?eID=tx_snisupportchat_pi1&cmd=checkIfOnline&chatPid="+this.pid,
			"method": "get",
			"link": "ignore",
			"onComplete": this.checkItDone.bind(this)
		});
		this.timer = this.checkIt.delay(this.frequency,this);
	},
	"checkIt": function() {
		$clear(this.timer);
		this.request.send();	
	},
	"checkItDone": function(respText,respXml) {
		online = respText;
		onlineChat = $("tx_snisupportchat_pi1_onlineLogo");
		offlineChat = $("tx_snisupportchat_pi1_offlineLogo");
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
		this.timer = this.checkIt.delay(this.frequency,this);
	}
});

function sniSupportchatOpenWindow(url,winName,winParams) {
	var sniTheWindow = window.open(url,winName,winParams);
	if (sniTheWindow) {
		sniTheWindow.focus();
	}
}

