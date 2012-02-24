<?php
/*

	redisExec 
	Ronaldo Barbacahno
	
	In the spirit of couchCurl and to implement noClass ... plug into no class
	for a quick and easy way to interact with a redis distro ... 
	
	Treats assoc. arrays as hashes that you can look up with views.
	
	List/set support forthcoming. Implements noSqlCRUD functions.
	
	Use as a standalone static class if needed. Quickly run most commands via
	redisExec::-COMMAND NAME-('command directive')

*/

abstract class redisExec implements noSqlCRUD{
	// very basic command constructor for redis CLI
	// should implement an interface to better /more easily fit in with noClass...
	// stores very simple json structures from arrays .. stores EVERYTHING as json 
	// it will be unlikely that within noClass that users are storing single string values anyhow...
		const pre = '/opt/redis/redis-cli';

		function get($id,$database=NULL){
			$key_type = self::key_check($id);
			if($key_type != false){
			// FIRST DETERMINE THE TYPE OF ID using a COMMAND before using GET or SMEMBERS etc..
			// since GET is the same name as the function we have to override _callStatic to avoid infininte loop
				if(!in_array($key_type,array('list','set','hash')))
					 exec(self::pre . " GET $id",$output);
				 elseif($key_type == 'set'){
				 	$output = self::SMEMBERS($id);
				 }elseif($key_type == 'hash'){
				 
				 	$output = self::HGETALL($id);
				 	
				 	
				 	foreach($output as $loc=>$value){
				 		if($loc % 2 != 0)
				 		// processing a value
					 		$output_2 [$output[$loc-1]] = $value;
				 	}
					return $output_2;				 	
				 	// process the array to return 'better' get ?
				 }
			 }
			return $output;
			
			// decode json ? 
		}
		
		public function key_check($id){
			$exists = self::EXISTS($id);
			if($exists == 1){
				$key_type = self::TYPE($id);
			}else{
				return false;
			}
			return $key_type;
		}
		
		public function put($data,$id,$database=NULL){

			// if is array store serialized version? just serilaze every value to avoid bs?
			// do array type checking ?? PERSIST by defaulT?
			if(is_array($data)){
				// can't really add linked sets of data here (not great for JSON...)
				// so if you give put an indexed array ... then it will attempt to add those values to the member of $id
				// if array is numeric then add as list ? ?
				
					// for compatability with other nosql classes
					// if ths value is changed it WILL not change the actual key .. but i should simply
					// make the key check/change on updates to solve problems
					$hset = '_id "'.$id.'" ';

					foreach($data as $key=>$value){
						if(is_array($value))
							$hset .= $key . ' "'. implode(',',$value) . '"';
						else{
							$hset .=  $key . ' "'. $value.'" ';
							}
					}
					return self::HMSET($id,NULL,$hset);

//					}
//				else{
				// store as a list instead of set
//					foreach($data as $value){
//						$hset .=   ' "'. $value.'" ';
						// override the static line iwth new new hset..
//					}
//					$hset = $id . $hset;
//					self::SADD($hset);

//				}
				
			}else{
			// redis in to only be used for sessions  just store values ... 
				return self::SET($id,$data);
			}
		}
		
		public function view($name,$key=NULL,$database=NULL,$opt=NULL){
		// NAME - of hash field to retrieve
		// KEY - null - provide array with list of documents to match - or regular string to get in on some commandline action search methods
		// basically a view should search for a field name $key, against a value $name inside of a database
		// maybe the database could be the redis key ?
		
		// but we want to work with hashes here... 
		// get a general list of all keys ... then figure out their types, remove any that are not hashes...
		
			// could pass exact term in as key ?? 
			if($key == NULL)
				$records = redisExec::KEYS(NULL,'*');
			elseif(is_array($key)){
				// allow user to define an array with keys (documents) to look for the names for avoid the 'look everything up'
				$records = $key;
			}elseif(!is_object($key)){
				$records = redisExec::KEYS(NULL,$key);
				
			}
			
			// would be cooler to match on  key value .. i.e. view ('this field' , 'in these documents', 'where this value is present')
			
			foreach($records as $loc=>$k){
				if(redisExec::key_check($k) != 'hash'){
				// learn array functions (reduce etc)
					unset($records[$loc]);
				}elseif(self::HEXISTS($k,$name) == 1){
					$r [$k]= self::HGET($k,$name);
					}
				}
			return $r;
			

		}
		public function update($id,$data,$database=NULL){
			$key_type = self::key_check($id);
			if($key_type == 'hash'){
				return self::put($data,$id);
			
			}elseif($key_type == 'set'){
				return self::SMEMBERS($id,$data);
			}
		// pretty much the same as 'get'
			return exec(self::pre . " GET $id");

		}
		public function delete($id,$opt=NULL,$database=NULL){
			// just delete whatever $ids are passed ...
			return self::DEL(trim($id));

		}

		public static function __callStatic($names,$arguments){
			$r = exec(self::pre . " $names $arguments[0]" . (isset($arguments[1])?" '".$arguments[1]."'":'') .  (isset($arguments[2])?' ' .$arguments[2]:'') ,$output);
			if(count($output) == 1 ) $output = $output[0];
			return $output;
		}
	}

