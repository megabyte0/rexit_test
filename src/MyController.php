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

    public function getClientsJson($paramsRaw) {
        $data = $this->getClients($paramsRaw);
        return (new Response($data))->setJson(true)->withGzip();
    }

    public function getClientsCsv($paramsRaw) {
        $csvHeader = "category,firstname,lastname,email,gender,birthDate";
        $fields = explode(",",$csvHeader);
        $data = $this->getClients($paramsRaw);
        $csv = [$csvHeader];
        foreach ($data as $item) {
            $csv[] = implode(",",array_map(function ($field) use ($item){
                return $item[$field];
            },$fields));
        }
        $res = implode("\r\n",$csv);
        return (new Response($res))->withGzip()
        // https://stackoverflow.com/a/651671
        ->withContentType("text/csv");
    }

    public function getStatic($fileName) {
        return function () use ($fileName) {
            return file_get_contents($fileName);
        };
    }
}