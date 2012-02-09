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
*/
class poform{
	public static function auto($object){
	// automatically load/make and echo out an object  - makes for cleaner syntax
		return self::make(self::load($object));
	
	}

	public static function make($object,$i=false){
	// prevents objects that have been 'cleared out' from rendering ...
	if((int) count($object) > 0 ){
	// .$_SERVER['PHP_SELF'] . $_SERVER['QUERY_STRING'] .
		$r='';
//		$r = ($i == false?"\n".'<form action="" method="POST">' . "\n<fieldset>" :'');
		foreach($object as $a=>$b)
			$r .= ($a != 'missing'? (is_array($b) || is_object($b)?self::make($b,true): $b) :'');
		// should make sure that we have actual inputs before rendering a 'form' (incase an object is replaced with purely html values...)
		return ($i == false && $r != ''  ? "\n<form action=\"\" method=\"POST\">\n<fieldset>\t$r\n</fieldset>". self::make_input('submit','Go') . 
		"\n</fieldset>\n</form>\n":($i == true?"\n\t$r\n":''));
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
					return    '<input' . implode($a,'') .  (isset($inner) ? ($inner != NULL? $inner : NULL) :NULL) . '/>' ;		
				break;
			case 'html':
				return $value;
				break;
			case 'submit':
				return '<input type ="submit" value ="' . $name . '">';
				break;
			case 'textarea':
//				die('this:'.print_r($type . $name . $inner . $value . $placeholder));
				return '<textarea name="'.$name.'"'. "$inner>$_POST[$name]</textarea>";
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
			return "\t\t<fieldset>" .self::labeler($field_name) . 
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
		return  "<label for='$field_name'>".  ucwords(str_replace('_',' ',$field_name)).($required == NULL ? '' : ( in_array($field_name,$required)? (!isset($_POST[$field_name]) || $_POST[$field_name] == '' ? '<b class="req">*required</b>' : '' ) : NULL    ) ) . "</label>\n"  ;
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
				foreach($select_array as $s)
					if(!(strpos($object,"$s ") === false ))
					// basically we have to make this do the block of code below .... hmmm
						return "\t<fieldset>\n\t\t". self::labeler($id,$required) . self::make_input(trim($s) , $id,'', '' ,$s) . "\n\t</fieldset>\n";

			// try to use new labler syntax for the make_input ? 
			return  "\t<fieldset>\n\t\t". self::labeler($id,$required) . 
			"\t\t". self::make_input('text',$id,'',($object != '0' || $object != ''?  (isset($_POST[$id]) ?  $_POST[$id]:NULL)  :NULL), ucwords(str_replace('_',' ',$id)) ).
			"\n\t</fieldset>\n";
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
						
		return (isset($r) ? $r : $object);
		}
}

