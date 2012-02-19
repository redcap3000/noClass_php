<?php

// uses a bit less memory ... but loads quite a bit faster (6-10 ms vs. 60-80 + with couchDB)
// could use some optimizations... support some mongo options in the config interface..
// like safe inserts/updates ... implement delete!!

abstract class moniKey implements noSqlCRUD{
	public static function __callStatic($names,$arguments){
		$names = explode('_',$names);
		$database = self::mongodb;
		if(count($names) ==  2)
			$collection = $names[1];
		elseif(count($names) == 3){
			$database = $names[1];
			$collection = $names[2];
		}else
			$collection = get_called_class();
				
		!$collection AND $collection = 'monikey_collection';

	
	// just make a collection already if we are using outside of a 
		//class/ do not define it to avoid massive errors consider making a class const
		
		$m = new Mongo();
		// select a database
		$db = $m->$database;
		$collection = $db->$collection;

		if($names[0] == 'find' && is_array($arguments[0]) ){
			$collection->find($arguments[0]);
		}else{
		
			if(is_array($arguments[0]))
				$data = $arguments[0];
			elseif(isset($arguments[1])){
				$id = $arguments[0];
				$data = $arguments[1];
				}
			else
				$id= $arguments[0];
			
			if(isset($data) && $data != NULL && $id != NULL){
			// all data needs to be arrays? whata pita ....
				if(isset($_POST['_id']) )
					$data['_id'] = $_POST['_id'];
				array_reverse($data);
				$collection->save($data);
			}
			elseif(isset($data) && is_array($data)){
				$data = array_reverse($data);
				$collection = $collection->save($data);					
			}elseif($id != NULL){
				$cursor = $collection->findOne(array('_id' => (string)$id ) ) ;
				foreach($cursor as $key=>$obj)
						$r [$key] = $obj;
				return $r;
			}else{
			// return a better structured array ?
			// dont display the _id as a mongoId object ...
				foreach( $collection->find() as $obj){
						$the_id = $obj['_id']->{'$id'};
						unset($obj['_id']);
						// converts ID to string and uses it as the key in the returned array
						$r [$the_id]=  $obj ;
				}
				return $r;
			}
		}	

	}
	public function get($id=NULL,$database=NULL){
	// if id null then get all records ?? in collection 
	// by 'database' for mongo we mean 'collection'
		$database == NULL AND $database = get_called_class();
		$call = "_$database";
		return self::$call($id);

	}
	public function put($data,$id,$database){
		!$data['_id'] AND $data['_id'] = $id;
		$database == NULL AND $database = get_called_class();
		$call = "_$database";
		return self::$call($data,$id);

	}
	public function view($name,$key,$database,$opt=NULL){
	// need to test this asap...
	// name can contain periods to do a 'find' and if opt not null
		if($opt == NULL && is_string($name))
			$find = array($key=>$name);
		elseif(is_array($key)  &&is_array($opt) )
		// for using 'find $in'
			$find = array($name=>array('$in'=>$key));
		$call = "find_$database";	
		return self::$call($find);
		// should be simple enough once i figure out the appr. interface...
	}
	public function update($id,$data,$database=NULL){
		$database == NULL AND $database = get_called_class();
		$call = "_$database";
		return self::$call($id,$data);
	}
	public function delete($id,$database){
	// coming soon
	}
}
