<?php
/*

poform
January 2012
Ronaldo Barbachano 

A bit messy.. to be rewritten using magic methods.

to do :: 
 better reporting of when a record inserts ... 
 
 create a class method (in poform cntrl) to create a viewer for users to edit/delete and view things
 in 'tabular' format .. ? 

implement '__to_string' ? in an object oriented fashion ? 

	 For basic uploads, designate class parameters as 'file ' to use
	couch DB stores the uploaded file name, and references self::img_dir_link when displaying.
	
	To remove an image delete it from the field and 'save'. Image will remain in image directory...
	Re-upload as desired.
	
	Filenames that are identical and existing will set the field name to that file.
	
	Uncomment below and provide with directory locations and appropriate permissions (755(might work) or 777 ..)
	
*/

// simple check that loads basic script to handle file and validation etc. when a file is posted...
// for now files are limited to jpg/gif/png but other types can be added to the valid field array



interface poformConfig{
// for poform...
	const img_dir = '/var/img/';
	const img_dir_link = '/up/img/';
	// kilobytes
	const file_size_limit = 2048;
	
}


class poform implements poformConfig{
	public static function auto($object){
	// all the issets is to avoid php notices. That would be fun. Make use of magic isset function to
	// drive a program flow...
	if(isset($_FILES[key($_FILES)]) && $_FILES[key($_FILES)]['size'] > 0){
		$file_types = array ('image/gif','image/jpeg','image/pjpeg','image/png');
		$field_name = key($_FILES);
		// do this better...
		if(in_array($_FILES[$field_name]["type"],$file_types)){
			if($_FILES[$field_name]["size"] <  1024  *  self::file_size_limit){
			  if ($_FILES[$field_name]["error"] > 0)
				{
				echo "Return Code: " . $_FILES[$field_name]["error"] . "<br />";
				}
			  else
				{
				if (file_exists(self::img_dir . $_FILES[$field_name]["name"]))
				  {
				  echo $_FILES[$field_name]["name"] . " already exists. ";
				  $_POST[$field_name] = $_FILES[$field_name]["name"];
				  unset($_FILES[$field_name]);
				  
				  }
				else
				  {
				  if(move_uploaded_file($_FILES[$field_name]["tmp_name"],  self::img_dir . $_FILES[$field_name]["name"]) ){
		//			  echo "Stored in: " . self::img_dir . $_FILES[$field_name]["name"];
					 echo "File Uploaded";
					  $_POST[$field_name] = $_FILES[$field_name]["name"];
					  unset($_FILES[$field_name]);
					  }
				   else
					   echo 'Did not move file from temp.. check permissions on "' . self::img_dir .'"';
				  }
				}
			}else{
				echo "File too large";
			}
		
		}else{
			echo "Invalid image file";
		}
		}
	
	// automatically load/make and echo out an object  - makes for cleaner syntax
		return self::make(self::load($object));
	
	}

	public static function make($object,$i=false){
	// prevents objects that have been 'cleared out' from rendering ...
	if((int) count($object) > 0 ){
		$r='';
		foreach($object as $a=>$b)
			$r .= ($a != 'missing'? (is_array($b) || is_object($b)?self::make($b,true): $b) :'');
		// should make sure that we have actual inputs before rendering a 'form' (incase an object is replaced with purely html values...)
		return ($i == false && $r != ''? noClass_html::form(noClass_html::fieldset($r) . noClass_html::input__submit('Go') ,'action="" method="POST" enctype="multipart/form-data"'):($i == true?"\n\t$r\n":''));
		}
	}
	
	private static function make_input($type,$name=NULL,$inner=NULL,$value=NULL,$placeholder=NULL){
	// This needs a tad bit of work. Simply builds an input HTML tag by the use of constructing an assoc. array
	// Could make use of array functions instead of foreach loops 
		$name = trim($name);
		// html field types that do not take 'value' statements ...
		switch (trim($type)) {
			default:
					$ab_types = array('select','radio','number','range','checkbox','password','textarea');
					$a_names = array('type','name','inner','value','placeholder');
					// this needs some work..
					foreach(func_get_args() as $loc=>$value)
						$value != NULL && $loc != 2 && $value != ''	AND	$a[trim($a_names[$loc])] = trim($value);
					
					!in_array($name,$ab_types) AND isset($_POST[$name])  && $_POST[$name] != '' AND $a['value'] = $_POST[$name];
					 $type != 'checkbox' && !isset($a['value'])  && strpos($inner,'value') === 0 AND $a['value'] = $inner;
					if(isset($a['value']))
						if($a['value'] == $inner)
							unset($inner);
					foreach(array_filter($a) as $key=>$value)
							$a[$key] = " $key='$value'";				
					return    noClass_html::input( NULL , (isset($a) && is_array($a) ? implode($a,'') : '') .    ($inner != NULL? $inner : NULL) );		
				break;
			case 'html':
				return $value;
				break;
			case 'submit':
				return noClass_html::input__sumbit($name);
				break;
			case 'textarea':
				return noClass_html::textarea((isset($_POST[$name]) ?$_POST[$name]:'') , 'name="'.$name.'"'. ($inner?$inner:'') );
				break;
		}	
	}
	
