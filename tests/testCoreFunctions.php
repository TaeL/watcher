<?php

/**
 * Created by PhpStorm.
 * User: tael
 * Date: 15. 1. 21.
 * Time: 오후 1:47
 */
namespace {
    use TestNamespace\ClassStatic;

    class TestArray extends PHPUnit_Framework_TestCase
    {


        public function testMap()
        {
            // class - method - callable_function
            $m["class1"] = ["method1" => "f1"];
            $o["class1"] = array("method1" => "f1");
            $this->assertEquals($m, $o);
        }

        public function testList()
        {
            $m["method1"] = ["f1", "f2", "f3"];
            $o["method1"] = array("f1", "f2", "f3");
            $this->assertEquals($m, $o);
        }

        public function testMapAdd()
        {
            $m["class1"] = ["method1" => "f1"];
            $o["class1"] = array("method1" => "f1");

            $m["class2"] = ["method1" => "f1"];
            $p = array_merge($o, ["class2" => ["method1" => "f1"]]);
            $o = array_merge($o, array("class2" => array("method1" => "f1")));
            $this->assertEquals($m, $o);
            $this->assertEquals($o, $p);
        }

        public function testArrayAdd()
        {
            $a['c1']['m1'][] = 'f1';
            $a['c2']['m2'][] = 'f2';
            $a['c3']['m3'][] = 'f2';

            $this->assertCount(3, $a);
        }

        public function testCallByRef()
        {
            function testUnsetArray(&$arr, $key)
            {
                if (empty($arr[$key])) {
                    //var_dump($var);
                    unset($arr[$key]);
                }
            }

            $a['c1']['m1'][] = 'f1';
            $a['c2']['m2'][] = 'f2';
            $a['c3']['m3'][] = 'f2';
            $a['c3']['m4'] = [];

            $b = $a;

            if (empty($b['c3']['m4'])) {
                unset($b['c3']['m4']);
            }

            testUnsetArray($a['c3'], 'm4');
            unset($a['c3']['m3'][0]);
            unset($b['c3']['m3'][0]);
            testUnsetArray($a['c3']['m3'], 0);
            $this->assertEquals($b, $a);
        }

        public function test_func_get_args()
        {
            function x()
            {
                $a = func_get_args();

                return count($a);

            }

            $this->assertEquals(2, x(1, 2));
        }


        public function test_method_exists()
        {
            $o = new ClassObject();
            self::assertTrue(method_exists($o, "testMethod"));
            self::assertTrue(method_exists("ClassStatic", "testMethod"));
            self::assertTrue(true);
        }

        public function test_reflection_method()
        {
            (new ReflectionMethod('ClassStatic', 'testMethod'))->isStatic();
            new ReflectionMethod('ClassObject', 'testMethod');
            self::assertTrue(true);
        }

        public function test_class_exists_다양한_포맷()
        {

            self::assertTrue(class_exists('\ClassStatic'));
            self::assertTrue(class_exists('ClassStatic'));
            self::assertTrue(class_exists('\ClassObject'));
            self::assertTrue(class_exists('ClassObject'));

            self::assertTrue(class_exists('\TestNamespace\ClassStatic'));
            self::assertTrue(class_exists('TestNamespace\ClassStatic'));
            self::assertTrue(class_exists('\testnamespace\ClassObject'));
            self::assertTrue(class_exists('testnamespace\ClassObject'));
        }

        public function test_ns()
        {
            // namespace를 조사하여 FQN으로 만들어준다.

            function ns($i_name)
            {
                if (!is_scalar($i_name)) {
                    return $i_name;
                }
                if (class_exists($i_name)) {
                    $c = new \ReflectionClass($i_name);
                    var_dump($c->getName());
                    $i_name = $c->getName();
                }

                return substr($i_name, 0, 1) === '\\' ? $i_name : (__NAMESPACE__ . '\\' . $i_name);
//                return strpos($i_name, '\\') !== false ? $i_name : (__NAMESPACE__ . '\\' . $i_name);
            }

            self::assertEquals('\TestNamespace\ClassStatic', ns('TestNamespace\ClassStatic'));
            self::assertEquals('\TestNamespace\ClassStatic', ns('\TestNamespace\ClassStatic'));
            self::assertEquals('\TestNamespace\ClassStatic', ns('\TestNamespace\ClassStatic'));
            self::assertEquals('\TestNamespace\ClassStatic', ns(ClassStatic::class));
            self::assertEquals('\TestNamespace\ClassStatic', ns(\TestNamespace\ClassStatic::class));
//            self::assertEquals('\TestNamespace\ClassStatic', ns('ClassStatic'));
//            self::assertEquals('\TestNamespace\ClassStatic', ns(ClassStatic::class));

        }


    }

    class ClassStaticIn
    {
        public function testMethod()
        {
        }
    }

    class ClassObjectIn
    {
        public function testMethod()
        {
        }
    }
}
namespace TestNamespace {
    class ClassStatic
    {
        public function testMethod()
        {
        }
    }

    class ClassObject
    {
        public function testMethod()
        {
        }
    }

}
