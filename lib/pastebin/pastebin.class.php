<?php
/**
 * $Project: Pastebin $
 * $Id: pastebin.class.php,v 1.2 2006/04/27 16:20:52 paul Exp $
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


/**
* Pastebin class models the pastebin data storage without getting involved
* in any UI generation
*/
class Pastebin
{
	var $conf=null;
	var $db=null;
	
	/**
	* Constructor expects a configuration array which should contain
	* the elements documented in config/default.conf.php
	*/
	function Pastebin(&$conf)
	{
		$this->conf=&$conf;
		$this->db=new DB;	
	}
	
	/**
	* Has a 5% probability of cleaning old posts from the database
	*/
	function doGarbageCollection()
	{
		if(rand()%100 < 1)
		{
			$this->db->gc();
		}
	}
	
	/**
	* Private method for validating a user-submitted username
	*/
	function _cleanUsername($name)
	{
		return trim(substr(preg_replace('/[^A-Za-z0-9_ \-]/', '',$name),0,24));	
	}
	
	/**
	* Private method for validating a user-submitted token
	*/
	function _cleanToken($token)
	{
		return trim(substr(preg_replace('/[^a-z0-9]/', '',$token),0,32));	
	}
	
	/**
	* Private method for validating a user-submitted format code
	*/
	function _cleanFormat($format)
	{
		if (!array_key_exists($format, $this->conf['all_syntax']))
			$format='text';
			
		return $format;	
	}
	
	/**
	* Private method for validating a user-submitted expiry code
	*/
	function _cleanExpiry($expiry)
	{
		if (!preg_match('/^[dmf]$/', $expiry))
			$expiry='d';
			
		return $expiry;
	}
	
	
	/**
	* returns array of cookie info if present, false otherwise
	* all cookie data is cleaned before returning
	*/
	function extractCookie()
	{
		$data=false;
		if (isset($_COOKIE["persistName"]))
		{
			$data=array();
			
			//blow apart the cookie
			list($poster,$last_format,$last_expiry)=explode('#', $_COOKIE["persistName"]);
			
			//clean and validate the cookie inputs
			$data['poster']=$this->_cleanUsername($poster);
			$data['last_format']=$this->_cleanFormat($last_format);
			$data['last_expiry']=$this->_cleanFormat($last_expiry);
			$data['token']=0;
		}
		
		if (isset($_COOKIE['persistToken']))
		{
			$data['token']=$this->_cleanToken($_COOKIE['persistToken']);
		}
		
		
		return $data;
	}
	
	//we expect the following
	//$post['remember'] =0|1 to remember poster/format in cookie
	//$post['poster'] = name of poster, empty for anonymous
	//$post['format'] = syntax highlight format
	//$post['expiry'] = d m or f for the expiry time
	//$post['code2']  = posted code
	//this method assumes that inputs do NOT have "magic" quotes!
	//returns post id if successful
	
	function doPost(&$post)
	{
		$id=0;
		
		$this->errors=array();
		
		//validate some inputs
		$post['poster']=$this->_cleanUsername($post['poster']);
		$post['format']=$this->_cleanFormat($post['format']);
		$post['expiry']=$this->_cleanExpiry($post['expiry']);
		
		//get a token we'll use to remember this post
		$post['token']=isset($_COOKIE['persistToken'])?
			$this->_cleanToken($_COOKIE['persistToken']):
			md5(uniqid(rand(), true));
		
			
		//set/clear the persistName cookie
		if (isset($post['remember']))
		{
			$value=$post['poster'].'#'.$post['format'].'#'.$post['expiry'];
			
			//set cookie if not set
			if (!isset($_COOKIE['persistName']) || 
				($value!=$_COOKIE['persistName']))
				setcookie ('persistName', $value, time()+3600*24*365);  
		
			if (!isset($_COOKIE['persistToken']))
				setcookie ('persistToken', $post['token'], time()+3600*24*365);  
		
		}
		else
		{
			//clear cookie if set
			if (isset($_COOKIE['persistName']))
				setcookie ('persistName', '', 0);
		}
		
		if (strlen($post['code2']))
		{
			if (strlen($post['poster'])==0)
				$post['poster']='Anonymous';
			
			$format=$post['format'];
			if (!array_key_exists($format, $this->conf['all_syntax']))
				$format='';
			
			$code=$post['code2'];
			
			//is it spam?
			require_once('pastebin/spamfilter.class.php');
			$filter=new SpamFilter;
			
			if ($filter->canPost($post))
			{
			
				//now insert..
				$parent_pid='';
				if (isset($post['parent_pid']))
					$parent_pid=$this->cleanPostId($post['parent_pid']);
					
				$id=$this->db->addPost($post['poster'],$this->conf['subdomain'],$format,$code,
					$parent_pid,$post['expiry'],$post['token']);
			}
			else
			{
				$this->errors[]='Sorry, your post tripped our spam/abuse filter - let us know if you think this could be improved';
			}
			
		}
		else
		{
			$this->errors[]='No code specified';
		}
		
		return $id;
	}	
	
