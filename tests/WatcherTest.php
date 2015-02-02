<?php
// -- 모든 기능 완료 후 autoload 고려


/*
\BCNX\Watcher::on('\Prj\TargetClass', 'targetMethod', function ($target_obj, $param_list, $result) {});
BCNX\Watcher::off('\Prj\TargetClass', 'targetMethod', $func);
 */
namespace {
    /**
     * Created by PhpStorm.
     * User: tael
     * Date: 15. 1. 15.
     * Time: 오후 7:33
     */
    use \Yellostory\Watcher as Watcher;
    use \Test\Computer;

    class WatcherTest extends PHPUnit_Framework_TestCase
    {
        public function test_off_예외발생상황()
        {
            Watcher::on('FooDummy', 'bar', 'echoA');
            $this->setExpectedException("Exception");
            Watcher::off('FooDummy', 'bar', 'echoB');
        }

        public function test_on_클래스이름이잘못된경우()
        {
            $this->setExpectedException('Exception');
            Watcher::on('_ERRkown', 'bar', 'echoB');
            //----
            $foo = new FooDummy();
            self::assertEquals(11, $foo->bar(10));
        }

        public function test_namespace()
        {
            Watcher::on(\Test\FooDummy::class, 'bar', 'echoA');
        }

        public function test_static_class_method()
        {
            Watcher::on("StaticClassExample", "staticMethod", "echoA");
            Watcher::on("StaticClassExample", "staticMethod", "echoB");
            Watcher::on("StaticClassExample", "staticMethod", "echoA");
            Watcher::on("StaticClassExample", "staticMethod", "echoA");

            self::assertEquals("Hello, my_string", \StaticClassExample::staticMethod("my_string"));
        }


        public function test_trait_basic()
        {
            Watcher::on('Test\Computer', 'multiple', 'echoA');
            Watcher::on('Test\Computer', 'add', 'echoA');
            Watcher::on(Test\Computer::class, 'add', 'echoA');

            $c = new \Test\Computer();

            self::assertEquals(6, $c->multiple(2, 3));
            self::assertEquals(5, $c->add(2, 3));

        }

        /**
         * @link http://php.net/manual/kr/language.namespaces.rules.php
         * @link http://rommelsantor.com/clog/2011/04/10/php-5-3-dynamic-namespace-resolution/
         * @link http://code.tutsplus.com/tutorials/namespacing-in-php--net-27203
         */
        public function test_다양한_포맷의_네임스페이스가_동작한다()
        {
            // 최초의 \ 가 있거나 없거나 동일하다.
            // 대소문자 구분 없이 동일하게 동작한다.
            // namespace\ClassName::class 로도 동작한다. (5.5 higher) // readability!
            // import 되어있으면 ClassName::class 로 동작한다.

            Watcher::on('\Test\Computer', 'multiple', 'echoB');
            Watcher::on('Test\Computer', 'multiple', 'echoB');
            Watcher::on(\Test\Computer::class, 'multiple', 'echoB');
            Watcher::on(Computer::class, 'multiple', 'echoB');


            $c = new Computer();

            self::assertEquals(6, $c->multiple(2, 3));
            self::assertEquals(5, $c->add(2, 3));
        }

        public function test_네임스페이스를_잘못알고_시도하면_예외발생()
        {
            $this->setExpectedException("Exception");
            Watcher::on('Computer', 'multiple', 'echoB'); // 이건 안되야함
        }

        protected function setUp()
        {
            parent::setUp();
            Watcher::clear();
        }


        public function test_on()
        {
            Watcher::on('FooDummy', 'bar', 'echoB');
            Watcher::on('FooDummy', 'add', 'echoA');
            //----
            $foo = new FooDummy();
            self::assertEquals(11, $foo->bar(10));
            self::assertEquals(3, $foo->add(1, 2));
            self::assertEquals(5, $foo->add(1, 4));
            self::assertEquals(12, $foo->add(10, 2));
        }

