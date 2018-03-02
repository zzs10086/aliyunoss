<?php
/**
 * This is a PDO exntend class
 * @author zhaorongwei
 * @date 2014-11-15
 * require public functions file
 */
class WYXPDO extends PDO
{
	private $charset = "UTF8";
	private static $instance = array();
	public $debug = TRUE;

	/**
	 *pdo扩展构造函数
	 @param array $dbconfig 数据库初始化
	*/
	public function __construct(array $dbconfig)
	{
		$dsn = 'mysql:host=' . $dbconfig['dbhost'] . ';dbname=' . $dbconfig['dbname'];
		parent::__construct($dsn, $dbconfig['username'], $dbconfig['password']);
		parent::exec('SET NAMES '.($dbconfig['charset']?$dbconfig['charset']:$this->charset));
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); //SQL错误抛异常
	}

	/**
	 * Get a instance of MyPDO
	 *
	 * @param string $key
	 * @return object
	 */
    static function getInstance($key)
    {
		if(!isset(self::$instance[$key]) || !self::$instance[$key])
	   	{
			$config=load_dbconfig($key);
			if(!$config)
			{
				debug_log('数据库配置信息不能为空','dberror');	
				return false;
			}
			self::$instance[$key] = new self($config);
		}
		return self::$instance[$key];
    }

	/**
	* 获取多行数据
	* @param string $sql
	* @return array
	*/
	public function getResults($sql, $type =PDO::FETCH_ASSOC)
	{
		return $this->doQuery($sql,'all',$type);
	}

	/**
	 * 获取一行数据
	 * @return mixed
	 */
	public function getRow($sql,$type=PDO::FETCH_ASSOC)
	{
		return $this->doQuery($sql,'row',$type);
	}

	public function getVar($sql,$col=0)
	{
		$row=$this->doQuery($sql,'row',PDO::FETCH_NUM);
		return $row[$col];
	}

	/**
	 * insert data
	 *
	 * @param	string	table name
	 * @param	array	cols and values
	 * @return	last_insert_id or flase
	 */
	public function insert($table, array $input, $method = 'INSERT')
	{
		$cols_name  = array();
		$cols_value = array();

		foreach ($input as $key=>$value) 
		{
			$cols_name[]  = '`'.$key.'`';
			$cols_value[] = ':' . $key;
		}

		$sql = $method . ' ' . $table . ' (' . implode(',', $cols_name) . ') ' . 'VALUES (' . implode(',', $cols_value) . ') ';
		

		try
	   	{
			$sth = $this->prepare($sql);
			if ( !$sth->execute($input) ) 
			{
				$errInfo = $sth->errorInfo();
				throw new PDOException($errInfo[2]."\tsql=".$sth->queryString, $errInfo[1]);
			}
            return $this->lastInsertId();
		} 
		catch (PDOException $e)
	   	{
			if ($this->debug) 
			{
				debug_log(sprintf("error:%s\n%s",$e->getMessage(),$e->getTraceAsString()),'dberror');
			}
			return false;
		}
	}

	/**
	 * delete data
	 *
	 * @param	string	table name
	 * @param	array|string	cols and values
	 * @return	boolean
	 */
	public function delete($table, $input='')
	{
		//不加条件直接删除返回false，防止误操作
		if(!$input)
			return false;

		if(is_array($input)) 
		{
			$where_cols = array();
			foreach ($input as $key=>$value) {
				$where_cols[] = $key . ' = :' . $key;
			}
			$where = ' WHERE ' . implode(' AND ', $where_cols);
		}
		else
		{
			$where=' WHERE '.$input;
		}

		$sql = 'DELETE FROM ' . $table . $where;
       
		try
	   	{
			$sth = $this->prepare($sql);
			$tmp=(is_array($input)?$sth->execute($input):$sth->execute());
			if ( !$tmp )
		   	{
				$errInfo = $sth->errorInfo();
				throw new PDOException($errInfo[2]."sql=".$sth->queryString, $errInfo[1]);
			}
            return true;
		} 
		catch (PDOException $e)
	   	{
			if ($this->debug)
		   	{
				debug_log(sprintf("error:%s\n%s",$e->getMessage(),$e->getTraceAsString()),'dberror');
			}
			return false;
		}
	}

	/**
	 * update data
	 *
	 * @param	string	table name
	 * @param	array|string	update cols and values
	 * @param	array	where cols and values
	 * @return	boolean
	 */
	public function update($table, $input = array(), $condition = array())
	{
		if(!$input || !$condition)
			return false;

		if(is_array($input))
		{
			//update cols
			$set_cols = array();
			foreach ($input as $key=>$value) {
				$set_cols[] = "`" . $key . "`" . ' = :' . $key;
			}
			$set = implode(',', $set_cols);
		}
		else
		{
			$set=$input;
		}

		//where cols
		if (is_array($condition))
	   	{
			$where_cols = array();
			foreach ($condition as $key=>$value)
		   	{
				$where_cols[] = "`" . $key . "`" . "= '" . $value . "'";
			}
			$where = ' WHERE ' . implode(' AND ', $where_cols);
		}
		else
	   	{
			$where=" WHERE ".$condition;
		}

		$sql = 'UPDATE ' . $table . ' SET ' . $set . $where;

		try 
		{
			$sth = $this->prepare($sql);
			$tmp=is_array($input)?$sth->execute($input):$sth->execute();
			if ( !$tmp )
		   	{
				$errInfo = $sth->errorInfo();
				throw new PDOException($errInfo[2]."\tsql=".$sth->queryString, $errInfo[1]);
			}
            return true;
		} 
		catch (PDOException $e)
	   	{
			if ($this->debug)
		   	{
				debug_log(sprintf("error:%s\n%s",$e->getMessage(),$e->getTraceAsString()),'dberror');
			}
			return false;
		}
	}

	/**
	 * 组合条件的数据查询
	 * @param string $table 数据表
	 * @param string|array $field 需要查询的字段,字符串或者数组
	 * @param string|array  $input 查询条件
	 * @param string|array $order 排序条件
	 * @param int $offset 数据偏移量
	 * @param int $limit 取数据条数
	 * @return array
	 */
	public function select($table,$field='*',$input='', $order = '', $offset = NULL, $limit = NULL)
	{
		$where='';
		if (!empty($input)) 
		{
			if(is_array($input))
			{
				$where_cols = array();
				foreach ($input as $key=>$value) 
				{
					$where_cols[] = '`'. $key . '`' . ' = :' . $key;
				}
				$where = ' WHERE ' . implode(' AND ', $where_cols);
			}
			else
			{
				$where=' WHERE '.$input ;
			}
		} 

		$orderby='';
		if($order)
		{
			if(is_array($order))
			{
				$orderby=' ORDER BY '.join(',',$order);
			}
			else
			{
				$orderby=' ORDER BY '.$order;
			}
		}
		$limitstr='';
		if($limit>0)
		{
			$limitstr=" LIMIT ".abs(intval($offset)).",".intval($limit);
		}

		$sql="SELECT ".($field && is_array($field)?join(',',$field):($field?$field:'*'))." FROM ".$table.$where.$orderby.$limitstr;

		try 
		{
			$sth = $this->prepare($sql);
			$flag=is_array($input)?$sth->execute($input):$sth->execute();	
			if(!$flag )
		   	{
				$errInfo = $sth->errorInfo();
				throw new PDOException($errInfo[2]."\tsql=".$sth->queryString, $errInfo[1]);
			}
            return $sth->fetchAll(PDO::FETCH_ASSOC);
		}
		catch (PDOException $e)
	   	{
			if ($this->debug)
		   	{
				debug_log(sprintf("error:%s\n%s",$e->getMessage(),$e->getTraceAsString()),'dberror');
			}
			return false;
		}
	}	

	/**
	 *执行查询操作
	 *@param string $sql sql语句
	 *@param string $ftype 执行类型,all:全部数据,one:一条记录
	 *@return mixed
	 */
	private function doQuery($sql,$ftype='all',$type =PDO::FETCH_ASSOC)
	{
		$result = false;
		try 
		{
			$pre = $this->prepare(trim($sql));
			if ( !is_object($pre) )
		   	{
				throw new PDOException('SQL语句预处理失败:'.$sql);
			}

			if ( !$pre->execute() )
		   	{
				throw new PDOException('SQL语句执行失败:'. $sql);
			}

			$type = !empty($type) ? $type : PDO::FETCH_ASSOC;
			if($ftype=='all')
			{
				$result=$pre->fetchAll($type);
			}
			else
			{
				$result=$pre->fetch($type);
			}
			return $result?$result:false;
		}
	   	catch (PDOException $e)
	    {
			if ($this->debug)
		   	{
				debug_log(sprintf("error:%s\n%s",$e->getMessage(),$e->getTraceAsString()),'dberror');
			}
		}
	}

	/**
	 * 增加字段的值,可同时操作多个字段
	 * @param string $table
	 * @param string|array $field 需要增加的字段名
	 * @param where string|array 增加条件
	 * example:
	 * $this->incVal('test','a','id=1');
	 * $this->incVal('test','a,b','id=1');
	 * $this->incVal('test',array('a'=>2),'id=1');
	 * $this->incVal('test',array('a'=>2),array('id'=>1));
	 */
	public function incVal($table,$field,$where)
	{
		if(!$table || !$field || !$where)
			return false;
		$sql="UPDATE ".$table." SET ";

		if(is_string($field))
		{
			$tmp=explode(',',$field);
			$field=array();
			foreach($tmp as $v)
			{
				$field[$v]=1;	
			}
		}

		$tmp=array();
		foreach($field as $k=>$v)
		{
			$tmp[]="`".$k."`=`".$k."`+".(!$v?1:$v);	
		}
		$sql.=join(',',$tmp);

		if(is_array($where))
		{
			$tmp=array();
			foreach($where as $k=>$v)
			{
				$tmp[]="`".$k."`=".$v;
			}
			$sql.=" WHERE ".join(' AND ',$tmp);
		}
		else
		{
			$sql.=" WHERE ".$where;
		}
		try
		{
			return $this->exec($sql);
		}
		catch(PDOException $e)
		{
			if ($this->debug)
			{
				debug_log("sql error:".$sql,'dberror');
			}
			return false;
		}
	}

	/**
	 * 约束更新操作只能用exec
	 */
	public function query($sql)
	{
		if(!$sql || $this->isWrite($sql))
		{
			return false;
		}
		return parent::query($sql);
	}

	public function isWrite($sql)
	{
		if ( ! preg_match('/^\s*"?(SET|INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|LOAD DATA|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK)\s+/i', $sql))
		{
			return false;
		}
		return true;
	}
}

