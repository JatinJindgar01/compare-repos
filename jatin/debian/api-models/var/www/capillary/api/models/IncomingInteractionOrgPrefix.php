<?php

    /**
     * Storing org-prefix to append to the SourceMapping command into 
     *  a relation called `incoming_interaction_org_prefix`(`id`, `org_id`, `prefix`)
     * Not storing this in CONF_MOBILE_TRIGGERS_ORG_PREFIX but in a table to maintain uniqueness 
     *  across organizations
     * There are individual unique constraints set on columns: `org_id` and `prefix` and 
     *  a primary constraint on column `id`
     * But before storing the prefix, a check with `sms_mapping`.`command` needs to be made to 
     *  ensure that the org-prefix does not already exist as a command for some other sms_mapping
     * 
     * @author jessy
     */

    include_once 'models/BaseModel.php';
    include_once 'exceptions/ApiIncomingInteractionOrgPrefixModelException.php';

    class IncomingInteractionOrgPrefix extends BaseApiModel {
        
        protected $id;
        protected $org_id;
        protected $prefix;

        public static function setIterableMembers() {
            $classname = get_called_class();
            $classname::$iterableMembers = array('id', 'org_id', 'prefix');
        }

        public static function findById($id) {
            
            $sql = 'SELECT * 
                    FROM `incoming_interaction_org_prefix` 
                    WHERE `id` = ' . addslashes($id);

            $rows = ShardedDbase::queryAllShards('masters', $sql, false);
            if (! empty($rows)) {
                $obj = self::fromArray(null, $rows [0]);
                return $obj;
            } else {
                throw new ApiIncomingInteractionOrgPrefixModelException(
                    ApiIncomingInteractionOrgPrefixModelException::NO_ORG_PREFIX_FOUND);
            }
        }
        
        public static function findByOrgId($orgId) {
            
            $sql = 'SELECT * 
                    FROM `incoming_interaction_org_prefix` 
                    WHERE `org_id` = ' . addslashes($orgId);
            $rows = ShardedDbase::queryAllShards('masters', $sql, false);
            if (! empty($rows)) {
                $obj = self::fromArray(null, $rows [0]);
                return $obj;
            } else {
                throw new ApiIncomingInteractionOrgPrefixModelException(
                    ApiIncomingInteractionOrgPrefixModelException::NO_ORG_PREFIX_FOUND);
            }
        }
        
        public static function findByPrefix($prefix) {
            
            $sql = "SELECT * 
                    FROM `incoming_interaction_org_prefix` 
                    WHERE `prefix` = '" . addslashes($prefix) . "'";

            $rows = ShardedDbase::queryAllShards('masters', $sql, false);
            if (! empty($rows)) {
                $obj = self::fromArray(null, $rows [0]);
                return $obj;
            } else {
                throw new ApiIncomingInteractionOrgPrefixModelException(
                    ApiIncomingInteractionOrgPrefixModelException::NO_ORG_PREFIX_FOUND);
            }
        }

        public static function findAll() {

            $sql = "SELECT `id`, `org_id`, `prefix` 
                    FROM `incoming_interaction_org_prefix` 
                    ORDER BY id DESC ";
            
            $rows = ShardedDbase::queryAllShards('masters', $sql, false);
            if($rows) {
                $orgPrefixObjs = array();
                foreach($rows as $row) 
                    $orgPrefixObjs [] = self::fromArray($row);
                
                return $ret;
            } else {
                throw new ApiIncomingInteractionOrgPrefixModelException(
                    ApiIncomingInteractionOrgPrefixModelException::NO_ORG_PREFIX_FOUND);
            }
        }

        public function insert() {
            $db = new Dbase('masters');
            
            $result = NULL;
            if ($this -> id) {

                if (isset($this -> prefix))
                    $columns['prefix'] = "'" . $db -> realEscapeString($this -> prefix) . "'";
                if (isset($this -> org_id))
                    $columns['org_id'] = $db -> realEscapeString($this -> org_id);
                
                $sql = "UPDATE incoming_interaction_org_prefix SET ";
                foreach ($columns as $key => $value) {
                    $sql .= " $key = $value, ";
                }
                $sql = substr($sql, 0, -2);
                $sql .= " WHERE id = " . $db -> realEscapeString($this -> id);

                $result = $db -> update($sql);
                if(! $result)
                    throw new ApiIncomingInteractionOrgPrefixModelException(ApiIncomingInteractionOrgPrefixModelException::ORG_PREFIX_UPDATE_FAILED); 
                else 
                    $result = $this -> id;
            } else  {
                if (isset($this -> prefix))
                    $columns['prefix'] = "'" . $db -> realEscapeString($this -> prefix) . "'";
                if (isset($this -> org_id))
                    $columns['org_id'] = $db -> realEscapeString($this -> org_id);

                $sql = "INSERT INTO incoming_interaction_org_prefix SET ";
                foreach ($columns as $key => $value) {
                    $sql .= " $key = $value, ";
                }
                $sql = substr($sql, 0, -2);
                
                $result = $db -> insert($sql);                
                if(! $result)
                    throw new ApiIncomingInteractionOrgPrefixModelException(ApiIncomingInteractionOrgPrefixModelException::ORG_PREFIX_INSERT_FAILED);
            }
            return $result;
        }
    
        public function getId() {
            return $this -> id;
        }

        public function setId($id) {
            $this -> id = $id;
        }

        public function getOrgId() {
            return $this -> org_id;
        }

        public function setOrgId($orgId) {
            $this -> org_id = $orgId;
        }

        public function getPrefix() {
            return $this -> prefix;
        }

        public function setPrefix($prefix) {
            $this -> prefix = $prefix;
        }
    }