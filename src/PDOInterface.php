<?php
namespace Asticode\QueryManager;

interface PDOInterface
{


    /**
     * @param $sStatement
     * @return \PDOStatement
     */
    public function prepare($sStatement);

}
