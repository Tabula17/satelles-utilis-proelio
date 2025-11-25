<?php

namespace Tabula17\Satelles\Utilis\Config;

use Tabula17\Satelles\Utilis\Exception\InvalidArgumentException;

class TCPServerConfig extends AbstractDescriptor
{
//$host, $port = null, $mode = null, $sock_type = null

    /*
     * SWOOLE_SOCK_TCP tcp ipv4 socket
    SWOOLE_SOCK_TCP6 tcp ipv6 socket
    SWOOLE_SOCK_UDP udp ipv4 socket
    SWOOLE_SOCK_UDP6 udp ipv6 socket
    SWOOLE_SOCK_UNIX_DGRAM unix socket dgram
    SWOOLE_SOCK_UNIX_STREAM unix socket stream
     */

    /**
     * The host address or IP of the database server.
     * @var string $host
     */
    protected(set) string $host = 'localhost' {
        set(string $host) {
            if (empty($host)) {
                throw new InvalidArgumentException('El host no puede estar vacío');
            }
            if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) ||
                filter_var($host, FILTER_VALIDATE_IP)) {
                $this->host = $host;
            } else {
                throw new InvalidArgumentException(sprintf('El formato de host "%s" es inválido', $host));
            }
        }
    }
    /**
     * The port number used to connect to the database server.
     * @var int|null $port
     */
    protected(set) ?int $port {
        set(?int $port) {
            if ($port !== null && ($port < 1 || $port > 65535)) {
                throw new InvalidArgumentException('El puerto debe estar entre 1 y 65535');
            }
            $this->port = $port;
        }
    }
    protected(set) int $mode = SWOOLE_PROCESS {
        set {
            $valid = [SWOOLE_PROCESS, SWOOLE_BASE];
            if (!in_array($value, $valid, true)) {
                $this->mode = SWOOLE_PROCESS;
            } else {
                $this->mode = $value;
            }
        }
    }
    protected(set) int $type {
        set {
            $valid = [
                SWOOLE_SOCK_TCP,
                SWOOLE_SOCK_TCP6,
                SWOOLE_SOCK_UDP,
                SWOOLE_SOCK_UDP6,
                SWOOLE_SOCK_UNIX_DGRAM,
                SWOOLE_SOCK_UNIX_STREAM
            ];
            if (!in_array($value, $valid, true)) {
                $this->type = SWOOLE_SOCK_TCP;
            } else {
                $this->type = $value;
            }
        }
    }
    /**
     * An associative array used to store configuration options -> https://wiki.swoole.com/en/#/server/setting
     * @var array $options
     */
    protected array $options = [];

}