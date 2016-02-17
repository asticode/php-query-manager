<?php
namespace Asticode\QueryManager;

use Asticode\CacheManager\Handler\HandlerInterface;
use PDO;

class QueryManager
{
    // Attributes
    /** @var HandlerInterface $oCacheHandler */
    private $oCacheHandler;
    private $bEnableCaching;
    private $sPrefix;

    // Constructor
    public function __construct(HandlerInterface $oCacheHandler, $bEnableCaching = true, $sPrefix = 'query_manager:')
    {
        $this->oCacheHandler = $oCacheHandler;
        $this->bEnableCaching = $bEnableCaching;
        $this->sPrefix = $sPrefix;
    }

    private function fetchAll(
        $sQueryString,
        array $aQueryValues,
        $iTTL,
        $sKey,
        callable $cFetchAllClosure
    ) {
        // Check if query must by executed
        list($bMustExecuteQuery, $sKey, $aItems) = $this->mustExecuteQuery($sQueryString, $aQueryValues, $sKey, $iTTL);

        // Query must be executed
        if ($bMustExecuteQuery) {
            // Fetch all
            $aItems = call_user_func($cFetchAllClosure);

            // Store results in cache
            if ($iTTL >= 0) {
                $this->oCacheHandler->set($sKey, $aItems, $iTTL);
            }
        }

        // Return
        return is_array($aItems) ? $aItems : [];
    }

    private function fetchOne(callable $cFetchAllClosure, array $aFetchAllArgs = [])
    {
        $aItems = call_user_func_array($cFetchAllClosure, $aFetchAllArgs);
        return count($aItems) > 0 ? $aItems[0] : null;
    }

    public function fetchAllAssoc(
        PDO $oPDO,
        $sQueryString,
        array $aQueryValues,
        $iTTL = -1,
        $sKey = ''
    ) {
        // Return
        return $this->fetchAssoc(
            $oPDO,
            $sQueryString,
            $aQueryValues,
            false,
            $iTTL,
            $sKey
        );
    }

    public function fetchOneAssoc(
        PDO $oPDO,
        $sQueryString,
        array $aQueryValues,
        $iTTL = -1,
        $sKey = ''
    ) {
        return $this->fetchOne(
            [$this, 'fetchAssoc'],
            [
                $oPDO,
                $sQueryString,
                $aQueryValues,
                true,
                $iTTL,
                $sKey,
            ]
        );
    }

    private function fetchAssoc(
        PDO $oPDO,
        $sQueryString,
        array $aQueryValues,
        $bFetchOnlyFirstItem = false,
        $iTTL = -1,
        $sKey = ''
    ) {
        return $this->fetchAll(
            $sQueryString,
            $aQueryValues,
            $iTTL,
            $sKey,
            function () use ($oPDO, $sQueryString, $aQueryValues, $bFetchOnlyFirstItem) {
                // Prepare
                $oStmt = $oPDO->prepare($sQueryString);
                $oStmt->setFetchMode(PDO::FETCH_ASSOC);
                $oStmt->execute($aQueryValues);

                // Loop through results
                $aItems = [];
                while ($oRow = $oStmt->fetch()) {
                    // Get items
                    $aItems[] = $oRow;

                    // Fetch only first item
                    if ($bFetchOnlyFirstItem) {
                        break;
                    }
                }

                // Return
                return $aItems;
            }
        );
    }

    public function fetchAllClass(
        PDO $oPDO,
        $sQueryString,
        array $aQueryValues,
        $sFetchClass,
        $iTTL = -1,
        $sKey = ''
    ) {
        // Return
        return $this->fetchClass(
            $oPDO,
            $sQueryString,
            $aQueryValues,
            $sFetchClass,
            false,
            $iTTL,
            $sKey
        );
    }

    public function fetchOneClass(
        PDO $oPDO,
        $sQueryString,
        array $aQueryValues,
        $sFetchClass,
        $iTTL = -1,
        $sKey = ''
    ) {
        return $this->fetchOne(
            [$this, 'fetchClass'],
            [
                $oPDO,
                $sQueryString,
                $aQueryValues,
                $sFetchClass,
                true,
                $iTTL,
                $sKey,
            ]
        );
    }

