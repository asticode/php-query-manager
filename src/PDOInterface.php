<?php
namespace Asticode\QueryManager;

interface PDOInterface
{

    /**
     * @param string $statement The SQL statement to prepare for execution.
     * @param array $options Set these attributes on the returned PDOStatement.
     * @return \PDOStatement
     */
    public function prepare($statement, $options = null);

}
