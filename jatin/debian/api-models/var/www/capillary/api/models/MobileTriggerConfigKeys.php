<?php

include_once 'models/BaseModel.php';
include_once 'filters/MobileTriggerConfigKeysFilter.php';

class MobileTriggerConfigKeys extends BaseApiModel{
    protected $id;
    protected $name;
    protected $action_id;
    protected $default_value;
    protected $possible_values;
    protected $type;
    protected $label;

    public static function setIterableMembers()
    {
        $classname = get_called_class();
        $classname::$iterableMembers = array(
            "id",
            "name",
            "action_id",
            "default_value",
            "possible_values",
            "type",
            "label"
        );
    }

    public function getLabel()
    {
        return $this->label;
    }
    /**
     * @return mixed
     */
    public function getActionId()
    {
        return $this->action_id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->default_value;
    }

    /**
     * @return mixed
     */
    public function getPossibleValues()
    {
        return $this->possible_values;
    }

    public static function loadAll($org_id, $filters)
    {
        if(isset($filters) && !($filters instanceof MobileTriggerConfigKeysFilter))
        {
            throw new Exceptions("Invalid filter");
        }
        else
        {
            $sql = "
                SELECT
                mtck.id AS id,
                mtck.name AS name,
                mtck.action_id AS action_id,
                mtck.type AS type,
                mtck.label AS label,
                mtck.default_value AS default_value,
                mtck.possible_values AS possible_values
                FROM masters.mobile_trigger_config_keys AS mtck
                WHERE 1 = 1
            ";

            if(isset($filters))
            {
                if($filters->action_id)
                    $sql .= " AND mtck.action_id = $filters->action_id
                    ";
                if($filters->name)
                    $sql .= " AND mtck.name = '$filters->name'
                    ";
                if($filters->id)
                    $sql .= " AND mtck.id = '$filters->id'
                    ";
            }

            $db_um = new Dbase('users');
            $rows = $db_um->query($sql);

            if($rows)
            {
                foreach($rows as $row)
                {
                    $obj = MobileTriggerConfigKeys::fromArray($org_id, $row);
                    $ret[] = $obj;
                }
                return $ret;
            }
            else
            {
                throw new Exception("No config found");
            }
        }
    }

    public function loadByActionIdName($action_id, $name)
    {
        $filter = new MobileTriggerConfigKeysFilter();
        $filter->action_id = $action_id;
        $filter->name = $name;
        $ret = self::loadAll(-1, $filter);
        if(count($ret) != 0)
            return $ret[0];
        else
            return null;
    }

    public function loadByActionId($action_id)
    {
        $filter = new MobileTriggerConfigKeysFilter();
        $filter->action_id = $action_id;
        return self::loadAll(-1, $filter);
    }

    public function loadById($id)
    {
        $filter = new MobileTriggerConfigKeysFilter();
        $filter->id = $id;
        return self::loadAll(-1, $filter);
    }

}
