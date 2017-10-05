<?php


include "src/Toro.php";

class HelloHandler {
    function get() {

        echo phpinfo();
        echo "Hello, world";
    }
}

Toro::serve(array(
    "/" => "HelloHandler",
));

