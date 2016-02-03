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
    private $sPrefix;

    // Constructor
    public function __construct(HandlerInterface $oCacheHandler, $sPrefix = 'query_manager:')
    {
        $this->oCacheHandler = $oCacheHandler;
        $this->sPrefix = $sPrefix;
    }

    public function fetchAllInto(
        ExtendedPdoInterface $oPDO,
        $sQueryString,
        array $aQueryValues,
        $sFetchIntoClass,
        $sFetchIntoCallable = '',
        array $aFetchIntoArgs = [],
        $iTTL = -1,
        $sKey = ''
    ) {
        // Check if query must by executed
        list($bMustExecuteQuery, $sKey, $aItems) = $this->mustExecuteQuery($sQueryString, $aQueryValues, $sKey, $iTTL);

        // Query must be executed
        if ($bMustExecuteQuery) {
            // Prepare
            $oStmt = $oPDO->prepare($sQueryString);
            if ($sFetchIntoCallable === '') {
                $oStmt->setFetchMode(PDO::FETCH_CLASS, $sFetchIntoClass);
            } else {
                $oDatabaseItem = new $sFetchIntoClass;
                $oStmt->setFetchMode(PDO::FETCH_INTO, $oDatabaseItem);
            }
            $oStmt->execute($aQueryValues);

            // Loop through results
            $aItems = [];
            while ($oRow = $oStmt->fetch()) {
                if ($sFetchIntoCallable === '') {
                    $aItems[] = $oRow;
                } else {
                    $aItems[] = call_user_func_array($sFetchIntoCallable, array_merge(
                        [$oRow],
                        $aFetchIntoArgs
                    ));
                }
            }

            // Store results in cache
            if ($iTTL >= 0) {
                $this->oCacheHandler->set($sKey, $aItems, $iTTL);
            }
        }

        // Return
        return is_array($aItems) ? $aItems : [];
    }

    public function fetchOneInto(
        ExtendedPdoInterface $oPDO,
        $sQueryString,
        array $aQueryValues,
        $sFetchIntoClass,
        $sFetchIntoCallable = '',
        array $aFetchIntoArgs = [],
        $iTTL = -1,
        $sKey = ''
    ) {
        // Get items
        $aItems = $this->fetchAllInto(
            $oPDO,
            $sQueryString,
            $aQueryValues,
            $sFetchIntoClass,
            $sFetchIntoCallable,
            $aFetchIntoArgs,
            $iTTL,
            $sKey
        );

        // Return
        return count($aItems) > 0 ? $aItems[0] : null;
    }

    private function getKey($sQueryString, array $aQueryValues, $sKey)
    {
        if ($sKey === '') {
            $sKey = md5(sprintf(
                '%s:%s',
                $sQueryString,
                serialize($aQueryValues)
            ));
        }
        return $this->sPrefix . $sKey;
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
