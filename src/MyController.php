<?php


namespace Server;


use Exception;
use Image\Image;

class MyController {
    protected $db;

    function __construct(MyDbModel &$db) {
        $this->db = $db;
    }

    //no views, not needed, pure static or json
    public function getClients($paramsRaw) {
        $params = [];
        foreach(explode("&",$paramsRaw) as $i) {
            list($k, $v) = explode("=",$i);
            $params[$k] = $v;
        }
        return $params;
    }

    public function getStatic($fileName) {
        return function () use ($fileName) {
            return file_get_contents($fileName);
        };
    }
}