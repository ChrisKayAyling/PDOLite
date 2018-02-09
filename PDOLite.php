<?php


namespace PDOLite;

/**
 * DB2
 *
 * DB2 is a MDB2 Lightweight replacement implementing MySQLi's database layer.
 *
 * @package PDOLite\PDOLite
 * @Author Chris Kay-Ayling
 */
class PDOLite
{

    private $string_quoting = array('start' => "'", 'end' => "\'", 'escape' => FALSE, 'escape_pattern' => FALSE);
    private $wildcards = array('%', '_');

    public $lastQuery = NULL;

    /**
     * @var \PDO
     */
    protected $db;

    private $host = NULL;
    private $user = NULL;
    private $pass = NULL;
    private $database = NULL;
    private $portnumber = NULL;
    private $socket = NULL;

    public $connected = NULL;

    public $last_insert_id = NULL; // Last insert ID.
    public $row_count = NULL;
    public $affected_rows = NULL;

    /**
     * @var null Error Flag
     */
    public $error = NULL;
    /**
     * @var array Error Stack Information
     */
    public $errorInfo = array();

    public function __construct($Settings)
    {
        $this->host         = $Settings['DatabaseHost'];
        $this->user         = $Settings['DatabaseUser'];
        $this->pass         = $Settings['DatabasePass'];
        $this->database     = $Settings['DatabaseName'];
        $this->portnumber   = $Settings['DatabasePort'];
        $this->socket       = $Settings['DatabaseSocket'];


        try {
            if (strlen($this->socket) > 0 && $this->socket !== 'DATABASE_SOCKET') {
                $this->db = new \PDO('mysql:unix_socket=' . $this->socket . ';dbname=' . $this->database . ';charset=utf8mb4', $this->user, $this->pass);
            } else {
                $this->db = new \PDO('mysql:host=' . $this->host . ';dbname=' . $this->database . ';charset=utf8mb4', $this->user, $this->pass);
            }
            $this->connected = TRUE;
        } catch (\PDOException $e) {
            $this->connected = false;
            $this->error = $e->getMessage();
            return $e;
        }

    }


    /**
     * @param $query
     * @return array|bool
     */
    public function query($query)
    {
        $this->error = FALSE;

        $this->lastQuery = $query;

        try {
            $retVal = $this->db->query($query);

            if ($retVal == FALSE) {
                $this->error = TRUE;
                $this->errorInfo = $this->db->errorInfo();
                echo "Error in query: " . var_export($query,true) . "<br />";
                die(var_Export($this->db->errorInfo(),true));
            } else {
                $this->row_count = $retVal->rowCount();
                return $retVal->fetchAll(\PDO::FETCH_ASSOC);
            }
        } catch (\PDOException $PDOException) {
            $this->error = TRUE;
            $this->errorInfo = $PDOException;
            throw $PDOException;
        }

    }


    /**
     * @param $query
     * @return array|bool|\Exception|\PDOException
     */
    public function exec($query) {
        $this->error = FALSE;
        $this->lastQuery = $query;

        try {
            $result = $this->db->exec($query);
            if ($result === FALSE) {
                $this->error = TRUE;
                $this->errorInfo = $this->db->errorInfo();
                file_put_contents('/tmp/pdo.errors', $query, FILE_APPEND);
                file_put_contents('/tmp/pdo.errors',$this->errorInfo, FILE_APPEND);
                return FALSE;
            } else {
                $this->last_insert_id = $this->db->lastInsertId();
                $this->affected_rows = $result;
                return TRUE;
            }
        } catch (\PDOException $PDOException) {
            $this->error = TRUE;
            $this->errorInfo = $PDOException;
            return FALSE;
        }
    }


    /**
     * @param $text
     * @return string
     */
    public function quote($text)
    {
        $value = addslashes($text);
        return "'" . $value . "'";
    }

    /**
     * escapePattern() - Quotes pattern (% and _) characters in a string)
     *
     * @param   string  the input string to quote
     * @return  string  quoted string
     * @abstract  Borrowed from the MDB2 lib.
     */
    protected function escapePattern($text)
    {
        if ($this->string_quoting['escape_pattern']) {
            $text = str_replace($this->string_quoting['escape_pattern'], $this->string_quoting['escape_pattern'] . $this->string_quoting['escape_pattern'], $text);
            foreach ($this->wildcards as $wildcard) {
                $text = str_replace($wildcard, $this->string_quoting['escape_pattern'] . $wildcard, $text);
            }
        }
        return $text;
    }
}
