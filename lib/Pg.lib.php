<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$time = microtime(TRUE);
pg_connect("host=localhost port=5432 dbname=postgres user=postgres password=admin");
dump(microtime(true) - $time);
?>
