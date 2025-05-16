<?php
include_once 'models/BaseModel.php';
include_once 'filters/MobileTriggerConfigKeyValuesFilter.php';

class MobileTriggerConfigKeyValues extends BaseApiModel{

    protected $id;
    protected $key_id;

    /**
     * @return mixed
     */
    public function getAddedBy()
    {
        return $this->added_by;
    }

    /**
     * @param mixed $added_by
     */
    public function setAddedBy($added_by)
    {
        $this->added_by = $added_by;
    }

    /**
     * @return mixed
     */
    public function getAddedOn()
    {
        return $this->added_on;
    }

    /**
     * @param mixed $added_on
     */
    public function setAddedOn($added_on)
    {
        $this->added_on = $added_on;
    }

    /**
     * @return mixed
     */
    public function getIsValid()
    {
        return $this->is_valid;
    }

    /**
     * @param mixed $is_valid
     */
    public function setIsValid($is_valid)
    {
        $this->is_valid = $is_valid;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getKeyId()
    {
        return $this->key_id;
    }

    /**
     * @param mixed $key_id
     */
    public function setKeyId($key_id)
    {
        $this->key_id = $key_id;
    }

    /**
     * @return mixed
     */
    public function getTriggerId()
    {
        return $this->trigger_id;
    }

    /**
     * @param mixed $trigger_id
     */
    public function setTriggerId($trigger_id)
    {
        $this->trigger_id = $trigger_id;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
    protected $value;
    protected $added_on;
    protected $added_by;
    protected $is_valid;
    protected $trigger_id;

    public static function setIterableMembers()
    {
        $classname = get_called_class();
        $classname::$iterableMembers = array(
            "id",
            "key_id",
            "trigger_id",
            "value",
            "added_on",
            "added_by",
            "org_id",
            "is_valid"
        );
    }

    public static function loadAll($org_id, $filters)
    {
        if(isset($filters) && !($filters instanceof MobileTriggerConfigKeyValuesFilter))
        {
            throw new Exceptions("Invalid filter");
        }
        else{
            $sql = "
                SELECT
                mtckv.id as id,
                mtckv.`value` as `value`,
                mtckv.key_id as key_id,
                mtckv.added_on as added_on,
                mtckv.added_by as added_by,
                mtckv.org_id as org_id,
                mtckv.trigger_id as trigger_id
                FROM masters.mobile_trigger_config_key_values as mtckv
                WHERE org_id = $org_id
                AND mtckv.is_valid = 1
                ";
            if($filters)
            {
                if($filters->key_id)
                    $sql .= " AND key_id = $filters->key_id
                    ";
                if($filters->trigger_id)
                    $sql .= " AND trigger_id = $filters->trigger_id
                    ";
                $db_um = new Dbase('masters');
                $rows = $db_um->query($sql);

                if($rows)
                {
                    $ret = array();
                    foreach($rows AS $row)
                    {
                        $obj = self::fromArray($org_id, $row);
                        $ret[] = $obj;
                    }
                    return $ret;
                }
                else
                {
                    throw new Exception("No value");
                }
            }
        }
    }

    public static function loadByKeyIdTriggerId($org_id, $key_id, $trigger_id)
    {
        $filters = new MobileTriggerConfigKeyValuesFilter();
        $filters->key_id = $key_id;
        $filters->trigger_id = $trigger_id;
        $ret = self::loadAll($org_id, $filters);
        if(count($ret) == 1)
            return $ret[0];
        else
            return null;
    }

    public function save()
    {
        $db = new Dbase('masters');
        if($this->id)
        {
            $sql = "UPDATE
            masters.mobile_trigger_config_key_values
            SET
            ";
            $columns["is_valid"] = $this->is_valid;
            foreach($columns as $key=>$value)
            {
                $sql .= " $key = $value, ";
            }
            $sql=substr($sql,0,-2);

            $sql .= " WHERE id = $this->id";
            $newId = $db->update($sql);

            if(! $newId)
            {
                throw new Exception("Could not update");
            }
        }
        else {
            $sql = "
            INSERT INTO
            masters.mobile_trigger_config_key_values
        ";
            if ($this->key_id)
                $columns['key_id'] = $this->key_id;
            if ($this->value !== null)
                $columns['`value`'] = "'" . $this->value . "'";
            if ($this->added_by)
                $columns["added_by"] = $this->added_by;
            if ($this->trigger_id)
                $columns['trigger_id'] = $this->trigger_id;
            if($this->current_org_id)
                $columns["org_id"] = $this->current_org_id;

            $columns["added_on"] = "NOW()";
            $columns["is_valid"] = 1;

            $sql .= "\n (" . implode(",", array_keys($columns)) . ") ";
            $sql .= "\n VALUES ";
            $sql .= "\n (" . implode(",", $columns) . ") ;";

            $sql = substr($sql, 0, -2);

            $newId = $db->insert($sql);

            if (!$newId) {
                throw new Exception("Could not insert");
            }

            return $newId;
        }
    }

} 