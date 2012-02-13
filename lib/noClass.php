<?php
require('couchCurl.php');
require('htmler.php');
/*
	noClass framework
	Ronaldo Barbachano
	AGPL 2012
	
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



class noClass_html{
	public $_f;
	public $_r;
	public $_id;
	public $head;
	public $title;
	public $body;
	public $footer;

	function load_template($array,$key=false){
		if(is_array($array)){
		
			if(strpos($key,'__')){
			// Handles iterative operations to properly generate the nested
			// HTML (when structured appropriately) for valid values..
			// I.e. reduces structures like body->page->post->post_title
			// body->page = string(post,post_title);
				$html_call = $key;
				$r = $this->load_template($array);
				foreach($r as $iField=>$iValue){
					if($iValue != '')
						$r2[$key] .= $iValue;				
				}
				return htmler::$key(implode($r2));
			}
			// use array walk ? 
			foreach($array as $key2=>$array2){
				$r[$key2] =  $this->load_template($array2,$key2) ;	
			}
		}
		elseif($key !=false && $this->$key != false){
		// get ready for htmler structure, which will set the field name to the 
		// could accept another parameter to determine how the field name is used within the created htmler
		// structure return HTML ?? paired with the object's key value as a return param ??
		 // return htmler::
			$html_call = "$array"."$key";
			return htmler::$html_call($this->$key);
		}elseif(is_string($array) && strpos($key,'__') ){
			$html_call = $key;
			return htmler::$html_call($array);
		}
		return $r;	
	}

	public function __construct($container){
	// consider moving the 'action' here to do 'edit' 'new record' actions ..
		$this->_container = $container;	
	}

	public function __invoke($_id=false,$action=false){
		// save the container, sometimes gets unset with toSelf function.. or some other recursion..
		$container = $this->_container;
		// load the head to include css etc. so the 'preview' works properly
		if($_id != false){
			$r = $this->getRecord($_id);
			// if this is false then we need to do something to prevent showing an empty structure?
			if($action == false){
				$this->arrayToSelf($r);
				return $this;
			}
			
		}elseif($_id == false && $action != false ){
			if(!class_exists('poform')) require('poform.php');
			$this->editor = poform::auto($this);
			// load the head to include stylesheets in the container
			// for when the record cannot be inserted (due to invalid fields)
			foreach($container['head'] as $key=>$value){
				$this->head .= htmler::$key($value); 
			}
			
			return($this);
		}elseif(!$action && $_id){ 
			$this->arrayToSelf($r);
			// simply show the object if we are displaying ...
			return $this;
		}elseif($_id && $action && isset($_POST['_rev']) && isset($_POST['_id']) ){
			$this->footer = couchCurl::update(json_encode($_POST),get_called_class() );
		}
		// load poform only if we need a web editor
		if(!class_exists('poform')) require('poform.php');
		if($_SERVER['REQUEST_METHOD'] != "POST"){
			// set the $_POST to $r so poform properly populates field values			
			$_POST = $r;
			// dont have to worry about reloading perform, because if !$action we return $this
			$this->editor = poform::auto($this);
		}elseif($action != 1){
		// server request method is post ...
		// force an insert with whatever keys exist ? this may not be nessary
			if(isset($r['_rev'])){ 	
				$_POST['_rev'] = $r['_rev'];
				// probably dont need this ..
				unset($_POST['_r']);
			}
			$no_update = false;
		
			if(isset($this->_r)){
				foreach($this->_r  as $key){
					if(!array_key_exists($key,$_POST) || $_POST[$key] == '' ){
						$no_update = true;
						end;
					}
				}
				if(!$no_update){
					if( $_id != false){
					$this->footer = couchCurl::update(json_encode($_POST),get_called_class() );
					}else{
						// maybe even filter empty values?
						unset($_POST['rev']);
						$id_check = str_replace(array(' ','_'),'',$_POST['_id']);
						// this check will fail on spaces anyway ... 
						if(ctype_alnum($id_check)){
							$the_id = str_replace(' ','_',$_POST['_id']);				
							unset($_POST['_id']);
						}else{
						// handle this better...
							die('ID is not alpha-numeric');
						}
					$put = json_decode(couchCurl::put(json_encode($_POST),$the_id,get_called_class()),true) ;
					/* DO THIS BETTER */
					if(isset($put['error'])){
					// make new oobject with the new record ? 
						$_POST['_id'] = $the_id;
						// this is a mess...
						$r = $this->getRecord($the_id);
						$_POST['_rev'] = $r['_rev'];
						couchCurl::update(json_encode($_POST),get_called_class() );
					}
					$_POST['_id'] = $the_id;
					// puts just fine ... how to then return the proper values ? 
					return $this($_POST['_id'],1);
					}
					
				}else{
				// show errors and return $this ..
					return $this($the_id,1);
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
			$this->arrayToSelf($r);
			$called_class = get_called_class();
			$called_class = new $called_class($container );
			$this->editor = poform::auto($called_class);
			$called_class = $called_class($_id);
			$called_class->editor = $this->editor;
			$called_class->_container = $container;
			return $called_class;
		}
		return $this;
	}
	private function getRecord($_id){
		return ( json_decode( couchCurl::get($_id,false,get_called_class()),true));
	}
	
	private function arrayToSelf($a){
		foreach($a as $k=>$v)
			$this->$k = $v;
	}
	public function __toString(){
	
		$this->head .= (isset($this->title)?'<title>'.$this->title.'</title>' : '');
		// container disappears ...
		if(isset($this->_container) && is_array($this->_container) ){
			$html = '';
			 foreach( $this->load_template($this->_container) as $loc=>$value )
			 // leave body open...
			 		$html .= htmler::$loc(implode($value));
			 // make doctype__html()
			 return "\n<!doctype html>".($html). (isset($this->editor) ? htmler::div__editor($this->editor) : ''). "\n</html>";
		
		}
		if(isset($this->editor)){
			$this->body .= htmler::div__editor($this->editor);
		}

		
		return "\n<!doctype html>\n<html>\n\t$this->head \n\t<body>\n\t\t$this->body\n\t".(isset($this->footer) ? "<footer>\n\t\t$this->footer\n\t\t</footer>":'')."\n\t</body>\n</html>";
	}
	
	public function __sleep(){
	// called automagically when serialize is invoked
		$valid = array();
		foreach($this as $k=>$v)
			if(strpos($k,'_') === 0 && strlen($k) == 2 )
				is_array($v) AND $valid = array_merge($valid,$v);
				
		return array_unique($valid);		
	}
}