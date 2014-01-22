<?php 

function edit_GET(Web $w) {

	$p = $w->pathMatch("id");
	$channel_id = $p["id"];

	$w->Channel->navigation($channel_id ? "Edit" : "Add" . " an Email Channel");

	// Get channel and form
	$channel_object = $channel_id ? $w->Channel->getChannel($channel_id) : new Channel($w);
	$form = $channel_object->getForm();

	$email_channel = $channel_id ? $w->Channel->getEmailChannel($channel_id) : new EmailChannelOption($w);

	// Decrypt username and password
	$email_channel->decrypt();

	$form["Email"] = array(
		array(
			array("Server URL", "text", "server", $email_channel->server)
		),
		array(
			array("Username", "text", "s_username", $email_channel->s_username),
			array("Password", "password", "s_password", $email_channel->s_password)
		),
		array(
			array("Port", "text", "port", $email_channel->port),
			array("Use Auth?", "checkbox", "use_auth", $email_channel->use_auth)
		),
		array(
			array("Folder", "text", "folder", $email_channel->folder)
		)
	);

	$w->out(Html::multiColForm($form, "/channels-email/edit/{$channel_id}", "POST", "Save"));
}

function edit_POST(Web $w) {

	$p = $w->pathMatch("id");
	$channel_id = $p["id"];

	$channel_object = $channel_id ? $w->Channel->getChannel($channel_id) : new Channel($w);
	$channel_object->fill($_POST);
	$channel_object->insertOrUpdate();

	$email_channel = $channel_id ? $w->Channel->getEmailChannel($channel_id) : new EmailChannelOption($w);
	$email_channel->fill($_POST);
	$email_channel->channel_id = $channel_object->id;
	$email_channel->insertOrUpdate();

	$w->msg("Email Channel " . ($channel_id ? "updated" : "created"), "/channels/listchannels");

}