<?php

function edit_GET(Web $w) {
	
	$p = $w->pathMatch("id");
	$_form_object = $p['id'] ? $w->Form->getForm($p['id']) : new Form($w);
	
	$form = [
		"Form" => [
			[["Title", "text", "title", $_form_object->title]],
			[["Description", "text", "description", $_form_object->description]],
		]
	];
	
	$w->out(Html::multiColForm($form, '/form/edit/' . $_form_object->id));
}

function edit_POST(Web $w) {
	
	$p = $w->pathMatch("id");
	$_form_object = $p['id'] ? $w->Form->getForm($p['id']) : new Form($w);
	
	$_form_object->fill($_POST);
	$_form_object->insertOrUpdate();
	
	$w->msg("Form " . ($p['id'] ? 'updated' : 'created'), "/form");
}
