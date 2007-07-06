<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
*   A class for scanning wireless networks and identifying
*   local wireless network interfaces.
*
*   PHP versions 4 and 5
*
*   LICENSE: This source file is subject to version 3.0 of the PHP license
*   that is available through the world-wide-web at the following URI:
*   http://www.php.net/license/3_0.txt.  If you did not receive a copy of
*   the PHP License and are unable to obtain it through the web, please
*   send a note to license@php.net so we can mail you a copy immediately.
*
*   @author Christian Weiske <cweiske@php.net>
*   @category Networking
*   @package Net_Wifi
*   @license http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
*   @version CVS: $Id$
*/

require_once 'Net/Wifi/Cell.php';
require_once 'Net/Wifi/Config.php';
//required for System::which() functionality
require_once 'System.php';

/**
*   This class uses tools like iwconfig and iwlist to scan
*   for wireless networks
*
*   @author Christian Weiske <cweiske@php.net>
*/
class Net_Wifi
{
    /**
    *   Various locations of programs
    *   @var array
    */
    var $arFileLocation = array(
        'iwconfig'           => '/usr/sbin/iwconfig',
        'iwlist'             => '/usr/sbin/iwlist',
        '/proc/net/wireless' => '/proc/net/wireless'
    );



    /**
    *   Constructor which tries to guess the paths of the tools
    */
    function Net_Wifi()
    {
        //try to find the paths
        $iwconfig = System::which('iwconfig');
        if ($iwconfig !== false) {
            $this->setPathIwconfig($iwconfig);
        } else if (file_exists('/sbin/iwconfig')) {
            $this->setPathIwconfig('/sbin/iwconfig');
        }

        $iwlist = System::which('iwlist');
        if ($iwlist !== false) {
            $this->setPathIwlist($iwlist);
        } else if (file_exists('/sbin/iwlist')) {
            $this->setPathIwlist('/sbin/iwlist');
        }
    }//function Net_Wifi()



    /**
    *   Returns an object with the current state of the interface (connected/not connected, AP,...).
    *
    *   @access public
    *   @param  string           The interface to check
    *   @return Net_Wifi_Config  The state information
    */
    function getCurrentConfig($strInterface)
    {
        //get the plain config
        $arLines = array();
        exec($this->arFileLocation['iwconfig'] . ' ' . escapeshellarg($strInterface), $arLines);
        $strAll = implode("\n", $arLines);

        return $this->parseCurrentConfig($strAll);
    }//function getCurrentConfig($strInterface)



