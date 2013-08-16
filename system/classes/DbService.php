<?php
/**
 * General Service class for subclassing in modules.
 *
 * @author carsten
 *
 */
class DbService {
	var $_db;
	var $w;

	/**
	 * array for automatic caching of objects for the duration of this
	 * invocation.
	 *
	 * @var <type>
	 */
	private static $_cache = array(); // used for single objects
	private static $_cache2 = array();  // used for lists of objects

	/**
	 * This variable keeps track of active transactions
	 *
	 * @var boolean
	*/
	private static $_active_trx = false;

	/**
	 * set this variable to false if you don't want
	 * the getObject, getObjects, etc. to use the
	 * cache.
	 *
	 * @var <type>
	 */
	private $__use_cache = true;

	public function __get($name) {
		return $this->w->$name;
	}

	function __construct(Web $w) {
		$this->_db = $w->db;
		$this->w = $w;
	}

	/**
	 * Formats a timestamp
	 * per default MySQL datetime format is used
	 *
	 * @param $time
	 * @param $format
	 */
	function time2Dt($time=null,$format='Y-m-d H:i:s') {
		return formatDate($time ? $time : time(),$format,false);
	}

	/**
	 * Formats a timestamp
	 * per default MySQL date format is used
	 *
	 * @param $time
	 * @param $format
	 */
	function time2D($time=null,$format='Y-m-d') {
		return formatDate($time ? $time : time(),$format,false);
	}

	function time2T($time=null,$format='H:i:s') {
		return date($format,$time ? $time : time());
	}

	function dt2Time($dt) {
		return strtotime(str_replace("/","-",$dt));
	}

	function d2Time($d) {
		return strtotime(str_replace("/","-",$d));
	}

	function t2Time($d) {
		return strtotime(str_replace("/","-",$d));
	}

	/**
	 * Clear object cache completely!
	 *
	 */
	function clearCache() {
		self::$_cache = array();
		self::$_cache2 = array();
	}
	/**
	 * Fetch one object either by id
	 * or by passing an array of key,value
	 * to be used in a where condition
	 *
	 * @param <type> $table
	 * @param <type> $idOrWhere
	 * @return <type>
	 */
	function & getObject($class,$idOrWhere) {
		if (!$idOrWhere || !$class) return null;

		$key = $idOrWhere;
		if (is_array($idOrWhere)) {
			$key = "";
			foreach ($idOrWhere as $k=>$v) {
				$key.=$k."::".$v."::";
			}
		}
		$usecache = $this->__use_cache && is_scalar($key);
		// check if we should use the cache
		// this will eliminate 80% of SQL calls per page view!
		if ($usecache) {
			$obj = self::$_cache[$class][$key];
			if ($obj) {
				//$this->w->logDebug("cached object:".$class.", ".$idOrWhere);
				return $obj;
			}
		}

		// not using cache or object not found in cache
		//$this->w->logDebug("load object:".$class.", ".$idOrWhere);

		$o = new $class($this->w);
		$table = $o->getDbTableName();
		if (is_scalar($idOrWhere)) {
			$result = $this->_db->get($table)->where('id',$idOrWhere)->fetch_row();
		} elseif (is_array($idOrWhere)) {
			$result = $this->_db->get($table)->where($idOrWhere)->fetch_row();
		}
		if ($result) {
			$obj = $this->getObjectFromRow($class, $result);
			if ($usecache) {
				self::$_cache[$class][$key]=$obj;
				if ($obj->id != $key && !self::$_cache[$class][$obj->id]) {
					self::$_cache[$class][$obj->id]=$obj;
				}
			}
			return $obj;
		} else {
			return null;
		}
	}

	/**
	 *
	 * @param <type> $table
	 * @param <type> $id
	 * @return <type>
	 */
	function & getObjects($class,$where=null,$useCache = false) {
		if (!$class) return null;// !$where || will return null as developers is assume ommiting the where clause will give them everything

		// create a cache key just in case
		if (is_array($where)) {
			foreach ($where as $k=>$v) {
				$key.=$k."::".$v."::";
			}
		} else {
			$key = $where;
		}

		if ($useCache && self::$_cache2[$class][$key]) {
			return self::$_cache2[$class][$key];
		}

		$o = new $class($this->w);
		$table = $o->getDbTableName();
		$this->_db->get($table);
		if ($where && is_array($where)) {
			foreach ($where as $par => $val) {
				$dbwhere[$o->getDbColumnName($par)]=$val;
			}
			$this->_db->where($dbwhere);
		} else if ($where && is_scalar($where)) {
			$this->_db->where($where,false);			//if no $where, only $this->_db->get($get)->fetch_all() will be called
		}
		$result = $this->_db->fetch_all();
		if ($result) {
			$objects = $this->fillObjects($class, $result);
			if ($objects && $this->__use_cache) {
				 
				// only store the full list if requested
				if ($useCache) {

					if (!self::$_cache2[$class][$key]) {
						self::$_cache2[$class][$key] = $objects;
					}
				}
				 
				// always store individual objects in cache
				foreach ($objects as $ob) {
					if (!self::$_cache[$class][$ob->id]) {
						self::$_cache[$class][$ob->id] = $ob;
					}
				}
			}
			return $objects;
		} else {
			return null;
		}
	}

	/**
	 *
	 * @param <type> $table
	 * @param <type> $id
	 * @return <type>
	 */
	function & getObjectFromRow($class,$row) {
		if (!$row || !$class) return null;
		$o = new $class($this->w);
		$o->fill($row);
		return $o;
	}

	function & fillObjects($class, $rows) {
		$list = array();
		if ($class && $rows && class_exists($class)) {
			foreach($rows as $row) {
				$o = new $class($this->w);
				$o->fill($row);
				$list[]=$o;
			}
		}
		return $list;
	}

	/**
	 * Start a transaction
	 *
	 */
	function startTransaction() {
		$this->_db->sql("START TRANSACTION")->execute();
		self::$_active_trx = true;
	}

	/**
	 * Commit a transaction
	 *
	 */
	function commitTransaction() {
		$this->_db->sql("COMMIT")->execute();
		self::$_active_trx = false;
	}

	/**
	 * Rollback a transaction!
	 * This includes a clear_sql()!
	 */
	function rollbackTransaction() {
		$this->_db->clear_sql();
		$this->_db->sql("ROLLBACK")->execute();
		self::$_active_trx = false;
	}

	/**
	 * Returns true if a transaction is currently active!
	 *
	 */
	function isActiveTransaction() {
		return self::$_active_trx;
	}


	function lookupArray($type) {
		$rows = $this->_db->select("code,title")->from("lookup")->where("type",$type)->fetch_all();
		foreach ($rows as $row) {
			$select[$row['code']]=$row['title'];
		}
		return $select;
	}

}
