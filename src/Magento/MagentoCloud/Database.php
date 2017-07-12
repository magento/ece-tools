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

    function __construct($host, /*$port,*/ $user, $pass, $databasename) {
        $this->connection = new mysqli($host, $user, $pass, $databasename);
        if ($this->connection->connect_errno) {
            throw new \RuntimeException("Error connecting to database.  $this->connection->connect_errno $this->connection->connect_error ", $this->connection->connect_error);
        }
    }

    function executeDbQuery($query, $parameters = [], $resulttype = null) {
        $statement = $this->connection->prepare($query);
        foreach ($parameters as $parameter) {
            if (count($parameter != 2)) {
                throw new \RuntimeException("Parameter not supplied correctly.", "parameter");
            }
            if (!$statement->bind_param($parameter[0], $parameter[1])) {
                throw new \RuntimeException("Database bind_param error.  $statement->error ", $statement->error );
            }
        }
        if (!$statement->execute() ) {
            throw new \RuntimeException("Database execute error.  $statement->error ", $statement->error );
        }
        $result = $statement->get_result();
        if ($result === FALSE) {
            throw new \RuntimeException("Database execute error.  $statement->error ", $statement->error );
        }
        $data = null;
        if ($resulttype == MYSQLI_NUM || $resulttype == MYSQLI_ASSOC || $resulttype == MYSQLI_BOTH) {
            $data = $result->fetch_all($resulttype);
        }
        $result->free();
        $statement->close();
        return $data;
    }

}
