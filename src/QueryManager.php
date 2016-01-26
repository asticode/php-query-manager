<?php
namespace Asticode\QueryManager;

use Asticode\CacheManager\Handler\HandlerInterface;

class QueryManager
{
    // Attributes
    /** @var HandlerInterface $oCacheHandler */
    private $oCacheHandler;

    // Constructor
    public function __construct(HandlerInterface $oCacheHandler)
    {
        $this->oCacheHandler = $oCacheHandler;
    }
}
