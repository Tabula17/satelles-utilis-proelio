<?php

namespace Tabula17\Satelles\Utilis\Log\Handler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\MongoDBFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Utopia\Mongo\Client;
use Utopia\Mongo\Exception;

/**
 * Handler for logging messages to a MongoDB collection.
 * This class extends AbstractProcessingHandler and implements the write() method to write log records to a MongoDB collection.
 * It provides a convenient way to integrate logging with MongoDB databases.
 *
 */
class MongoDbHandler extends AbstractProcessingHandler
{
    private Client $mongoDb;

    /**
     * Constructor method for initializing the MongoDB client and managing the specified collection.
     *
     * @param string $host The host of the MongoDB server.
     * @param int $port The port of the MongoDB server.
     * @param string $database The name of the MongoDB database.
     * @param string|null $user The username for authentication, or null if not required.
     * @param string|null $password The password for authentication, or null if not required.
     * @param string $collection The name of the collection to be used.
     * @param int $ttl The Time-To-Live (TTL) in seconds for the collection's documents, default is 0.
     * @param int|string|Level $level The logging level, default is Level::Debug.
     * @param bool $bubble Whether logging messages bubble up the stack, default is true.
     *
     * @return void
     * @throws \Exception
     * @throws Exception
     */
    public function __construct(
        string                  $host,
        int                     $port,
        string                  $database,
        ?string                 $user,
        ?string                 $password,
        private readonly string $collection,
        int                     $ttl = 0,
        int|string|Level        $level = Level::Debug,
        bool                    $bubble = true
    )
    {
        $this->mongoDb = new Client($database, $host, $port, $user, $password);
        $this->mongoDb->connect();
        $list = $this->mongoDb->listCollectionNames(["name" => $collection]);
        if (\count($list->cursor->firstBatch) === 0) {
            $this->mongoDb->createCollection($collection);
            if ($ttl > 0) {
                $this->mongoDb->createIndexes($collection, [
                    [
                        'name' => $collection . '_createdAt_1',
                        'key' => ['datetime' => 1],
                        'expireAfterSeconds' => 3600
                    ]
                ]);
            }
        }

        parent::__construct($level, $bubble);
    }

    /**
     * Writes a log record to the MongoDB collection.
     *
     * @param LogRecord $record The log record to be written to the database.
     * @return void
     * @throws Exception
     */
    protected function write(LogRecord $record): void
    {
        $this->mongoDb->insert($this->collection, $record->formatted);
    }


    /**
     * @inheritDoc
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new MongoDBFormatter;
    }

}