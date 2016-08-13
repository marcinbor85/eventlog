# eventlog
Micro (single-file) PHP framework for event logging with simple ORM layer and SQLite3 database.
For documentation and more details see example and source code.

### Main features
- extremely simple, fast and easy to use
- SQLite3 database support
- Object-Relational Mapping for text, integer and float fields
- support multi-tables
- automatically generate table model schema
- build-in simple table view
- build-in IP and DATE logging
- customizable logging table fields
- Object-Oriented design
- examples

### Main goals
- reusing at event logging applications 
- lightweight
- educational aspects

### TODO:
- [ ] add some TDD tests

# Examples
A few lines of source code means more than huge and fat documentation...

### Simple event logging example:
```php
<?php

// import library
require("eventlog.php");

// define database "Event" table model
class Event extends RowModel {
    // text
    public $source = "default_source";
    // text
    public $event = "default_event";
    // integer
    public $priority = 100;
    // real
    public $factor = 0.5;
}

// create main EventLogger object with database initialization
$app = new EventLogger("base.db");
// call default request (GET and POST) handler for "Event" table
$app->process("Event");
// close database
$app->close();

?>
```

