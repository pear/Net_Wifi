<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Net_Wifi_AllTests::main');
}

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';


require_once 'Net_WifiTest.php';


class Net_Wifi_AllTests
{
    public static function main()
    {

        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Net_WifiTest');
        /** Add testsuites, if there is. */
        $suite->addTestSuite('Net_WifiTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Net_Wifi_AllTests::main') {
    Net_Wifi_AllTests::main();
}
?>