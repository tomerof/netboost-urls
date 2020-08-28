<?php

use Pixie\Connection;
use Pixie\QueryBuilder\QueryBuilderHandler;

require 'vendor/autoload.php';

$config = array(
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'netboost-urls',
    'username'  => 'test',
    'password'  => 'Qwerty7890!',
    'charset'   => 'utf8',
    'collation' => 'utf8_general_ci'
);

$connection = new Connection('mysql', $config);
$qb = new QueryBuilderHandler($connection);
// create table in case it's not exists
$qb->query("CREATE TABLE IF NOT EXISTS `urls` (
	`id` INT AUTO_INCREMENT PRIMARY KEY,
	`host` VARCHAR(255) NOT NULL,
	`url` TEXT NOT NULL
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8
COLLATE=utf8_general_ci;
");
$website = "http://lordbingo.co.uk";
$seen = [];


// check if link returns 200 OK
function url_exists($url) {
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_FAILONERROR, false);
    curl_setopt($ch,  CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HEADER  , true);
    curl_setopt($ch, CURLOPT_NOBODY  , true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode === 200;
}

function crawl_page($url, $depth = 1){
    global $qb, $seen;

    if (isset($seen[$url]) || $depth === 0) {
        return;
    }

    // add the url to an array to skip it next time, we want to crawl urls only once
    $seen[$url] = true;

    $dom = new DOMDocument('1.0');
    @$dom->loadHTMLFile($url);

    $anchors = $dom->getElementsByTagName('a');
    foreach ($anchors as $element) {
        $href = $element->getAttribute('href');
        if(url_exists($href)){
            // keep crawl the next page
            crawl_page($href, $depth - 1);
            // check if NOT exists then insert new url
            if(!$qb->table('urls')->where('url', '=', $href)->first()){
                $hrefParts = parse_url($href);
                $qb->table('urls')->insert([
                    'host' => $hrefParts["host"] ?? '',
                    'url' => $href
                ]);
                echo $href."\n";
            }
        }
    }
}

crawl_page($website, 3);
