***noClass framework***
	
**a NSQL approach to framework development**


**Purpose of Repository**

This repo is here to demonstrate how the noClass framework design is implemented. The only files truly needed in a noClass framework are external libraries that modify the final 'object'.

Most of the functionality (in the php version) works based on modern versions of PHP (>5.3) and uses magic methods, and late static binding features.
	


**Framework Best Pratices**

*Define and store class parameters using JSON

*Use classes in scripting enviornment to 'prototype'.
	
*Use late static binding methods to perform object class name checks.


*Most (ideally all) common db interactions happen via naming convention and special class array parameters.
	
*By default a class name is a database/storage unit.
	
*Static libraries can be made to manipulate output of object (apply html behaviors); See 'poform'.
	
*Functions are defined in classes to access a NoSQL view. Parameters can be 'related' to other databases/classes via class extension (example you can call a view function from a parent class to fill in a selection option list in the child class.)
	
*Special class parameters can be defined as arrays containing class parameter names and modified to do basic tasks such as field visibilty and other coded behaviors.

*These class parameters can be quickly edited in a couch db in a shared enviornment as simply as modifying json array structures.
	
*Class parameters can be defined as values (in the class) to designate specific functionality in the framework (such as the naming conventions made available by library poform or other libraries to limit or define how the data is stored in the db).

•Classes can then be serialized/jsonized and loaded into a object cacher	(like apc cache/memcached). Parameter designations can be removed, and specific database/class functionality is performed in a construction method.


**Included Example**

The example includes a 'html' class which also contains a  overloaded __invoke() function to handle basic database tasks (from apache couch using couchCurl). This class is an excellent starting point for creating simple HTML-based sites.
