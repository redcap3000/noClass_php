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

*Supports input types	

	echo htmler::input__text__myInput('A value');
*A link syntax

	echo htmler::a('http://here.com','Here','title="here"');
				
*/

class htmler{
	public static function __callStatic($name,$arguments){
		$name = explode('__',$name,3);
		// to do avoid all the returns ... funnel all calls into one or max two calls ... especially for the swtiches ..
		if(in_array($name[0],array('a','link','input') ))
		// theres a few other types that use 'html' element' think all things meta or in the head
			if($name[0] == 'a')
				$extra = " href='$arguments[0]'" . (!isset($arguments[2])?'' : " $arguments[2]") AND $arguments[0] = $arguments[1];
			else
				return  trim("\n<$name[0] ". ($name[0] == 'link' ? 'rel="'.$name[1].'" href="'.$arguments[0].'" ' . (isset($arguments[1])? "$arguments[1]" :NULL) : (isset($name[2]) ? " id='$name[2]'":'') . (isset($name[1]) ? "type='$name[1]'" :'') .(isset($arguments[0])? "value='$arguments[0]'" :NULL) . (isset($arguments[1])? " $arguments[1]" :NULL) ).'>' );
		return "\n<$name[0]".(!isset($name[1])?'':" class='$name[1]'") . (!isset($name[2])?'': " id='$name[2]'") . (!isset($extra)?'':$extra). (!isset($arguments[1]) ?'' : " $arguments[1]").'>'. (isset($arguments[0]) ? $arguments[0] : '') . "</$name[0]>\n";
	}
}
