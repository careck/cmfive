<?php
	namespace WebTest {
	use \Codeception\Util\Stub;
	use \Config as Config;
	/*
	 * 
	 * 
	TODO
		* 
		
		
	NOT TESTED
		function test_dump() {}  - low priority function, hard to generate oracle case for output of $this
		function test_sendFile() {} - cannot capture output from CLI
		function test_CachedTemplate() {} - Unused
		
	NOT TESTED - DEPRECATED
	 
		function test__callPreListeners() {}
		function test__callPostListeners() {}

		
	NOT TESTED - STUBBED
		// these methods are stubbed so cannot be tested INSIDE THIS TEST CLASS
		// TODO need to test these functions in  another test class where they are not stubbed in the Web instance
		function test_checkAccess($msg = "Access Restricted") {}
		
		function test_redirect($url) {}
		function test_sendHeader($key, $value) {}
		function test_error($msg, $url = "") {}
		function test_msg($msg, $url = "") {}
		function test_notFoundPage() {}
		
		function test_session($key, $value = null) {}
		function test_sessionUnset($key) {}
		function test_sessionDestroy() {}




	*/

	// disable header function
	function header($a) {
		echo("::HEADER::".$a);
	}
			
		
	class WebTest extends  \Codeception\TestCase\Test {
		
		
		/**
		* @var \UnitGuy
		*/
		protected $guy;
		/**
		 * @var \Web
		 */
		protected static $web;

		
		private static function _stubWeb() {
				//self::$web = new Web(); //Stub::construct('Web');;
				$overrideFunctions=[];
				$overrideFunctions['dump']='::DUMP::';
				
				// REDIRECTS 
				$overrideFunctions['redirect']=function($url) {
					echo('::REDIRECT::'.$url);
				};
				$overrideFunctions['error']=function($msg,$url) {
					echo('::ERROR::'.$msg.'::'.$url); 
				};
				$overrideFunctions['msg']=function($msg,$url) {
					echo('::MESSAGE::'.$msg.'::'.$url); 
				};
				$overrideFunctions['notFoundPage']=function() {
					echo('::NOTFOUNDPAGE::'); 
				};
				
				// SESSION
				$overrideFunctions['session']=function($key, $value) {
				//	codecept_debug('::SESSION::'.$key.'::'.$value);
				};
				$overrideFunctions['sessionUnset']=function($key) {
					//codecept_debug('::SESSIONUNSET::'.$key);
				};
				$overrideFunctions['sessionDestroy']=function() {
					//codecept_debug('::SESSIONDESTROY::');
				};
				$blankFunctions=[];
				foreach ($blankFunctions as $functionName) {
					$overrideFunctions[$functionName]='';
				}
				// create stubbed Web
				$web = Stub::construct("Web",[],$overrideFunctions);
				// override Auth service with stub
				$web->_services['Auth']=Stub::make("AuthService",[
					'login'=>'',
					'allowed'=>function($path,$url) {
						if (strpos($path,'admin')===0) {
							return false;
						} else { 
							return $url;
						}
					},
					'loggedIn'=>true,
					'user'=>function() {
						return Stub::make('User',[]);
					}
				]);
				return $web;
		}
		
		public function _before() {
			// before is called per test so check existence before creating
			$this->createTestTemplateFiles();
			self::$web=self::_stubWeb();
		}

		public function _after() {
			$this->removeTestTemplateFiles();
		}
		
		private function captureOutput($class,$functionToRun,$arguments=[]) {
			ob_start();
			call_user_func_array(array($class,$functionToRun),$arguments);
			$generated=ob_get_contents();
			ob_end_clean();
			return $generated;
		}
		
		/*****************************
		 * Recursively delete a folder
		 *****************************/
		function rmdirRecursive($dir) { 
			if (is_dir($dir)) { 
				$files = array_diff(scandir($dir), array('.','..')); 
				foreach ($files as $file) { 
					if (is_dir($dir.DIRECTORY_SEPARATOR.basename($file))) {
						$this->rmdirRecursive($dir.DIRECTORY_SEPARATOR.basename($file)) ;
					} else { 
						unlink($dir.DIRECTORY_SEPARATOR.basename($file)); 
					}
				} 
				return rmdir($dir); 
			}
		}
		
		/*****************************************
		 * Return a list of files that cover all the permutations for templateExists
		 ****************************************/	
		function getTestTemplateFiles() {
			$paths=[
				
				'modules/testmodule/templates/submodule/testtemplate',
				'modules/testmodule/templates/submodule/get',
				'modules/testmodule/templates/submodule/edit',
				'modules/testmodule/templates/submodule/submodule',
				'modules/testmodule/templates/submodule/testmodule',
				
				'modules/testmodule/templates/testtemplate',
				'modules/testmodule/templates/get',
				'modules/testmodule/templates/edit',
				'modules/testmodule/templates/submodule',
				'modules/testmodule/templates/testmodule',
				
				'modules/testmodule/testtemplate',
				'modules/testmodule/get',
				'modules/testmodule/edit',
				'modules/testmodule/submodule',
				'modules/testmodule/testmodule',

				'system/modules/systestmodule/templates/submodule/testtemplate',
				'system/modules/systestmodule/templates/submodule/get',
				'system/modules/systestmodule/templates/submodule/edit',
				'system/modules/systestmodule/templates/submodule/submodule',
				'system/modules/systestmodule/templates/submodule/testmodule',
				
				'system/modules/systestmodule/templates/testtemplate',
				'system/modules/systestmodule/templates/get',
				'system/modules/systestmodule/templates/edit',
				'system/modules/systestmodule/templates/submodule',
				'system/modules/systestmodule/templates/testmodule',
				
				'system/modules/systestmodule/testtemplate',
				'system/modules/systestmodule/get',
				'system/modules/systestmodule/edit',
				'system/modules/systestmodule/submodule',
				'system/modules/systestmodule/testmodule',

				'templates/testmodule/testtemplate',
				'templates/testmodule/get',
				'templates/testmodule/edit',
				'templates/testmodule/submodule',
				'templates/testmodule/testmodule',

				'templates/testtemplate',
				'templates/get',
				'templates/edit',
				'templates/submodule',
				'templates/testmodule',

				'system/templates/testtemplate',
				'system/templates/get',
				'system/templates/edit',
				'system/templates/submodule',
				'system/templates/testmodule'
			];
			return $paths;
		}	
		
		function createTestTemplateFiles() {
			$paths=$this->getTestTemplateFiles();
			foreach ($paths as $path) {
				$file=ROOT_PATH."/".$path.".tpl.php";
				if (!is_dir(dirname($file))) {
					mkdir(dirname($file),0777,true);
				}
				file_put_contents($file,':::TEMPLATE:::'.$file."::");
			}
			file_put_contents(ROOT_PATH."/system/modules/systestmodule/config.php",'<'.'?php Config::set("systestmodule",["active"=>true,"topmenu"=>false,"path"=>"system/modules","hooks"=>["systestmodule","core_web"]]);');
			file_put_contents(ROOT_PATH."/modules/testmodule/config.php",'<'.'?php Config::set("testmodule",["active"=>true,"topmenu"=>false,"path"=>"modules","hooks"=>["systestmodule","core_web"]]);');
			// partial in testmodule
			@mkdir(ROOT_PATH."/modules/testmodule/mypartials");
			@mkdir(ROOT_PATH."/modules/testmodule/mypartials/actions");
			@mkdir(ROOT_PATH."/modules/testmodule/mypartials/templates");
			file_put_contents(ROOT_PATH."/modules/testmodule/mypartials/actions/testpartial.php",'<'.'?php '. 
			'function testpartial_ALL(Web $w,$params) { $w->ctx("partialvalue","thepartialvalue".$params["paramsvalue"]);} ;'
			);
			file_put_contents(ROOT_PATH."/modules/testmodule/mypartials/templates/testpartial.tpl.php",'<'.'?php '. 
			'echo "testpartial:::".$partialvalue.":::";'
			);
		}

		function removeTestTemplateFiles() {
			$this->rmdirRecursive(ROOT_PATH."/system/modules/systestmodule");
			$this->rmdirRecursive(ROOT_PATH."/modules/testmodule");
			foreach ([
				'templates/testmodule/testtemplate',
				'templates/testmodule/get',
				'templates/testmodule/edit',
				'templates/testmodule/submodule',
				'templates/testmodule/testmodule',

				'templates/testtemplate',
				'templates/get',
				'templates/edit',
				'templates/submodule',
				'templates/testmodule',

				'system/templates/testtemplate',
				'system/templates/get',
				'system/templates/edit',
				'system/templates/submodule',
				'system/templates/testmodule'] as $toRemove) {
					if (file_exists(ROOT_PATH.DIRECTORY_SEPARATOR.$toRemove.'.tpl.php')) {
						unlink(ROOT_PATH.DIRECTORY_SEPARATOR.$toRemove.'.tpl.php');
					}
				}

		}	
		/*****************************************
		 * TESTS 
		 *****************************************/
		
		// accessors
		function test_webroot() {
			$this->assertEquals(self::$web->webroot(),self::$web->_webroot);
		}
		function test_sendHeader() {
			self::$web->sendHeader('mykey', 'myvalue');
			$this->assertEquals(self::$web->_headers['mykey'],'myvalue');
		}
				
		function test_moduleConf() {
			\Config::set('testmodule.mykey','myvalue');
			$this->assertEquals(self::$web->moduleConf('testmodule', 'mykey'),\Config::get('testmodule.mykey'));
		}
		
		function test_isAjax() {
			unset($_SERVER['HTTP_X_REQUESTED_WITH']);
			$this->assertFalse(self::$web->isAjax());
			$_SERVER['HTTP_X_REQUESTED_WITH']=true;
			$this->assertTrue(self::$web->isAjax());
		}
		function test_setTitle() {
			self::$web->setTitle('thetitle');
			$this->assertEquals(self::$web->ctx('title'),'thetitle');
		}
		function test_currentRequestMethod() {
			$this->assertEquals(self::$web->currentRequestMethod(),self::$web->_requestMethod);
		}
		function test_getPath() {
			self::$web->_paths=['a','b','c'];
			$this->assertEquals(self::$web->getPath(),implode("/", self::$web->_paths));
		}
		function test_requestIpAddress() {
			$_SERVER['REMOTE_ADDR']='111.111.111.111';
			$this->assertEquals(self::$web->requestIpAddress(),'111.111.111.111');
		}
		function test_currentModule() {
			$this->assertEquals(self::$web->currentModule(),self::$web->_module);
		}
		function test_currentSubModule() {
			$this->assertEquals(self::$web->currentSubModule(),self::$web->_submodule);
		}
		function test_currentAction() {
			$this->assertEquals(self::$web->currentAction(),self::$web->_action);
		}
		function test_modules() {
			$this->assertEquals(self::$web->modules(),\Config::keys());
		}
		
		
		
		
		function test_modelLoader() {
			// modelLoader is called by autoloader
			try {
				if (! 
					class_exists('Task',true)
					&& class_exists('User',true)
					&& class_exists('ExampleData',true)
				) {
					$this->fail('Failed to load models');
				}
				if (class_exists('ExampleDataISNotReallyATypeOfObject',true)) {
					$this->fail('Failed to throw exception on load of invalid object type');
				}
			} catch (Exception $e) {
				
			}
		}
		/**
		 * Testing Web->enqueueScript($script)
		 */
		public function testEnqueueAndOutputScript() {
		
			self::$web->enqueueScript(array("name" => "modernizr.js", "uri" => "/system/templates/js/modernizr.js", "weight" => 10));
			
			// Test one script
			$this->assertEquals(count(self::$web->_scripts),1);
			$this->assertEquals($this->captureOutput(self::$web,'outputScripts'),"<script src='/system/templates/js/modernizr.js'></script>");
			
			// Test a second script
			self::$web->enqueueScript(array("name" => "jquery.js", "uri" => "/system/templates/js/jquery.js", "weight" => 50));
			$this->assertEquals(count(self::$web->_scripts),2);
			$this->assertEquals($this->captureOutput(self::$web,'outputScripts'),"<script src='/system/templates/js/jquery.js'></script><script src='/system/templates/js/modernizr.js'></script>");
			
			// Test that adding a previous value isnt duplicated
			self::$web->enqueueScript(array("name" => "jquery.js", "uri" => "/system/templates/js/jquery.js", "weight" => 50));
			$this->assertEquals(count(self::$web->_scripts),2);
			$this->assertEquals($this->captureOutput(self::$web,'outputScripts'),"<script src='/system/templates/js/jquery.js'></script><script src='/system/templates/js/modernizr.js'></script>");

			// Test weight based sorting by injecting another script which should sort to the middle
			self::$web->enqueueScript(array("name" => "myscript.js", "uri" => "/eek/myscript.js", "weight" => 20));
			$this->assertEquals(count(self::$web->_scripts),3);
			$this->assertEquals($this->captureOutput(self::$web,'outputScripts'),"<script src='/system/templates/js/jquery.js'></script><script src='/eek/myscript.js'></script><script src='/system/templates/js/modernizr.js'></script>");
			
		}
		
		/**
		 * Testing Web->enqueueStyle($style)
		 */
		public function testEnqueueAndOutputStyle() {
			self::$web->enqueueStyle(array("name" => "style.css", "uri" => "/system/style.css", "weight" => 10));
			
			// Test one script
			$this->assertEquals(count(self::$web->_styles),1);
			$this->assertEquals($this->captureOutput(self::$web,'outputStyles'),"<link rel='stylesheet' href='/system/style.css'/>");
			
			// Test a second script
			self::$web->enqueueStyle(array("name" => "jquery.css", "uri" => "/system/jquery.css", "weight" => 50));
			$this->assertEquals(count(self::$web->_styles),2);
			$this->assertEquals($this->captureOutput(self::$web,'outputStyles'),"<link rel='stylesheet' href='/system/jquery.css'/><link rel='stylesheet' href='/system/style.css'/>");
			
			// Test that adding a previous value isnt duplicated
			self::$web->enqueueStyle(array("name" => "jquery.css", "uri" => "/system/jquery.css", "weight" => 50));
			$this->assertEquals(count(self::$web->_styles),2);
			$this->assertEquals($this->captureOutput(self::$web,'outputStyles'),"<link rel='stylesheet' href='/system/jquery.css'/><link rel='stylesheet' href='/system/style.css'/>");

			// Test weight based sorting by injecting another style which should sort to the middle
			self::$web->enqueueStyle(array("name" => "mine.css", "uri" => "/eek/mine.css", "weight" => 20));
			$this->assertEquals(count(self::$web->_styles),3);
			$this->assertEquals($this->captureOutput(self::$web,'outputStyles'),"<link rel='stylesheet' href='/system/jquery.css'/><link rel='stylesheet' href='/eek/mine.css'/><link rel='stylesheet' href='/system/style.css'/>");
		}
		


		function test_getSubmodules() {
			//codecept_debug(self::$web->getSubmodules('report'));
			//codecept_debug(self::$web->getSubmodules('task'));
			$this->assertTrue(self::$web->getSubmodules('report')==['connections','templates']);
		}
		
		function test_checkUrl() {
			//function test_parseUrl($url) {}
		
			$this->assertTrue(self::$web->checkUrl('tasks-groups/delete/5','tasks','groups','delete'));
			$this->assertTrue(self::$web->checkUrl('tasks-groups/delete/5','*','groups','delete'));
			$this->assertTrue(self::$web->checkUrl('tasks-groups/delete/5','tasks','*','delete'));
			$this->assertTrue(self::$web->checkUrl('tasks-groups/delete/5','tasks','groups','*'));
			$this->assertFalse(self::$web->checkUrl('tasks-groups/delete/5','tasks','groups','add'));
			$this->assertFalse(self::$web->checkUrl('tasks-groups/delete/5','tasks','friends','delete'));
			$this->assertFalse(self::$web->checkUrl('tasks-groups/delete/5','users','groups','add'));
		}
		
	   
		function test_ctx() {
			// as setter
			self::$web->ctx('name','joe');
			// append
			self::$web->ctx('name',' janes',true);
			// as getter
			$this->assertTrue(self::$web->ctx('name')==="joe janes");
		}
		
		function test_validate() {
			$_REQUEST['myparam']='this is not a phone';
			$this->assertEquals(self::$web->validate([['myparam','phone:','not a phone']]),['not a phone']);
			$_REQUEST['myparam']='phone:876876876767';
			$this->assertEquals(self::$web->validate([['myparam','phone:','not a phone']]),[]);
			$this->assertNull(self::$web->validate(false));
		}
		
		function test_request() {
			// normal case
			$_REQUEST['myparam']='%21%7E%23%24+%26%2A%28';
			$_REQUEST['mynullparam']=null;
			$this->assertEquals(self::$web->request('myparam'),'!~#$ &*(');
			// non existent case
			$this->assertNull(self::$web->request('mynonexistingparam'));
			// empty value
			$this->assertEquals(self::$web->request('mynullparam'),'');
			// array
			$_REQUEST['arrayparam']=['data'=>'%21%7E%23%24+%26%2A%28'];
			$this->assertEquals(self::$web->request('arrayparam'),['data'=>'!~#$ &*(']);
		}
		
		function test_getCommandPath() {
			$_SERVER['REQUEST_URI']='site/tasks/showfriends/4';
			$_SERVER['SCRIPT_NAME']='site/index.php';
			// with leading path
			$this->assertEquals(self::$web->_getCommandPath(),['tasks','showfriends','4']);
			// with parameter
			$this->assertEquals(self::$web->_getCommandPath('site/admin/users/find/me/some/bread'),['admin','users','find','me','some','bread']);
			// without leading path
			$this->assertEquals(self::$web->_getCommandPath('admin/users/find/me/some/bread'),['admin','users','find','me','some','bread']);
			//* eg /site/users/do/2 + site/index.php  => [users,do,2]
		}
		
		function test_pathMatch() {
			$_SERVER['REQUEST_URI']='site/tasks/showfriends/4';
			$_SERVER['SCRIPT_NAME']='site/index.php';
			self::$web->_paths=self::$web->_getCommandPath();
			$this->assertEquals(self::$web->pathMatch('module','action','id'),['module'=>'tasks','action'=>'showfriends','id'=>'4']);
		}
		
		
		function test_getModuleDir() {
			// system
			$this->assertEquals(self::$web->getModuleDir('task'),'system/modules/task/');
			// non system
			$this->assertEquals(self::$web->getModuleDir('example'),'modules/example/');
		}
		
		
		function stripDomainFromUrl($taskUrl) {
			$after=explode('/',$taskUrl);
			if (strpos($taskUrl,'http://')===0) { 
				$taskUrl=substr($taskUrl,7);
				$after=array_slice(explode('/',$taskUrl),1);
			} else if (strpos($taskUrl,'https://')===0) {
				$taskUrl=substr($taskUrl,8);
				$after=array_slice(explode('/',$taskUrl),1);
			}
			
			return implode("/",$after);
		}
		
		function test_moduleUrl() {
			$this->assertEquals($this->stripDomainFromUrl(self::$web->moduleUrl('task')),'system/modules/task/');
			$this->assertEquals($this->stripDomainFromUrl(self::$web->moduleUrl('example')),'modules/example/');
			
		}
		
		function test_service() {
			//function test___get($name) {}
			$s=self::$web->service('Task');
			$this->assertTrue(get_class($s)==='TaskService');
			$s=self::$web->service('example');
			$this->assertTrue(get_class($s)==='ExampleService');
		}
		
		function test_getModuleNameForModel() {
			$this->assertEquals(self::$web->getModuleNameForModel('TaskGroup'),'task');
			$this->assertEquals(self::$web->getModuleNameForModel('ExampleData'),'example');
		}
		
		function test_isClassActive() {
			$this->assertTrue(self::$web->isClassActive('TaskGroup'));
			$this->assertTrue(self::$web->isClassActive('ExampleData'));
			$this->assertFalse(self::$web->isClassActive('TaskGroupDDDD'));
		}
		
		function test_errorMessage() {
			$user=Stub::make('User',[]);
			// empty cases
			$output=$this->captureOutput(self::$web,'errorMessage',[$user, '', false, false,  "/"]);
			$this->assertEmpty($output);
			$output=$this->captureOutput(self::$web,'errorMessage',[$user, 'user', true, false,  "/"]);
			$this->assertEmpty($output);
			
			// simple case
			$output=$this->captureOutput(self::$web,'errorMessage',[$user, 'user', false, false,  "/"]);
			$this->assertEquals($output,'::ERROR::Creating this user failed.::/');
			// change type and isUpdate
			$output=$this->captureOutput(self::$web,'errorMessage',[$user, 'frog', false, true,  "/"]);
			$this->assertEquals($output,'::ERROR::Updating this frog failed.::/');
			// add validation messages
			$output=$this->captureOutput(self::$web,'errorMessage',[$user, 'frog', ['invalid'=>['username'=>['cannot be empty'],'age'=>['must be older']]], true,  "/"]);
			$this->assertEquals($output,
				"::ERROR::Updating this frog failed because<br/><br/>\nUsername: cannot be empty <br/>\nAge: must be older <br/>\n::/"
			);
			
		}
		
		function test_getMimetype() {
			// from sample files in _data folder
			$this->assertEquals(self::$web->getMimetype('..'.DIRECTORY_SEPARATOR.'_data'.DIRECTORY_SEPARATOR.'test.txt'),'text/plain');
			$this->assertEquals(self::$web->getMimetype('..'.DIRECTORY_SEPARATOR.'_data'.DIRECTORY_SEPARATOR.'cat.jpg'),'image/jpeg');
		}


		function test_initDB() {
			self::$web->initDB();
			if (empty(self::$web->db)) {
				$this->fail('No database connection found');
			}
			self::$web->db=null;
			self::$web->initDB();
			if (empty(self::$web->db)) {
				$this->fail('No database connection found on second attempt');
			}
			
		}
		
		function test_localUrl() {
			$webRoot=self::$web->webroot();
			// with and without leading slash
			$this->assertEquals(self::$web->localUrl('tasks/edit/1'),$webRoot.'/tasks/edit/1');
			$this->assertEquals(self::$web->localUrl('/tasks/edit/1'),$webRoot.'/tasks/edit/1');
		}   
		
		function test_internalLink() {
			// generate link
			$this->assertEquals(self::$web->internalLink('Title','task','delete'),"<a href='http://localhost/task/delete'>Title</a>");
			// no access
			$this->assertNull(self::$web->internalLink('Title','admin','edit'));
		}
		
		
		function test_menuLink() {
			$links=[];
			// not allowed empty
			$this->assertFalse(self::$web->menuLink('admin/users/2','Admin User 2',$links));
			$expect="<a href='http://localhost/task/edit/2' >Edit Task 2</a>";
			$this->assertEquals(self::$web->menuLink('task/edit/2','Edit Task 2',$links),$expect);
			// check links array
			$this->assertEquals($links,[0=>'',1=>$expect]);
		}

		function test_menuButton() {
			$links=[];
			// not allowed empty
			$this->assertFalse(self::$web->menuButton('admin/users/2','Admin User 2',$links));
			$expect="<button class=\"button tiny \" onclick=\"parent.location='http://localhost/task/edit/2'; return false;\" >Edit Task 2</button>";
			//codecept_debug(self::$web->menuButton('task/edit/2','Edit Task 2'));
			$this->assertEquals(self::$web->menuButton('task/edit/2','Edit Task 2',$links),$expect);
			// check links array
			$this->assertEquals($links,[0=>'',1=>$expect]);
		} 
		
		function test_menuBox() {
			$links=[];
			// not allowed empty
			$this->assertFalse(self::$web->menuBox('admin/users/2','Admin User 2',$links));
			$expect="<a onclick=\"modal_history.push(&quot;http://localhost/task/edit/2?isbox=1&quot;); $(&quot;#cmfive-modal&quot;).foundation(&quot;reveal&quot;, &quot;open&quot;, &quot;http://localhost/task/edit/2?isbox=1&quot;);return false;\">Edit Task 2</a>";
			$this->assertEquals(self::$web->menuBox('task/edit/2','Edit Task 2',$links),$expect);
			// check links array
			$this->assertEquals($links,[0=>'',1=>$expect]);
		} 

		// WORKING 	
		function test_loadConfigurationFiles() {
			$dbUser=Config::get('database.username');
			//function test_scanModuleDirForConfigurationFiles($dir = "") {}
			// clear existing configuration
			$cachefile = ROOT_PATH . "/cache/config.cache";
			unlink($cachefile);
			foreach (\Config::keys(true) as $key) {
				\Config::set($key,NULL);
			}
			// now reload
			self::$web->loadConfigurationFiles();
			// and check
			if (!file_exists($cachefile)) {
				$this->fail('Cache file was not written');
			}
			// system config setting
			$this->assertEquals(\Config::get('testing.base'),'fred');
			// system module config setting
			$this->assertEquals(\Config::get('systestmodule.testing'),'fred');
			// module config setting
			$this->assertEquals(\Config::get('testmodule.testing'),'fred');
			// site config setting
			$dbUser=Config::get('database.username');
			$this->assertTrue(strlen(\Config::get('database.username'))>0);
			// check cache loading
			// change value then reload and check value reverted
			\Config::set('database.username','fred');
			self::$web->loadConfigurationFiles();
			$this->assertEquals(\Config::get('database.username'),$dbUser);
		}

		function test_validateCSRF() {
			// disabled
			global $_SESSION;
			global $_POST;
			\Config::set('system.csrf.enabled',false);
			$output=$this->captureOutput(self::$web,'validateCSRF');
			$this->assertEquals($output,'');
			// csrf is valid
			\Config::set('system.csrf.enabled',true);
			self::$web->_requestMethod='get';
			$output=$this->captureOutput(self::$web,'validateCSRF');
			$this->assertEquals($output,'');
			self::$web->_requestMethod='notget';
			// csrf is invalid
			// not in history
			//$_SESSION[\CSRF::getTokenHistoryName()]=['a'=>'b'];
			//$_POST['a']='e';
			try {
				unset($_SESSION[\CSRF::getTokenHistoryName()]);
				self::$web->validateCSRF();
				$this->fail('Failed to throw exception for invalid csrf with no history available');
			} catch (\CSRFException $e) {
				// all good
			}
			// in history
			$_SESSION[\CSRF::getTokenHistoryName()]=['a'=>'b'];
			$_POST['a']='b';
			$output=$this->captureOutput(self::$web,'validateCSRF');
			//codecept_debug($output);
			$this->assertEquals($output,'::MESSAGE::Duplicate form submission detected, make sure you only click buttons once::');
			
		}
		
		function test_getTemplateRealFilename() {
			$ext=self::$web->_templateExtension;
			$this->assertEquals(self::$web->getTemplateRealFilename('fred'),'fred'.$ext);
		}
		
		function test_setLayout() {
			$l='fred';
		//	self::$web->setLayout($l);
		//	$this->assertEquals(self::$web->getLayout(),$l);
		}
		function test_setTemplate() {
			$old=self::$web->getTemplate();
			$l='fred';
			//self::$web->setTemplate($l);
			//$this->assertEquals(self::$web->getTemplate(),$l);
			self::$web->setTemplate($old);
		}
		function test_setTemplatePath() {
			$old=self::$web->_templatePath;
			$l='fred';
			self::$web->setTemplatePath($l);
			$this->assertEquals(self::$web->_templatePath,$l);
			self::$web->setTemplatePath($old);
		}
		function test_setTemplateExtension() {
			$old=self::$web->_templateExtension;
			$l='fred';
			self::$web->setTemplateExtension($l);
			$this->assertEquals(self::$web->_templateExtension,$l);
			self::$web->_templateExtension=$old;
		}
		


			
		function test_templateExists() {
			$this->createTestTemplateFiles();
			self::$web->_module='testmodule';
			self::$web->_submodule='';
			self::$web->_action='';
			self::$web->_actionMethod='';
			
			$this->assertNull(self::$web->templateExists('thisIsATemplateNameThatWillNeverExist'));
			
			// tests based on explicit template parameter to templateExists
			// remove found templates and search again to test priority
			$findPath='modules/testmodule/templates/testtemplate';
			//$findPath='templates/testmodule/testtemplate';
			$this->assertEquals(self::$web->templateExists('testtemplate'),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			//codecept_debug(self::$web->templateExists('testtemplate'));
			$findPath='modules/testmodule/testtemplate';
			$this->assertEquals(self::$web->templateExists('testtemplate'),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			$findPath='templates/testmodule/testtemplate';
			$this->assertEquals(self::$web->templateExists('testtemplate'),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			//codecept_debug(self::$web->templateExists('testtemplate'));
			
			$findPath='templates/testtemplate';
			$this->assertEquals(self::$web->templateExists('testtemplate'),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			//codecept_debug(self::$web->templateExists('testtemplate'));
			
			$findPath=ROOT_PATH."/".'system/templates/testtemplate';
			$this->assertEquals(self::$web->templateExists('testtemplate'),$findPath);
			unlink(self::$web->getTemplateRealFilename($findPath));
			//codecept_debug(self::$web->templateExists('testtemplate'));
			// NO MORE MATCHING TEMPLATES LEFT
			$this->assertNull(self::$web->templateExists('testtemplate'));
			
			// tests based on internal values for self::$web->_submodule, self::$web->_action, self::$web->_actionMethod
			self::$web->_submodule='submodule';
			self::$web->_action='edit';
			self::$web->_actionMethod='get';
			
			// first thing found is actionMethod
			// modules/testmodule/templates
			$findPath='modules/testmodule/templates/submodule/get';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			$findPath='modules/testmodule/templates/submodule/edit';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			$findPath='modules/testmodule/templates/submodule/submodule';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			$findPath='modules/testmodule/templates/get';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			$findPath='modules/testmodule/templates/edit';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			$findPath='modules/testmodule/templates/submodule';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			self::$web->_submodule='';
			$findPath='modules/testmodule/templates/testmodule';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			self::$web->_submodule='submodule';
			
			
			// modules/testmodule
			$findPath='modules/testmodule/get';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			$findPath='modules/testmodule/edit';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			$findPath='modules/testmodule/submodule';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			self::$web->_submodule='';
			$findPath='modules/testmodule/testmodule';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			self::$web->_submodule='submodule';

			// templates/testmodule
			$findPath='templates/testmodule/get';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			$findPath='templates/testmodule/edit';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			$findPath='templates/testmodule/submodule';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			self::$web->_submodule='';
			$findPath='templates/testmodule/testmodule';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			self::$web->_submodule='submodule';

			// templates/
			$findPath='templates/get';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			$findPath='templates/edit';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			$findPath='templates/submodule';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			
			self::$web->_submodule='';
			$findPath='templates/testmodule';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename(ROOT_PATH."/".$findPath));
			self::$web->_submodule='submodule';

			// system/templates
			$findPath=ROOT_PATH."/".'system/templates/get';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename($findPath));
			
			$findPath=ROOT_PATH."/".'system/templates/edit';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename($findPath));
			
			$findPath=ROOT_PATH."/".'system/templates/submodule';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename($findPath));
			
			self::$web->_submodule='';
			$findPath=ROOT_PATH."/".'system/templates/testmodule';
			$this->assertEquals(self::$web->templateExists(''),$findPath);
			unlink(self::$web->getTemplateRealFilename($findPath));
			self::$web->_submodule='submodule';

			
			// no matches left
			$this->assertNull(self::$web->templateExists(''));
			
			
			$this->removeTestTemplateFiles();
		}

		
		function test_fetchTemplate() {
			//function test_putTemplate($key, $template) {}
			//function test_templateOut() {}
			self::$web->_module='testmodule';
			self::$web->_submodule='';
			self::$web->_action='';
			self::$web->_actionMethod='';
			self::$web->ctx('a','avalue');
			// write template
			file_put_contents(ROOT_PATH."/modules/testmodule/editme.tpl.php",':::TEMPLATE:::<'.'?php echo $a; ?'.'>:::');
			// fetchTemplate
			$this->assertEquals(self::$web->fetchTemplate('editme'),':::TEMPLATE:::avalue:::');
			// putTemplate
			self::$web->putTemplate('editkey','editme');
			$this->assertEquals(self::$web->ctx('editkey'),':::TEMPLATE:::avalue:::');
			// templateOut
			self::$web->templateOut('editme');
			$this->assertEquals(self::$web->_buffer,':::TEMPLATE:::avalue:::');
		}
		
		
		function test_out() {
			$buffer=self::$web->_buffer;
			self::$web->out('testoutput');
			$this->assertEquals(self::$web->_buffer,$buffer.'testoutput');
		}
			
		
		function test_WebTemplate() {
			file_put_contents(ROOT_PATH."/modules/testmodule/edityou.tpl.php",':::TEMPLATE:::<'.'?php echo $a.":::".(!empty($b) ? $b : "nob").":::".$c.":::"; ?'.'>');
			$t=new \WebTemplate();
			$t->set('a','aval');
			$t->set_vars(['a'=>'avalue','b'=>'bvalue','c'=>'cvalue']);
			$this->assertEquals($t->fetch(ROOT_PATH."/modules/testmodule/edityou.tpl.php"),":::TEMPLATE:::avalue:::bvalue:::cvalue:::");
			$t->set_vars(['a'=>'avalue','c'=>'cvalue'],true);
			$this->assertEquals($t->fetch(ROOT_PATH."/modules/testmodule/edityou.tpl.php"),":::TEMPLATE:::avalue:::nob:::cvalue:::");
		}
		

		// stubs ?? WITH STUB::once,exactly
		function test_callHook() {
			// write hooks file
			file_put_contents(ROOT_PATH."/system/modules/systestmodule/systestmodule.hooks.php",'<'.'?php function systestmodule_systestmodule_dostuff($w,$a) {echo ":::".$w->_module.":::".$a.":::stuff done";}');
			
			ob_start();
			// check that self::$web is available inside hook function
			self::$web->_module='notamodule';
			// call hook
			self::$web->callHook('systestmodule','dostuff','fred');
			$content=ob_get_contents();
			ob_end_clean();
			$this->assertEquals($content,":::notamodule:::fred:::stuff done");
			
		}
		function test_callWebHooks() {
			self::$web->_module='testmodule';
			self::$web->_submodule='submodule';
			self::$web->_action='sleep';
			self::$web->_requestMethod='ping';
			self::$web->initDB();
			$content='<'.'?php';
			foreach ([
			'testmodule_core_web_testhooks',
			'testmodule_core_web_testhooks_ping',
			'testmodule_core_web_testhooks_ping_testmodule',
			'testmodule_core_web_testhooks_ping_testmodule_submodule',
			'testmodule_core_web_testhooks_ping_testmodule_submodule_sleep',
			'testmodule_core_web_testhooks_ping_testmodule_sleep',
			] as $hookFunction) {
				$content.=' function '.$hookFunction.'($w,$a) {echo ":::HOOK:::'.$hookFunction.':::"; }'."\n";
			}
			file_put_contents(ROOT_PATH."/system/modules/systestmodule/systestmodule.hooks.php",$content);
			$output=$this->captureOutput(self::$web,'_callWebHooks',['testhooks']);
			self::$web->_submodule='';
			$output.=$this->captureOutput(self::$web,'_callWebHooks',['testhooks']);
			$this->assertEquals($output,':::HOOK:::testmodule_core_web_testhooks::::::HOOK:::testmodule_core_web_testhooks_ping::::::HOOK:::testmodule_core_web_testhooks_ping_testmodule::::::HOOK:::testmodule_core_web_testhooks_ping_testmodule_submodule::::::HOOK:::testmodule_core_web_testhooks_ping_testmodule_submodule_sleep::::::HOOK:::testmodule_core_web_testhooks::::::HOOK:::testmodule_core_web_testhooks_ping::::::HOOK:::testmodule_core_web_testhooks_ping_testmodule::::::HOOK:::testmodule_core_web_testhooks_ping_testmodule_sleep:::');
		}
		  
		
		function test_cmp_weights() {}
		function test_install() {}
		
		function test_partial() {
			self::$web->_partialsdir='mypartials';
			$ctx=self::$web->_context;
			$buffer=self::$web->_buffer;
			$output=self::$web->partial('testpartial',['paramsvalue'=>'eek'],'testmodule');
			$this->assertEquals($output,'testpartial:::thepartialvalueeek:::');
			$this->assertEquals($ctx,self::$web->_context);
			$this->assertEquals($buffer,self::$web->_buffer);
		}
		
		function test_start() {}

			
	}  // class
}  // namespace
