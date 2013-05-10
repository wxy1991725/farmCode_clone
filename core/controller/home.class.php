<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of index
 *
 * @author Administrator
 */
class Home extends controller {

    //put your code here

    function indexAction() {
        //if (!$this->isCache($this->controller . DS . $this->action)) {
        $model = buildModel();
        $result = $model->getbyid(1);
//        dump($result);
//        dump($model->showError());
//        dump($model);
        $this->assgin('array', $result);
        $this->assgin('eee', '1111fqew1');
        //}

        $this->display('~index', true);
    }

    function cookieAction() {
        Cookie::set('firstCookie', 'Test By Myself');
        $this->assgin('url', 'uncookie', true);
        $this->indexAction();
    }

    function uncookieAction($cookie = null) {
        dump($cookie);
        if (!empty($cookie))
            Cookie::delete($cookie);
        else {
            Cookie::clear();
        }
        $this->assgin('eee', 'Test By Myself', true);
        $this->assgin('url', 'cookie');
        $this->indexAction();
    }

}

?>
