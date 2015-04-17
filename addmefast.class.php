<?php

require_once 'lib/simple_html_dom.php';

class AddMeFast {
    
    private $email, $password, $curl;
    
    private static $types = array(
        1 	=> 'facebook/likes',
        18 	=> 'facebook/share',
        11 	=> 'facebook/followers',
        25 	=> 'facebook/post-like',
        28 	=> 'facebook/post-share',
        39 	=> 'tsu.co/followers',
        35 	=> 'vine/followers',
        36 	=> 'vine/likes',
        37 	=> 'vine/revines',
        9 	=> 'google/circles',
        34 	=> 'google/post-share',
        6 	=> 'youtube/subscribe',
        8 	=> 'youtube/video-likes',
        31 	=> 'youtube/favorites',
        5 	=> 'youtube/views',
        3 	=> 'twitter/followers',
        17 	=> 'twitter/tweets',
        16 	=> 'twitter/retweets',
        15 	=> 'twitter/favorites',
        19 	=> 'instagram/followers',
        32 	=> 'instagram/likes',
        33 	=> 'ask.fm/likes',
        29 	=> 'vkontakte/pages',
        30 	=> 'vkontakte/groups',
        20 	=> 'myspace/friends',
        7 	=> 'pinterest/followers',
        23 	=> 'pinterest/repins',
        24 	=> 'pinterest/likes',
        21 	=> 'reverbnation/fans',
        22 	=> 'soundcloud/follow',
        38 	=> 'soundcloud/likes',
        10 	=> 'soundcloud/plays',
        14 	=> 'stumbleupon/followers',
        4 	=> 'website/hits'
    );
    
    public function __construct($email, $password) {
        $this->email = $email;
        $this->password = $password;
    }
    
    private function login() {
        
        $this->initCurl();

        curl_setopt($this->curl, CURLOPT_URL, 'http://addmefast.com/');

        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
        http_build_query(array(
            'email' => $this->email,
            'password' => $this->password,
            'login_button' => '1'
        )));

        $answer = curl_exec($this->curl);

        if (strpos($answer, '<a href="/profile">My Profile</a>') != true)
            throw new Exception('can not login');
        
    }
    
    private function initCurl() {
        if (empty($this->curl)) {
            $this->curl = curl_init();

            curl_setopt($this->curl, CURLOPT_POST, 1);
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION , 1);
            curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookie);
            curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookie);
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->curl, CURLOPT_USERAGENT, 'Opera/9.80 (X11; Linux i686) Presto/2.12.388 Version/12.16');
        }
        
    }
    
    public function getSites() {
        $this->login();

        curl_setopt($this->curl, CURLOPT_URL, 'http://addmefast.com/my_sites');
        $answer = curl_exec($this->curl);

        
        $html = str_get_html($answer);
        
        if (empty($html)) throw new Exception('broken markup: no html');

        $cells = $html->find('.mysites2_table', 0);
        
        $result = array();
        foreach ($cells->find('.table_item') as $row) {

            list(,,, $type, $id) = explode('/', $row->find('td', 8)->find('a', 0)->rel);
            $id = $type.'_'.$id;
            
            list(,,, $status) = explode('/', $row->find('td', 1)->find('img', 0)->src);
            list($status) =  explode('.', $status);
            
            
            $result[$id] = (object) array(
                'id' => $id,
                'status' => $status,
                'title' => $row->find('td', 2)->title,
                'type' => empty(self::$types[$type]) ? 'unknown' : self::$types[$type],
                'DCL' => $row->find('td', 4)->plaintext,
                'TCL' => $row->find('td', 5)->plaintext,
                'Clicks' => $row->find('td', 6)->plaintext,
                'CPC' => $row->find('td', 7)->plaintext,
               
            );

        }
        
        print_r($result);

    }

    public function addSite($type, $url, $points_per_click = 5, $total_clicks = 0, $daily_clicks = 0, $countries = null, $title = '') {
        
        if (!$keys = array_keys(self::$types, $type)) throw new Exception('wrong site type');
                
        $type = reset($keys);
        echo($type);
        $this->login();

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
            "Accept: application/json, text/javascript, */*; q=0.01",
            "Accept-Language: en-us,en;q=0.5",
            "Connection: keep-alive",
            "X-Requested-With: XMLHttpRequest",
        ));

        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
        $query = http_build_query(array(
                'act' => 'addsite',
                'params' =>
                '{' . implode(',', array(
                    '"title"    : "'.$title.'"',
                    '"url"      : "'.$url.'"',
                    '"type"     : "'.$type.'"',
                    '"cpc"      : "'.$points_per_click.'"',
                    '"clicks"   : "'.$total_clicks.'"',
                    '"daily"    : "'.$daily_clicks.'"',
                    '"countries": "null"'
                )).
                '}'
            ))

        );

//        die($query);
        curl_setopt($this->curl, CURLOPT_URL, 'http://addmefast.com/includes/ajax.php');

        $answer = curl_exec($this->curl);

        $html = str_get_html($answer);

        if (empty($html)) throw new Exception('bad request');
        
        if ($errors = $html->find('.error2 li')) {
                
            foreach ($errors as $key => $value) {
                $errors[$key] = $value->plaintext;
            }
            
            return $errors;
                
        }


        curl_setopt($this->curl, CURLOPT_URL, 'http://addmefast.com/my_sites/add_success');
        $answer = curl_exec($this->curl);
        
        $html = str_get_html($answer);
        if (empty($html)) throw new Exception('bad request');
        
        $rel = $html->find('table.mysites2_table tr.table_item td', 8)->find('a', 0)->rel;
        
        list(,,, $type, $id) = explode('/', $rel);
        $id = $type.'_'.$id;

        curl_setopt($this->curl, CURLOPT_URL, 'http://addmefast.com' . $rel);
        $answer = curl_exec($this->curl);
        
        
//        var_dump($answer); 
        
        print_r(curl_getinfo($this->curl));
    }
    
}