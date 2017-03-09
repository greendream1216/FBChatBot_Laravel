<?php namespace Common\Models;

use Jenssegers\Mongodb\Eloquent\Model;

/**
 * @property string                 $id
 * @property \MongoDB\BSON\ObjectID $_id
 * @property \Carbon\Carbon         $created_at
 * @property \Carbon\Carbon         $updated_at
 */
abstract class BaseModel extends Model
{

    protected static $unguarded = true;

    /**
     * @param $query
     * @param $columnName
     * @param $value
     *
     * @return mixed
     */
    public function scopeDate($query, $columnName, $value)
    {
        $boundaries = date_boundaries($value);

        $query->where($columnName, '>=', $boundaries[0])->where($columnName, '<', $boundaries[1]);

        return $query;
    }


    /**
     * Get a plain attribute (not a relationship).
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        if ($key === '_id') {
            return $this->getAttributeFromArray($key);
        }

        return parent::getAttributeValue($key);
    }
}