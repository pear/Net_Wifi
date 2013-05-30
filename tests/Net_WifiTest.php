<?php
/**
 * Unit tests for Net_Wifi
 *
 * PHP Versions 5
 *
 * @category   Networking
 * @package    Net_Wifi
 * @subpackage Unittests
 * @author     Christian Weiske <cweiske@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    SVN: $Id$
 * @link       http://pear.php.net/package/Net_Wifi
 */
// Call Net_WifiTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "Net_WifiTest::main");
}

require_once "PHPUnit/Framework/TestCase.php";
require_once "PHPUnit/Framework/TestSuite.php";

//make cvs testing work
chdir(dirname(__FILE__) . '/../');
require_once 'Net/Wifi.php';
require_once 'Stream/Var.php';

/**
 * Net_Wifi tests
 *
 * @category   Networking
 * @package    Net_Wifi
 * @subpackage Unittests
 * @author     Christian Weiske <cweiske@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @link       http://pear.php.net/package/Net_Wifi
 */
class Net_WifiTest extends PHPUnit_Framework_TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     *
     * @return void
     */
    public static function main()
    {
        include_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite('Net_WifiTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * This method is called before the first test of
     * this test class is run.
     *
     * @since Method available since Release 3.4.0
     *
     * @return void
     */
    public static function setUpBeforeClass()
    {
        stream_wrapper_register('var', 'Stream_Var');
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->wls = new Net_Wifi();
    }



    /**
     * tests getSupportedInterfaces() method without any interface
     *
     * @return void
     */
    public function testGetSupportedInterfacesNone()
    {
        $GLOBALS['getSItest'] = <<<EOD
Inter-| sta-|   Quality        |   Discarded packets               | Missed | WE
 face | tus | link level noise |  nwid  crypt   frag  retry   misc | beacon | 22

EOD;
        $this->wls->setPathProcWireless('var://GLOBALS/getSItest');
        $arInterfaces = $this->wls->getSupportedInterfaces();
        $this->assertEquals(0, count($arInterfaces));
    }



    /**
     * tests getSupportedInterfaces() method with a single interface
     *
     * @return void
     */
    public function testGetSupportedInterfacesSingle()
    {
        $GLOBALS['getSItest'] = <<<EOD
Inter-| sta-|   Quality        |   Discarded packets               | Missed | WE
 face | tus | link level noise |  nwid  crypt   frag  retry   misc | beacon | 22
 wlan0: 0000    0     0     0        0      0      0      0      0        0

EOD;
        $this->wls->setPathProcWireless('var://GLOBALS/getSItest');
        $arInterfaces = $this->wls->getSupportedInterfaces();
        $this->assertEquals(array('wlan0'), $arInterfaces);
    }



    /**
     * Tests getSupportedInterfaces() method with multiple interfaces.
     *
     * @return void
     */
    public function testGetSupportedInterfacesMultiple()
    {
        $GLOBALS['getSItest'] = <<<EOD
Inter-| sta-|   Quality        |   Discarded packets               | Missed | WE
 face | tus | link level noise |  nwid  crypt   frag  retry   misc | beacon | 22
 wlan0: 0000    0     0     0        0      0      0      0      0        0
 wlan1: 0000    0     0     0        0      0      0      0      0        0

EOD;
        $this->wls->setPathProcWireless('var://GLOBALS/getSItest');
        $arInterfaces = $this->wls->getSupportedInterfaces();
        $this->assertEquals(array('wlan0', 'wlan1'), $arInterfaces);
    }


    /**
    * Tests the "current config" parser.
    *
    * @return void
    */
    function testParseCurrentConfig()
    {
        //not associated
        $strConfig
            = "eth1      unassociated  ESSID:off/any\r\n"
            . "          Mode:Managed  Channel=0  Access Point: 00:00:00:00:00:00\r\n"
            . "          Bit Rate=0 kb/s   Tx-Power=20 dBm\r\n"
            . "          RTS thr:off   Fragment thr:off\r\n"
            . "          Encryption key:off\r\n"
            . "          Power Management:off\r\n"
            . "          Link Quality:0  Signal level:0  Noise level:0\r\n"
            . "          Rx invalid nwid:0  Rx invalid crypt:0  Rx invalid frag:0\r\n"
            . "          Tx excessive retries:0  Invalid misc:0   Missed beacon:0\r\n";

        $objConfig = $this->wls->parseCurrentConfig($strConfig);

        $this->assertEquals('net_wifi_config', strtolower(get_class($objConfig)));
        $this->assertFalse($objConfig->associated);
        $this->assertTrue($objConfig->activated);
        $this->assertEquals('00:00:00:00:00:00', $objConfig->ap);


        //associated
        $strConfig
            = "eth1      IEEE 802.11g  ESSID:\"wlan.informatik.uni-leipzig.de\"  Nickname:\"bogo\"\r\n"
            . "          Mode:Managed  Frequency:2.437 GHz  Access Point: 00:07:40:A0:75:E2   \r\n"
            . "          Bit Rate=54 Mb/s   Tx-Power=20 dBm   \r\n"
            . "          RTS thr:off   Fragment thr:off\r\n"
            . "          Power Management:off\r\n"
            . "          Link Quality=100/100  Signal level=-28 dBm  \r\n"
            . "          Rx invalid nwid:0  Rx invalid crypt:0  Rx invalid frag:0\r\n"
            . "          Tx excessive retries:0  Invalid misc:0   Missed beacon:102\r\n";

        $objConfig = $this->wls->parseCurrentConfig($strConfig);

        $this->assertTrue($objConfig->associated);
        $this->assertTrue($objConfig->activated);
        $this->assertEquals('00:07:40:A0:75:E2',              $objConfig->ap);
        $this->assertEquals('wlan.informatik.uni-leipzig.de', $objConfig->ssid);
        $this->assertEquals('managed',                        $objConfig->mode);
        $this->assertEquals('bogo',                           $objConfig->nick);
        $this->assertEquals(54,                               $objConfig->rate);
        $this->assertEquals(20,                               $objConfig->power);
        $this->assertEquals('802.11g',                        $objConfig->protocol);
        $this->assertEquals(-28,                              $objConfig->rssi);
        $this->assertEquals(null,                             $objConfig->noise);
        $this->assertEquals(0,                                $objConfig->packages_invalid_misc);
        $this->assertEquals(102,                              $objConfig->packages_missed_beacon);
        $this->assertEquals(0,                                $objConfig->packages_rx_invalid_crypt);
        $this->assertEquals(0,                                $objConfig->packages_rx_invalid_frag);
        $this->assertEquals(0,                                $objConfig->packages_rx_invalid_nwid);
        $this->assertEquals(0,                                $objConfig->packages_tx_excessive_retries);

        //format changed a bit
        $strConfig = <<<EOD
eth1      IEEE 802.11g  ESSID:"wlan.informatik.uni-leipzig.de"
          Mode:Managed  Frequency:2.412 GHz  Access Point: 00:07:40:A0:75:E2
          Bit Rate:54 Mb/s   Tx-Power=20 dBm   Sensitivity=8/0
          Retry limit:7   RTS thr:off   Fragment thr:off
          Power Management:off
          Link Quality=93/100  Signal level=-35 dBm  Noise level=-89 dBm
          Rx invalid nwid:0  Rx invalid crypt:0  Rx invalid frag:0
          Tx excessive retries:0  Invalid misc:0   Missed beacon:0
EOD;
        $objConfig = $this->wls->parseCurrentConfig($strConfig);

        $this->assertTrue($objConfig->associated);
        $this->assertTrue($objConfig->activated);
        $this->assertEquals('00:07:40:A0:75:E2'                , $objConfig->ap);
        $this->assertEquals('wlan.informatik.uni-leipzig.de'   , $objConfig->ssid);
        $this->assertEquals('managed'                          , $objConfig->mode);
        $this->assertEquals(null                               , $objConfig->nick);
        $this->assertEquals(54                                 , $objConfig->rate);
        $this->assertEquals(20                                 , $objConfig->power);
        $this->assertEquals('802.11g'                          , $objConfig->protocol);
        $this->assertEquals(-35                                , $objConfig->rssi);
        $this->assertEquals(-89                                , $objConfig->noise);

        //radio off = deactivated interface
        $strConfig
            = "eth1      radio off  ESSID:\"phpconf\"\r\n"
            . "          Mode:Managed  Channel:0  Access Point: 00:00:00:00:00:00\r\n"
            . "          Bit Rate=0 kb/s   Tx-Power=off\r\n"
            . "          RTS thr:off   Fragment thr:off\r\n"
            . "          Power Management:off\r\n"
            . "          Link Quality:0  Signal level:0  Noise level:0\r\n"
            . "          Rx invalid nwid:12  Rx invalid crypt:23  Rx invalid frag:42\r\n"
            . "          Tx excessive retries:332  Invalid misc:6   Missed beacon:923\r\n";

        $objConfig = $this->wls->parseCurrentConfig($strConfig);
        $this->assertFalse($objConfig->associated);
        $this->assertFalse($objConfig->activated);
        $this->assertEquals(6, $objConfig->packages_invalid_misc);
        $this->assertEquals(923, $objConfig->packages_missed_beacon);
        $this->assertEquals(23, $objConfig->packages_rx_invalid_crypt);
        $this->assertEquals(42, $objConfig->packages_rx_invalid_frag);
        $this->assertEquals(12, $objConfig->packages_rx_invalid_nwid);
        $this->assertEquals(332, $objConfig->packages_tx_excessive_retries);


        //Bug #11343: fix preg_match for rssi value/signal strength
        $strConfig = <<<EOT
wlan0     802.11b linked  ESSID:"Project-Node-Zero"
          Mode:Managed  Frequency:2.437 GHz  Access Point: 00:12:17:AD:2C:CE
          Bit Rate=11 Mb/s   Sensitivity=80/85
          Retry:on   Fragment thr:off
          Power Management:off
          Link Quality:93/100  Signal level:-49 dBm  Noise level:-249 dBm
          Rx invalid nwid:0  Rx invalid crypt:0  Rx invalid frag:0
          Tx excessive retries:0  Invalid misc:0   Missed beacon:0
EOT;
        $objConfig = $this->wls->parseCurrentConfig($strConfig);
        $this->assertEquals(-49, $objConfig->rssi);
        $this->assertEquals(-249, $objConfig->noise);
        $this->assertEquals('802.11b', $objConfig->protocol);
    }//function testParseCurrentConfig()



    /**
    * tests the "parseScan" function which
    * scans the iwlist output
    *
    * @return void
    */
    function testParseScan()
    {
        //no peers
        $arLines = array("eth1      No scan results");
        $arCells = $this->wls->parseScan($arLines);
        $this->assertEquals(0, count($arCells));

        //some peers
        //driver: ipw2200 0.21, acer travelmate 6003
        $arLines = array(
            "eth1      Scan completed :",
            "          Cell 01 - Address: 00:02:6F:08:4E:8A",
            "                    ESSID:\"eurospot\"",
            "                    Protocol:IEEE 802.11b",
            "                    Mode:Master",
            "                    Channel:1",
            "                    Encryption key:off",
            "                    Bit Rate:11 Mb/s",
            "                    Extra: Rates (Mb/s): 1 2 5.5 11 ",
            "                    Extra: RSSI: -54  dBm ",
            "                    Extra: Last beacon: 8ms ago",
            "          Cell 02 - Address: 00:0F:3D:4B:0D:6E",
            "                    ESSID:\"RIKA\"",
            "                    Protocol:IEEE 802.11g",
            "                    Mode:Master",
            "                    Channel:6",
            "                    Encryption key:on",
            "                    Bit Rate:54 Mb/s",
            "                    Extra: Rates (Mb/s): 1 2 5.5 9 11 6 12 18 24 36 48 54 ",
            "                    Extra: RSSI: -53  dBm ",
            "                    Extra: Last beacon: 754ms ago",
            "          Cell 03 - Address: 00:0D:BC:50:62:06",
            "                    ESSID:\"skyspeed\"",
            "                    Protocol:IEEE 802.11b",
            "                    Mode:Master",
            "                    Channel:1",
            "                    Encryption key:off",
            "                    Bit Rate:11 Mb/s",
            "                    Extra: Rates (Mb/s): 1 2 5.5 11 ",
            "                    Extra: RSSI: -59  dBm ",
            "                    Extra: Last beacon: 544ms ago",
            "          Cell 04 - Address: 64:70:02:2E:FF:EA",
            "                    Channel:6",
            "                    Frequency:2.437 GHz (Channel 6)",
            "                    Quality=63/70  Signal level=-47 dBm  ",
            "                    Encryption key:on",
            "                    ESSID:\"test\"",
            "                    Bit Rates:1 Mb/s; 2 Mb/s; 5.5 Mb/s; 11 Mb/s; 6 Mb/s",
            "                              9 Mb/s; 12 Mb/s; 18 Mb/s",
            "                    Bit Rates:24 Mb/s; 36 Mb/s; 48 Mb/s; 54 Mb/s",
            "                    Mode:Master",
            "                    Extra:tsf=00000001e5290ee3",
            "                    Extra: Last beacon: 2360ms ago",
            "                    IE: Unknown: 000474657374",
            "                    IE: Unknown: 010882848B960C121824",
            "                    IE: Unknown: 030106",
            "                    IE: Unknown: 0706495420010B14",
            "                    IE: Unknown: 2A0104",
            "                    IE: Unknown: 32043048606C",
            "                    IE: IEEE 802.11i/WPA2 Version 1",
            "                        Group Cipher : TKIP",
            "                        Pairwise Ciphers (1) : CCMP",
            "                        Authentication Suites (1) : PSK",
            "                    IE: WPA Version 1",
            "                        Group Cipher : TKIP",
            "                        Pairwise Ciphers (2) : CCMP TKIP",
            "                        Authentication Suites (1) : PSK",
            "                    IE: Unknown: 2D1A0C001FFF00000001000000000096000100000000000000000000",
            "                    IE: Unknown: 3D1606000000000000000000000000000000000000000000",
            "                    IE: Unknown: 7F0400000080",
            "                    IE: Unknown: 6B0100",
            "                    IE: Unknown: 6C027F00",
            "                    IE: Unknown: DD180050F2020101000003A4000027A4000042435E0062322F00",
            );

        $arCells = $this->wls->parseScan($arLines);

        $this->assertEquals(4                           , count($arCells));

        $this->assertEquals('net_wifi_cell'             , strtolower(get_class($arCells[0])));

        $this->assertEquals('string'                    , gettype($arCells[0]->mac));
        $this->assertEquals('string'                    , gettype($arCells[0]->ssid));
        $this->assertEquals('string'                    , gettype($arCells[0]->mode));
        $this->assertEquals('integer'                   , gettype($arCells[0]->channel));
        $this->assertEquals('boolean'                   , gettype($arCells[0]->encryption));
        $this->assertEquals('string'                    , gettype($arCells[0]->protocol));
        //floatval() should return float and not double...
        $this->assertEquals('double'                    , gettype($arCells[0]->rate));
        $this->assertEquals('array'                     , gettype($arCells[0]->rates));
        $this->assertEquals('integer'                   , gettype($arCells[0]->rssi));
        $this->assertEquals('integer'                   , gettype($arCells[0]->beacon));
        $this->assertEquals('boolean'                   , gettype($arCells[0]->wpa));
        $this->assertEquals('boolean'                   , gettype($arCells[0]->wpa2));
        $this->assertEquals('array'                    , gettype($arCells[0]->wpa_group_cipher));
        $this->assertEquals('array'                    , gettype($arCells[0]->wpa_pairwise_cipher));
        $this->assertEquals('array'                    , gettype($arCells[0]->wpa_auth_suite));
        $this->assertEquals('array'                    , gettype($arCells[0]->wpa2_group_cipher));
        $this->assertEquals('array'                    , gettype($arCells[0]->wpa2_pairwise_cipher));
        $this->assertEquals('array'                    , gettype($arCells[0]->wpa2_auth_suite));


        $this->assertEquals('00:02:6F:08:4E:8A'         , $arCells[0]->mac);
        $this->assertEquals('eurospot'                  , $arCells[0]->ssid);
        $this->assertEquals('master'                    , $arCells[0]->mode);
        $this->assertEquals(1                           , $arCells[0]->channel);
        $this->assertEquals(false                       , $arCells[0]->encryption);
        $this->assertEquals('802.11b'                   , $arCells[0]->protocol);
        $this->assertEquals(11                          , $arCells[0]->rate);
        $this->assertEquals(array(1., 2., 5.5, 11.)     , $arCells[0]->rates);
        $this->assertEquals(-54                         , $arCells[0]->rssi);
        $this->assertEquals(8                           , $arCells[0]->beacon);
        $this->assertEquals(false                       , $arCells[0]->wpa);
        $this->assertEquals(false                       , $arCells[0]->wpa2);
        $this->assertEquals(array()                     , $arCells[0]->wpa_group_cipher);
        $this->assertEquals(array()                     , $arCells[0]->wpa_pairwise_cipher);
        $this->assertEquals(array()                     , $arCells[0]->wpa_auth_suite);
        $this->assertEquals(array()                     , $arCells[0]->wpa2_group_cipher);
        $this->assertEquals(array()                     , $arCells[0]->wpa2_pairwise_cipher);
        $this->assertEquals(array()                     , $arCells[0]->wpa2_auth_suite);

        $this->assertEquals('00:0F:3D:4B:0D:6E'         , $arCells[1]->mac);
        $this->assertEquals('RIKA'                      , $arCells[1]->ssid);
        $this->assertEquals('master'                    , $arCells[1]->mode);
        $this->assertEquals(6                           , $arCells[1]->channel);
        $this->assertEquals(true                        , $arCells[1]->encryption);
        $this->assertEquals('802.11g'                  , $arCells[1]->protocol);
        $this->assertEquals(54                          , $arCells[1]->rate);
        $this->assertEquals(array(1., 2., 5.5, 6., 9., 11., 12., 18., 24., 36., 48., 54.), $arCells[1]->rates);
        $this->assertEquals(-53                         , $arCells[1]->rssi);
        $this->assertEquals(754                         , $arCells[1]->beacon);
        $this->assertEquals(false                       , $arCells[1]->wpa);
        $this->assertEquals(false                       , $arCells[1]->wpa2);
        $this->assertEquals(array()                     , $arCells[1]->wpa_group_cipher);
        $this->assertEquals(array()                     , $arCells[1]->wpa_pairwise_cipher);
        $this->assertEquals(array()                     , $arCells[1]->wpa_auth_suite);
        $this->assertEquals(array()                     , $arCells[1]->wpa2_group_cipher);
        $this->assertEquals(array()                     , $arCells[1]->wpa2_pairwise_cipher);
        $this->assertEquals(array()                     , $arCells[1]->wpa2_auth_suite);

        $this->assertEquals('00:0D:BC:50:62:06'         , $arCells[2]->mac);
        $this->assertEquals('skyspeed'                  , $arCells[2]->ssid);
        $this->assertEquals('master'                    , $arCells[2]->mode);
        $this->assertEquals(1                           , $arCells[2]->channel);
        $this->assertEquals(false                       , $arCells[2]->encryption);
        $this->assertEquals('802.11b'                   , $arCells[2]->protocol);
        $this->assertEquals(11                          , $arCells[2]->rate);
        $this->assertEquals(array(1., 2., 5.5, 11.)     , $arCells[2]->rates);
        $this->assertEquals(-59                         , $arCells[2]->rssi);
        $this->assertEquals(544                         , $arCells[2]->beacon);
        $this->assertEquals(false                       , $arCells[2]->wpa);
        $this->assertEquals(false                       , $arCells[2]->wpa2);
        $this->assertEquals(array()                     , $arCells[2]->wpa_group_cipher);
        $this->assertEquals(array()                     , $arCells[2]->wpa_pairwise_cipher);
        $this->assertEquals(array()                     , $arCells[2]->wpa_auth_suite);
        $this->assertEquals(array()                     , $arCells[2]->wpa2_group_cipher);
        $this->assertEquals(array()                     , $arCells[2]->wpa2_pairwise_cipher);
        $this->assertEquals(array()                     , $arCells[2]->wpa2_auth_suite);

        $this->assertEquals('64:70:02:2E:FF:EA'         , $arCells[3]->mac);
        $this->assertEquals('test'                      , $arCells[3]->ssid);
        $this->assertEquals('master'                    , $arCells[3]->mode);
        $this->assertEquals(6                           , $arCells[3]->channel);
        $this->assertEquals(true                        , $arCells[3]->encryption);
        $this->assertEquals(''                          , $arCells[3]->protocol);
        $this->assertEquals(54                          , $arCells[3]->rate);
        $this->assertEquals(array(1., 2., 5.5, 6., 9., 11., 12., 18., 24., 36., 48., 54.), $arCells[3]->rates);
        $this->assertEquals(-47                         , $arCells[3]->rssi);
        $this->assertEquals(2360                        , $arCells[3]->beacon);
        $this->assertEquals(true                        , $arCells[3]->wpa);
        $this->assertEquals(true                        , $arCells[3]->wpa2);
        $this->assertEquals(array('TKIP')               , $arCells[3]->wpa_group_cipher);
        $this->assertEquals(array('CCMP', 'TKIP')       , $arCells[3]->wpa_pairwise_cipher);
        $this->assertEquals(array('PSK')                , $arCells[3]->wpa_auth_suite);
        $this->assertEquals(array('TKIP')               , $arCells[3]->wpa2_group_cipher);
        $this->assertEquals(array('CCMP')               , $arCells[3]->wpa2_pairwise_cipher);
        $this->assertEquals(array('PSK')                , $arCells[3]->wpa2_auth_suite);


        //some other peers
        //driver: ipw2100 ???, samsung x10
        $arLines = array(
            "eth2      Scan completed :",
            "          Cell 01 - Address: 00:40:05:28:EB:45",
            "                    ESSID:\"default\"",
            "                    Protocol:IEEE 802.11b",
            "                    Mode:Master",
            "                    Channel:6",
            "                    Encryption key:on",
            "                    Bit Rate:22 Mb/s",
            "                    Extra: Rates (Mb/s): 1 2 5.5 11 22",
            "                    Extra: Signal: -88  dBm",
            "                    Extra: Last beacon: 747642ms ago",
            "          Cell 02 - Address: 00:30:F1:C8:E4:FB",
            "                    ESSID:\"Alien\"",
            "                    Protocol:IEEE 802.11g",
            "                    Mode:Master",
            "                    Channel:8",
            "                    Encryption key:on",
            "                    Bit Rate:54 Mb/s",
            "                    Extra: Rates (Mb/s): 1 2 5.5 6 9 11 12 18 24 36 48 54",
            "                    Extra: Signal: -84  dBm",
            "                    Extra: Last beacon: 1872456ms ago",
            "          Cell 03 - Address: 00:09:5B:2B:5F:74",
            "                    ESSID:\"Wireless\"",
            "                    Protocol:IEEE 802.11b",
            "                    Mode:Master",
            "                    Channel:10",
            "                    Encryption key:on",
            "                    Bit Rate:11 Mb/s",
            "                    Extra: Rates (Mb/s): 1 2 5.5 11",
            "                    Extra: Signal: -48  dBm",
            "                    Extra: Last beacon: 27631ms ago"
        );

        $arCells = $this->wls->parseScan($arLines);

        $this->assertEquals(3                           , count($arCells));

        $this->assertEquals('00:40:05:28:EB:45'         , $arCells[0]->mac);
        $this->assertEquals('default'                   , $arCells[0]->ssid);
        //different signal name
        $this->assertEquals(-88                         , $arCells[0]->rssi);
        $this->assertEquals(747642                      , $arCells[0]->beacon);


        //with ipw2200 1.0 we've got "Signal level=..." instead of "Extra: Signal"
        $arLines = array(
            "eth1      Scan completed :",
            "  Cell 01 - Address: 00:03:C9:44:34:2C",
            "            ESSID:\"<hidden>\"",
            "            Protocol:IEEE 802.11bg",
            "            Mode:Master",
            "            Channel:5",
            "            Encryption key:on",
            "            Bit Rate:54 Mb/s",
            "            Extra: Rates (Mb/s): 1 2 5.5 6 9 11 12 18 24 36 48 54",
            "            Signal level=-51 dBm",
            "            Extra: Last beacon: 9ms ago"
        );

        $arCells = $this->wls->parseScan($arLines);

        $this->assertEquals(1                           , count($arCells));

        $this->assertEquals('00:03:C9:44:34:2C'         , $arCells[0]->mac);
        $this->assertEquals('<hidden>'                  , $arCells[0]->ssid);
        //different signal name
        $this->assertEquals(-51                         , $arCells[0]->rssi);
        $this->assertEquals(9                           , $arCells[0]->beacon);


        //ipw2200 1.0.1
        $arLines = array(
            "eth1      Scan completed :",
            "          Cell 01 - Address: 00:0D:BC:68:28:1A",
            "                    ESSID:\"Rai Private\"",
            "                    Protocol:IEEE 802.11b",
            "                    Mode:Master",
            "                    Channel:1",
            "                    Encryption key:off",
            "                    Bit Rate:11 Mb/s",
            "                    Extra: Rates (Mb/s): 1 2 5.5 11",
            "                    Quality=67/100  Signal level=-60 dBm",
            "                    Extra: Last beacon: 59ms ago",
            "          Cell 02 - Address: 00:0D:BC:68:28:05",
            "                    ESSID:\"Rai Private\"",
            "                    Protocol:IEEE 802.11b",
            "                    Mode:Master",
            "                    Channel:6",
            "                    Encryption key:off",
            "                    Bit Rate:11 Mb/s",
            "                    Extra: Rates (Mb/s): 1 2 5.5 11",
            "                    Quality=39/100  Signal level=-77 dBm",
            "                    Extra: Last beacon: 11ms ago"
        );

        $arCells = $this->wls->parseScan($arLines);

        $this->assertEquals(2                          , count($arCells));

        $this->assertEquals('00:0D:BC:68:28:1A'        , $arCells[0]->mac);
        $this->assertEquals('Rai Private'              , $arCells[0]->ssid);
        $this->assertEquals(-60                        , $arCells[0]->rssi);
        $this->assertEquals(59                         , $arCells[0]->beacon);



        //ipw2100 carsten (unknown version)
        $arLines = array(
            'eth1      Scan completed :',
            '          Cell 01 - Address: 00:12:D9:AC:BD:00',
            '                    ESSID:"Rai Wireless"',
            '                    Mode:Master',
            '                    Frequency:2.412GHz',
            '                    Bit Rate:1Mb/s',
            '                    Bit Rate:2Mb/s',
            '                    Bit Rate:5.5Mb/s',
            '                    Bit Rate:6Mb/s',
            '                    Bit Rate:9Mb/s',
            '                    Bit Rate:11Mb/s',
            '                    Bit Rate:12Mb/s',
            '                    Bit Rate:18Mb/s',
            '                    Quality:12/100  Signal level:-86 dBm  Noise level:-98 dBm',
            '                    Encryption key:off'
        );

        $arCells = $this->wls->parseScan($arLines);

        $this->assertEquals(1                          , count($arCells));
        $this->assertEquals('00:12:D9:AC:BD:00'        , $arCells[0]->mac);
        $this->assertEquals('Rai Wireless'             , $arCells[0]->ssid);
        $this->assertEquals('master'                   , $arCells[0]->mode);
        $this->assertEquals(-86                        , $arCells[0]->rssi);
        $this->assertEquals('2.412GHz'                 , $arCells[0]->frequency);
        $this->assertEquals(18                         , $arCells[0]->rate);
        $this->assertEquals(array(1.,2.,5.5,6.,9.,11.,12.,18.), $arCells[0]->rates);
        $this->assertEquals(false                      , $arCells[0]->encryption);


        //ipw2200 kernel 2.18.1 (probably version 1.1.3)
        $arLines = explode(
            "\n",
            <<<EOD
eth1      Scan completed :
          Cell 01 - Address: 00:12:D9:AC:BD:00
                    ESSID:"<hidden>"
                    Protocol:IEEE 802.11bg
                    Mode:Master
                    Channel:1
                    Encryption key:off
                    Bit Rates:1 Mb/s; 2 Mb/s; 5.5 Mb/s; 6 Mb/s; 9 Mb/s
                              11 Mb/s; 12 Mb/s; 18 Mb/s; 24 Mb/s; 36 Mb/s
                              48 Mb/s; 54 Mb/s
                    Quality=89/100  Signal level=-40 dBm
                    Extra: Last beacon: 1472ms ago
EOD
        );
        $arCells = $this->wls->parseScan($arLines);

        $this->assertEquals(1                          , count($arCells));
        $this->assertEquals('00:12:D9:AC:BD:00'        , $arCells[0]->mac);
        $this->assertEquals('<hidden>'                 , $arCells[0]->ssid);
        $this->assertEquals('master'                   , $arCells[0]->mode);
        $this->assertEquals(-40                        , $arCells[0]->rssi);
        $this->assertEquals(null                       , $arCells[0]->frequency);
        $this->assertEquals(54                         , $arCells[0]->rate);
        $this->assertEquals(
            array(1.,2.,5.5,6.,9.,11.,12.,18.,24.,36.,48.,54.),
            $arCells[0]->rates
        );
        $this->assertEquals(false                      , $arCells[0]->encryption);
        $this->assertEquals(1472                       , $arCells[0]->beacon);

    }//function testParseScan()



    /**
     * More scans on 2009-10-18
     *
     * @return void
     */
    function testParseScan20091018()
    {
        $arLines = explode(
            "\n",
            <<<EOD
wlan0     Scan completed :
          Cell 01 - Address: 00:50:7F:9B:A7:D8
                    Channel:8
                    Frequency:2.447 GHz (Channel 8)
                    Quality=64/70  Signal level=-46 dBm  
                    Encryption key:off
                    ESSID:"UPSTREAM_NEU"
                    Bit Rates:1 Mb/s; 2 Mb/s; 5.5 Mb/s; 11 Mb/s; 9 Mb/s
                              18 Mb/s; 36 Mb/s; 54 Mb/s
                    Bit Rates:6 Mb/s; 12 Mb/s; 24 Mb/s; 48 Mb/s
                    Mode:Master
                    Extra:tsf=00000002f8bae155
                    Extra: Last beacon: 1221684936ms ago
                    IE: Unknown: 000C555053545245414D5F4E4555
                    IE: Unknown: 010882848B961224486C
                    IE: Unknown: 030108
                    IE: Unknown: 2A0100
                    IE: Unknown: 32040C183060
                    IE: Unknown: 2D1A6E1017FFFF000001000000000000000000000000000000000000
                    IE: Unknown: 3D1608070700000000000000000000000000000000000000
                    IE: Unknown: 3E0100
                    IE: Unknown: DD180050F2020101000003A4000027A4000042435E0062322F00
                    IE: Unknown: 7F0101
                    IE: Unknown: DD07000C4300000000
                    IE: Unknown: 0706545720010D10
                    IE: Unknown: DD1E00904C336E1017FFFF000001000000000000000000000000000000000000
                    IE: Unknown: DD1A00904C3408070700000000000000000000000000000000000000
          Cell 02 - Address: 00:13:49:D6:AC:3C
                    Channel:6
                    Frequency:2.437 GHz (Channel 6)
                    Quality=33/70  Signal level=-77 dBm  
                    Encryption key:off
                    ESSID:"home.cweiske.de"
                    Bit Rates:1 Mb/s; 2 Mb/s; 5.5 Mb/s; 11 Mb/s
                    Bit Rates:6 Mb/s; 9 Mb/s; 12 Mb/s; 18 Mb/s; 24 Mb/s
                              36 Mb/s; 48 Mb/s; 54 Mb/s
                    Mode:Master
                    Extra:tsf=00000002f8cc11df
                    Extra: Last beacon: 3560ms ago
                    IE: Unknown: 000F686F6D652E63776569736B652E6465
                    IE: Unknown: 010482848B96
                    IE: Unknown: 030106
                    IE: Unknown: 32080C1218243048606C

EOD
        );
        $arCells = $this->wls->parseScan($arLines);

        $this->assertEquals(2                          , count($arCells));
        $this->assertEquals('00:50:7F:9B:A7:D8'        , $arCells[0]->mac);
        $this->assertEquals('UPSTREAM_NEU'             , $arCells[0]->ssid);
        $this->assertEquals('master'                   , $arCells[0]->mode);
        $this->assertEquals(-46                        , $arCells[0]->rssi);
        $this->assertEquals('2.447 GHz'                , $arCells[0]->frequency);
        $this->assertEquals(8                          , $arCells[0]->channel);
        $this->assertEquals(
            array(1.,2.,5.5,6.,9.,11.,12.,18.,24.,36.,48.,54.),
            $arCells[0]->rates
        );
        $this->assertEquals(false                      , $arCells[0]->encryption);
        $this->assertEquals(1221684936                 , $arCells[0]->beacon);

        $this->assertEquals('00:13:49:D6:AC:3C'        , $arCells[1]->mac);
        $this->assertEquals('home.cweiske.de'          , $arCells[1]->ssid);
        $this->assertEquals('master'                   , $arCells[1]->mode);
        $this->assertEquals(-77                        , $arCells[1]->rssi);
        $this->assertEquals('2.437 GHz'                , $arCells[1]->frequency);
        $this->assertEquals(6                          , $arCells[1]->channel);
        $this->assertEquals(
            array(1.,2.,5.5,6.,9.,11.,12.,18.,24.,36.,48.,54.),
            $arCells[1]->rates
        );
        $this->assertEquals(false                      , $arCells[1]->encryption);
        $this->assertEquals(3560                       , $arCells[1]->beacon);
    }



    /**
     * Tests setting and getting the proc-wireless path
     *
     * @return void
     */
    public function testSetGetPathProcWireless()
    {
        $path = '/this/is/my/path';
        $this->wls->setPathProcWireless($path);
        $this->assertEquals($path, $this->wls->getPathProcWireless());
    }



    /**
     * Tests setting and getting the path to iwconfig
     *
     * @return void
     */
    public function testSetGetPathIwconfig()
    {
        $path = '/this/is/my/path';
        $this->wls->setPathIwconfig($path);
        $this->assertEquals($path, $this->wls->getPathIwconfig());
    }



    /**
     * Tests setting and getting the path to iwlist
     *
     * @return void
     */
    public function testSetGetPathIwlist()
    {
        $path = '/this/is/my/path';
        $this->wls->setPathIwlist($path);
        $this->assertEquals($path, $this->wls->getPathIwlist());
    }
}

// Call Net_WifiTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "Net_WifiTest::main") {
    Net_WifiTest::main();
}
?>