	function cleanPostId($raw)
	{
		return $this->db->cleanPostId($raw);	
	}
	
	function getPostURL($id)
	{
		global $CONF;
		return sprintf("http://{$_SERVER['HTTP_HOST']}".$this->conf['url_format'], $id);
	}

	function redirectToPost($id)
	{
		header("Location:".$this->getPostURL($id));	
	}
	
	function doDownload($pid)
	{
		$ok=false;
		$post=$this->db->getPost($pid, $this->conf['subdomain']);
		if ($post)
		{
			//figure out extension
			$ext="txt";
			switch($post['format'])
			{
				case 'bash':
					$ext='sh';
					break;
				case 'actionscript':
					$ext='html';
					break;
				case 'html4strict':
					$ext='html';
					break;
				case 'javascript':
					$ext='js';
					break;
				case 'perl':
					$ext='pl';
					break;
				case 'php':
				case 'c':
				case 'cpp':
				case 'css':
				case 'xml':
					$ext=$post['format'];
					break;
			}
			
			
			// dl code
			header('Content-type: text/plain');
			header('Content-Disposition: attachment; filename="'.$pid.'.'.$ext.'"');
			echo $post['code'];
			$ok=true;
	
		}
		else
		{
			//not found
			header('HTTP/1.0 404 Not Found');
		}
	
		return $ok;
	}

    function outputExpiryHeaders($post)
	{
		if (!isset($post['expires'])) {
			//most probably a non-existent post
			return;
		}

		$expires=$post['expires'];
		$updated=isset($post['modified'])?$post['modified']:$post['posted'];
                     

        $last_modified = gmdate('D, d M Y H:i:s', $updated) . ' GMT';
        header("Last-Modified: $last_modified");
		
		if (!$expires)
		{
			#cache it for a year
			$expires=time()+86400*365;
		}

		if ($expires)
		{
            $date = gmdate('D, d M Y H:i:s', $expires) . ' GMT';
			header("Expires: $date"); 

			$maxage=$expires-time();
			header("Cache-Control: max-age=$maxage, must-revalidate");

		}
	}
	
	/**
	* returns array of post summaries, each element has
	* url
	* poster
	* age
	*
	* parameter is a count or 0 for all
	*/
	function getRecentPosts($list=10)
	{
		//get raw db info
		$posts=$this->db->getRecentPostSummary($this->conf['subdomain'], $list);
		
		//augment with some formatting
		foreach($posts as $idx=>$post)
		{
			$age=$post['age'];
			$days=floor($age/(3600*24));
			$hours=floor($age/3600);
			$minutes=floor($age/60);
			$seconds=$age;
			
			if ($days>1)
				$age=sprintf(t('%d days ago'), $days);
			elseif ($hours>0)
				$age=($hours>1)?sprintf(t('%d hours ago'), $hours):t('1 hour ago');
			elseif ($minutes>0)
				$age=($minutes>1)?sprintf(t('%d mins ago'), $minutes):t('1 min ago');
			else
				$age=($seconds>1)?sprintf(t('%d secs ago'), $seconds):t('1 sec ago');
			
			$url=$this->getPostURL($post['pid']);
			
			$posts[$idx]['agefmt']=$age;
			$posts[$idx]['url']=$this->getPostURL($post['pid']);
			
		}
		
		return $posts;		
	}

