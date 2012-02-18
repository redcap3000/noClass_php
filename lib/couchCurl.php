<?php

abstract class couchCurl implements noSqlCRUD{ 
	function put($data,$id=NULL,$database=NULL){
		if(isset($data['_rev'])) unset($data['_rev']);
		

		$data = json_encode($data);
		$data = str_replace("'","*c*",$data);
		return self::__cc( ($id == NULL?'POST':'PUT'  ) , ($id==NULL?"'":"/$id'"). " -d \ '$data'",$database);
	}
	
	function get($id,$database=null){
		if($database==NULL)$database=get_called_class();
		$r=json_decode(self::__cc('GET',"/$id'",$database),true);
		return $r;
	}
	function view($name,$key,$database=NULL,$opt=NULL){
		// set server in opt too??
		// if view doesnt exist auto generate it based on viewname conventions ?
		// most all views are super simple anyhow ... or class a couch view? look into couch extensions for php
		$opt = array('by_value'=> false,'design_doc'=>'cntrl');
		$server = self::database;
		if(isset($opt['design_doc'])) $design_doc = $opt['design_doc'];
		else $design_doc = 'cntrl';
		if(isset($opt['by_value'])) $by_value = true;
		else $by_value = false;
		// this should not work....  views not tested with 
		$database = ($database == NULL?get_called_class():$database);
		if(is_string($name)) $name = '"' . urlencode($name).'"';
		$json_obj = json_decode(self::_query("curl -X GET '$server/$database/_design/$design_doc/_view/$key?".($by_value== false?'key':'value')."=$name".$extras."'") ,true);
		foreach($json_obj['rows'] as $loc=>$emit)
			$reduced_emit[$emit['id']] = ($by_value == false? $emit['value'] : $emit['key']);
		$json_obj['rows'] = $reduced_emit;

		return ($json_obj?$json_obj:false);
	}
	function update($id,$data,$database=null){
		$database ==null AND $database = get_called_class();
		!isset($data['_id']) && isset($id) AND $data['_id'] = $id;
		if($data['_id'] && $data['_rev']){
			// this is shennanigans because couch / curl requires that the first two fields in passed json must be the _id and _rev, in that order
			// otherwise you get nothing returned.
			$json= array();	
			$json['_id'] = $data['_id'];
			$json['_rev'] = $data['_rev'];
			unset($data['_id'],$data['_rev']);
			// do some other filtering too ...
			foreach($data as $key=>$value){
				$json [$key]= (is_numeric($value) ? (int) $value : $value);
			}
			$data['_id'] = $json['_id'];
			$json = json_encode($json);
			$json = str_replace("'","*c*",$json);
			exec("curl -X PUT -d \ '$json' '".self::couchdb . "/$database/".$data['_id']  ."' -s -H HTTP/1.0 -H ".'"Content-Type: application/json"',$output);
			return $output[0];
		}else
			return "\nMissing _id and/or _rev fields\n";
	}

	function delete($id,$rev,$database=NULL){return self::__cc('DELETE',"/$id?rev=$rev'".' -H "Content-Length:1"',$db);}
	// create a master function list of array functions that take parameters to adjust things in various processing points (i.e. foreach loops)
	public static function handle_couch_id($id,$base = 12,$decode = false){
		foreach(explode(':',$id) as $num)
			$r []= ($decode == true? base_convert($num,$base,10): base_convert($num, 10,$base));
		return implode(':',$r);
	}
	
	private function __cc($method='GET',$query,$db=NULL,$no_exe=false){
		if($db == null) $db = get_called_class();
		$query = "curl -X $method '" . self::couchdb."/".$db."$query" . ' -s  -H "HTTP/1.0" '. ($method=='PUT' || $method == 'POST'? ' -H "Content-type: application/json"':NULL) ;
		// returns the appropriate curl call, no exe is for debugging (returns the command as a string)
		$result = ($no_exe==false? exec($query,$output):$query);
		if(count($output > 1) && is_array($output)) {
			$output = str_replace("*c*","'",$output[0]);
			return $output;
		}
		return ($result?$result:($no_exec==true?$query:false));
	}

	private function _query($query){
		$result = exec($query,$output);
		$json_string = '';
		foreach($output as $json)
			$json_string .= $json;
		return ($output?$json_string:$result);
	}
}

