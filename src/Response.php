<?php


namespace Server;


class Response {
    protected $data;
    protected $contentType = "text/html; charset=utf-8";
    protected $headers = [];
    protected $allowGzip = true;
    protected $isJson = false;
    function __construct(&$data) {
        $this->data = $data;
    }

    public function getData() {
        return $this->data;
    }

    public function withContentType($contentType) {
        $this->contentType = $contentType;
        return $this;
    }

    public function getContentType() {
        return $this->contentType;
    }

    public function withHeaders($headers) {
        $this->headers = $headers;
        return $this;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function isGzipAllowed() {
        return $this->allowGzip;
    }

    public function withGzip() {
        $this->allowGzip = true;
        return $this;
    }

    public function withoutGzip() {
        $this->allowGzip = false;
        return $this;
    }

    public function isJson() {
        return $this->isJson;
    }

    public function setJson($isJson) {
        $this->isJson = $isJson;
        return $this;
    }
}