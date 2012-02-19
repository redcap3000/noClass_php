<?php
// Configure Mongo/Couch DB Settings in interface.php
require('interfaces.php');
// Require either couchCurl or moniKey based on noClass extension
// noClass_html extends moniKey ... noClass_html extends couchCurl
require('couchCurl.php');
//require('moniKey.php');

/*
	noClass framework
	Ronaldo Barbachano
	AGPL 2012
	
	Now Supports Apache Couch DB with library 'couchCurl' and Mongo DB with
	library 'moniKey' ... simply extend the noClass_html class with library of choice and provide 
	library specifc constants in interfaces.php !
	
	
	whats new -
	
	mongo db support , implemented interface noSqlCRUD , moved
	htmler into noClass_html as callStatic, couch curl modified to be abstract
	Redis coming soon ... perhaps.. mysql (if storing everything atomically)
	
	a NoSQL approach to framework development; define and store class parameters using JSON; use 
	
	classes in scripting enviornment to 'prototype' before eventually storing the class definition
	in a NoSQL database. 
	
	noClass currently implements Apache Couch, but Mongo will be on its way; 
	in PHP5 - but ruby and python editions would probably be easier to implement due to their 
	native OOP language design.
	
	DEPENDENCIES :
	
	PHP 5.3
	poform (for record editing/creation)
	couchCurl (for database interactions)
	
	Below is a simple 'html' class that when called as a string will render its parameters
	as a web page.
	
	And further on is a 'blog' class that extends the html class. To use this you will need
	a database named after the bottom classes (in this case 'blog'). Poform has a simple definition
	(defaulting to the normal server setup of localhost:5984)
	
	If it is invoked as a function and provided an ID, it will load the ID from a couch server
	defined in couchCurl (static library) from a database named after the calling class. If provided
	an id and another value (not equal to false) it will load an editor for the record.
	
	If just loaded without directives (new object) a form to insert the object becomes available.
	

	Example use for blog class
	
	$blog = new blog;
	// create a new record editor
	echo $blog();
	// display record in 'blog' database with id of 'test'
	echo $blog('test');
	// display editor for record 'test' with live preview
	echo $blog('test','edit');
	
	
	The special parameters $_f and $_r keep track of very basic functionality. $_r is required before
	an insert can happen. $_f designates which fields are to be visible. These are simple numeric-key arrays
	containing string values of class fields.
	
	Their usage becomes more important as more classes extend each other.
	
	The idea is to extend noClass_html, and create a database structure via class parameters, setting
	its values to recognizable markup for library poform to interpret as a form.
	
	
*/

// this does something to the child class and returns it
// as the function in the abstract class is called in child class

class noClass_html extends couchCurl{
	public $_f;
	public $_r;
	public $_id;

	public $_rev;
	public $head;
	public $title;
	public $body;
	public $footer;
	
	// what about poform ?! relegate to __call ? or have poform extend noClass_html ?
	// extend an entire class just for one function?
	public static function __callStatic($name,$arguments){
		$name = explode('__',$name,3);
		// to do avoid all the returns ... funnel all calls into one or max two calls ... especially for the swtiches ..
		if(in_array($name[0],array('a','link','input','img') ))
		// theres a few other types that use 'html' element' think all things meta or in the head
			if($name[0] == 'a' || $name[0] == 'img')			
				$extra = ($name[0] == 'a'?' href':' src') ."='$arguments[0]'" . (!isset($arguments[2])?'' : " $arguments[2]") AND $arguments[0] = $arguments[1];
			else
				return  trim("\n<$name[0] ". ($name[0] == 'link' ? 'rel="'.$name[1].'" href="'.$arguments[0].'" ' . (isset($arguments[1])? "$arguments[1]" :NULL) : (isset($name[2]) ? " id='$name[2]'":'') . (isset($name[1]) ? "type='$name[1]'" :'') .(isset($arguments[0])? "value='$arguments[0]'" :NULL) . (isset($arguments[1])? " $arguments[1]" :NULL) ).'>' );
		return "\n<$name[0]".(!isset($name[1])?'':" class='$name[1]'") . (!isset($name[2])?'': " id='$name[2]'") . (!isset($extra)?'':$extra). (!isset($arguments[1]) ?'' : " $arguments[1]").'>'. (isset($arguments[0]) ? $arguments[0] : '') . "</$name[0]>\n";
	}
	

	private function load_template($array,$key=false){
	// values that cannot be set (sometimes text areas/ files get published if the
	// field in the record (json) is blank 
	// these are from POFORM ... 
		$restricted_values = array('number','range','email','password','textarea','file');
		if(is_array($array)){
			if(strpos($key,'__')){
			// Handles iterative operations to properly generate the nested
			// HTML (when structured appropriately) for valid values..
			// I.e. reduces structures like body->page->post->post_title
			// body->page = string(post,post_title);
				$html_call = $key;
				$r = $this->load_template($array);
				$r2 = array();
				foreach($r as $iField=>$iValue)
					if($iValue != '')
					// throwing undefined index notices for $key
						$r2[$key] .= $iValue;
				return self::$key(implode($r2));
			}
			// use array walk ? 
			foreach($array as $key2=>$array2)
				$r[$key2] =  $this->load_template($array2,$key2);	
		}
		elseif($key !=false && isset($this->$key) && $this->$key != false && $this->$key != '' && !in_array(trim($this->$key),$restricted_values) ){
	// get ready for htmler structure, which will set the field name to the 
		// could accept another parameter to determine how the field name is used within the created htmler
		// structure return HTML ?? paired with the object's key value as a return param ??
		 // return self::
			$html_call = "$array"."$key";
			return self::$html_call($this->$key);
		}elseif(is_string($array) && strpos($key,'__') ){
			$html_call = $key;
			return self::$html_call($array);
		}
		return (isset($r)?$r:0);	
	}

