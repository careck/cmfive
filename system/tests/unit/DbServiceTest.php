<?php
use \Codeception\Util\Stub;
	
class DbServiceTest extends  \Codeception\TestCase\Test {
	
	
	/**
	* @var \UnitGuy
	*/
	protected $guy;
	
	
	public static $web;
	public static $dbService;
	
	public function _before() {
		// renew sample modules for each test
		//require_once(getenv('thisTestRun_testRunnerPath').'/src/CmFiveTestModuleGenerator.php');
		//$gen=new \CmFiveTestModuleGenerator(getenv('thisTestRun_testIncludePath'));
		//$gen->createTestTemplateFiles();
		self::$web = Stub::construct("Web",[],[]);
		self::$web->initDb();
		self::$dbService = new DbService(self::$web);

	}

	public function _after() {
	}

	function test_buildSelect() {
		// create stubbed Web
				
		$object=new TestmoduleData(self::$web);
		$table='testmodule_data';
		$class='TestmoduleData';
		$dbService=self::$dbService;
		$dbService->buildSelect($object, $table, $class);
		//codecept_debug($dbService::$_select_cache[$class][$table]);
		// check that correct select fields held in cache
		$this->assertEquals($dbService::$_select_cache[$class][$table],'id,title,data,s_data,UNIX_TIMESTAMP(testmodule_data.`d_last_known`) AS `d_last_known`,UNIX_TIMESTAMP(testmodule_data.`t_killed`) AS `t_killed`,UNIX_TIMESTAMP(testmodule_data.`dt_born`) AS `dt_born`,is_deleted,UNIX_TIMESTAMP(testmodule_data.`dt_created`) AS `dt_created`,creator_id,UNIX_TIMESTAMP(testmodule_data.`dt_modified`) AS `dt_modified`,modifier_id');
		
	} //() {
    
