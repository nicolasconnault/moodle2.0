<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package   moodle-core
 * @copyright 2008 Kim Bloggs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 *  Some class_name
 *
 * @author  Nicolas Connault <nicolasconnault@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class some_class_name {
    var $test= 'booh';

    /**
     * some_class_name
     *
     * @param mixed  $var1 My var 1
     * @param string $var2 My var 1
     *
     * @access public
     * @return void
     */
    function some_class_name($var1, $var2 = 'test') {

    }

}

/**
 * test
 *
 * @param mixed $var1 Comment
 * @param mixed $var3 Comment
 *
 * @return void
 */
function test($var1, $var3= 'BALH') {
    echo "Blah";
    define('INVALID_CONSTANT', 4);
    $myvar = INVALID_CONSTANT;
    $mysecondvar = $var1;
}

test('test', 'test');
$CFG->object = 'glah';

function test($var1 = 1 ,$var2=5) {

}

test ( $var1 , $var2 );
