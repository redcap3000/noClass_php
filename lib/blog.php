<?php
require('noClass.php');

 define('IMAGE_DIR','/var/www/htdocs/mdocs.net/up/img/');
// a 'truncated' directory link for public facing URLS/links etc.
 define('IMAGE_DIR_LINK','/up/img/');


class blog extends noClass_html{

	public $_f = array('_id','post_title','category','post_type','post_tags','thumbnail_image','publisher','summary','content','status');
	public $_r = array('_id','post_title','post_type','publisher','content','status');
	public $post_title;
	public $category;
	public $parent_category;
	public $content = 'textarea ';
	public $post_tags;
	public $thumbnail_image = 'file ';
	public $front_image;
	public $parent_post;
	public $post_type = array('select:post_type '=> array('post'=>'Post','page'=>'Page'));
	public $publisher;
	public $summary = 'textarea ';
	public $published = 'date ';
	public $created;
	public $last_edit;
	public $status = array('select:status'=> array('draft'=>'Draft','published'=>'Published','private'=>'Private'  ) );

	public function __toString(){
		if(!isset($this->category)) $this->categories();
		if($this->_id != ''){
			$this->title .= $this->post_title;
			// Try to add via container..?
			// quick and easy way to avoid a field showing its parameter setting (if its a string..)
			if(isset($this->thumbnail_image) && strpos($this->thumbnail_image,'.'))
				$this->thumbnail_image = IMAGE_DIR_LINK . $this->thumbnail_image;
		}
		
		return parent::__toString();
	}
	
	public function __construct($container=NULL){
	// do database check to see that a 'blog' db is present
		$this->categories();
		parent::__construct($container);
	}

	public function categories($recurse=false){
		$r = $this->get('blog_categories');
		
		if((isset($r['error']) ||$r == false) && $recurse==false){
		// auto population ... assumes that the error is a document not found ...	
			$default_cat = array('_id'=> 'blog_categories', 'category'=> array ('news','events','blog','other','uncategorized') );
			$this->put($default_cat,'blog_categories','blog');
		// instead of returning the values above, I thought this was safer to attempt to run the transaction again,
		// although if it is not sucessful will end up with an infinte loop ...
			return $this->categories(1);
		}elseif(isset($r['error']) || $r == false ){		
			die('Something is up with the blog database; could not create default categories.');
		}
		if(isset($r['category']))
			$this->category = array('select:category'=> array_combine($r['category'],$r['category']));	
	}
}
