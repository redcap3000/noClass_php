**NoClass Quickstart Guide**

***To run the 'blog' example.php***

1) Let noClass_html extend either 'couchCurl', for Apache Couch DB, or 'moniKey' for Mongo DB.

2) Edit lib/interfaces.php - noSqlConfig - to define basic database settings (host).

**Couch DB**

3) If you are using CouchDB and do not have a database created as the same name of the class (in this case 'blog')
then that will need to be created. CouchCurl uses 'exec' so you'll need the appropriate permissions and may want to take 
a few precautions.


**Mongo DB**

3) If you already have the MongoDB php extension (and operating)...You're set....
Load example.php and begin adding records.