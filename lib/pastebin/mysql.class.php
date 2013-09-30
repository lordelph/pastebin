<?php
/**
 * $Project: Pastebin $
 * $Id: pastebin.php,v 1.3 2006/04/27 16:21:10 paul Exp $
 * 
 * Pastebin Collaboration Tool
 * http://pastebin.com/
 *
 * This file copyright (C) 2006 Paul Dixon (paul@elphin.com)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the Affero General Public License 
 * Version 1 or any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * Affero General Public License for more details.
 * 
 * You should have received a copy of the Affero General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
 

class MySQL
{
	var $dblink=null;
	var $dbresult;
	
	/**
	* Constructor - establishes DB connection
	*/
	function MySQL()
	{
	}
	
	function _connect()
	{
		global $CONF;
		$this->dblink=mysql_pconnect(
			$CONF["dbhost"],
			$CONF["dbuser"],
			$CONF["dbpass"])
			or die("Unable to connect to database");
	
		mysql_select_db($CONF["dbname"], $this->dblink)
			or die("Unable to select database {$GLOBALS[dbname]}");
	}
	
	/**
	* execute query - show be regarded as private to insulate the rest of
	* the application from sql differences
	* @access private
	*/
	function query($sql)
	{
		global $CONF;
		
		if (is_null($this->dblink))
			$this->_connect();
		
		
		//been passed more parameters? do some smart replacement
		if (func_num_args() > 1)
		{
			//query contains ? placeholders, but it's possible the
			//replacement string have ? in too, so we replace them in
			//our sql with something more unique
			$q=md5(uniqid(rand(), true));
			$sql=str_replace('?', $q, $sql);
			
			$args=func_get_args();
			for ($i=1; $i<=count($args); $i++)
			{
				$sql=preg_replace("/$q/", "'".preg_quote(mysql_real_escape_string($args[$i]))."'", $sql,1);
				
			}
		
			//we shouldn't have any $q left, but it will help debugging if we change them back!
			$sql=str_replace($q, '?', $sql);
		}
		
		if ($CONF["maintainer_mode"])
		{
			list($usec, $sec) = explode(" ", microtime()); 
			$tStart=((float)$usec + (float)$sec); 
		}

		$this->dbresult=mysql_query($sql, $this->dblink);
		if (!$this->dbresult)
		{
			die("Query failure: ".mysql_error()."<br />$sql");
		}
		
		if ($CONF["maintainer_mode"])
		{
			list($usec, $sec) = explode(" ", microtime()); 
			$tElapsed=((float)$usec + (float)$sec) - $tStart; 
			
			global $_queries;
			$q=array();
			$q['sql']=$sql;
			$q['time']=$tElapsed;
			$_queries[]=$q;
		}

		

		return $this->dbresult;
	}
	
	static function dumpDiagnostics()
	{
		global $CONF;
		if ($CONF["maintainer_mode"])
		{
			global $_queries;
			echo "<hr>";
			foreach($_queries as $q)
			{
				echo "<pre><code>\n".htmlentities($q['sql'])."\n</code></pre>\n";
				echo "<b>".$q['time']."</b><hr>\n\n\n";
			}
		}
	}
	

	
	/**
	* get next record after executing _query
	* @access private
	*/
	function next_record()
	{
		$this->row=mysql_fetch_array($this->dbresult);
		return $this->row!=FALSE;
	}
	function num_rows()
	{
		return 	mysql_num_rows($this->dbresult);
	}
	
	/**
	* get result column $field
	* @access private
	*/
	function f($field)
	{
		return $this->row[$field];
	}

	/**
	* get last insertion id
	* @access private
	*/
	function get_insert_id()
	{
		return mysql_insert_id($this->dblink);
	}
	
	
	/**
	* get last error
	* @access public
	*/
	function get_db_error()
	{
		return mysql_last_error();
	}
}
?>