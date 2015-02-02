<?php
namespace tael;

use Exception;

class Watcher
{
    /**
     * @var array class-method-callable pair
     */
    private static $m = [];

    private function __construct()
    {
    }

    // TODO: callable example code  (sample??)
    /**
     * @param $class
     * @param $method
     * @param callable $callable
     * @throws Exception
     */
    public static function on($class, $method, callable $callable)
    {
        self::validateClass($class);
        self::validateMethod($class, $method);
        self::validateCallable($callable);

        self::register(self::getQualifiedName($class), $method, $callable);
    }

    private static function isRegisteredMethod($class, $method)
    {
        return isset(self::$m[$class][$method]);
    }

    private static function runkitRenameMethod($className, $original, $toChange)
    {
        if (!runkit_method_rename($className, $original, $toChange)) {
            throw new Exception("runkit_method_rename failed");
        }
    }


    public static function off($class, $method, callable $callable)
    {
        self::validateClass($class);
        self::validateMethod($class, $method);
        self::validateCallable($callable);
        self::deregister(self::getQualifiedName($class), $method, $callable);
    }

    private static function validateClass($class)
    {
        if (!class_exists($class)) {
            throw new Exception("Class " . $class . " not found!");
        }
    }

    // only for debug
    private static function validate()
    {
        foreach (self::$m as $className => $methodMaps) {
            if (!class_exists($className, false)) {
                throw new Exception("class [" . $className . "] is not exists");
            }
            foreach (array_keys($methodMaps) as $method) {
                if (!method_exists($className, $method)) {
                    throw new Exception("class [" . $className . "] is not have the method [" . $method . "]");
                }
                $hookMethod = self::retrieveHookMethod($method);
                if (!method_exists($className, $method)) {
                    throw new Exception("class [" . $className . "] is not have the hooked method [" . $hookMethod . "]");
                }
            }
        }
    }

    public static function clear()
    {
        self::cleanup(); // not needed?
        foreach (self::$m as $className => $methodMaps) {
            foreach ($methodMaps as $method => $callableList) {
                foreach ($callableList as $callable) {
                    self::deregister($className, $method, $callable);
                }
            }
        }
    }

    private static function cleanup()
    {
        foreach (self::$m as $k => $v) {
            foreach ($v as $vk => $vv) {
                self::cleanupMethod($k, $vk);
            }
            self::cleanupClass($k);
        }
    }

    private static function retrieveHookMethod($methodName)
    {
        $hookMethodName = "__hook__" . $methodName;

        return $hookMethodName;
    }

    private static function registerCallable($class, $method, callable $callable)
    {
        self::$m[$class][$method][] = $callable;
    }

    private static function deregisterCallable($class, $method, callable $callable)
    {
        $index = array_search($callable, self::$m[$class][$method]);
        if ($index === false) {
            throw new Exception("callable is not registered");
        }
        unset(self::$m[$class][$method][$index]);
    }

    private static function runkitAddMethod($class, $method, $isStatic)
    {
        $code = self::generateTemplate($method, __CLASS__ . "::callerTemplate");
        $flag = RUNKIT_ACC_PUBLIC | ($isStatic ? RUNKIT_ACC_STATIC : 0x00);
        if (!runkit_method_add($class, $method, "", $code, $flag)) {
            throw new Exception("runkit_method_add failed");
        }
    }

    public static function getFunctions($class, $method)
    {
        $class = self::getQualifiedName($class);
        if (!self::isRegisteredMethod($class, $method)) {
            return [];
        }

        return self::$m[$class][$method];
    }

    private static function generateTemplate($method, callable $methodCaller)
    {
        return "return $methodCaller(isset(\$this)?\$this:get_called_class(), '$method', func_get_args());";
    }

    public static function callerTemplate($object, $method, $args)
    {
        $class = is_object($object) ? get_class($object) : $object;
        $info['object'] = is_object($object) ? $object : null;

        $hooked = self::retrieveHookMethod($method);
        $r = call_user_func_array([$object, $hooked], $args);
        foreach (self::getFunctions($class, $method) as $function) {
            $function($info, $args, $r);
        }

        return $r;
    }


    private static function validateCallable(callable $callable)
    {
        if (!is_callable($callable)) {
            throw new Exception("callable is not callable");
        }
    }

    private static function register($class, $method, callable $callable)
    {
        $isNewRegister = !self::isRegisteredMethod($class, $method);
        self::registerCallable($class, $method, $callable);
        if ($isNewRegister) {
            $isStatic = self::isStatic($class, $method);
            self::runkitRenameMethod($class, $method, self::retrieveHookMethod($method));
            self::runkitAddMethod($class, $method, $isStatic);
        }
    }

    private static function deregister($class, $method, callable $callable)
    {
        self::deregisterCallable($class, $method, $callable);
        if (!self::isRegisteredMethod($class, $method)) {
            self::cleanupMethod($class, $method);
            self::cleanupClass($class);
        }
    }

    private static function cleanupMethod($class, $method)
    {
        if (empty(self::$m[$class][$method])) {
            unset(self::$m[$class][$method]);
            // .. reconstruct to original ( rename )
            self::runkitRemoveMethod($class, $method);
            self::runkitRenameMethod($class, self::retrieveHookMethod($method), $method);
        }
    }

    private static function cleanupClass($class)
    {
        if (empty(self::$m[$class])) {
            unset(self::$m[$class]);
        }
    }

    private static function runkitRemoveMethod($class, $method)
    {
        if (!runkit_method_remove($class, $method)) {
            throw new Exception("runkit_method_remove failed");
        }
    }

    /**
     * @param $class
     * @param $method
     * @return bool
     */
    private static function isStatic($class, $method)
    {
        $r = new \ReflectionMethod($class, $method);
        $isStatic = $r->isStatic();

        return $isStatic;
    }

    private static function validateMethod($class, $method)
    {
        if (!method_exists($class, $method)) {
            throw new Exception("Method " . $method . " not found in [$class]!");
        }
    }

    /**
     * @param $class
     * @return string
     * @throws Exception
     */
    private static function getQualifiedName($class)
    {
        $c = new \ReflectionClass($class);

        return $c->getName();
    }
}

if (!extension_loaded("runkit")) {
    throw new Exception("runkit");
}
