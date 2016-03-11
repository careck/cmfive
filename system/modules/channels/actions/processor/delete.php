<?php

function delete_GET(Web $w) {
	$p = $w->pathMatch("id");
	$id = $p["id"];

	if ($id) {
		$processor = $w->Channel->getProcessor($id);
		$processor->delete();

		$w->msg(__("Processor deleted"), "/channels/listprocessors");
	} else {
		$w->error(__("Could not find processor"));
	}
}
