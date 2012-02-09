<?php
require('couchCurl.php');

$time = microtime(); 
$time = explode(" ", $time); 
$time = $time[1] + $time[0]; 
$start = $time; 

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
	
	DEPENDECIES :
	
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

class noClass_html{
	public $_f;
	public $_r;
	public $_id;
	public $head;
	public $title;
	public $body;
	public $footer;


	public function __invoke($_id=false,$action=false){
	// this invoke function is more complicated than it needs to be ..
	// this is what it does : basically allows one invoke function to do
	// many things - display a record $object('record_id')
	// create /edit a new record $object()
	// edit an existing record $object('record_id','edit');
		if(!$_id && !$action && $_SERVER['REQUEST_METHOD'] != "POST"){
		// a little cleanup mostly for debugging...
			unset($_POST);
		}
	
		if($_id != false){
			$r = $this->getRecord($_id);
			if($action == false){
				$this->arrayToSelf($r);
				return $this;
			}
			
		}elseif($_id == false && $action == false && !isset($_POST['_id']) ){
			if(!class_exists('poform')) require('poform_final.php');
			$this->editor = poform::auto($this);
			return($this);
		}elseif(!$action && $_id){ 
			$this->arrayToSelf($r);
			// simply show the object if we are displaying ...
			return $this;
		}elseif($_id && $action && isset($_POST['_rev']) && isset($_POST['_id']) ){
			$this->footer = couchCurl::update(json_encode($_POST),get_called_class() );
		}
		// load poform only if we need a web editor
		if(!class_exists('poform')) require('poform_final.php');
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
				// operate on a clone so we can retain the field attributes that get replaced when using $this($_id),
				// and properly populate the input fields ;
					$this->editor = poform::auto(get_class_vars(get_called_class()));
					return $this($_id);
					
				}else{
				// show errors and return $this ..
					return $this($the_id,1);
				}
		}
		}
		// hmm probably make a form ... ?
		if($action != false){
			!isset($this->editor) AND $this->editor = poform::auto($this);
			$this->arrayToSelf($r);
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
		$this->head .= '<title>'.$this->title.'</title>';
		if(isset($this->editor)){
			$this->body .= '<div id="editor">'.$this->editor."</div>";
		}
		return "\n<!doctype html>\n<html>\n\t<head>\n\t$this->head</head>\n\t<body>\n\t\t$this->body\n\t<footer>\n\t\t$this->footer\n\t\t</footer>\n\t</body>\n</html>";
	}
	
	public function __sleep(){
	// called automagically when serialize is invoked
		$valid = array()
		foreach($this as $k=>$v)
			if(strpos($k,'_') === 0 && strlen($k) == 2 )
				is_array($v) AND $valid = array_merge($valid,$v);
				
		return array_unique($valid);		
	}
	
	/*
	
		Haven't used these yet ... but makes for more 'enforced' design patterns.
		More stuff needs to be written to properly handle dynamically defined 
		array parameters like '_r' and '_f'. This also wont jive well with classes
		that mix defined parameters and ones that are created from an external source.
		
		It would best be advisable to not mix 'dynamic loaded' classes, but I've done it
		plenty of times without real ill effects. The processing time could be improved
		but the memory use is outstanding.
		
		These will come into use when users begin adding parameters to classes without
		defining them first in the class as they set/get/check them.
		
		Basically the pattern shown on php.net was to store these parameters to another array.
		
		I take it a step further and add a value entry for it to $this->_f. I suppose we do not
		need to set the value to an extra array? Or can we let it do what it does normally (set the value to
		the param we request)
		
		But I do see the advantage of this pattern to track 'external' changes to a class. And
		in the case of dynamically loaded classes, we can use it to 'revert' to its default, although
		we may still be able to do this with a dynamically loaded class.
	
	*/
	
	public function __set($name,$value){
	// __set() is run when writing data to inaccessible properties.
	// add the parameter to $_f ;
	
		$this->_set[$name] = $value;
		if(isset($this->_f)) $this->_f []= $name;
		else
			$this->_f = array($name);
	}
	
	public function __get($name){
		if(array_key_exists($name,$this->_set)){
			return $this->_set[$name];
		}
	}
	
	public function __isset($name){
		return isset($this->_set[$name]);
	}
	
	public function __unset($name){
	
		unset($this->_set[$name]);
		unset($this->_f[$name]);
		if(isset($this->_r[$name])) unset($this->_r);
		
	// remove name from any values in the _r using values from __sleep ? 
	
	}
	

}

// the more advanced 'blog' class

class blog extends noClass_html{

	public $_f = array('_id','post_title','category','post_type','post_tags','publisher','summary','content','status');
	public $_r = array('_id','post_title','post_type','publisher','content','status');
	public $post_title;
	public $category;
	public $parent_category;
	public $post_tags;
	public $thumbnail_image;
	public $front_image;
	public $parent_post;
	public $post_type = array('select:post_type '=> array('post'=>'Post','page'=>'Page'));
	public $content = 'textarea ';
	public $publisher;
	public $summary = 'textarea ';
	public $published = 'date ';
	public $created;
	public $last_edit;
	public $status = array('select:status'=> array('draft'=>'Draft','published'=>'Published','private'=>'Private'  ) );

	public function __toString(){
		$this->title .= $this->post_title;
		// add other stuff and formatting inside of $this->body mostly...
		// set content to a mashup of the array's parameters create function that generates html...
		$template = 
"\n\t<div id='page'>
		<div class='post'>
			<div class='title'>$this->post_title</div>
			<div class='cat'>$this->category</div>
			<div class='publisher'>$this->publisher</div>
			<div class='published'>$this->published</div>
			
			<div class='post_content'>
				$this->content
			</div>
		</div>
	</div>\n";

		$this->body = $template;
		// css code
		$this->head .= "<link rel='stylesheet' href='style.css'>\n\t";
		return parent::__toString();
	}
	public function __construct($json){
		;		
	}

}

$blog = apc_fetch('blog');
// simple one line APC check/setter
!$blog && $blog = new blog AND apc_add('blog',$ez_blog);

	
echo $blog();

$time = microtime(); 
$time = explode(" ", $time); 
$time = $time[1] + $time[0]; 
$finish = $time; 
$totaltime = (round($finish - $start,3)  * 1000); 

echo '<div id="stats">'.round((memory_get_usage() / 1024),1) . "(k) $totaltime (ms)</div>";
