<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class error extends controller {

    function indexAction() {
        send_http_status(404);
        $this->display('error\404', false);
    }

}

?>