	private static function build_arr($array,$field_name,$type,$required=NULL){
	// Designed for radio/checkboxes and select html types that need sets of values as its input.
	// Also handles proper 'selected' radio on/offs values based on $_POST object
	// Checkboxes are still under development, as well as multi-select items ...
		$field_name = trim($field_name);
		if($type == 'number' || $type == 'range'){
			// this needs work...
			return noClass_html::fieldset(self::labeler($field_name) )  . 
			self::make_input($type,$field_name, (count($array) > 0? ($array[0] !== 0?" min='".$array[0]."' ":'') . (isset($array[1]) ?" max='".$array[1]."' ":'') . (isset($array[2])?" step='".$array[2]."' ":'') .  (isset($_POST[$field_name]) && $_POST[$field_name] != '' ? ' value="' . $_POST[$field_name].'"' : '')  : ''))."\n" . ( isset($_POST[$field_name])  && in_array(trim($field_name),$required)? ($_POST[$field_name] == '' ? '<b class="req">*required</b>' : '') : NULL    ) . '</fieldset>';	
		}elseif(is_object($array) || is_array($array)){
		// consider comparing isa against another array value to avoid two type checks ? 
		$r='';
		foreach($array as $k=>$v){
				// not a great place for this complex statement ...
				$r .=($type == 'radio' || $type == 'checkbox'?"\n\t\t<fieldset><em>$v</em> \n" :''). ($type != 'select'?
																									"\t\t".self::make_input($type,$field_name,
																																				($k != '' && $k != '0'?' value = "' . $k . '"'.	((isset($_POST[$field_name]) && ($_POST[$field_name] == $k || ($k == 1 && $_POST[$field_name] == 'on')))?($type == 'radio' ?' checked ':'') :''):(isset($_POST[$field_name])?$_POST[$field_name]:''))): "\t\t\t<option value='$k' ".(isset($_POST[$field_name]) == $k? (isset($_POST[$field_name])  ? ($_POST[$field_name] == $k ?' selected="selected" ' : '') : ''):'').">$v</option>\n" ).($type == 'radio' || $type == 'checkbox'? '</fieldset>':NULL);
			}
			return   '<fieldset>'.self::labeler($field_name,   $required ).  
			($type != 'select'? $r : "\t\t" .'<select name="'.$field_name.'"'.'>'."\n\t\t\t".'<option value="">Select '.str_replace('_',' ',$field_name)."</option>\n" . $r . "\t\t</select>" ) .'</fieldset>';
		}
	}
	
	private static function labeler($field_name,$required=NULL){
		isset($_SERVER['REQUEST_METHOD']) AND $_SERVER['REQUEST_METHOD'] != "POST" AND $required = NULL;
		return  noClass_html::label(ucwords(str_replace('_',' ',$field_name)).($required == NULL ? '' : ( in_array($field_name,$required)? (!isset($_POST[$field_name]) || $_POST[$field_name] == '' ? '<b class="req">*required</b>' : '' ) : NULL    ) ) ,"for='$field_name'");
	}

// make a special function for fields that need to be 'confirmed' ? (right now mainly for email/passwords)
// search the _POST object for a confirm ... and if the two fields dont match pass a special message to the labeler ?

	public static function load($object,$id=NULL,$alt_id=NULL,$required=NULL){
		isset($object->_f) || isset($object->_d) AND $a = $object->_f AND isset($object->_d) && is_array($a) AND $a = array_merge($a,$object->_d) AND $a [] = '_d';

		isset($object->_r) && $required == NULL AND $_r = $object->_r AND $required = $_r;
		// remove values that are not in the _f or _d lists
		
		if(is_object($object))
			foreach($object as $key=>$value){
				if(is_array($a) && !in_array($key,$a)){
					unset($object->$key);
					}
				}
				
		if(isset($object->_d) ){
		// refers to 'hidden' variables, those used for forms or database identification
			foreach($_POST as $k=>$v)
				in_array($k,$object->_d) AND $object->$k = array("hidden:$k"=>array(''=>'hidden'));
			unset($object->_d);
		}

		// special directive processing based on field type...
		$select_array = array('select','checkbox','password','textarea','radio','sumbit','email','search','number','date','hidden','html');

		if($id != NULL){
			$id = explode(':',$id,2);
			count($id) == 2 AND $a_id = $id[1];
			
			$id = $id[0];

			if($id == 'html') return($object);
			if(is_array($object) && in_array($id ,$select_array )){
				if($id == 'hidden') return self::make_input($id,$alt_id,NULL,$_POST[$alt_id]) ."\n";
				return self::build_arr($object , ($a_id?$a_id:$alt_id),$id,$required);
			}
			elseif(!is_array($object) && !is_object($object)){
				if((!isset($_POST[$id]) || $_POST[$id] == '') && $object=='file ')
					$select_array [] = 'file';
				foreach($select_array as $s)
					if(!(strpos($object,"$s ") === false ))
					// basically we have to make this do the block of code below .... hmmm
						return noClass_html::fieldset( self::labeler($id,$required) . self::make_input(trim($s) , $id,'', '' ,$s) ) ;

			// try to use new labler syntax for the make_input ? 
			return  noClass_html::fieldset( self::labeler($id,$required) . 
			"\t\t". self::make_input( ($object != 'file'? 'text': 'file'  ),$id,'',($object != '0' || $object != ''?  (isset($_POST[$id]) ?  $_POST[$id]:NULL)  :NULL), ucwords(str_replace('_',' ',$id)) )
			);
			}
		}
		// Recurse... 
		elseif(is_array($object))
			foreach($object as $x=>$y)
				$r [$x] = self::load($y,$x,NULL,$required);
					
		elseif(is_object($object))
				foreach($object as $x=>$y)
				// check to see if an entry is contained in the a parameter
				// to do validations or create drop down menus with values 
					if(is_array($y) || is_object($y))
						foreach($y as $a=>$b)
							$r[$x][$a] = self::load($b,$a,$x,$required);
					else
						$r[$x] = self::load($y,$x,$x,$required);
						
		return (isset($r) ? array_reverse($r) : $object);
		}
}

