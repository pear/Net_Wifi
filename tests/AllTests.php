<?php
/**
 * Unit tests for Net_Wifi
 *
 * PHP Versions 4 and 5
 *
 * @category   Networking
 * @package    Net_Wifi
 * @subpackage Unittests
 * @author     Christian Weiske <cweiske@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    SVN: $Id$
 * @link       http://pear.php.net/package/Net_Wifi
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Net_Wifi_AllTests::main');
}

require_once 'PHPUnit/TextUI/TestRunner.php';


require_once 'Net_WifiTest.php';


/**
 * AllTests for the PEAR-wide test suite.
 * "pear run-tests -u" also uses it.
 *
 * @category   Networking
 * @package    Net_Wifi
 * @subpackage Unittests
 * @author     Christian Weiske <cweiske@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @link       http://pear.php.net/package/Net_Wifi
 */
class Net_Wifi_AllTests
{
    /**
     * Helper function to automatically run the suite with php. 
     *
     * @return void
     */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    /**
     * Setup the Net_Wifi unit test suite and return it.
     *
     * @return PHPUnit_Framework_TestSuite Test suite
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Net_Wifi Tests');
        /** Add testsuites, if there is. */
        $suite->addTestSuite('Net_WifiTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Net_Wifi_AllTests::main') {
    Net_Wifi_AllTests::main();
}
?>