    /**
    *   Parses the iwconfig output to collect the current config information.
    *
    *   @access protected
    *   @param  string           The iwconfig output to parse
    *   @return Net_Wifi_Config  The current config object
    */
    function parseCurrentConfig($strAll)
    {
        $objConfig = new Net_Wifi_Config();

        $arMatches = array();
        if (preg_match('/ESSID:"([^"]+)"/', $strAll, $arMatches)) {
            $objConfig->ssid = $arMatches[1];
        }
        if (preg_match('/Access Point: ([0-9:A-F]{17})/', $strAll, $arMatches)) {
            $objConfig->ap = $arMatches[1];
        }
        if (preg_match('/Nickname:"([^"]+)"/', $strAll, $arMatches)) {
            $objConfig->nick = $arMatches[1];
        }
        if (strpos($strAll, 'Mode:Managed')) {
            $objConfig->mode = 'managed';
        } else if (strpos($strAll, 'Mode:Ad-Hoc')) {
            $objConfig->mode = 'ad-hoc';
        }
        if (preg_match('/Bit Rate[:=]([0-9.]+) [mk]b\\/s/i', $strAll, $arMatches)) {
            $objConfig->rate = $arMatches[1];
        }
        if (preg_match('/Power[:=]([0-9]+) dBm/', $strAll, $arMatches)) {
            $objConfig->power = $arMatches[1];
        }
        if (preg_match('/Signal level[:=](-?[0-9]+) dBm/', $strAll, $arMatches)) {
            $objConfig->rssi = $arMatches[1];
        }
        if (preg_match('/Noise level[:=](-?[0-9]+) dBm/', $strAll, $arMatches)) {
            $objConfig->noise = $arMatches[1];
        }
        if (preg_match('/IEEE ([0-9.]+[a-z])/', $strAll, $arMatches)) {
            $objConfig->protocol = $arMatches[1];
        } else if (preg_match('/([0-9.]+[a-z])\s+linked\s+ESSID/', $strAll, $arMatches)) {
            $objConfig->protocol = $arMatches[1];
        }

        if (preg_match('/Rx invalid nwid[:=](-?[0-9]+)/', $strAll, $arMatches)) {
            $objConfig->packages_rx_invalid_nwid = $arMatches[1];
        }
        if (preg_match('/Rx invalid crypt[:=](-?[0-9]+)/', $strAll, $arMatches)) {
            $objConfig->packages_rx_invalid_crypt = $arMatches[1];
        }
        if (preg_match('/Rx invalid frag[:=](-?[0-9]+)/', $strAll, $arMatches)) {
            $objConfig->packages_rx_invalid_frag = $arMatches[1];
        }
        if (preg_match('/Tx excessive retries[:=](-?[0-9]+)/', $strAll, $arMatches)) {
            $objConfig->packages_tx_excessive_retries = $arMatches[1];
        }
        if (preg_match('/Invalid misc[:=](-?[0-9]+)/', $strAll, $arMatches)) {
            $objConfig->packages_invalid_misc = $arMatches[1];
        }
        if (preg_match('/Missed beacon[:=](-?[0-9]+)/', $strAll, $arMatches)) {
            $objConfig->packages_missed_beacon = $arMatches[1];
        }

        //available in ipw2200 1.0.3 only
        if (strpos($strAll, 'radio off')) {
            $objConfig->activated = false;
        }

        if (strpos($strAll, 'unassociated') === false
            && $objConfig->ap != null && $objConfig->ap != '00:00:00:00:00:00') {
            $objConfig->associated = true;
        }

        return $objConfig;
    }//function parseCurrentConfig($strAll)



    /**
    *   Checks if a network interface is connected to an access point.
    *
    *   @access public
    *   @param  string      The network interface to check
    *   @return boolean     If the interface is connected
    */
    function isConnected($strInterface)
    {
        $objConfig = $this->getCurrentConfig($strInterface);

        return $objConfig->associated;
    }//function isConnected($strInterface)



    /**
    *   Returns an array with the names/device files of all supported wireless lan devices.
    *
    *   @access public
    *   @return array   Array with wireless interfaces as values
    */
    function getSupportedInterfaces()
    {
        $arWirelessInterfaces = array();
        if (file_exists($this->arFileLocation['/proc/net/wireless'])) {
            /**
            *   use /proc/net/wireless
            */
            $arLines = file($this->arFileLocation['/proc/net/wireless']);
            //begin with 3rd line
            if (count($arLines) > 2) {
                for ($nA = 2; $nA < count($arLines); $nA++) {
                    $nPos = strpos($arLines[$nA], ':', 0);
                    $strInterface = trim(substr($arLines[$nA], 0, $nPos));
                    $arWirelessInterfaces[] = $strInterface;
                }
            }//we've got more than 2 lines
        } else {
            /**
            *   use iwconfig
            */
            $arLines = array();
            exec($this->arFileLocation['iwconfig'], $arLines);
            foreach ($arLines as $strLine) {
                if (trim($strLine[0]) != '' && strpos($strLine, 'no wireless extensions') === false) {
                    //there is something
                    $arWirelessInterfaces[] = substr($strLine, 0, strpos($strLine, ' '));
                }
            }//foreach line
        }//use iwconfig

        return $arWirelessInterfaces;
    }//function getSupportedInterfaces()



    /**
    *   Scans for access points / ad hoc cells and returns them.
    *
    *   @access public
    *   @param  string  The interface to use
    *   @return array   Array with cell information objects (Net_Wifi_Cell)
    */
    function scan($strInterface)
    {
        $arLines = array();
        exec($this->arFileLocation['iwlist'] . ' ' . escapeshellarg($strInterface) . ' scanning', $arLines);

        return $this->parseScan( $arLines);
    }//function scan($strInterface)



