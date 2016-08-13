<?php

/*
The MIT License (MIT)

Copyright (c) 2016 Marcin Borowicz <marcinbor85@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

class EventLogger {
    private $db;
    function __construct($database) {
        if (is_string($database)) $this->db = new SQLite3($database);
        elseif ($database instanceof SQLite3) $this->db = $database;
    }
    private function create_table($model) {
        $this->db->exec("CREATE TABLE ".$model::get_schema());
    }
    private function check_table_exist($model) {
        $res = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='".$model."'");
        $ret = $res->fetchArray();
        return (bool)$ret;
    }
    private function check_table($model) {
        if (!$this->check_table_exist($model)) $this->create_table($model);
    }
    function get_table($model) {
        $this->check_table($model);
        $res = $this->db->query("SELECT rowid, * FROM ".$model." ORDER BY date DESC LIMIT 100");
        $ret = array();
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            array_push($ret, $model::build($row));
        }
        return $ret;
    }
    function process($model) {
        switch ($_SERVER["REQUEST_METHOD"]) {
            case "POST":
                $row = $model::build($_POST);
                $this->insert($row);
                $this->show_table($model, array($row));
                break;
            case "GET":
                $rows = $this->get_table($model);
                $this->show_table($model, $rows);
                break;
        }
    }
    function show_table($model, $rows) {
        echo "<table border='1'>";
        echo "<tr>";
        foreach (get_class_vars($model) as $key => $value) {
            echo "<th>".$key."</th>";
        }
        echo "</tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>".$value."</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    function insert($row) {
        $model = get_class($row);
        $this->check_table($model);
        $query = "INSERT INTO ".$model::get_columns_schema()." VALUES ".$model::get_values_schema();
        $commit = $this->db->prepare($query);
        $i = 1;
        foreach (get_object_vars($row) as $key => $value) {
            $commit->bindValue($i, $value);
            $i += 1;
        }
        $commit->execute();
    }
    function close() {
        $this->db->close();
    }
}

abstract class RowModel {
    public $ip = "";
    public $date = "";
    
    static function get_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP'];
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else $ip = $_SERVER['REMOTE_ADDR'];
        return (string)$ip;
    }
    static function get_date() {
        $now = new DateTime();
        return (string)$now->format('Y-m-d H:i:s');
    }
    static function get_schema() {
        $model = get_called_class();
        $ret = $model." (";
        foreach (get_class_vars($model) as $key => $value) {
            if (is_int($value)) $ret .= $key." INTEGER, ";
            elseif (is_float($value)) $ret .= $key." REAL, ";
            else $ret .= $key." TEXT, ";
        }
        $ret = substr($ret, 0, -2).")";
        return $ret;
    }
    static function get_columns_schema() {
        $model = get_called_class();
        $ret = $model." (";
        foreach (get_class_vars($model) as $key => $value) {
            $ret .= $key.", ";
        }
        $ret = substr($ret, 0, -2).")";
        return $ret;
    }
    static function get_values_schema() {
        $model = get_called_class();
        $ret = "(";
        foreach (get_class_vars($model) as $key => $value) {
            $ret .= "?, ";
        }
        $ret = substr($ret, 0, -2).")";
        return $ret;
    }
    static function build($map) {
        $model = get_called_class();
        $row = new $model();
        foreach (get_class_vars($model) as $key => $value) {
            if (isset($map[$key])) {
                if (is_int($row->$key)) $row->$key = (int)$map[$key];
                elseif (is_float($row->$key)) $row->$key = (float)$map[$key];
                else $row->$key = (string)$map[$key];
            }
        }
        if (empty($row->ip)) $row->ip = $model::get_ip();
        if (empty($row->date)) $row->date = $model::get_date();
        return $row;
    }
}

?>
