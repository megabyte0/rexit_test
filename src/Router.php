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
        $this->registerRoute('/^\\/api\\/data$/',
            //https://stackoverflow.com/a/13543245
            array($this->controller,'getUsersWithPosts')
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
            if (preg_match($route["matcher"],$uri)) {
                //return;
                $result = $route["handler"]();
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
        if (in_array("gzip",
            explode(", ",$_SERVER['HTTP_ACCEPT_ENCODING'])
        )) {
            header('Content-Encoding: gzip');
            $data = gzencode($data);
        }
        header("Content-Length: ".(strlen($data)));
        echo ($data);

    }

    public static function test() {
        return "Passed";
    }
}