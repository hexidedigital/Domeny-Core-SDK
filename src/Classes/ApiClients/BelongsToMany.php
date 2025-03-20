<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients;

use Hexidedigital\DomenyCoreSdk\Classes\ApiClients\Traits\SyncTrait;

/**
 * @template U
 * @extends BaseApiClient<U>
 */
class BelongsToMany extends BaseApiClient
{
    use SyncTrait;
    public const IS_ARRAY = true;

    public const METHOD_NAME = 'belongsToMany';

    protected ?string $_relationAdapterClass = null;

    protected ?array $_pivotColumns = [];

    /**
     * @return array
     */
    public function getPivotColumns(): array
    {
        return $this->_pivotColumns;
    }

    /**
     * @return string|null
     */
    public function getRelationAdapterClass(): ?string
    {
        return $this->_relationAdapterClass ?? \stdClass::class;
    }

    /**
     * @param $columns
     * @return $this
     */
    public function withPivot($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->_pivotColumns = $columns;

        return $this;
    }

    /**
     * @param string|null $relationAdapterClass
     * @return $this
     */
    public function using(?string $relationAdapterClass)
    {
        $this->_relationAdapterClass = $relationAdapterClass;

        return $this;
    }
}
