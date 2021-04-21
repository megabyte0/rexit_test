<?php


namespace Server;

class MyController {
    protected $db;

    function __construct(MyDbModel &$db) {
        $this->db = $db;
    }

    //no views, not needed, pure static or json
    public function getClients($paramsRaw) {
        $params = [];
        foreach (explode("&", $paramsRaw) as $i) {
            list($k, $v) = explode("=", $i);
            $params[$k] = $v;
        }
        return
//            [$params,
//            $this->db->generateSelectClientsSql($params)];
        $this->db->getClients($params);
    }

    public function getStatic($fileName) {
        return function () use ($fileName) {
            return file_get_contents($fileName);
        };
    }
}