<?php
/*
	Htmler
	Ronaldo Barbachano
	Feb 2012
	AGPL
	
	A very simple php5.3 class that takes advantage of overloaded magic method '__callStatic'.
	
	Htmler processes method names to dynamically call private functions	that generate html.
	
***Usage***



*Example make a 'div' container with 'hello world'
	
	echo htmler::div__('hello world');
	
*Example make a 'div' container with 'hello world', with class name 'worlds'
	
	echo htmler::div__worlds('hello world');
	
*Example make a 'div' container with 'hello world', with class name 'worlds', id 'my_world';
	
	echo htmler::div__worlds__my_world('hello world');

*Dynamic method naming example..

	$method_call = 'div__words';
		
	echo htmler::$method_call('hello world');
	
*Html 'element' example

	// Also supports 'link' elements (mostly for the head)..
	// others to follow shortly

	echo htmler::link__stylesheet('style.css');
	

*/

class htmler{
// to-do add input types - this is becoming poform

	public static function __callStatic($name,$arguments){
		$name = explode('__',$name,3);
		$name_count = (int) count($name);
		
		if($name_count == 1){
		// more than likely a container (like making a quick div, or ul, li, etc)
			return self::html_container($arguments[0],NULL,NULL,$name[0]);
		}elseif($name[0] == 'link'){
		// theres a few other types that use 'html' element' think all things meta or in the head
			// do for link__rel('href','anything else inside the quotes....)
			return self::html_element($name[0],'rel=" '.$name[1].'" href="'.$arguments[0].'" ' . (isset($arguments[1])? "$arguments[1]" :NULL));
		}
		// some basic container types that can fit the mold.. might want to check out my own html5 core
		elseif(in_array($name[0],array( 'div','ul','li','ol','table'))) {
			switch ($name) {
				case 2:
					// div__#div class#__#div id#(-inner-)
					return self::html_container($arguments[0],$name[1],NULL,$name[0]);
					break;
				default:
					// div__#div class#__#div id#(-inner-)
					return self::html_container($arguments[0],$name[1],$name[2],$name[0]);
					break;	
			}	
		}
	}
	
	private function html_container($value,$class=NULL,$id=NULL,$container='div'){
		// probably best with div or ul/ol etc.	
		return "\n<$container ".trim(($class ==NULL?'':" class='$class' ") . ($id == NULL?'': " id='$id' ")).">\n\t$value\n</$container>\n";
	}
	
	// allows almost complete override of how an element is displayed ? for self closing tags like <style > and others ? 
	private function html_element($element,$inner,$close_slash=false){
		return "\n<$element $inner". (!$close_slash?'':'/') .'>' ;
	}

}
