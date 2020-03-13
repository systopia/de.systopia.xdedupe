<?php
/*-------------------------------------------------------+
| SYSTOPIA's Extended Deduper                            |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use CRM_Xdedupe_ExtensionUtil as E;


/**
 * Embodies a XDedupe configuration
 */
class CRM_Xdedupe_Configuration
{

    protected static $main_attributes = [
        'name'         => 'String',
        'description'  => 'String',
        'is_manual'    => 'Integer',
        'is_automatic' => 'Integer',
    ];

    protected $configuration_id;
    protected $attributes;
    protected $config;

    /**
     * Constructor for an XDedupe configuration
     *
     * @param integer $configuration_id
     *      configuration ID
     * @param array $data
     *      configuration data
     */
    public function __construct($configuration_id, $data = [])
    {
        $this->configuration_id = $configuration_id;
        $this->attributes       = [];
        $this->config           = [];

        // main attributes go into $this->attributes
        foreach (self::$main_attributes as $attribute_name => $attribute_type) {
            $this->attributes[$attribute_name] = CRM_Utils_Array::value($attribute_name, $data);
        }

        // everything else goes into $this->config
        foreach ($data as $attribute_name => $value) {
            if (!isset(self::$main_attributes[$attribute_name])) {
                $this->config[$attribute_name] = $value;
            }
        }
    }

    /**
     * Delete a configuration with the given ID
     *
     * @param integer $cid
     *      configuration ID
     */
    public static function delete($cid)
    {
        $cid = (int)$cid;
        if (!empty($cid)) {
            CRM_Core_DAO::executeQuery("DELETE FROM civicrm_xdedupe_configuration WHERE id = {$cid}");
        }
    }

    /**
     * Get a list of all tasks
     *
     * @return array
     *      list of CRM_Xdedupe_Configuration objects
     */
    public static function getAll()
    {
        return self::getConfigurations('SELECT * FROM civicrm_xdedupe_configuration ORDER BY weight ASC');
    }

    /**
     * Load a list of tasks based on the data yielded by the given SQL query
     *
     * @param integer $cid
     *      configuration ID
     *
     * @return CRM_Xdedupe_Configuration|null
     *      return a configuration object
     */
    public static function getConfiguration($cid)
    {
        $cid = (int)$cid;
        if (empty($cid)) return NULL;
        $configurations = self::getConfigurations("SELECT * FROM `civicrm_xdedupe_configuration` WHERE id = {$cid}");
        return reset($configurations);
    }

    /**
     * Get a list of XDedupe configurations
     *
     * @param string $sql_query
     *      selection criteria to load from rows from civicrm_xdedupe_configuration
     *
     * @return array
     *      list of CRM_Xdedupe_Configuration objects
     */
    public static function getConfigurations($sql_query)
    {
        $configs = [];
        $configuration_search = CRM_Core_DAO::executeQuery($sql_query);
        while ($configuration_search->fetch()) {
            $data = [];
            foreach (self::$main_attributes as $attribute_name => $attribute_type) {
                $data[$attribute_name] = $configuration_search->$attribute_name;
            }
            if (isset($configuration_search->config)) {
                $config = json_decode($configuration_search->config, TRUE);
                foreach ($config as $key => $value) {
                    $data[$key] = $value;
                }
            }
            $configs[] = new CRM_Xdedupe_Configuration($configuration_search->id, $data);
        }

        return $configs;
    }

    /**
     * get a single attribute from the configuration
     */
    public function getID()
    {
        return $this->configuration_id;
    }

    /**
     * get configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * set entire configuration
     */
    public function setConfig($config)
    {
        return $this->config = $config;
    }

    /**
     * set a single attribute
     */
    public function setAttribute($attribute_name, $value, $writeTrough = FALSE)
    {
        if (isset(self::$main_attributes[$attribute_name])) {
            $this->attributes[$attribute_name] = $value;
            if ($writeTrough && $this->configuration_id) {
                                CRM_Core_DAO::executeQuery("UPDATE `civicrm_xdedupe_configuration`
                                    SET `{$attribute_name}` = %1
                                    WHERE id = {$this->configuration_id}",
                                    array(1 => array($value, self::$main_attributes[$attribute_name])));
        }
        } else {
            throw new Exception("Attribute '{$attribute_name}' unknown", 1);
        }
    }

    /**
     * Store this configuration (create or update)
     */
    public function store()
    {
        // sort out paramters
        $params = [];
        $fields = [];
        $index  = 1;
        foreach (self::$main_attributes as $attribute_name => $attribute_type) {
            if ($attribute_name == 'last_execution'
                || $attribute_name == 'last_runtime') {
                // don't overwrite timestamp
                continue;
            }
            $value = $this->getAttribute($attribute_name);
            if ($value === NULL || $value === '') {
                $fields[$attribute_name] = "NULL";
            } else {
                $fields[$attribute_name] = "%{$index}";
                $params[$index]          = array($value, $attribute_type);
                $index                   += 1;
            }
        }
        $fields['config'] = "%{$index}";
        $params[$index]   = array(json_encode($this->config), 'String');

        // generate SQL
        if ($this->configuration_id) {
            $field_assignments = [];
        foreach ($fields as $key => $value) {
            $field_assignments[] = "`{$key}` = {$value}";
        }
        $field_assignment_sql = implode(', ', $field_assignments);
        $sql                  = "UPDATE `civicrm_xdedupe_configuration` SET {$field_assignment_sql} WHERE id = {$this->configuration_id}";
    } else {
        $columns = [];
        $values  = [];
        foreach ($fields as $key => $value) {
            $columns[] = $key;
            $values[]  = $value;
        }
        $columns_sql = implode(',', $columns);
        $values_sql  = implode(',', $values);
        $sql         = "INSERT INTO `civicrm_xdedupe_configuration` ({$columns_sql}) VALUES ({$values_sql});";
    }
        error_log("STORE QUERY: " . $sql);
        error_log("STORE PARAM: " . json_encode($params));
        CRM_Core_DAO::executeQuery($sql, $params);
    }

    /**
     * get a single attribute from the configuration
     *
     * @param string $attribute_name
     *      name of the attribute to get
     *
     * @return mixed
     *      value of the attribute
     */
    public function getAttribute($attribute_name)
    {
        return CRM_Utils_Array::value($attribute_name, $this->attributes);
    }


    //  +---------------------------------+
    //  |        Execution Logic          |
    //  +---------------------------------+

    /**
     * Executes the given configuration with automatic merges
     *
     * @param array $parameters
     *      additional parameters like
     *          'limit' => only try merging this number of tuples
     *          'skip'  => skip this number of tuples
     */
    public function run($parameters = [])
    {
        // TODO
    }

}