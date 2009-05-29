/**** check if the supportChat is online ****/

function initOnlineCheck() {
	if(checkPids) {
		var theChecker = new checker(checkPids,globFreq);
	}	
}

var checker = new Class({
	"initialize": function(checkPids,frequency) {
		// checkPids is a comma separetet list of page-uids
		this.pids = checkPids;
		this.frequency = frequency*1000;
		this.timer = null; 
		this.request = new Request({
			"url": "index.php?eID=tx_snisupportchat_pi1&cmd=checkIfOnline&chatPids="+this.pids,
			"method": "get",
			"link": "cancel",
			"onComplete": this.checkItDone.bind(this)
		});
		this.timer = this.checkIt.delay(this.frequency,this);
	},
	"checkIt": function() {
		$clear(this.timer);
		this.request.send();	
	},
	"checkItDone": function(respText,respXml) {
		var els = respXml.getElements("numIndex");
		els.each(function(item,index) {
			var chatUid = item.get("index");
			var isOnline = item.get("text"); 
			onlineChat = $("tx_snisupportchat_pi1_onlineLogo_"+chatUid);
			offlineChat = $("tx_snisupportchat_pi1_offlineLogo_"+chatUid);
			if(isOnline == 1 && onlineChat.className == "hidden") {
				offlineChat.className = "hidden";
				offlineChat.style.display = "none";
				onlineChat.className = "";
				onlineChat.style.display = "inline";
			}
			else {
				if(isOnline==0 && offlineChat.className == "hidden") {
					onlineChat.className = "hidden";
					onlineChat.style.display = "none";
					offlineChat.className = "";
					offlineChat.style.display = "inline";
				}
			}

		});
		this.timer = this.checkIt.delay(this.frequency,this);
	}
});

function sniSupportchatOpenWindow(url,winName,winParams) {
	var sniTheWindow = window.open(url,winName,winParams);
	if (sniTheWindow) {
		sniTheWindow.focus();
	}
}
