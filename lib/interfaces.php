<?php
interface noSqlCRUD extends noSqlConfig
{
    public function get($id,$database);
    public function put($data,$id,$database);
    public function view($name,$key,$database,$opt);
    public function update($id,$data,$database);
    public function delete($id,$opt,$database);
}

interface noSqlConfig{
	/* store sql config related stuff here depending on your 
	 db type (couch,mongo) becomes accessisble everywhere within object scope as self::-const-
	 database name, url if using couchCurl self::mongodb
	*/
	const mongodb= 'moniKeyDB';
	// application prefix (for cookies mostly)
	const apf = 'default';
	// for couch ONLY, name of 'default' design document - specifically needed for authentication
	// views
	const couch_design_doc = 'cntrl';
	const couchdb= 'http://localhost:5984';
}



interface noSqlSession{
    public function make_token($id,$expire);
    public function check_token($id);
    public function destroy_token($id);
    public function __construct($id);
}




//define('self::apf','default');
// this is the name of the design document (for convention reason its advisible this
// is the same across all databases
// cookie class extends couch curl ... ? 

require('couchCurl.php');

/*

Coming soon...

interface noSqlSession extends noSqlCRUD{
    public function make_token($id,$expire);
    public function check_token($id);
    public function destroy_token($id);
    public function __construct($session);
}

interface noSqlUser{

}
*/
