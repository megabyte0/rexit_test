<?php


namespace Image;


use Imagick;

class Image {
    public static function thumbnail($url) {
        // https://stackoverflow.com/a/35243976
        $imageBlob = file_get_contents($url);
        $img = new Imagick();
        $img->readImageBlob($imageBlob);
        $img->setImageFormat("png");
        $img->thumbnailImage(40, 40,true);
        $res = $img->getImageBlob();
        $img->destroy();
        return $res;
    }
}