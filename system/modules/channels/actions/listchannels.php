<?php

function listchannels_GET(Web $w) {
	$w->Channels->navigation($w, "Channels List");
	// Get all email, FTP, local channels
	$email_channels = $w->Channel->getEmailChannels();

	$w->ctx("channels", $email_channels);
}