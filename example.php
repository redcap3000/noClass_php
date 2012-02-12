<?php

/*

	NoClass Framework Blog Example

	Shows a basic example of how to make custom classes work with noClass_html
	Includes stats reporting (time and memory use)

*/
$time = microtime(); 
$time = explode(" ", $time); 
$time = $time[1] + $time[0]; 
$start = $time; 

// Require blog class
require('lib/blog.php');

// Example Template
$container = 
array('head' => 
	      array('link__stylesheet'=>'main.css') ,
      'body'=> 
	       array('div__page'=>
               		array('div__post' =>
	                            array( 'post_title'=> 'h2__' ,
					   'category'=>'h3__',
					   'publisher'=>'h4__',
					   'post' => 'div__',
					   'post_tags' => 'div__'
					  )
                             )
                     )
       );
// Load Template
$blog = new blog($container);

// Load blog editor 
echo $blog();


//$blog = apc_fetch('blog');
// simple one line APC check/setter
//!$blog && $blog = new blog AND apc_add('blog',$blog);


// Stats Reporting
$time = microtime(); 
$time = explode(" ", $time); 
$time = $time[1] + $time[0]; 
$finish = $time; 
$totaltime = (round($finish - $start,3)  * 1000); 

echo '<div id="stats">'.round((memory_get_usage() / 1024),1) . "(k) $totaltime (ms)</div>";
