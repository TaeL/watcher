<?php

class Foo
{
    public function __call($n, $arg)
    {
        echo '__call called';

    }
}

/**
 * Created by PhpStorm.
 * User: tael
 * Date: 15. 1. 23.
 * Time: 오후 1:36
 */
class RunkitTest extends PHPUnit_Framework_TestCase
{
    public function test__call_rename()
    {
        $f = new Foo();
        $f->x();
        runkit_method_rename('Foo', '__call', '__hook____call');
        $f->x();
    }
}