	function deletePost($pid, $delete_linked=false)
	{
		return $this->db->deletePost($pid, $delete_linked);	
	}

	/**
	* Get formatted post, ready for inserting into a page
	* Returns an array of useful information
	*/
	function getPost($pid)
	{
		$post=$this->db->getPost($pid, $this->conf['subdomain']);
		if ($post)
		{
			//show a quick reference url, poster and parents
			$post['posttitle']="Posted by {$post['poster']} on {$post['postdate']}";
			
			if ($post['parent_pid']!='0')
			{
				$parent_pid=$post['parent_pid'];
				
				$parent=$this->db->getPost($parent_pid, $this->conf['subdomain']);
				if ($parent)
				{
					
					$post['parent_poster']=trim($parent['poster']);
					if (strlen($post['parent_poster'])==0)
						$post['parent_poster']='Anonymous';
				
					$post['parent_url']=$this->getPostUrl($parent_pid);
					$post['parent_postdate']=$parent['postdate'];
					$post['parent_diffurl']=$this->conf['this_script']."?diff=$pid";
					
				}
			}
	
			//any amendments - note that a db class might have already
			//filled this if efficient, othewise we grab it on demand
			if (!isset($post['followups']))
				$post['followups']=$this->db->getFollowupPosts($pid);
			
			foreach($post['followups'] as $idx=>$followup)
			{
				$post['followups'][$idx]['followup_url']=$this->getPostUrl($followup['pid']);	
			}
			
			$post['downloadurl']=$this->conf['this_script']."?dl=$pid";
			
			$post['deleteurl']=$this->conf['this_script']."?erase=$pid";
			
			$post['spamurl']=$this->conf['this_script']."?reportspam=$pid";
			
			
		
			
			//store the code for later editing
			$post['editcode']=$post['code'];
	
	
			//preprocess
			$highlight=array();
			$prefix_size=strlen($this->conf['highlight_prefix']);
			if ($prefix_size)
			{
				$lines=explode("\n",$post['editcode']);
				$post['editcode']="";
				foreach ($lines as $idx=>$line)
				{
					if (substr($line,0,$prefix_size)==$this->conf['highlight_prefix'])
					{
						$highlight[]=$idx+1;
						$line=substr($line,$prefix_size);
					}
					$post['editcode'].=$line."\n";
				}
				$post['editcode']=rtrim($post['editcode']);
			}
				
			//get formatted version of code
			if (empty($post['codefmt']))
			{
				$geshi = new GeSHi($post['editcode'], $post['format']);
				
				$geshi->set_encoding($this->conf['htmlentity_encoding']);
				
				$geshi->enable_classes();
				$geshi->set_header_type(GESHI_HEADER_DIV);
				$geshi->set_line_style('background: #ffffff;', 'background: #f8f8f8;');
				//$geshi->set_comments_style(1, 'color: #008800;',true);
				//$geshi->set_comments_style('multi', 'color: #008800;',true);
				//$geshi->set_strings_style('color:#008888',true);
				//$geshi->set_keyword_group_style(1, 'color:#000088',true);
				//$geshi->set_keyword_group_style(2, 'color:#000088;font-weight: normal;',true);
				//$geshi->set_keyword_group_style(3, 'color:black;font-weight: normal;',true);
				//$geshi->set_keyword_group_style(4, 'color:#000088',true);
				//$geshi->set_symbols_style('color:#ff0000');
				
				if (count($highlight))
				{
					$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
					$geshi->highlight_lines_extra($highlight);
					$geshi->set_highlight_lines_extra_style('color:black;background:#FFFF88;');
				}
				else
				{
					$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS,2);
					
				}
				
				
				$post['codefmt']=$geshi->parse_code();
				$post['codecss']=$geshi->get_stylesheet();
				
				//save it!
				$this->db->saveFormatting($pid, $post['codefmt'], $post['codecss']);
			}
			
			$post['pid']=$pid;
		}
		else
		{
			$post['codefmt']="<b>Unknown post id, it may have been deleted</b><br />";
			
			$this->errors[]="Unknown post id, it may have expired or been deleted";
		}	
		
		return $post;
	}
	
}