    private function fetchClass(
        PDO $oPDO,
        $sQueryString,
        array $aQueryValues,
        $sFetchClass,
        $bFetchOnlyFirstItem = false,
        $iTTL = -1,
        $sKey = ''
    ) {
        return $this->fetchAll(
            $sQueryString,
            $aQueryValues,
            $iTTL,
            $sKey,
            function () use ($oPDO, $sQueryString, $aQueryValues, $sFetchClass, $bFetchOnlyFirstItem) {
                // Prepare
                $oStmt = $oPDO->prepare($sQueryString);
                $oStmt->setFetchMode(PDO::FETCH_CLASS, $sFetchClass);
                $oStmt->execute($aQueryValues);

                // Loop through results
                $aItems = [];
                while ($oRow = $oStmt->fetch()) {
                    // Get items
                    $aItems[] = $oRow;

                    // Fetch only first item
                    if ($bFetchOnlyFirstItem) {
                        break;
                    }
                }

                // Return
                return $aItems;
            }
        );
    }

    public function fetchAllInto(
        PDO $oPDO,
        $sQueryString,
        array $aQueryValues,
        $sFetchIntoClass,
        $sFetchIntoCallable,
        array $aFetchIntoArgs = [],
        $iTTL = -1,
        $sKey = ''
    ) {
        // Return
        return $this->fetchInto(
            $oPDO,
            $sQueryString,
            $aQueryValues,
            $sFetchIntoClass,
            $sFetchIntoCallable,
            $aFetchIntoArgs,
            false,
            $iTTL,
            $sKey
        );
    }

    public function fetchOneInto(
        PDO $oPDO,
        $sQueryString,
        array $aQueryValues,
        $sFetchIntoClass,
        $sFetchIntoCallable,
        array $aFetchIntoArgs = [],
        $iTTL = -1,
        $sKey = ''
    ) {
        return $this->fetchOne(
            [$this, 'fetchInto'],
            [
                $oPDO,
                $sQueryString,
                $aQueryValues,
                $sFetchIntoClass,
                $sFetchIntoCallable,
                $aFetchIntoArgs,
                true,
                $iTTL,
                $sKey,
            ]
        );
    }

    private function fetchInto(
        PDO $oPDO,
        $sQueryString,
        array $aQueryValues,
        $sFetchIntoClass,
        $sFetchIntoCallable,
        array $aFetchIntoArgs = [],
        $bFetchOnlyFirstItem = false,
        $iTTL = -1,
        $sKey = ''
    ) {
        return $this->fetchAll(
            $sQueryString,
            $aQueryValues,
            $iTTL,
            $sKey,
            function () use (
                $oPDO,
                $sQueryString,
                $aQueryValues,
                $sFetchIntoClass,
                $sFetchIntoCallable,
                $aFetchIntoArgs,
                $bFetchOnlyFirstItem
            ) {
                // Prepare
                $oStmt = $oPDO->prepare($sQueryString);
                $oDatabaseItem = new $sFetchIntoClass;
                $oStmt->setFetchMode(PDO::FETCH_INTO, $oDatabaseItem);
                $oStmt->execute($aQueryValues);

                // Loop through results
                $aItems = [];
                while ($oStmt->fetch()) {
                    // Get items
                    $aItems[] = call_user_func_array($sFetchIntoCallable, array_merge(
                        [$oDatabaseItem],
                        $aFetchIntoArgs
                    ));

                    // Fetch only first item
                    if ($bFetchOnlyFirstItem) {
                        break;
                    }
                }

                // Return
                return $aItems;
            }
        );
    }

    private function buildKey($sQueryString, array $aQueryValues, $sKey)
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
        if (!$this->bEnableCaching || $iTTL === -1) {
            // Return
            return [
                true,
                '',
                null,
            ];
        } else {
            // Get key
            $sKey = $this->buildKey($sQueryString, $aQueryValues, $sKey);

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

    public function delKey($sKey)
    {
        $this->oCacheHandler->del($this->buildKey('', [], $sKey));
    }
}
