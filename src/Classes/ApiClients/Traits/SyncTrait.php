<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\ApiClients\Traits;

trait SyncTrait
{
    /**
     * @param $ids
     * @return mixed
     */
    public function syncWithoutDetaching($ids): mixed
    {
        return $this->runRelationChangeData('syncWithoutDetaching', $this->_parentItem, $this->_parentRelation, $ids, rawResult: true);
    }

    public function detach($id)
    {
        return $this->runRelationChangeData('detach', $this->_parentItem, $this->_parentRelation, $id, rawResult: true);
    }

    public function updateExistingPivot($id, $attributes = [])
    {
        return $this->runRelationChangeData(
            'updateExistingPivot',
            $this->_parentItem,
            $this->_parentRelation,
            $attributes,
            ['update_existing_pivot_id' => $id],
            rawResult: true
        );
    }
}