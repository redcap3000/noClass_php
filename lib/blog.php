<?php
require('noClass.php');
class blog extends noClass_html{

	public $_f = array('_id','post_title','category','post_type','post_tags','publisher','summary','content','status');
	public $_r = array('_id','post_title','post_type','publisher','content','status');
	public $post_title;

	public $category;
	public $parent_category;
	public $content = 'textarea ';

	public $post_tags;
	public $thumbnail_image;
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
		$this->title .= $this->post_title;
		return parent::__toString();
	}
}

