<?php

require_once 'lib/simple_html_dom.php';

class AddMeFast {
    
    private $email, $password, $cookie;
    
    public function __construct($email, $password, $cookie = '/tmp/addmefastapi.cookie') {
        $this->email = $email;
        $this->password = $password;
        $this->cookie = $cookie;
    }
    
    private function login() {
        $curl = $this->initCurl();
        
        curl_setopt($curl, CURLOPT_URL, 'http://addmefast.com/');

        curl_setopt($curl, CURLOPT_POSTFIELDS,
        http_build_query(array(
        'email' => $this->email,
        'password' => $this->password,
        'login_button' => '1'
        )));

        $res = curl_exec($curl);

        print_r($res);
        

        
        
    }
    
    private function initCurl() {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION , 1);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookie);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookie);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        
        return $curl;
    }
    
    public function getSites() {
        $curl = $this->initCurl();
        
        $this->login();
        
    }

    public function addSite() {
        
    }
    
}