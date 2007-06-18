<?php

/**
*   Configuration settings of a wifi network interface.
*
*   @author Christian Weiske <cweiske@php.net>
*/
class Net_Wifi_Config
{
    /**
    *   If the interface is activated.
    *   Some notebooks have a button which deactivates wifi, this is recognized here.
    *   Note that this setting can't be read by all drivers, and so
    *    it's "true" if it can't be determined. You can be sure that it's deactivated
    *    if this setting is false, but not that it's activated if it's true
    *   @var boolean
    */
    var $activated  = true;

    /**
    *   MAC address of the associated access point.
    *   @var string
    */
    var $ap         = null;

    /**
    *   If the interface is connected to an access point or an ad-hoc network.
    *   @var boolean
    */
    var $associated = false;

    /**
    *   Network type.
    *   Can be "master" or "ad-hoc" (without quotes)
    *   @var string
    */
    var $mode       = null;

    /**
    *   The nickname which the interface (computer) uses.
    *   Something like a computer name
    *   @var string
    */
    var $nick       = null;

    /**
    *   Noise level in dBm - how much the signal is disturbed
    *   example: -249
    *   @var int
    */
    var $noise      = null;

    /**
    *   Power setting of the interface.
    *   @var int
    */
    var $power      = null;

    /**
    *   Protocol version which is used for connection.
    *   example: "IEEE 802.11g" without quotes
    *   @var string
    */
    var $protocol   = null;

    /**
    *   The bit rate of the connection.
    *   @var float
    */
    var $rate       = null;

    /**
    *   Signal strength in dBm.
    *   example: -59
    *   @var int
    */
    var $rssi       = null;

    /**
    *   "Service Set IDentifier" of the cell which identifies current network.
    *   Max. 32 alphanumeric characters
    *   example: "My Network" (without quotes)
    *   @var string
    */
    var $ssid       = null;

}//class Net_Wifi_Config
?>