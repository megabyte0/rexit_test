<?php


namespace Email;

use Words\Words;


class EmailGenerator {
    protected static $patterns = [
        "fLy@" => 2,
        "F.L.Y@" => 4,
        "Wn@" => 1,
    ];

    public static function generate($firstName, $lastName, $year) {
        $res = [];
        $pattern = self::choosePattern();
        $patternArray = str_split($pattern);
        foreach ($patternArray as $letter) {
            switch ($letter) {
                case 'f':$x=substr($firstName,0,1);break;
                case 'F':$x=$firstName;break;
                case 'L':$x=$lastName;break;
                case 'y':$x=(string)($year%100);break;
                case 'Y':$x=(string)($year);break;
                case 'W':$x=implode("",self::chooseWords(15));break;
                case 'n':$x=(string)(mt_rand(100,999));break;
                case '@':$x="@".self::generateDomain();break;
                default: $x=$letter;
            }
            $res[]=$x;
        }
        return implode("",$res);
    }

    protected static function generateDomain() {
        if (mt_rand(0, 1) === 0) {
            return "gmail.com";
        }
        $words = self::chooseWords(12);
        return implode("", $words) . "." . (["com", "org", "edu"][mt_rand(0, 2)]);
    }

    protected static function choosePattern() {
        $sums = [];
        $sum = 0;
        $patternsStr = [];
        foreach (self::$patterns as $key => $value) {
            $sum += $value;
            $sums[] = $sum;
            $patternsStr[] = $key;
        }
        $index = 0;
        $patternsCount = count(self::$patterns);
        $random = mt_rand(0, $sum - 1);
        while (($index < $patternsCount) && ($random >= $sums[$index])) ++$index;
        //--$index;
        return $patternsStr[$index];
    }

    protected static function chooseWords($minLength) {
        $len = 0;
        $words = [];
        while ($len < $minLength) {
            $word = Words::$words[mt_rand(0, count(Words::$words) - 1)];
            $words[] = $word;
            $len += strlen($word);
        }
        return $words;
    }
}