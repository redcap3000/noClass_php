<?php
/*
	Basic crud interface. It gets the job done... interface extends a 
	config too that the implementing class can use like a (Static) global.
*/

interface noSqlCRUD extends noSqlConfig
{
    public function get($id,$database);
    public function put($data,$id,$database);
    public function view($name,$key,$database,$opt);
    public function update($id,$data,$database);
    public function delete($id,$database);
}

interface noSqlConfig{
	/* store sql config related stuff here depending on your 
	 db type (couch,mongo) becomes accessisble everywhere within object scope as self::-const-
	 database name, url if using couchCurl self::mongodb
	*/
	const mongodb= 'moniKeyDB';
//	const couchdb= 'http://localhost:5984';
}
/*

Coming soon...


interface sqlCRUD extends noSqlConfig{ 
/* Idea - Allow noClass_php to use mysql databases

	Here's how it could be done :
	A) Store serialized objects with only an _id key
		- Challenge
			Handling views without loading each record.
		- Solution
			Use class param ($_-letter-) to designate pkey,unique key etc. (but still store serialized object)
	B) Use alter statements to modify a table schema
		- Challenge 
			Losing data when dropping columns.
			Updating any referencing queries (if present?)

*/
    public function alter($table,$action,$opt);
    public function 	
}

interface noSqlSession extends noSqlCRUD{
    public function make_token($id,$expire);
    public function check_token($id);
    public function destroy_token($id);
    public function __construct($session);
}




interface noSqlUser{

}
*/

