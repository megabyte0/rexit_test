<?php


namespace Server;


class Router {
    protected $controller;
    protected $routes = [];
    function __construct(MyController &$controller) {
        $this->controller=$controller;
        $this->registerRoutes();
    }

    protected function registerRoutes() {
        $d = [
            'category_id' => '\\d+',
            'firstname_like' => '[A-Za-z]+',
            'lastname_like' => '[A-Za-z]+',
            'email_like' => '[-A-Za-z.@_]+',
            'gender_id' => '\\d+',
            'limit' => '\\d+',
            'offset' => '\\d+',
            'age' => '\\d+',
            'bday' => '\\d+',
            'bmonth' => '\\d+',
            'byear' => '\\d+',
            'min_age' => '\\d+',
            'max_age' => '\\d+',
        ];
        $dStr = implode('|',array_map(
            function ($k,$v){
                return sprintf('(?:%s\\=%s)',preg_quote($k,'/'),$v);
            },
            array_keys($d),array_values($d)
        ));
        $this->registerRoute(
            sprintf('/^\\/api\\/data\\/\\?((?:(?:%s)\\&?)*)\\/?$/',$dStr),
            //https://stackoverflow.com/a/13543245
            array($this->controller, 'getClients')
        );
        $this->registerRoute('/^\\/$/',
            $this->controller->getStatic("./index.html")
        );
    }

    protected function registerRoute($regexpString,callable $handler) {
        $this->routes[]=[
            "matcher"=>$regexpString,
            "handler"=>$handler
        ];
    }

    public function route($uri) {
        foreach ($this->routes as $route) {
            //var_dump($route,$uri,preg_match($route["matcher"],$uri));
            $matches = NULL;
            if (preg_match($route["matcher"],$uri,$matches)) {
                //return;
                //var_dump(array_slice($matches,1));die;
                $result = $route["handler"](...array_slice($matches,1));
                //var_dump($result);
                //return;
                $this->performCompressionAndEcho($result);
                return;
            }
        }
        return false;
    }

    public function performCompressionAndEcho($data) {
        if (is_array($data)) {
            $data = json_encode($data);
            header('Content-Type: application/json; charset=utf-8');
        }
        $typePng = is_string($data) && substr($data,0,4) === "\x89PNG";
        if (in_array("gzip",
            explode(", ",$_SERVER['HTTP_ACCEPT_ENCODING'])
        ) && !$typePng) {
            header('Content-Encoding: gzip');
            $data = gzencode($data);
        }
        //TODO: extensibility
        if ($typePng) {
            header("Content-Type: image/png");
        }
        header("Content-Length: ".(strlen($data)));
        header("Access-Control-Allow-Origin: *");
        echo ($data);
    }

    public static function test() {
        return "Passed";
    }
}