<?php

namespace Tabula17\Satelles\Utilis\Config;

use Redis;

/**
 * Represents the configuration settings for a Redis client.
 *
 * This class defines configurable options for connecting to a Redis server.
 * It includes details for connection handling, authentication, and retry/backoff strategies.
 *
 * Properties:
 * - host: The Redis server hostname or IP address. Defaults to 127.0.0.1.
 * - port: The port on which the Redis server is accessible. Defaults to 6379.
 * - connectTimeout: Timeout in seconds for connecting to the Redis server.
 * - readTimeout: Timeout in seconds for reading responses from the Redis server.
 * - retryInterval: Delay in seconds before retrying a failed connection. This setting may be overridden by backoff strategies.
 * - persistent: Boolean indicating whether to use persistent connections.
 * - auth: Authentication credentials for the Redis server. Valid formats are NULL, a string password, or an array of ['user', 'pass'] or ['pass'].
 * - database: The Redis database number to use. Defaults to 0.
 * - ssl: SSL configuration options for setting up secure connections. Accepts an array of valid PHP stream options or null.
 * - backoff: Array defining the retry backoff algorithm and configuration. Supported algorithms include:
 *   - REDIS_BACKOFF_ALGORITHM_DEFAULT
 *   - REDIS_BACKOFF_ALGORITHM_CONSTANT
 *   - REDIS_BACKOFF_ALGORITHM_UNIFORM
 *   - REDIS_BACKOFF_ALGORITHM_EXPONENTIAL
 *   - REDIS_BACKOFF_ALGORITHM_FULL_JITTER
 *   - REDIS_BACKOFF_ALGORITHM_EQUAL_JITTER
 *   - REDIS_BACKOFF_ALGORITHM_DECORRELATED_JITTER
 *   The backoff configuration may also include 'base' and 'cap' values, defined in milliseconds, to set the minimum and maximum retry delays.
 */
class RedisConfig extends ConnectionConfig
{

    protected(set) float $connectTimeout = 0;
    protected(set) float $readTimeout = 0;

    // How quickly to retry a connection after we time out or it  closes.
    // Note that this setting is overridden by 'backoff' strategies.
    protected(set) float $retryInterval = 0;
    protected(set) bool $persistent = false;

    // Valid formats: NULL, ['user', 'pass'], 'pass', or ['pass']
    protected(set) array|string|null $auth {
        set {
            if (is_string($value) || (is_array($value) && count($value) <= 2)) {
                $this->auth = $value;
            }
        }
    }
    protected(set) int $database = 0;

    // See PHP stream options for valid SSL configuration settings.
    protected(set) ?array $ssl {
        set(SSLStreamConfig|array|null $value){
            if ($value instanceof SSLStreamConfig) {
                $this->ssl = $value->toArray();
            } else {
                $this->ssl = $value;
            }
        }
    }

    // Which backoff algorithm to use.  'decorrelated jitter' is
    // likely the best one for most solution, but there are many
    // to choose from:
    //     REDIS_BACKOFF_ALGORITHM_DEFAULT
    //     REDIS_BACKOFF_ALGORITHM_CONSTANT
    //     REDIS_BACKOFF_ALGORITHM_UNIFORM
    //     REDIS_BACKOFF_ALGORITHM_EXPONENTIAL
    //     REDIS_BACKOFF_ALGORITHM_FULL_JITTER
    //     REDIS_BACKOFF_ALGORITHM_EQUAL_JITTER
    //     REDIS_BACKOFF_ALGORITHM_DECORRELATED_JITTER
    // 'base', and 'cap' are in milliseconds and represent the first
    // delay redis will use when reconnecting, and the maximum delay
    // we will reach while retrying.
    protected(set) ?array $backoff {
        set {
            if (is_array($value)) {
                if (isset($value['algorithm'])) {
                    $algorithms = [
                        Redis::BACKOFF_ALGORITHM_DEFAULT,
                        Redis::BACKOFF_ALGORITHM_CONSTANT,
                        Redis::BACKOFF_ALGORITHM_UNIFORM,
                        Redis::BACKOFF_ALGORITHM_EXPONENTIAL,
                        Redis::BACKOFF_ALGORITHM_FULL_JITTER,
                        Redis::BACKOFF_ALGORITHM_EQUAL_JITTER,
                        Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER
                    ];
                    if (!in_array($value['algorithm'], $algorithms)) {
                        unset($value['algorithm']);
                    }
                }
                if(count($value)> 0) {
                    $this->backoff = $value;
                }
            }
        }
    }

}