    /**
    *   Parses the output of iwlist and returns the recognized cells.
    *
    *   @access protected
    *   @param  array       Lines of the iwlist output as an array
    *   @return array       Array with cell information objects
    */
    function parseScan($arLines)
    {
        if (count($arLines) == 1) {
            //one line only -> no cells there
            return array();
        }

        //if bit rates are alone on lines
        $bStandaloneRates = false;

        //split into cells
        $arCells = array();
        $nCurrentCell = -1;
        $nCount = count($arLines);
        for ($nA = 1; $nA < $nCount; $nA++) {
            $strLine = trim($arLines[$nA]);
            if ($strLine == '') {
                continue;
            }

            if (substr($strLine, 0, 4) == 'Cell') {
                //we've got a new cell
                $nCurrentCell++;
                //get cell number
                $nCell = substr($strLine, 5, strpos($strLine, ' ', 5) - 5);
                $arCells[$nCurrentCell] = new Net_Wifi_Cell();


                $arCells[$nCurrentCell]->cell = $nCell;

                //remove cell information from line for further interpreting
                $strLine = substr($strLine, strpos($strLine, '- ') + 2);
            }

            $nPos       = strpos($strLine, ':');
            $nPosEquals = strpos($strLine, '=');
            if ($nPosEquals !== false && ($nPos === false || $nPosEquals < $nPos)) {
                //sometimes there is a "=" instead of a ":"
                $nPos = $nPosEquals;
            }
            $nPos++;

            $strId    = strtolower(substr($strLine, 0, $nPos - 1));
            $strValue = trim(substr($strLine, $nPos));
            switch ($strId) {
                case 'address':
                    $arCells[$nCurrentCell]->mac = $strValue;
                    break;

                case 'essid':
                    if ($strValue[0] == '"') {
                        //has quotes around
                        $arCells[$nCurrentCell]->ssid = substr($strValue, 1, -1);
                    } else {
                        $arCells[$nCurrentCell]->ssid = $strValue;
                    }
                    break;

                case 'bit rate':
                    $nRate = floatval(substr($strValue, 0, strpos($strValue, 'Mb/s')));
                    $arCells[$nCurrentCell]->rate    = $nRate;
                    $arCells[$nCurrentCell]->rates[] = $nRate;
                    break;

                case 'bit rates':
                    $bStandaloneRates = true;
                    $arLines[$nA] = $strValue;
                    $nA--;//go back one so that this line is re-parsed
                    break;

                case 'protocol':
                    if (substr($strValue, 0, 5) == 'IEEE ') {
                        $strValue = substr($strValue, 5);
                    }
                    $arCells[$nCurrentCell]->protocol = $strValue;
                    break;

                case 'channel':
                    $arCells[$nCurrentCell]->channel = intval($strValue);
                    break;

                case 'encryption key':
                    if ($strValue == 'on') {
                        $arCells[$nCurrentCell]->encryption = true;
                    } else {
                        $arCells[$nCurrentCell]->encryption = false;
                    }
                    break;

                case 'mode':
                    if (strtolower($strValue) == 'master') {
                        $arCells[$nCurrentCell]->mode = 'master';
                    } else {
                        $arCells[$nCurrentCell]->mode = 'ad-hoc';
                    }
                    break;

                case 'signal level':
                    $arCells[$nCurrentCell]->rssi = substr($strValue, 0, strpos($strValue, ' '));
                    break;

                case 'quality':
                    $arData = explode('  ', $strValue);
                    $arCells[$nCurrentCell]->quality = $arData[0];
                    if (trim($arData[1]) != '') {
                        //bad hack
                        $arLines[$nA] = $arData[1];
                        $nA--;
                        if (isset($arData[2])) {
                            $arLines[$nA - 1] = $arData[1];
                            $nA--;
                        }
                    }
                    break;

                case 'frequency':
                    $arCells[$nCurrentCell]->frequency = $strValue;
                    break;

                case 'extra':
                    $nPos     = strpos($strValue, ':');
                    $strSubId = strtolower(trim(substr($strValue, 0, $nPos)));
                    $strValue = trim(substr($strValue, $nPos + 1));
                    switch ($strSubId) {
                        case 'rates (mb/s)':
                            //1 2 5.5 11 54
                            $arRates = explode(' ', $strValue);
                            //convert to float values
                            foreach ($arRates as $nB => $strRate) {
                                $arCells[$nCurrentCell]->rates[$nB] = floatval($strRate);
                            }
                            break;

                        case 'signal':
                        case 'rssi':
                            //-53 dBm
                            $arCells[$nCurrentCell]->rssi = intval(substr($strValue, 0, strpos($strValue, ' ')));
                            break;

                        case 'last beacon':
                            //25ms ago
                            $arCells[$nCurrentCell]->beacon = intval(substr($strValue, 0, strpos($strValue, 'ms')));
                            break;

                        default:
                            echo 'unknown iwconfig extra information: ' . $strSubId . "\r\n";
                            break;
                    }
                    break;

                default:
                    if ($bStandaloneRates) {
                        if (preg_match_all('|([0-9.]+) Mb/s|', $strLine, $arMatches) > 0) {
                            foreach ($arMatches[1] as $nRate) {
                                $nRate = floatval($nRate);
                                $arCells[$nCurrentCell]->rate    = $nRate;
                                $arCells[$nCurrentCell]->rates[] = $nRate;
                            }
                            break;
                        }
                    }
                    echo 'unknown iwconfig information: ' . $strId . "\r\n";
                    break;
            }
        }//foreach line


        //not all outputs are sorted (note the 6)
        //Extra: Rates (Mb/s): 1 2 5.5 9 11 6 12 18 24 36 48 54
        //additionally, some drivers have many single "Bit Rate:" fields instead of one big one
        foreach ($arCells as $nCurrentCell => $arData) {
            sort($arCells[$nCurrentCell]->rates);
            $arCells[$nCurrentCell]->rates = array_unique($arCells[$nCurrentCell]->rates);
        }


        return $arCells;
    }//function parseScan($arLines)



