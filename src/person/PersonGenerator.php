<?php


namespace Person;

use Phone\PhoneGenerator;
use Names\Names;
use Email\EmailGenerator;

class PersonGenerator {
    protected $phoneGenerator;
    function __construct() {
        $this->phoneGenerator = new PhoneGenerator();
    }
    public function generate() {
        $firstName=Names::$firstNames[mt_rand(0,Names::$firstNamesCount-1)];
        $lastName=Names::$lastNames[mt_rand(0,Names::$lastNamesCount-1)];
        $year=mt_rand(1950,1999);
        return [
            "first_name"=>$firstName,
            "last_name"=>$lastName,
            "phone"=>$this->phoneGenerator->getRandom(),
            "email"=>EmailGenerator::generate($firstName,$lastName,$year),
        ];
    }
}