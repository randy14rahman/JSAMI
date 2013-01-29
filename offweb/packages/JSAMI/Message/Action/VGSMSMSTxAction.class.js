/**
 *   Copyright 2013 Mehran Ziadloo & Siamak Sobhany
 *   JSAMI: A Javascript client to Asterisk's AMI
 *   (https://github.com/ziadloo/JSAMI)
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 **/

Pomegranate.extend(".JSAMI.Message.Action.ActionMessage", __CLASS__, {
	init: function() {
		this._super("vgsm_sms_tx");
	}
	, setTo: function(to) {
		this.setKey("To", to);
	}
	, setContentType: function(contentType) {
		this.setKey("Content-type", contentType);
	}
	, setContentEncoding: function(encoding) {
		this.setKey("Content-Transfer-Encoding", encoding);
	}
	, setMe: function(id) {
		this.setKey("X-SMS-ME", id);
	}
	, setContent: function(content) {
		this.setKey("Content", content);
	}
	, setSmsClass: function(_class) {
		this.setKey("X-SMS-Class", _class);
	}
	, setConcatRefId: function(refid) {
		this.setKey("X-SMS-Concatenate-RefID", refid);
	}
	, setConcatSeqNum: function(seqnum) {
		this.setKey("X-SMS-Concatenate-Sequence-Number", seqnum);
	}
	, setConcatTotalMsg: function(totalmsg) {
		this.setKey("X-SMS-Concatenate-Total-Messages", totalmsg);
	}
	, setAccount: function(account) {
		this.setKey("Account", account);
	}
});
