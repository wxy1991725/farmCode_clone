<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class HomeModel extends Model {

    function __construct($tablename) {
        parent::__construct($tablename);
        var_dump($this->showlog());
    }

}

?>
