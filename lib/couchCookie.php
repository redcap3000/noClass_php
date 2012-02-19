
<?php
!class_exists('couchCurl') and require('couchCurl.php');

class couchCookie extends couchCurl implements noSqlSession{
	public $username;
// $password='password ' is for poform to display this field as a password
	public $password= 'password ';

	public function destroy_token($id=null){
		if($id == null) $id = self::apf;
		if(isset($_COOKIE[$id] )){
		// update 'couchCurl' calls with 'self::get' and reorder the params to match properly...
//			$couch_delete = json_decode(couchCurl::get($_COOKIE[self::apf],false,self::apf.'_session'),true);
			$couch_delete = $this->get($_COOKIE[$id], $id.'_session');

			$couch_delete = $this->delete($couch_delete['_id'],$couch_delete['_rev'],$id.'_session');
			$params = session_get_cookie_params();
			$time_val = (int) (time() - 36000);
			setcookie($id, '', $time_val,$params["path"], $params["domain"],$params["secure"], $params["httponly"]);
		}
}
	public function __construct($id=null){
		global $couch_queries;
		if($id == null) $id = self::apf;
		if($_COOKIE[$id]){
		// if the cookie is presnet check to see if it is in the DB and if not then we can deauthenticate
			if(self::check_token($_COOKIE[$id]) != false){
		}else{
		// PUT YOUR INVALID MESSAGE HERE ...
			return false;
		}
		unset($this->username,$this->password);
		}
		
		if(isset($_POST['username']) && $_POST['password'] != NULL){
		// IMPLEMENT VIEWS
			$result = $this->view($_POST['username'],'username',self::couch_design_doc,self::apf.'_users',NULL);
			$couch_queries++;
			foreach($result['rows'] as $key=>$value){
				$uid = $key;
				$pass_hash = $value;
			// only process the top most row, this shouldn't have more than one entry
				end;
		}
		if($pass_hash == md5(trim($_POST['passw/ord']))){
			return self::make_token($uid);
		}else{
		// SHOW INVALID LOGIN
			return false;
			die();
		}
	}
	
	}

	public function check_token($id=null,$expire=NULL){
// USE THIS instead of the lookup thingee this happens every page probably..
	if($id==null && isset($_COOKIE[self::apf]) ) $id = isset($_COOKIE[self::apf]);
		$sess = json_decode(couchCurl::get($id,false,self::apf.'_session'),true);
		return (isset($sess['_rev'])?$sess['_rev']:false);
	}
	public function make_token($userid,$expire=null){
		$user_ip = $_SERVER["REMOTE_ADDR"];
		unset($this->_f,$this->_d, $this->username,$this->password);
		$cookie_value = substr(md5(uniqid(rand(),true)),1,36);
	// this wont make for very simple lookups ... by any of the array values...
		$json = json_encode(array('u'=> $userid, 'ts'=> couchCurl::handle_couch_id(time()), 'ip'=> $user_ip ) );
		couchCurl::put($json,$cookie_value,self::apf.'_session') ;
		if($expire ==null)
			setcookie(self::apf, $cookie_value);
		else
			setcookie(self::apf,$cookie_value,$expire);
	}
}
