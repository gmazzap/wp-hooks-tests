<?php

use PHPUnit\Framework\TestCase;

class HookCallbacksSerializationTest extends TestCase
{
    /**
     * @return void
     */
    public static function noop(): void
    {
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_filter'] = [];
    }

    /**
     * @test
     * @dataProvider provideCallbacksData
     */
    public function testBackWardCompatibilityRemoveByCallback(callable $callback): void
    {
        $priority = rand(0, 100);
        $args = rand(1, 5);

        $identifier = $this->oldIdentifier($callback);

        add_filter('an_hook', $callback, $priority, $args);

        static::assertSame($priority, has_filter('an_hook', $identifier));
        static::assertSame($priority, has_filter('an_hook', $callback));

        // wrong priority should not remove
        remove_filter('an_hook', $identifier, $priority + 1);

        static::assertSame($priority, has_filter('an_hook', $identifier));
        static::assertSame($priority, has_filter('an_hook', $callback));

        remove_filter('an_hook', $callback, $priority);

        static::assertFalse(has_filter('an_hook', $callback));
        static::assertFalse(has_filter('an_hook', $identifier));
    }

    /**
     * @test
     * @dataProvider provideCallbacksData
     */
    public function testBackWardCompatibilityRemoveByIdentifier(callable $callback): void
    {
        $priority = rand(0, 100);
        $args = rand(1, 5);

        $identifier = $this->oldIdentifier($callback);

        add_filter('an_hook', $callback, $priority, $args);

        static::assertSame($priority, has_filter('an_hook', $identifier));
        static::assertSame($priority, has_filter('an_hook', $callback));

        // wrong priority should not remove
        remove_filter('an_hook', $identifier, $priority + 1);

        static::assertSame($priority, has_filter('an_hook', $identifier));
        static::assertSame($priority, has_filter('an_hook', $callback));

        remove_filter('an_hook', $identifier, $priority);

        static::assertFalse(has_filter('an_hook', $callback));
        static::assertFalse(has_filter('an_hook', $identifier));
    }

    /**
     * @test
     * @dataProvider provideCallbacksData
     */
    public function testCheckAndRemoveByNewIdentifier(
        callable $callback,
        string $id
    ): void {

        $priority = rand(0, 100);
        $args = rand(1, 5);

        add_filter('an_hook', $callback, $priority, $args);

        static::assertSame($priority, has_filter('an_hook', $id));
        static::assertSame($priority, has_filter('an_hook', $callback));

        // wrong priority should not remove
        remove_filter('an_hook', $id, $priority + 1);

        static::assertSame($priority, has_filter('an_hook', $id));
        static::assertSame($priority, has_filter('an_hook', $callback));

        remove_filter('an_hook', $id, $priority);

        static::assertFalse(has_filter('an_hook', $id));
        static::assertFalse(has_filter('an_hook', $callback));
    }

    /**
     * @test
     */
    public function testAddHookWithNewIdForPlainFunctionIsDoingItWrong(): void
    {
        $this->expectExceptionMessageMatches('/^_doing_it_wrong/');

        add_filter('init', 'strtolower', 10, 2, 'my_custom_hook');
    }

    /**
     * @test
     */
    public function testAddHookWithNewIdForStaticMethodIsDoingItWrong(): void
    {
        $this->expectExceptionMessageMatches('/^_doing_it_wrong/');

        add_filter('init', [__CLASS__, 'assertTrue'], 10, 2, 'my_custom_hook');
    }

    /**
     * @test
     */
    public function testAddThenRemoveHookWithCustomCallbackId(): void
    {
        $function = static function () {};
        $class = new class () { function __invoke() {} };
        $method = [new ArrayObject(), 'getArrayCopy'];

        $priority = random_int(1, 999);
        $args = random_int(0, 5);

        add_filter('init', $function, $priority, $args, 'my_custom_closure_id');
        add_filter('init', $class, $priority + 1, $args, 'my_custom_invokable_id');
        add_filter('init', $method, $priority + 2, $args, 'my_custom_method_id');

        static::assertSame($priority, has_filter('init', 'my_custom_closure_id'));
        static::assertSame($priority + 1, has_filter('init', 'my_custom_invokable_id'));
        static::assertSame($priority + 2, has_filter('init', 'my_custom_method_id'));

        remove_filter('init', 'my_custom_closure_id', $priority);
        remove_filter('init', 'my_custom_invokable_id', $priority + 1);
        remove_filter('init', 'my_custom_method_id', $priority + 2);

        static::assertFalse(has_filter('init', 'my_custom_hook'));
        static::assertFalse(has_filter('init', 'my_custom_invokable_id'));
        static::assertFalse(has_filter('init', 'my_custom_method_id'));
    }

    /**
     * @return array<array{callable,string,string}>
     */
    public function provideCallbacksData(): array
    {
        $object = new ArrayObject();
        $anonymous = new class {

            public function __invoke() { }

            public function a_method() { }
        };

        $closure = function () { };
        $namespacedClosure = include getenv('FIXTURES_PATH');
        $namespacedObject = new \Fixtures\SomeClass();
        $basename = basename(__FILE__);

        return [
            'Plain function' => [
                'strtolower',
                'strtolower',
            ],
            'Static method as string' => [
                __CLASS__ . '::noop',
                __CLASS__ . '::noop',
            ],
            'Static method as array' => [
                [__CLASS__, 'noop'],
                __CLASS__ . '::noop',
            ],
            'Object method' => [
                [$object, 'getArrayCopy'],
                'ArrayObject->getArrayCopy',
            ],
            'Closure' => [
                $closure,
                "function()@{$basename}",
            ],
            'Anonymous object method' => [
                [$anonymous, 'a_method'],
                "class()@{$basename}->a_method",
            ],
            'Anonymous invokable object' => [
                $anonymous,
                "class()@{$basename}->__invoke",
            ],
            'Namespaced plain function' => [
                'Fixtures\a_function',
                'Fixtures\a_function',
            ],
            'Namespaced closure' => [
                $namespacedClosure,
                'Fixtures\function()@fixtures.php',
            ],
            'Namespaced object method' => [
                [$namespacedObject, 'a_method'],
                'Fixtures\SomeClass->a_method',
            ],
            'Namespaced invokable object' => [
                $namespacedObject,
                'Fixtures\SomeClass->__invoke',
            ],
            'Namespaced static method as string' => [
                \Fixtures\SomeClass::class . '::a_static_method',
                'Fixtures\SomeClass::a_static_method',
            ],
            'Namespaced static method as array' => [
                [\Fixtures\SomeClass::class, 'a_static_method'],
                'Fixtures\SomeClass::a_static_method',
            ],
        ];
    }

    /**
     * @param $function
     * @return string
     *
     * Copied from WP 5.5.1 _wp_filter_build_unique_id
     */
    private function oldIdentifier($function)
    {
        if (is_string($function)) {
            return $function;
        }

        if (is_object($function)) {
            // Closures are currently implemented as objects.
            $function = [$function, ''];
        } else {
            $function = (array)$function;
        }

        if (is_object($function[0])) {
            // Object class calling.
            return spl_object_hash($function[0]) . $function[1];
        } elseif (is_string($function[0])) {
            // Static calling.
            return $function[0] . '::' . $function[1];
        }
    }
}
