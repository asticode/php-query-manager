<?php
namespace Asticode\QueryManager;

use Asticode\CacheManager\Handler\HandlerInterface;
use Aura\Sql\ExtendedPdoInterface;
use PDO;

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

    public function fetchInto(
        ExtendedPdoInterface $oPDO,
        $sQueryString,
        array $aQueryValues,
        $sFetchIntoClass,
        $sFetchIntoCallable,
        array $aFetchIntoArgs = [],
        $iTTL = -1,
        $sKey = ''
    ) {
        // Check if query must by executed
        list($bMustExecuteQuery, $sKey, $aItems) = $this->mustExecuteQuery($sQueryString, $aQueryValues, $sKey, $iTTL);

        // Query must be executed
        if ($bMustExecuteQuery) {
            // Prepare
            $oDatabaseItem = new $sFetchIntoClass;
            $oStmt = $oPDO->prepare($sQueryString);
            $oStmt->setFetchMode(PDO::FETCH_INTO, $oDatabaseItem);
            $oStmt->execute($aQueryValues);

            // Loop through results
            $aItems = [];
            while ($oStmt->fetch()) {
                $aItems[] = call_user_func_array($sFetchIntoCallable, array_merge(
                    [$oDatabaseItem],
                    $aFetchIntoArgs
                ));
            }

            // Store results in cache
            if ($iTTL >= 0) {
                $this->oCacheHandler->set($sKey, $aItems, $iTTL);
            }
        }

        // Return
        return $aItems;
    }

    private function getKey($sQueryString, array $aQueryValues, $sKey)
    {
        if ($sKey !== '') {
            return $sKey;
        }
        return md5(sprintf(
            '%s:%s',
            $sQueryString,
            serialize($aQueryValues)
        ));
    }

    private function mustExecuteQuery($sQueryString, array $aQueryValues, $sKey, $iTTL)
    {
        if ($iTTL === -1) {
            // Return
            return [
                true,
                '',
                null,
            ];
        } else {
            // Get key
            $sKey = $this->getKey($sQueryString, $aQueryValues, $sKey);

            // Check cache
            $aItems = $this->oCacheHandler->get($sKey);

            // Return
            return [
                is_null($aItems),
                $sKey,
                $aItems,
            ];
        }
    }
}