        public function test_off()
        {
            Watcher::on('FooDummy', 'bar', 'echoA');
            $foo = new FooDummy();
            self::assertEquals(11, $foo->bar(10));
            Watcher::off('FooDummy', 'bar', 'echoA');
            self::assertEquals(21, $foo->bar(20));
        }

        public function testUndefinedClass()
        {
            try {
                Watcher::on('undefinedClass', 'xxxx', 'echoA');
                self::fail("it must throw exception");
            } catch (Exception $e) {
                self::isTrue();
            }
        }


        public function testRetrieveAfterFunction()
        {
            $getFunctions = new \ReflectionMethod('\BCNX\Watcher', 'getFunctions');

            Watcher::on('FooDummy', 'bar', 'echoB');
            self::assertEquals(['echoB'], $getFunctions->invoke(null, 'FooDummy', 'bar'));

            Watcher::on('FooDummy', 'bar', 'echoB');
            self::assertEquals(['echoB', 'echoB'], $getFunctions->invoke(null, 'FooDummy', 'bar'));

            Watcher::on('FooDummy', 'bar', 'echoA');
            self::assertEquals(['echoB', 'echoB', 'echoA'], $getFunctions->invoke(null, 'FooDummy', 'bar'));
        }

        public function test_clear()
        {
            Watcher::on('FooDummy', 'bar', 'echoB');
            Watcher::on('FooDummy', 'bar', 'echoB');
            Watcher::on('FooDummy', 'bar', 'echoB');
            Watcher::on('FooDummy', 'bar', 'echoB');
            Watcher::on(FooDummy::class, 'bar', 'echoB');
            Watcher::clear();
            $functions = Watcher::getFunctions('FooDummy', 'bar');
            self::assertEquals([], $functions);
        }
    }
}

namespace Test {

    use Math\Calculator;

    class Computer
    {
        use Calculator;

        public function multiple($a, $b)
        {
            return $a * $b;
        }
    }

    class FooFatherFather
    {
    }

    class FooFather extends FooFatherFather
    {
        public function hello()
        {
            return "hello father";
        }
    }

    class FooDummy extends FooFather
    {
        public function hello()
        {
            return "hello child";
        }

        public function world()
        {
            return "world";
        }

        public function bar($n)
        {
            return $n + 1;
        }

        public function add($a, $b)
        {
            return $a + $b;
        }
    }
}
namespace {

    // XXX: 일정 규칙을 만들고 공유해야 한다.
    function echoA($info, $args, $result)
    {
        // TODO:  내용 정리..
//        $target = $info['class']; // 호출한 클래스 명
//        $target = $info['method']; // 호출한 메소드 명
//        $target = $info['object']; // 호출한 인스턴스, static 일 경우에는 NULL

        error_log(var_export($info, true));

        // class name , method, target_obj
        error_log("[echoA] args = " . print_r($args, true) . " result = " . print_r($result, true));
    }

    function echoB()
    {
        error_log("[echoB] is not use any arguments");
    }

    class FooFather extends FooFatherFather
    {
        public function hello()
        {
            return "hello father";
        }
    }

    class FooFatherFather
    {
    }


    class StaticClassExampleFather
    {
        public static function staticMethod($string)
        {
            error_log("[" . __CLASS__ . "]" . "[" . __METHOD__ . "]" . " was Called with argument(" . $string . ")");

            return "Hello, $string";
        }
    }

    class StaticClassExample extends StaticClassExampleFather
    {

    }


    class FooDummy extends FooFather
    {
        public $varTest = 1;

        public function hello()
        {
            return "hello child";
        }

        public function world()
        {
            return "world";
        }

        public function bar($n)
        {
            return $n + 1;
        }

        public function add($a, $b)
        {
            return $a + $b;
        }
    }

}
namespace Math {
    trait Calculator
    {
        public function add($a, $b)
        {
            return $a + $b;
        }
    }
}

