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
            array($this->controller, 'getProductsWithReviews')
        );
        $this->registerRoute('/^\\/$/',
            $this->controller->getStatic("./index.html")
        );
        $this->registerRoute('/^\\/api\\/picture\\/(.*)$/',
            array($this->controller,'getImage')
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