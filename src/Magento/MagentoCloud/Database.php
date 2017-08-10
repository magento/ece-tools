<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud;

/**
 * Contains logic for interacting with the database in a safe way
 */
class Database
{
    private $connection;
    private $host;
    private $user;
    private $pass;
    private $databaseName;

    public function __construct($host, $user, $pass, $databasename)
    {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->databaseName = $databasename;
    }

    private function lazyConnect()
    {
        if ($this->connection == null) {
            $this->connection = new \mysqli($this->host, $this->user, $this->pass, $this->databaseName);
            if ($this->connection->connect_errno) {
                throw new \RuntimeException("Error connecting to database.  $this->connection->connect_errno $this->connection->connect_error ");
            }
        }
    }

    /**
     * Executes database query.  We are currently using mysqli for this, so the parameters, and output are specific to that.
     * If we switch to a different database backend in the future, this will change.
     *
     * @param string $query
     * $query must be completed, finished with semicolon (;)  Use question mark (?) for parameters.
     * @param string $parameters
     * $parameters is empty, [], if you don't need them.  Otherwise, the first element in the array must be the types
     * of the rest of the array.  See http://php.net/manual/en/mysqli-stmt.bind-param.php for documentation on this.
     * @param string $resulttype
     * $resulttype should be set to null when you don't need the result of the query (ie.  UPDATE, INSERT, DELETE, etc)
     * $resulttype should be either MYSQLI_NUM, MYSQLI_ASSOC, or MYSQLI_BOTH if you do want the output (ie. SELECT).
     * http://php.net/manual/en/mysqli-stmt.get-result.php
     * @return array|null
     */
    public function executeDbQuery($query, $parameters = [], $resulttype = null)
    {
        $this->lazyConnect();
        $statement = $this->connection->prepare($query);
        if (count($parameters) >= 2) {
            $referencearray = array(); // Note: workaround because bind_param requires references instead of values.
            foreach ($parameters as $key => $value) {
                $referencearray[$key] = &$parameters[$key];
            }
            if (!call_user_func_array(array($statement, 'bind_param'), $referencearray)) {
                throw new \RuntimeException("Database bind_param error.  $statement->error ");
            }
        }
        if (!$statement->execute()) {
            throw new \RuntimeException("Database execute error.  $statement->error ");
        }
        $data = null;
        if ($resulttype == MYSQLI_NUM || $resulttype == MYSQLI_ASSOC || $resulttype == MYSQLI_BOTH) {
            $result = $statement->get_result();
            if ($result === false) {
                throw new \RuntimeException("Database execute error.  $statement->error ");
            }
            $data = $result->fetch_all($resulttype);
            $result->free();
        }
        $statement->close();
        return $data;
    }
}
