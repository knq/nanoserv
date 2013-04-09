<?php

/**
 *
 * nanoserv handlers - DHCP protocol handler
 *
 * Copyright (C) 2004-2010 Vincent Negrier aka. sIX <six@aegis-corp.org>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package nanoserv
 * @subpackage Handlers
 */

namespace Nanoserv\DHCP;

use Nanoserv;

/**
 * DCHP message options parser and builder class
 *
 * @package nanoserv
 * @subpackage Handlers
 */
class Server {
    const MAGIC_COOKIE = 0x63825363;

    public $subnet_mask;
    public $time_offset;
    public $routers;
    public $time_servers;
    public $dns_servers;
    public $log_servers;
    public $hostname;
    public $domain_name;

    public $address_request;
    public $address_time;
    public $option_overload;
    public $dhcp_msg_type;
    public $dhcp_server_id;
    public $parameter_list;
    public $dhcp_message;
    public $renewal_time;
    public $rebinding_time;
    public $server_name;
    public $bootfile_name;
    public $client_identifier;
    public $client_fqdn;
    public $vendor_class_id;
    public $parameter_request_list;

    public $unhandled = array();

    public function __construct($data = NULL) {
        if (isset($data)) {
            list(,$magic) = unpack("N", substr($data, 0, 4));

            if ($magic !== self::MAGIC_COOKIE) throw new \Exception("unable to decode options, wrong magic cookie 0x" . dechex($magic));

            $cur = 4;
            $optlen = strlen($data);

            while ($cur < $optlen) {
                $tmp = unpack("Ccode/Clen", substr($data, $cur, 2));

                $code = $tmp["code"];
                $len = $tmp["len"];

                if ($code === 0) {
                    $cur++;
                    continue;
                } elseif ($code === 255) {
                    break;
                }

                $opt = substr($data, $cur + 2, $len);

                switch ($code) {
                    case 12:
                    $this->hostname = $opt;
                    break;

                    case 50:
                    list(,$tmp) = unpack("N", $opt);
                    $this->address_request = long2ip($tmp);
                    break;

                    case 53:
                    list(,$this->dhcp_msg_type) = unpack("C", $opt);
                    break;

                    case 54:
                    list(,$tmp) = unpack("N", $opt);
                    $this->dhcp_server_id = long2ip($tmp);
                    break;

                    case 55:
                    $this->parameter_request_list = unpack("C*", $opt);
                    break;

                    case 60:
                    $this->vendor_class_id = $opt;
                    break;

                    case 61:
                    $this->client_identifier = bin2hex($opt);
                    break;

                    case 81:
                    $this->client_fqdn = $opt;
                    break;

                    default:
                    $this->unhandled[] = array("code" => $code, "opt" => bin2hex($opt));
                    break;
                }

                $cur += $len + 2;
            }
        }
    }

    public static function Decode($data) {
        return new self($data);
    }

    public static function Encode() {
    }
}

/**
 * DHCP message parser and builder class
 *
 * @package nanoserv
 * @subpackage Handlers
 */
class Message {
    const BOOTP_REQUEST = 1;
    const BOOTP_REPLY = 2;

    const HTYPE_ETHERNET = 1;

    const DHCP_DISCOVER = 1;
    const DHCP_OFFER = 2;
    const DHCP_REQUEST = 3;
    const DHCP_DECLINE = 4;
    const DHCP_ACK = 5;
    const DHCP_NAK = 6;
    const DHCP_RELEASE = 7;
    const DHCP_INFORM = 8;

    public $op;
    public $htype;
    public $hlen;
    public $hops;
    public $xid;
    public $secs;
    public $flags;
    public $ciaddr;
    public $yiaddr;
    public $siaddr;
    public $giaddr;
    public $chaddr;
    public $sname;
    public $file;
    public $options;

    public function __construct($data = NULL) {
        if (isset($data)) {
            $tmp = unpack("Cop/Chtype/Chlen/Chops/Nxid/nsecs/nflags/Nciaddr/Nyiaddr/Nsiaddr/Ngiaddr", $data);

            $this->op = $tmp["op"];
            $this->htype = $tmp["htype"];
            $this->hlen = $tmp["hlen"];
            $this->hops = $tmp["hops"];
            $this->xid = $tmp["xid"];
            $this->secs = $tmp["secs"];
            $this->flags = $tmp["flags"];
            $this->ciaddr = long2ip($tmp["ciaddr"]);
            $this->yiaddr = long2ip($tmp["yiaddr"]);
            $this->siaddr = long2ip($tmp["siaddr"]);
            $this->giaddr = long2ip($tmp["giaddr"]);
            $this->chaddr = bin2hex(substr($data, 28, 16));
            $this->sname = substr($data, 44, 64);
            $this->file = substr($data, 108, 128);

            $this->options = Options::Decode(substr($data, 236));
        } else {
            $this->options = new Options();
        }
    }

    public function Op_To_String() {
        switch ($this->op) {
            case self::BOOTP_REQUEST:	return "BOOTPREQUEST";
            case self::BOOTP_REPLY:		return "BOOTPREPLY";
            default:					return "unknown";
        }
    }

    public function Htype_To_String() {
        switch ($this->htype) {
            case self::HTYPE_ETHERNET:	return "Ethernet";
            default:					return "unknown";
        }
    }

    public function Msg_Type_To_String() {
        switch ($this->options->dhcp_msg_type) {
            case self::DHCP_DISCOVER:	return "DHCPDISCOVER";
            case self::DHCP_OFFER:		return "DHCPOFFER";
            case self::DHCP_REQUEST:	return "DHCPREQUEST";
            case self::DHCP_DECLINE:	return "DHCPDECLINE";
            case self::DHCP_ACK:		return "DHCPACK";
            case self::DHCP_NAK:		return "DHCPNAK";
            case self::DHCP_RELEASE:	return "DHCPRELEASE";
            case self::DHCP_INFORM:		return "DHCPINFORM";
            default:					return "unknown";
        }
    }

    public static function Decode($data) {
        return new self($data);
    }

    public static function Encode() {
    }
}

/**
 * DHCP protocol handler handler class
 *
 * @package nanoserv
 * @subpackage Handlers
 */
abstract class Server extends Nanoserv\DatagramHandler {
    public function on_Read($from, $data) {
        $this->on_DHCP_Message($from, Message::Decode($data));
    }

    /**
     * Event called when a DHCP message is received
     *
     * @param string  $from ip address and port of the sending side
     * @param Message $msg
     */
    abstract public function on_DHCP_Message($from, Message $msg);
}
