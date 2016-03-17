<?php

/**
 * Addmefast-API-PHP : Simple PHP wrapper for the AddMeFast
 * 
 * PHP version 5.3.2
 * 
 * @version 0.8
 * @package  Addmefast-API-PHP
 * @author   Anton Bagaiev <tony@dontgiveafish.com>
 * @license  GNU GENERAL PUBLIC LICENSE
 * @link     https://github.com/abagayev/addmefastapi
 */

use Sunra\PhpSimple\HtmlDomParser;

class AddMeFast {

    private $email, $password, $curl;

    /**
     * AddMeFast constructor.
     * @param string $email Your Addmefast login email
     * @param string $password Your Addmefast login password
     * @param string $cookie Path to cookie file, make sure that it's alright with access right
     */
    public function __construct($email, $password, $cookie = '/tmp/addmefast.cookie')
    {
        $this->email = $email;
        $this->password = $password;
        $this->cookie = $cookie;
    }

    /**
     * Curl initialization
     */
    private function initCurl()
    {
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

    /**
     * Curl initialization
     * @throws Exception
     */
    private function login()
    {
        $this->initCurl();

        curl_setopt($this->curl, CURLOPT_URL, 'http://addmefast.com/');

        curl_setopt($this->curl, CURLOPT_POSTFIELDS,
            http_build_query([
                'email' => $this->email,
                'password' => $this->password,
                'login_button' => '1'
            ]));

        $answer = curl_exec($this->curl);

        if (strpos($answer, '<a href="/profile">My Profile</a>') != true) {
            echo($answer);
            throw new \Exception('can not login');
        }
    }

    /**
     * Get the list of sites in your account
     * @return array containing all site as stdClass Object
     * @throws Exception
     */
    public function getSites()
    {
        $this->login();

        curl_setopt($this->curl, CURLOPT_URL, 'http://addmefast.com/my_sites');
        $answer = curl_exec($this->curl);

        $html = HtmlDomParser::str_get_html($answer);

        if (empty($html)) {
            throw new \Exception('broken markup: no html');
        }

        $cells = $html->find('.mysites2_table', 0);

        $result = array();
        foreach ($cells->find('.table_item') as $row) {

            list(, , , $type, $id) = explode('/', $row->find('td', 8)->find('a', 0)->rel);
            $id = $type . '/' . $id;

            list(, , , $status) = explode('/', $row->find('td', 1)->find('img', 0)->src);
            list($status) = explode('.', $status);


            $result[$id] = (object)array(
                'id' => $id,
                'status' => $status,
                'title' => $row->find('td', 2)->title,
                'type' => empty(self::$site_types[$type]) ? 'unknown' : self::$site_types[$type],
                'DCL' => $row->find('td', 4)->plaintext,
                'TCL' => $row->find('td', 5)->plaintext,
                'Clicks' => $row->find('td', 6)->plaintext,
                'CPC' => $row->find('td', 7)->plaintext,
            );

        }

        return $result;
    }

    /**
     * Add site to your account
     * @param string $site_type - list of types are in self::$site_types
     * @param string $subject - subject to share(url, message, account, etc)
     * @param int $points_per_click - cost per click for your site/page
     * @param string or array $countries - country name or array of country names(list of countries are in self::$countries)
     * @param string $title - title for your site
     * @param int $total_clicks - limit for total clicks for this site / page
     * @param int $daily_clicks - limit for $daily_clicks for this site / page
     * @return array containing:
     * - status(success or error)
     * - id(if exists)
     * - errors(array of errors if exists)
     * @throws Exception
     */
    public function addSite($site_type, $subject, $points_per_click, $countries = null, $title = null, $total_clicks = 0, $daily_clicks = 0)
    {

        $errors = array();

        // check and translate site type to addmefast format
        if (!$keys = array_keys(self::$site_types, $site_type)) throw new \Exception('wrong site type');
        $site_type = reset($keys);

        // translate countries list to addmefast country codes       
        if (!empty($countries)) {

            // if array given
            if (is_array($countries)) {
                foreach ($countries as $key => $value) {

                    $country_code = array_search($value, self::$countries);

                    if ($country_code === false) {
                        $errors[] = "unknown country: $value";
                        unset($countries[$key]);
                    } else {
                        $countries[$key] = $country_code;
                    }
                }

                // array to null if empty or to string
                $countries = empty($countries) ? null : implode(',', $countries);

            } // if string given
            else if (is_string($countries)) {
                $country_code = array_search($countries, self::$countries);

                if ($country_code === false) {
                    $errors[] = "unknown country: $value";
                    $countries = null;
                } else {
                    $countries = $country_code;
                }
            } // if something else given
            else {
                $errors[] = "unknown country format";
                $countries = null;
            }

        }

        // collect params

        $params = array(
            'type' => $site_type,
            'url' => $subject,
            'cpc' => (int)$points_per_click,
        );

        if (!empty($countries)) $params['countries'] = $countries;
        if (!empty($title)) $params['title'] = $title;
        if (!empty($total_clicks)) $params['clicks'] = (int)$total_clicks;
        if (!empty($daily_clicks)) $params['daily'] = (int)$daily_clicks;

        // start curling

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
                'params' => $this->raw_json_encode($params, JSON_UNESCAPED_UNICODE)
            ))
        );


        // exec ajax
        curl_setopt($this->curl, CURLOPT_URL, 'http://addmefast.com/includes/ajax.php');
        $answer = curl_exec($this->curl);

        // check result
        $html = HtmlDomParser::str_get_html($answer);
        if (empty($html)) throw new \Exception('bad request');

        // collect errors and return
        if ($answer_errors = $html->find('.error2 li')) {

            foreach ($answer_errors as $key => $value) {
                $errors[] = $value->plaintext;
            }

            return $this->formatReturn(false, null, $errors);
        }

        // get id and return
        curl_setopt($this->curl, CURLOPT_URL, 'http://addmefast.com/my_sites/add_success');
        $answer = curl_exec($this->curl);

        $html = HtmlDomParser::str_get_html($answer);
        if (empty($html)) throw new \Exception('bad request');

        $rel = $html->find('table.mysites2_table tr.table_item td', 8)->find('a', 0)->rel;

        list(, , , $site_type, $id) = explode('/', $rel);
        $id = $site_type . '/' . $id;

        return $this->formatReturn(true, $id, $errors);
    }

    /**
     * Touch site(start, pause or delete)
     * @param string $id - id in addmefast format(11/11111)
     * @param string $action - start, pause or delete
     * @return associative_array containing:
     * - status(success or error)
     * - id(if exists)
     * - errors(empty - addmefast is not giving correct answer for this actions)
     * @throws Exception
     */
    public function touchSite($id, $action)
    {
        // check action
        if (!in_array($action, array('start', 'pause', 'delete')))
            throw new \Exception('unkown action');

        $this->login();

        curl_setopt($this->curl, CURLOPT_URL, "http://addmefast.com/my_sites/$action/" . $id);
        $answer = curl_exec($this->curl);

        $html = HtmlDomParser::str_get_html($answer);
        if (empty($html)) throw new \Exception('bad request');

        return $this->formatReturn(true, $id);
    }


    /**
     * Format return
     * @param $status
     * @param $id
     * @param null $errors
     * @return array
     */
    private function formatReturn($status, $id, $errors = null)
    {
        return [
            'status' => ($status ? 'success' : 'error'),
            'id' => $id,
            'errors' => $errors
        ];
    }

    /**
     * JSON_UNESCAPED_UNICODE analogue for using UTF-8 on PHP < 5.4
     * @param $input
     * @return mixed
     */
    private function raw_json_encode($input)
    {
        return preg_replace_callback(
            '/\\\\u([0-9a-zA-Z]{4})/',
            function ($matches) {
                return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UTF-16');
            },
            json_encode($input)
        );
    }

    /**
     * @var array $site_types Types and ids from Addmefast
     */
    private static $site_types = [
        1 => 'facebook/likes',
        18 => 'facebook/share',
        11 => 'facebook/followers',
        25 => 'facebook/post-like',
        28 => 'facebook/post-share',
        39 => 'tsu.co/followers',
        35 => 'vine/followers',
        36 => 'vine/likes',
        37 => 'vine/revines',
        9 => 'google/circles',
        34 => 'google/post-share',
        6 => 'youtube/subscribe',
        8 => 'youtube/video-likes',
        31 => 'youtube/favorites',
        5 => 'youtube/views',
        3 => 'twitter/followers',
        17 => 'twitter/tweets',
        16 => 'twitter/retweets',
        15 => 'twitter/favorites',
        19 => 'instagram/followers',
        32 => 'instagram/likes',
        33 => 'ask.fm/likes',
        29 => 'vkontakte/pages',
        30 => 'vkontakte/groups',
        20 => 'myspace/friends',
        7 => 'pinterest/followers',
        23 => 'pinterest/repins',
        24 => 'pinterest/likes',
        21 => 'reverbnation/fans',
        22 => 'soundcloud/follow',
        38 => 'soundcloud/likes',
        10 => 'soundcloud/plays',
        14 => 'stumbleupon/followers',
        4 => 'website/hits'
    ];

    /**
     * @var array $countries Countries and ids from Addmefast
     */
    private static $countries = [
        1 => 'afghanistan',
        2 => 'aland islands',
        3 => 'albania',
        4 => 'algeria',
        5 => 'american samoa',
        6 => 'andorra',
        7 => 'angola',
        8 => 'anguilla',
        9 => 'antarctica',
        10 => 'antigua and barbuda',
        11 => 'argentina',
        12 => 'armenia',
        13 => 'aruba',
        14 => 'australia',
        15 => 'austria',
        16 => 'azerbaijan',
        17 => 'bahamas',
        18 => 'bahrain',
        19 => 'bangladesh',
        20 => 'barbados',
        21 => 'belarus',
        22 => 'belgium',
        23 => 'belize',
        24 => 'benin',
        25 => 'bermuda',
        26 => 'bhutan',
        27 => 'bolivia, plurinational state of',
        28 => 'bonaire, sint eustatius and saba',
        29 => 'bosnia and herzegovina',
        30 => 'botswana',
        31 => 'bouvet island',
        32 => 'brazil',
        33 => 'british indian ocean territory',
        34 => 'brunei darussalam',
        35 => 'bulgaria',
        36 => 'burkina faso',
        37 => 'burundi',
        38 => 'cambodia',
        39 => 'cameroon',
        40 => 'canada',
        41 => 'cape verde',
        42 => 'cayman islands',
        43 => 'central african republic',
        44 => 'chad',
        45 => 'chile',
        46 => 'china',
        47 => 'christmas island',
        48 => 'cocos (keeling) islands',
        49 => 'colombia',
        50 => 'comoros',
        51 => 'congo',
        52 => 'congo, the democratic republic of the',
        53 => 'cook islands',
        54 => 'costa rica',
        55 => "cote d'ivoire",
        56 => 'croatia',
        57 => 'cuba',
        58 => 'curacao',
        59 => 'cyprus',
        60 => 'czech republic',
        61 => 'denmark',
        62 => 'djibouti',
        63 => 'dominica',
        64 => 'dominican republic',
        65 => 'ecuador',
        66 => 'egypt',
        67 => 'el salvador',
        68 => 'equatorial guinea',
        69 => 'eritrea',
        70 => 'estonia',
        71 => 'ethiopia',
        72 => 'falkland islands (malvinas)',
        73 => 'faroe islands',
        74 => 'fiji',
        75 => 'finland',
        76 => 'france',
        77 => 'french guiana',
        78 => 'french polynesia',
        79 => 'french southern territories',
        80 => 'gabon',
        81 => 'gambia',
        82 => 'georgia',
        83 => 'germany',
        84 => 'ghana',
        85 => 'gibraltar',
        86 => 'greece',
        87 => 'greenland',
        88 => 'grenada',
        89 => 'guadeloupe',
        90 => 'guam',
        91 => 'guatemala',
        92 => 'guernsey',
        93 => 'guinea',
        94 => 'guinea-bissau',
        95 => 'guyana',
        96 => 'haiti',
        97 => 'heard island and mcdonald islands',
        98 => 'holy see (vatican city state)',
        99 => 'honduras',
        100 => 'hong kong',
        101 => 'hungary',
        102 => 'iceland',
        103 => 'india',
        104 => 'indonesia',
        105 => 'iran, islamic republic of',
        106 => 'iraq',
        107 => 'ireland',
        108 => 'isle of man',
        109 => 'israel',
        110 => 'italy',
        111 => 'jamaica',
        112 => 'japan',
        113 => 'jersey',
        114 => 'jordan',
        115 => 'kazakhstan',
        116 => 'kenya',
        117 => 'kiribati',
        118 => "korea, democratic people's republic of",
        119 => 'korea, republic of',
        251 => 'kosovo',
        120 => 'kuwait',
        121 => 'kyrgyzstan',
        122 => "lao people's democratic republic",
        123 => 'latvia',
        124 => 'lebanon',
        125 => 'lesotho',
        126 => 'liberia',
        127 => 'libya',
        128 => 'liechtenstein',
        129 => 'lithuania',
        130 => 'luxembourg',
        131 => 'macao',
        132 => 'macedonia, the former yugoslav republic of',
        133 => 'madagascar',
        134 => 'malawi',
        135 => 'malaysia',
        136 => 'maldives',
        137 => 'mali',
        138 => 'malta',
        139 => 'marshall islands',
        140 => 'martinique',
        141 => 'mauritania',
        142 => 'mauritius',
        143 => 'mayotte',
        144 => 'mexico',
        145 => 'micronesia, federated states of',
        146 => 'moldova, republic of',
        147 => 'monaco',
        148 => 'mongolia',
        149 => 'montenegro',
        150 => 'montserrat',
        151 => 'morocco',
        152 => 'mozambique',
        153 => 'myanmar',
        154 => 'namibia',
        155 => 'nauru',
        156 => 'nepal',
        157 => 'netherlands',
        250 => 'netherlands antilles',
        158 => 'new caledonia',
        159 => 'new zealand',
        160 => 'nicaragua',
        161 => 'niger',
        162 => 'nigeria',
        163 => 'niue',
        164 => 'norfolk island',
        165 => 'northern mariana islands',
        166 => 'norway',
        167 => 'oman',
        168 => 'pakistan',
        169 => 'palau',
        170 => 'palestinian territory, occupied',
        171 => 'panama',
        172 => 'papua new guinea',
        173 => 'paraguay',
        174 => 'peru',
        175 => 'philippines',
        176 => 'pitcairn',
        177 => 'poland',
        178 => 'portugal',
        179 => 'puerto rico',
        180 => 'qatar',
        181 => 'reunion',
        182 => 'romania',
        183 => 'russian federation',
        184 => 'rwanda',
        185 => 'saint barthelemy',
        186 => 'saint helena, ascension and tristan da cunha',
        187 => 'saint kitts and nevis',
        188 => 'saint lucia',
        189 => 'saint martin (french part)',
        190 => 'saint pierre and miquelon',
        191 => 'saint vincent and the grenadines',
        192 => 'samoa',
        193 => 'san marino',
        194 => 'sao tome and principe',
        195 => 'saudi arabia',
        196 => 'senegal',
        197 => 'serbia',
        198 => 'seychelles',
        199 => 'sierra leone',
        200 => 'singapore',
        201 => 'sint maarten (dutch part)',
        202 => 'slovakia',
        203 => 'slovenia',
        204 => 'solomon islands',
        205 => 'somalia',
        206 => 'south africa',
        207 => 'south georgia and the south sandwich islands',
        208 => 'south sudan',
        209 => 'spain',
        210 => 'sri lanka',
        211 => 'sudan',
        212 => 'suriname',
        213 => 'svalbard and jan mayen',
        214 => 'swaziland',
        215 => 'sweden',
        216 => 'switzerland',
        217 => 'syrian arab republic',
        218 => 'taiwan, province of china',
        219 => 'tajikistan',
        220 => 'tanzania, united republic of',
        221 => 'thailand',
        222 => 'timor-leste',
        223 => 'togo',
        224 => 'tokelau',
        225 => 'tonga',
        226 => 'trinidad and tobago',
        227 => 'tunisia',
        228 => 'turkey',
        229 => 'turkmenistan',
        230 => 'turks and caicos islands',
        231 => 'tuvalu',
        232 => 'uganda',
        233 => 'ukraine',
        234 => 'united arab emirates',
        235 => 'united kingdom',
        236 => 'united states',
        237 => 'united states minor outlying islands',
        238 => 'uruguay',
        239 => 'uzbekistan',
        240 => 'vanuatu',
        241 => 'venezuela, bolivarian republic of',
        242 => 'viet nam',
        243 => 'virgin islands, british',
        244 => 'virgin islands, u.s.',
        245 => 'wallis and futuna',
        246 => 'western sahara',
        247 => 'yemen',
        248 => 'zambia',
        249 => 'zimbabwe'
    ];
}