    /**
    *   Tells the driver to use the access point with the given MAC address only.
    *
    *   You can use "off" to enable automatic mode again without
    *   changing the current AP, or "any" resp. "auto" to force
    *   the card to re-associate with the currently best AP
    *
    *   EXPERIMENTAL! WILL CHANGE IN FUTURE VERSIONS
    *   @access public
    *   @param  string      The interface to use
    *   @param  string      The mac address of the access point
    *   @return boolean     True if setting was ok, false if not
    */
    function connectToAccessPoint($strInterface, $strMac)
    {
        $arLines = array();
        $nReturnVar = 0;
        exec($this->arFileLocation['iwconfig'] . ' ' . escapeshellarg($strInterface) . ' ap ' . escapeshellarg($strMac), $arLines, $nReturnVar);

        return $nReturnVar == 0;
    }//function connectToAccessPoint($strInterface, $strMac)



    /**
    *   and now some dumb getters and setters
    */



    /**
    *   Returns the set path to /proc/wireless.
    *   @access public
    *   @return string      The path to "/proc/net/wireless"
    */
    function getPathProcWireless()
    {
        return $this->arFileLocation['/proc/net/wireless'];
    }//function getPathProcWireless()



    /**
    *   Returns the set path to /proc/net/wireless.
    *   @access public
    *   @param  string      The new /proc/net/wireless path
    */
    function setPathProcWireless( $strProcWireless)
    {
        $this->arFileLocation['/proc/net/wireless'] = $strProcWireless;
    }//function setPathProcWireless( $strProcWireless)



    /**
    *   Returns the set path to iwconfig.
    *   @access public
    *   @return string      The path to iwconfig
    */
    function getPathIwconfig()
    {
        return $this->arFileLocation['iwconfig'];
    }//function getPathIwconfig()



    /**
    *   Returns the set path to iwconfig.
    *   @access public
    *   @param  string      The new ifwconfig path
    */
    function setPathIwconfig( $strPathIwconfig)
    {
        $this->arFileLocation['iwconfig'] = $strPathIwconfig;
    }//function setPathIwconfig( $strPathIwconfig)



    /**
    *   Returns the set path to iwlist.
    *   @access public
    *   @return string      The path to iwlist
    */
    function getPathIwlist()
    {
        return $this->arFileLocation['iwlist'];
    }//function getPathIwlist()



    /**
    *   Returns the set path to iwlist.
    *   @access public
    *   @param  string      The new iwlist path
    */
    function setPathIwlist( $strPathIwlist)
    {
        $this->arFileLocation['iwlist'] = $strPathIwlist;
    }//function setPathIwlist( $strPathIwlist)


}//class Net_Wifi

?>