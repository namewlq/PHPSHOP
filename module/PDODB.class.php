<?php
/**
 * Name: PDO封装类,封装数据库底层的操作,暂只支持mysql
 * Date: 2016-9-16 21:48
 * Log:  主要为sql语句的过滤,防止SQL注入
 */

class PDODB
{
	private $PDODBName;
	private $PDODBPort;
	private $PDODBUser;
	private $PDODBPassword;
	private $Host;
	public $PDO;
	private $sql;
	public  $tableName = '';
	public  $dbName;	//留待扩展
	public  $serverID;	//留待扩展
	private $errorCode = array(
				'0' => 'success',
				'1' => 'table name is null!',
				'2' => 'connect failed',
				'3' => 'handle sql failed'
			);
	
	/**
	 * 构造函数,初始化PDO参数
	 * @param string $host
	 * @param string $port
	 * @param string $DBName
	 * @param string $DBUser
	 * @param string $DBPassword
	 */
	public function __construct($host, $port, $DBName, $DBUser, $DBPassword)
	{
		$this->Host = $host;
		$this->PDODBPort = $port;
		$this->PDODBName = $DBName;
		$this->PDODBPassword = $DBPassword;
		$this->PDODBUser = $DBUser;
		$this->connect();
	}
	
	public function setTableName($tableName)
	{
		$this->tableName = $tableName;
	}
	
	public function setDbName($dbName)
	{
		$this->dbName = $dbName;
	}
	
	public function setServerID($id)
	{
		$this->serverID = $id;
	}
	/**
	 * 返回PDO类
	 */
	private function connect()
	{
		try
		{
			$options = array(
					PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"',
					PDO::ATTR_EMULATE_PREPARES => false,
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
			);
			$this->PDO = new PDO("mysql:dbname={$this->PDODBName};host={$this->Host};port={$this->PDODBPort};charset=utf8",
							$this->PDODBUser,
							$this->PDODBPassword,
							$options
						);
		}
		catch (PDOException $e)
		{
			//此处需要记录日子,暂缓
			var_dump(array('result'=>2, 'message'=>'connention failed: ' . $e->getMessage()));
			exit();
		}
	}
	
	/**
	 * 关闭PDO
	 */
	public function close()
	{
		$this->PDO = null;
	}
	
	/**
	 * 执行sql语句查询
	 * @param strin $sql
	 * @param array $parameters
	 * @param int $fetchmode
	 * @return boolean or array
	 */
	public function query($sql, $parameters = null, $fetchmode = PDO::FETCH_ASSOC)
	{
		$sql = trim($sql);
		$this->handle($sql, $parameters);
		$sqlArr = explode(' ', $sql);
		$firstWord = strtolower($sqlArr[0]);
		if ( $firstWord == 'select' )
		{
			return $this->sql->fetchAll($fetchmode);
		}
		else if ( $firstWord == 'insert' || $firstWord == 'update' || $firstWord == 'delete')
		{
			return $this->sql->rowCount();
		}
		else
		{
			return array('result'=>4, 'message'=>'sql error');
		}
	}
	
	/**
	 * 处理sql语句,将参数绑定到sql语句中,防止SQL注入
	 * @param string $sql
	 * @param array $parameters
	 */
	public function handle($sql, $parameters = '')
	{
		if (!$this->PDO)
		{
			$this->connect();
		}
		try 
		{
			$this->sql = $this->PDO->prepare($sql);
			if ( !empty($parameters) && is_array($parameters) )
			{
				$indexArray = false;
				if ( array_key_exists(0, $parameters) )
				{
					$indexArray = true;
					array_unshift($parameters, 0);
					unset($parameters[0]);
				}
				foreach ($parameters as $key => $value)
				{
					$this->sql->bindValue($indexArray ? intval($key) : ":{$key}", $value);
				}
			}
			$this->sql->execute();
		}
		catch (PDOException $e)
		{
			return array('result'=>3, 'message'=>'sql prepare failed: ' . $e->getMessage() . ' params: ' . serialize($parameters));
			exit();
		}
	}
	
	public function lastInsertId($name = NULL)
	{
		return $this->PDO->lastInsertId($name);
	}
	
	/**
	 * 获取一条记录,需要先设置好表名
	 * @param array $where
	 * @param string $fields
	 * @param string $sort
	 * @param int $fetchMode
	 * @return array
	 */
	public function find($where = array(), $fields = '*', $sort = '', $fetchMode = PDO::FETCH_ASSOC)
	{
		if ( $this->tableName )
		{
			$sort = $sort ? ' ORDER BY ' . mysql_escape_string($sort) : '';
			$whereStr = ' WHERE 1 ';
			foreach ($where as $k=>$v)
			{
				$k = mysql_escape_string($k);
				$whereStr .= " AND {$k}=:{$k} ";
			}
			$sql = "SELECT {$fields} FROM {$this->tableName} {$whereStr} {$sort} LIMIT 1";
			$this->handle($sql, $where);
			return $this->sql->fetch($fetchMode);
		}
		return array('result'=>1, 'message'=>$this->errorCode[1]);
	}
	
	/**
	 * 返回指定的记录条数
	 * @param array $where
	 * @param string $fields
	 * @param string $sort
	 * @param string $limit
	 * @param int $fetchMode
	 * @return array
	 */
	public function findAll($where = array(), $fields = '*', $sort = '', $limit = '', $fetchMode = PDO::FETCH_ASSOC)
	{
		if ( $this->tableName )
		{
			$fields = mysql_escape_string($fields);
			$sort = $sort ? ' ORDER BY ' . mysql_escape_string($sort) : '';
			if ( preg_match('/\d+,\d+/', $limit) )
			{
				$tmp = explode(',', $limit);
				$limitStr = ' LIMIT ' . intval($tmp[0]) . ',' . intval($tmp[1]);
			}
			else
			{
				$limitStr = ' LIMIT 0,20 ';
			}
			$whereStr = ' WHERE 1 ';
			foreach ($where as $k=>$v)
			{
				$k = mysql_escape_string($k);
				$whereStr .= " {$k}=:{$k} ";
			}
			$sql = "SELECT {$fields} FROM {$this->tableName} {$whereStr} {$sort} {$limitStr}";
			$this->handle($sql, $where);
			return $this->sql->fetchAll($fetchMode);
		}
		return array('result'=>1, 'message'=>$this->errorCode[1]);
	}
	
	/**
	 * 获取结果集的统计值
	 * @param array $where
	 * @param string $field
	 * @return array or int
	 */
	public function findCount($where = array(), $field = '*')
	{
		if ( $this->tableName )
		{
			$field = mysql_escape_string($field);
			$whereStr = ' WHERE 1 ';
			foreach ($where as $k=>$v)
			{
				$k = mysql_escape_string($k);
				$whereStr .= " {$k}=:{$k} ";
			}
			$sql = "SELECT COUNT({$field}) as findCount FROM {$this->tableName} {$whereStr}";
			$this->handle($sql, $whereStr);
			$count = $this->sql->fetch(PDO::FETCH_ASSOC);
			$this->sql->closeCursor();
			return $count['findCount'];
		}
		else
		{
			return array('result'=>1, 'message'=>$this->errorCode[1]);
		}
	}
}