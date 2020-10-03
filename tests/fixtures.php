<?php

declare(strict_types=1);

namespace {

    if (!function_exists('_doing_it_wrong')) {
        function _doing_it_wrong($function, $message, $version) {
            $data = json_encode(compact('function', 'message', 'version'));

            throw new Exception("_doing_it_wrong: {$data}.");
        }
    }
}

namespace Fixtures {

    if (!function_exists('Fixtures\a_function')) {
        function a_function()
        {

        }
    }

    if (!class_exists('Fixtures\SomeClass')) {
        class SomeClass
        {

            public static function a_static_method()
            {

            }

            public function a_method()
            {

            }

            public function __invoke()
            {

            }
        }
    }

    return function () {

    };
}