    function test_getObject() {
		$this->guy->haveInDatabase('testmodule_data',['id'=>'1001','title'=>'ffreda','data'=>'what is my name']);
		$this->guy->haveInDatabase('testmodule_data',['id'=>'999','title'=>'zfreddo','data'=>'what is my name']);
		$dbService=self::$dbService;
		$dbService->clearCache(); 
		$this->assertNull($dbService::getCacheValue('TestmoduleData','1001'));
		// get object bt scalar id
		$object=$dbService->getObject('TestmoduleData', '1001');
		$this->assertEquals($object->title,'ffreda');
		// check old value
		$this->assertEquals($object->__old['title'],'ffreda');
		// check afterConstruct hook
		$this->assertEquals($object->_flagField,true);
		// check cache
		$object=$dbService::getCacheValue('TestmoduleData','1001');
		$this->assertEquals($object->title,'ffreda');
		$dbService->clearCache(); 
		// get object by array query
		$object=$dbService->getObject('TestmoduleData', ['id'=>'1001']);
		$this->assertEquals($object->title,'ffreda');
		// ordering
		$dbService->clearCache(); 
		$object=$dbService->getObject('TestmoduleData', ['data'=>'what is my name'],true,'id');
		$this->assertEquals($object->title,'zfreddo');
		$dbService->clearCache(); 
		$object=$dbService->getObject('TestmoduleData', ['data'=>'what is my name'],true,'title');
		$this->assertEquals($object->title,'ffreda');
		
		// null cases
		// no results
		$this->assertNull($dbService->getObject('TestmoduleData',['title'=>'james%'],false,true,'id'));
		// no query
		$this->assertNull($dbService->getObject('TestmoduleData','',false,true,'id'));
		// no class
		$this->assertNull($dbService->getObject('',['title'=>'james%'],false,true,'id'));
		// fail non associative array query
		$this->assertNull($dbService->getObject('TestmoduleData', ['id'=>'1001','999']));
		
	} //($class, $idOrWhere, $use_cache = true, $order_by = null) {
	function test_getObjects() {
		$this->guy->haveInDatabase('testmodule_data',['id'=>'1001','title'=>'fred','data'=>'what is my age']);
		$this->guy->haveInDatabase('testmodule_data',['id'=>'1002','title'=>'afreddo','data'=>'what is my age']);
		$dbService=self::$dbService;
		$dbService->clearCache(); 
		// get all
		$objects=$dbService->getObjects('TestmoduleData',['data'=>'what is my age'],true,true);
		$this->assertEquals($objects[0]->title,'fred');
		$this->assertEquals($objects[1]->title,'afreddo');
		// check cache
		$object=$dbService::getCacheValue('TestmoduleData','1001');
		$this->assertEquals($object->title,'fred');
		$object=$dbService::getCacheValue('TestmoduleData','1002');
		$this->assertEquals($object->title,'afreddo');
		$objects=$dbService::getCacheListValue('TestmoduleData','data::what is my age::');
		$this->assertEquals(count($objects),2);
		$this->assertEquals($objects[0]->title,'fred');
		$this->assertEquals($objects[1]->title,'afreddo');
		//$dbService->clearCache(); 
		
		// ordering
		$objects=$dbService->getObjects('TestmoduleData',['data'=>'what is my age'],false,true,'title');
		$this->assertEquals($objects[0]->title,'afreddo');
		$this->assertEquals($objects[1]->title,'fred');
		
		// offset
		$objects=$dbService->getObjects('TestmoduleData',['data'=>'what is my age'],false,true,'title','1','2');
		$this->assertEquals(count($objects),1);
		$this->assertEquals($objects[0]->title,'fred');
		
		// limit
		$objects=$dbService->getObjects('TestmoduleData',['data'=>'what is my age'],false,true,'title',0,1);
		$this->assertEquals(count($objects),1);
		$this->assertEquals($objects[0]->title,'afreddo');
		
		
		// null cases
		// no results
		$this->assertNull($dbService->getObjects('TestmoduleData',['title'=>'james%'],false,true,'id'));
		// no class
		$this->assertNull($dbService->getObjects('',['title'=>'james%'],false,true,'id'));
		// fail non associative array query
		$this->assertNull($dbService->getObjects('TestmoduleData', ['id'=>'1001','999']));
		
	} //($class, $where = null, $cache_list = false, $use_cache = true, $order_by = null, $offset = null, $limit = null) {
    function test_transactionsObjects() {
		// TODO fix this test
		return;
		$dbService=self::$dbService;
		$this->assertFalse($dbService->isActiveTransaction());
		$dbService->startTransaction();
		$this->assertTrue($dbService->isActiveTransaction());
		$object=new TestmoduleData(self::$web);
		$object->fill(['title'=>'fred','data'=>'what is my age']);
		$object->insertOrUpdate();
		$dbService->rollbackTransaction();
		$this->guy->dontSeeInDatabase('testmodule_data',['title'=>'fred','data'=>'what is my age']);
		$dbService->startTransaction();
		$object=new TestmoduleData(self::$web);
		$object->fill(['title'=>'fred','data'=>'what is my age']);
		$object->insertOrUpdate();
		$dbService->commitTransaction();
		$this->guy->seeInDatabase('testmodule_data',['title'=>'fred','data'=>'what is my age']);
	} //$class, $rows, $from_db = false) {
	
    function test_lookupArray() {
		$this->guy->haveInDatabase('lookup',['id'=>'1001','type'=>'money', 'code'=>'AUD', 'title'=>'Australian Dollars']);
		$this->guy->haveInDatabase('lookup',['id'=>'1002','type'=>'money', 'code'=>'USD', 'title'=>'American Dollars']);
		$dbService=self::$dbService;
		$rows=$dbService->lookupArray('money');
		$this->assertEquals(count($rows),2);
		$this->assertEquals($rows['AUD'],'Australian Dollars');
		$this->assertEquals($rows['USD'],'American Dollars');
	} //$type) {
	
}	
	/*****************************************
	 * TESTS
	 *****************************************/
/*
    public function __get($name) {
    function __construct(Web $w) {
    function time2Dt($time = null, $format = 'Y-m-d H:i:s') {
    function time2D($time = null, $format = 'Y-m-d') {
    function time2T($time = null, $format = 'H:i:s') {
    function dt2Time($dt) {
    function d2Time($d) {
    function t2Time($d) {

   
  
*/


