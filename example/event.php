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
