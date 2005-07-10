<?php
/**
*   runs the PHPUnit tests for Net_Wifi
*   and outputs the results
*
*   @author Christian Weiske <cweiske@cweiske.de>
*/

require_once 'Wifi_testcase.php';
require_once 'PHPUnit.php';

$suite  = new PHPUnit_TestSuite( "Net_Wifi_Test");
$result = PHPUnit::run( $suite);
echo $result -> toString();
?>