<?php

function test_dump( $data ){
    global $test;

    if ( !!$test ) {
        echo '<pre>';
        var_dump ($data);
        echo '</pre>';
    }
}
