<?php
$i4 = gmp_pow('2', 32);
$i6 = gmp_pow('2', 128);
$g = gmp_div($i6,$i4);
$i64 = gmp_pow(2, 64);
//2^128 is 340282366920938463463374607431768211456
//which is 79228162514264337593543950336 times bigger than 2^32
// /64 is 18446744073709551616

//assuming 16,000,000 pixels sq/m
$x = gmp_div($i4 , 16000000);
echo $x . "\n";
//ipv4 = 268m^2 ~= size of tennis court (260)
$y = gmp_div($i6, 16000000);
echo $y."\n";
$z = gmp_div($y, 1000000);
echo $z . "\n";
//Area of solar system (to neptune)
$r = gmp_pow(gmp_div(gmp_mul('4545000000', 314), 100), 2);
echo $r . "\n";
$q = gmp_div($z, $r);
echo $q . "\n";
//Area about 104422 times the are of the solar system!