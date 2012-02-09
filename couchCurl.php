<?php
define('COUCH_HOST','http://localhost:5984');
// sessions do not like pcntl_exec ??, are processes not playing nice ? probably not ..
class couchCurl{
	static function put($json,$title=NULL,$db=NULL){
	
		$json = str_replace("'","*c*",$json);

		return self::__cc( ($title == NULL?'POST':'PUT'  ) , ($title==NULL?"'":"/$title'"). " -d \ '$json'",$db);
	}
	
	
	static function get($title,$range_stop=FALSE,$db=NULL){return self::__cc('GET',"/$title'",$db);}

	static function view($key,$view,$design_doc,$db=NULL,$server=NULL,$by_value = false,$extras=NULL){
		$db = ($db == NULL?COUCH_DB:$db);
		$server = ($server == NULL?COUCH_HOST:$server);
		if(is_string($key)) $key = '"' . urlencode($key).'"';
		
		
		$json_obj = json_decode(self::_query("curl -X GET '$server/$db/_design/$design_doc/_view/$view?".($by_value== false?'key':'value')."=$key".$extras."'") ,true);
//		if(REDUCED_EMIT){
			foreach($json_obj['rows'] as $loc=>$emit)
				$reduced_emit[$emit['id']] = ($by_value == false? $emit['value'] : $emit['key']);
			$json_obj['rows'] = $reduced_emit;
//		}
		return ($json_obj?$json_obj:$json_string);
	}
	
	
	
	static function update($json,$db=NULL){
		// doing it here because of annoying quote bash error for the database..
		$db = self::___db($db);
		// checking for _id and _rev before attempting a curl..	
		$doc = json_decode($json,true);
		
		if($doc['_id'] && $doc['_rev']){
			// this is shennanigans because couch / curl requires that the first two fields in passed json must be the _id and _rev, in that order
			// otherwise you get nothing returned.
			$json= array();	
			$json['_id'] = $doc['_id'];
			$json['_rev'] = $doc['_rev'];
			
			unset($doc['_id'],$doc['_rev']);
			// do some other filtering too ...
			foreach($doc as $key=>$value){
				$json [$key]= (is_numeric($value) ? (int) $value : $value);
			}
			$doc['_id'] = $json['_id'];
				
			$json = json_encode($json);
			$json = str_replace("'","*c*",$json);
			
				exec("curl -X PUT -d \ '$json' '".COUCH_HOST . "/$db/".$doc['_id']  ."' -s -H HTTP/1.0 -H ".'"Content-Type: application/json"',$output);
				return $output[0];
			}else
				return "\nMissing _id and/or _rev fields\n";
	}

	static function delete($title,$rev,$db=NULL){return self::__cc('DELETE',"/$title?rev=$rev'".' -H "Content-Length:1"',$db);}

	static function get_update_seq($db){
		$db_check = json_decode(self::get(NULL,false,$db),true);
		return $db_check['update_seq'];
	}

	// create a master function list of array functions that take parameters to adjust things in various processing points (i.e. foreach loops)
	public static function handle_couch_id($id,$base = 12,$decode = false){
		foreach(explode(':',$id) as $num)
			$r []= ($decode == true? base_convert($num,$base,10): base_convert($num, 10,$base));
		return implode(':',$r);
	}
	
	private static function __cc($method='GET',$query,$db=NULL,$no_exe){
		$query = "curl -X $method '" . COUCH_HOST."/".self::___db($db)."$query" . ' -s  -H "HTTP/1.0" '. ($method=='PUT' || $method == 'POST'? ' -H "Content-type: application/json"':NULL) ;
//		echo($query . "\n");
		// returns the appropriate curl call, no exe is for debugging (returns the command as a string)
		$result = ($no_exe==false? exec($query,$output):$query);
		
//		if($method=="PUT" || $method=='POST')
//			die($query);
		// incase we have a resultset with more than a row - otherwise result is permissible
		if(count($output > 1) && is_array($output)) {
//				$output = implode('',$output);
				$output = str_replace("*c*","'",$output[0]);
			
				
				return $output;
		}
		return ($result?$result:($no_exec==true?$query:false));
	}
	private static function ___db($db){return ($db == NULL?COUCH_DB :$db);}
	private static function _query($query){
		$result = exec($query,$output);
		$json_string = '';
		foreach($output as $json)
			$json_string .= $json;
		return ($output?$json_string:$result);
	}
}

