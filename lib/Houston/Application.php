<?php

require_once 'Houston/DataObject.php';

/**
 * Ids for operation statuses.
 *
 * TODO: These should be defined in the application.
 */
define('HOUSTON_STATUS_UNLOCKED', 0);
define('HOUSTON_STATUS_LOCKED', 1);
define('HOUSTON_STATUS_LOAD_LOCK', 1);
define('HOUSTON_STATUS_SAVE_LOCK', 2);
define('HOUSTON_STATUS_DELETE_LOCK', 4);


/**
 * This class represents the Houston application as a whole.
 */
class Houston_Application extends Houston_DataObject {

  /**
   * The Houston Variables table.
   */
  const HOUSTON_VARIABLES_TABLE = '.houston_variables';

  /**
   * salesForceEnabled
   *
   * @var boolean
   */
  protected $salesForceEnabled = TRUE;

  /**
   * An array of Houston Application variables.
   */
  protected $variables = array();

  /**
   * This method is called by the Houston_DataObject constructor.
   *
   * @return void
   */
  public function init() {

  }

  /**
   * Get a loaded object.
   *
   * @param mixed $type
   * @param mixed $id
   * @return void
   */
  public function getLoadedObject($type, $id) {

    static $objects = array();
    if (!isset($objects[$type . '_' . $id])) {
      // TODO: This may need to load a Houston object, but that can break things in Queue.
      //$object = Houston_DataObject::factory('Houston_' . $type, array('db' => $this->db));
      $object = Houston_DataObject::factory($type, array('db' => $this->db));
      $object->load($id);
      $objects[$type . '_' . $id] = $object;
    }
    return $objects[$type . '_' . $id];
  }

  /**
   * TODO: Implement this!
   */
  public function getController($controllerName) {
  }

  /**
   * Setter function for Houston Application variables.
   *
   * @param string $name
   *   The unique name for this variable
   * @param mixed $value
   *   A value of any type to be serialized and stored.
   * @return bool
   *   True
   */
  public function variableSet($name, $value) {

    // Update our variable array that provides a cache for values in this object
    $this->variables[$name] = $value;
    $serialized_value = serialize($value);
    $table = HOUSTON_DB . self::HOUSTON_VARIABLES_TABLE;
    $sql = "INSERT INTO $table
            (name, value) VALUES ('$name', '$serialized_value')
            ON DUPLICATE KEY UPDATE value = '$serialized_value'";
    $this->db->query($sql);
    return TRUE;
  }

  /**
   * Getter function for Houston Application variables.
   * @param string $name
   *   The unique name for this variable.
   * @param string $default
   *   A default value for this variable.  If the variable has not yet been set the default is returned.
   * @return mixed
   *   The value if set or the default passed in if not.
   */
  public function variableGet($name, $default) {

    if (isset($this->variables[$name])) {
      return $this->variables[$name];
    }
    elseif ($result = $this->db->fetchRow('SELECT value FROM ' . HOUSTON_DB . self::HOUSTON_VARIABLES_TABLE . ' WHERE name = ?', array($name))) {
      if (isset($result->value)) {
        $value = $result->value;
      }
      $this->variables[$name] = unserialize($value);
      return $this->variables[$name];
    }
    else {
      return $default;
    }
  }
}


