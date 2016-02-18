<?php
namespace Asticode\ExtendedPDO;

interface PDOInterface
{


    /**
     * @param $sStatement
     * @return \PDOStatement
     */
    public function prepare($sStatement);

}
