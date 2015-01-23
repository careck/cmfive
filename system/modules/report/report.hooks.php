<?php
function report_core_web_before_get(Web $w) {
	// build Navigation to Reports for current Module
	if ($w->Auth->loggedIn()) {
		$reports = $w->Report->getReportsforNav();
		if ($reports) {
			$w->ctx("reports",$reports);
		}
	}
}