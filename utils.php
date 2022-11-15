<?php

function test_dump( $data ){
    global $test;

    if ( !!$test ) {
        echo '<pre>';
        var_dump ($data);
        echo '</pre>';
    }
}



function commonDateBeautify( $date_str ){

    if ( ctype_alpha( $date_str ) ){
        return $date_str;
    }

    if ($date_str){
        $date = $date_str;
    }

    $year = intval( $date[0].$date[1].$date[2].$date[3] );
    $month = intval( $date[4].$date[5] );
    $day =  intval( $date[6].$date[7] );


    if ($year == '1498'){
        return $date_str;
    }

    if ( $year < 2005 || $year > 2022 ){
        var_dump( array( $year, $month, $day));
        // return false;
        return $date_str;
    }

    // if (!is_int($month) || !is_int($day) || !is_int($year)){
    //     return false;
    // }

    $madetime = mktime( 0, 0, 0,  $month, $day, $year );

    $return =  date('F jS, Y', $madetime );

    var_dump ($date);
    var_dump ($return);

    if (checkdate( $month, $day, $year)){
        return $return;
    }
}