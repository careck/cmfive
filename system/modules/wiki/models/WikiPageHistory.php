<?php
class WikiPageHistory extends WikiPage {
	
	// remove the searchable aspect which was defined
	// in the parent class
	var $_remove_searchable;
	
	var $wiki_page_id;

	function update() {
		DbObject::update();
	}

	function insert() {
		DbObject::insert();
	}

	function getDbTableName() {
		return "wiki_page_history";
	}
}