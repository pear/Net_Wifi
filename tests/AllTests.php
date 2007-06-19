<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
    chdir(dirname(__FILE__) . '/../');
}

if (!defined('PHPUnit_INSIDE_OWN_TESTSUITE')) {
    define('PHPUnit_INSIDE_OWN_TESTSUITE', TRUE);
}
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';


require_once 'Net_WifiTest.php';


class AllTests
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

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
    AllTests::main();
}
?>