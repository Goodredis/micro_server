<?php

class Abc
{
    public function __construct()
    {
        echo 123;
    }
    public static function ao()
    {
        echo 'abc ao';
    }
}

class Def extends Abc
{
    public function __construct()
    {
        echo 'edf';
    }

    public static function ao()
    {
        return 'edf ao';
    }
}

//$a = new Abc();
var_dump(Def::ao()) ;exit();