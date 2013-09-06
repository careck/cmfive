<?php
// report member object
class ReportMember extends DbObject {
	public $report_id;		// report id
	public $user_id; 		// user id
	public $role;			// user role: user, editor
	public $is_deleted; 	// deleted flag

	public static $_db_table = "report_member";
	// actual table name
	public function getDbTableName() {
		return "report_member";
	}
}