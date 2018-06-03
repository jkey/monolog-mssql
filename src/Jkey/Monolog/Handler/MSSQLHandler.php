<?php

namespace Jkey\Monolog\Handler;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use PDO;
use PDOStatement;

/**
 * This class is a handler for Monolog, which can be used
 * to write records in a MSSQL table
 *
 * Class MSSQLHandler
 * @package jkey\monolog-mssql
 * @license https://github.com/jkey/monolog-mssql/blob/master/LICENSE MIT
 */
class MSSQLHandler extends AbstractProcessingHandler
{
    /**
     * @var PDO pdo object of database connection
     */
    protected $pdo;

    /**
     * @var PDOStatement statement to insert a new record
     */
    private $statement;

    /**
     * @var string the table to store the logs in
     */
    private $table = 'logs';

    /**
     * @var array default fields that are stored in db
     */
    private $defaultfields = array('id', 'channel', 'level', 'message', 'time');

    /**
     * @var string[] additional fields to be stored in the database
     *
     * For each field $field, an additional context field with the name $field
     * is expected along the message, and further the database needs to have these fields
     * as the values are stored in the column name $field.
     */
    private $additionalFields = array();

    /**
     * @var array
     */
    private $fields           = array();


    /**
     * Constructor of this class, sets the PDO and calls parent constructor
     *
     * @param PDO $pdo                  PDO Connector for the database
     * @param bool $table               Table in the database to store the logs in
     * @param array $additionalFields   Additional Context Parameters to store in database
     * @param bool|int $level           Debug level which this handler should store
     * @param bool $bubble
     */
    public function __construct(
        PDO $pdo = null,
        $table,
        $additionalFields = array(),
        $level = Logger::DEBUG,
        $bubble = true
    ) {
       if (!is_null($pdo)) {
            $this->pdo = $pdo;
        }
        $this->table = $table;
        $this->additionalFields = $additionalFields;

        // merge default and additional field to one array
        $this->fields = array_merge($this->defaultfields, $this->additionalFields);

        $this->prepareStatement();

        parent::__construct($level, $bubble);
    }

    /**
     * Prepare the sql statment depending on the fields that should be written to the database
     */
    private function prepareStatement()
    {
        //Prepare statement
        $columns = "";
        $fields  = "";
        foreach ($this->fields as $key => $f) {
            if ($f == 'id') {
                continue;
            }
            if ($key == 1) {
                $columns .= "[$f]";
                $fields .= ":$f";
                continue;
            }

            $columns .= ", [$f]";
            $fields .= ", :$f";
        }
        echo 'INSERT INTO [' . $this->table . '] (' . $columns . ') VALUES (' . $fields . ')';
        $this->statement = $this->pdo->prepare(
            'INSERT INTO [' . $this->table . '] (' . $columns . ') VALUES (' . $fields . ')'
        );
    }


    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  $record[]
     * @return void
     */
    protected function write(array $record)
    {
        /*
         * merge $record['context'] and $record['extra'] as additional info of Processors
         * getting added to $record['extra']
         * @see https://github.com/Seldaek/monolog/blob/master/doc/02-handlers-formatters-processors.md
         */
        if (isset($record['extra'])) {
            $record['context'] = array_merge($record['context'], $record['extra']);
        }

        //'context' contains the array
        $contentArray = array_merge(array(
                                        'channel' => $record['channel'],
                                        'level' => $record['level'],
                                        'message' => $record['message'],
                                        'time' => $record['datetime']->format('U')
                                    ), $record['context']);

        // unset array keys that are passed put not defined to be stored, to prevent sql errors
        foreach($contentArray as $key => $context) {
            if (! in_array($key, $this->fields)) {
                unset($contentArray[$key]);
                unset($this->fields[array_search($key, $this->fields)]);
                continue;
            }

            if ($context === null) {
                unset($contentArray[$key]);
                unset($this->fields[array_search($key, $this->fields)]);
            }
        }

        //Fill content array with "null" values if not provided
        $contentArray = $contentArray + array_combine(
            $this->additionalFields,
            array_fill(0, count($this->additionalFields), null)
        );

        $this->statement->execute($contentArray);
    }
}