	public function __construct($container){
		$this->_container = $container;	
	}

	public function __invoke($_id=false,$action=false){
		// save the container, sometimes gets unset with toSelf function.. or some other recursion..
		$container = $this->_container;
		// load the head to include css etc. so the 'preview' works properly
		if($_id != false ){
			// do some more sanitization ... get keys from this and compare? (with default values and pull the $_r out
			// and see if all those fields exist..
			// this could cause problems just to avoid looking up a record.. if we
			// do it this way we always get the most 'latest' edition and not whatever changes
			// the user made... but then we need to gracefully handle conflicts...
			// to avoid can avoid the second post lookup ... 
			$r = ( isset($_POST['_id'])  && $_POST['_id'] == $_id ? $_POST : $this->get($_id));
			if($action == false){
				$this->arrayToSelf($r);
				return $this;
			}else
				$_POST = $r;
		}elseif($_id == false && $action != false ){
		
			!class_exists('poform') AND require('poform.php');
			$this->editor = poform::auto($this);
			// load the head to include stylesheets in the container
			// for when the record cannot be inserted (due to invalid fields)
			foreach($container['head'] as $key=>$value)
				$this->head .= self::$key($value); 
			return($this);
		}elseif(!$action && $_id){ 
		// check post object for an $_ID and do something else >>
			$this->arrayToSelf($r);
			// simply show the object if we are displaying ...
			return $this;
		}elseif($_id && $action && isset($_POST['_rev']) || isset($_POST['_id']) ){
			$this->footer = $this->update(true,$_POST );
		}
		// load poform only if we need a web editor
		!class_exists('poform') AND require('poform.php');
		if( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] != "POST"){
			// set the $_POST to $r so poform properly populates field values			
			isset($r) AND $_POST = $r;
			// dont have to worry about reloading perform, because if !$action we return $this
			$this->editor = poform::auto($this);
			
		}
		if($action != 1 || $action == NULL	){
		// server request method is post ...
		// force an insert with whatever keys exist ? this may not be nessary
			if(isset($r['_rev'])){ 	
				$_POST['_rev'] = $r['_rev'];
				// probably dont need this ..
				unset($_POST['_r']);
			}
			$no_update = false;
			if(isset($this->_r)){
				foreach($this->_r  as $key)
					!array_key_exists($key,$_POST) || $_POST[$key] == '' AND $no_update = true;
						// throws weird error (end const not found assumed end) notice
//						end;
				if(!$no_update){
					if( $_id != false){
//						$this->footer .= $this->update(true,$_POST );
						$this->update(true,$_POST);
					}else{
						// maybe even filter empty values?
						//unset($_POST['rev']);
						$id_check = str_replace(array(' ','_'),'',$_POST['_id']);
						// this check will fail on spaces anyway ... 
						if(ctype_alnum($id_check)){
							$the_id = str_replace(' ','_',$_POST['_id']);				
							unset($_POST['_id']);
						}else{
						// handle this better...
							die('ID is not alpha-numeric');
						}
					$put = $this->put($_POST,$the_id) ;
					/* DO THIS BETTER .. probably let $this->put return false */
					// or just return a $put['error'] in the abstract class ?
					if(isset($put['error']) || $put == false){
						$r = $this->get($the_id);
						$_POST['_rev'] = $r['_rev'];
						$this->update($the_id,$_POST );
					}
					$_POST['_id'] = $the_id;
					// puts just fine ... how to then return the proper values ? 
					return $this($the_id,1);
					}
					
				}else{
				// show errors and return $this ..
					return $this((isset($the_id)?$the_id:''),1);
				}
		}
		}
		// hmm probably make a form ... ?
		$this->_container = $container;
		if($action != false){
		// weird hack to get the record to display in the editor (as you are editing it, and 
		// as new records are created to automatically go into 'edit' mode until the page is reloaded
		// is this call needed ? sometimes... in the event that we are attempting to not include a field
		// in an existing record that is required
		// could i make this easier ? 
			$this->arrayToSelf($r);
			$called_class = get_called_class();
			$called_class = new $called_class();
			$this->editor = poform::auto($called_class);
			$called_class = $called_class($_id);
			$called_class->editor = $this->editor;
			$called_class->_container = $this->_container;
			return $called_class;
		}
		return $this;
	}
	
	private function arrayToSelf($a){
		foreach($a as $k=>$v)
			$this->$k = $v;
	}
	public function __toString(){
		$this->head .= (isset($this->title)?'<title>'.$this->title.'</title>' : '');
		// container disappears ...
		if(isset($this->_container) && is_array($this->_container) && (isset($this->_id) || isset($this->_rev) || isset($_POST['_id'])) ){
			$html = '';
			 foreach( $this->load_template($this->_container) as $loc=>$value )
			 // leave body open...
			 		$html .= self::$loc(implode($value));
			 // make doctype__html()
			 return "<!doctype html>".($html). (isset($this->editor) ? self::div__editor($this->editor) : ''). "\n</html>";
		
		}
		isset($this->editor) AND $this->body .= self::div__editor($this->editor);
		
		return "<!doctype html>\n<html>\n\t$this->head \n\t<body>\n\t\t$this->body\n\t".(isset($this->footer) ? "<footer>\n\t\t$this->footer\n\t\t</footer>":'')."\n\t</body>\n</html>";
	}
/*	
	public function __sleep(){
	// called automagically when serialize is invoked
		$valid = array();
		foreach($this as $k=>$v)
			if(strpos($k,'_') === 0 && strlen($k) == 2 )
				is_array($v) AND $valid = array_merge($valid,$v);
				
		return array_unique($valid);		
	}*/
}
