<?php


include "src/Toro.php";

class HelloHandler {
    function get() {
        echo "Hello, world";
    }
}

Toro::serve(array(
    "/" => "HelloHandler",
));

