<?php

use Zend\Mail\Storage as Zend_Mail_Storage;
use \Zend\Mail\Storage\Imap as Zend_Mail_Storage_Imap;

class EmailChannelOption extends DbObject {
	static $_db_table = "channel_email_option";
	public $_channeltype = "email";

	public $channel_id;
	public $server;
	public $s_username;
	public $s_password;
	public $port;
	public $use_auth;

	public $folder;

	public function __construct(Web $w) {
		parent::__construct($w);
		$this->setPassword(hash("md5", $w->moduleConf("channels", "__password")));
	}

	public function getChannel() {
		if (!empty($this->channel_id)) {
			return $this->w->Channel->getChannel($this->channel_id);
		}
		return null;
	}

	public function getNotifyUser() {
		$channel = $this->getChannel();
		if (!empty($channel)) {
			return $channel->getNotifyUser();
		}
	}

	public function doJob() {
		$this->getUnreadMail();
 		
	}	

	private function connectToMail($shouldDecrypt = true) {
		if ($shouldDecrypt) {
			$this->decrypt();
		}

		$mail = null;
		try {
			// Open email connection
			$mail = new Zend_Mail_Storage_Imap(array('host'     => $this->server,
			                                         'user'     => $this->s_username,
			                                         'password' => $this->s_password,
			                                         'ssl'		=> ($this->use_auth == 1 ? "SSL" : false)));
			return $mail;
		} catch(Exception $e) {
			echo "Error: " . $e->getMessage();
		}
	}

	public function getFolderList($shouldDecrypt = true) {
		$mail = $this->connectToMail($shouldDecrypt);
		$folders = array();

		if (!empty($mail)) {
			if ($mail) {
				foreach($mail->getFolders() as $mailfolder) {
					foreach($mailfolder as $folder) {
						$folders[] = $folder->__toString();
					}
				}
			}
		}
		return $folders;
	}

	public function getUnreadMail() {
		$mail = $this->connectToMail();

		if (!empty($mail)) {
			$count = $mail->count();
			$results = $mail->protocol->search(array("UNSEEN"));

			foreach($results as $messagenum) {
				$mail_message = $mail->getMessage($messagenum);
				echo $mail_message->subject . "<br/>\n";	
			}
		}
	}

}