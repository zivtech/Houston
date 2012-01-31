<?php

namespace Houston;

/**
 * Ids for operation statuses.
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
class Houston extends DataObject implements HoustonInterface {

  /**
   * The Houston Variables table.
   */
  const HOUSTON_VARIABLES_TABLE = '.houston_variables';

  /**
   * An array of Houston Application variables.
   * @deprecated
   */
  protected $variables = array();

  /** 
   * Configuration settings for this houston instance.
   */
  protected $config = NULL;

  /**
   * Application wide configured connectors.
   */
  protected $connectors = array();

  /**
   * Get a loaded object.
   *
   * @param mixed $type
   * @param mixed $id
   * @return void
   */
  public function getLoadedObject($id, $type) {

    static $objects = array();
    if (!isset($objects[$type . ':' . $id])) {
      // TODO: This may need to load a Houston object, but that can break things in Queue.
      //$object = Houston_DataObject::factory('Houston_' . $type, array('db' => $this->db));
      $object = Houston_DataObject::factory($type, array('db' => $this->db));
      $object->load($id);
      $objects[$type . ':' . $id] = $object;
    }
    return $objects[$type . ':' . $id];
  }

  /**
   * Get a connection to the specified controller.  This relies upon a configuration class
   *
   * @param string $controllerName
   * @return mixed
   */
  public function getController($controllerName) {
    static $controllers = array();
    if (!class_exists('Houston_Config')) {
      return FALSE;
    }

    if (!isset($controllers[$controllerName])) {
      // We want errors if the controller doesn't exist.
      $controller = $this->config->config['controllers'][$controllerName];
      $controllers[$controllerName] = Houston_DataObject::factory($controller['controller'], $controller['config']);
    }
    return $controllers[$controllerName];
  }

  /**
   * Get the connector type for a given controller.
   *
   * @param string $controllerName
   */
  public function getControllerType($controllerName) {
      // We want errors if the controller doesn't exist.
    $controller = $this->config->config['controllers'][$controllerName];
    return $controller['controller'];
  }

  /**
   * Get the connector configuration for a given controller.
   *
   * @param string $controllerName
   */
  public function getControllerConfig($controllerName) {
      // We want errors if the controller doesn't exist.
    $controller = $this->config->config['controllers'][$controllerName];
    return isset($controller['config']) ? $controller['config'] : array();
  }

  /**
   * Update houston data given a list of exteral objects.
   *
   * @param array $objects
   * @param string $controller
   * 2return boolean
   */
  public function updateLocalObjects($objects, $controllerName) {
    $success = TRUE;
    foreach ($objects as $externalId => $object) {
      $localObjects = $this->findLocalObjects($controllerName, $externalId, $object->type);
      if (count($localObjects)) {
        foreach ($localObjects as $localObject) {
          if ($localObject->isNew()) {
            continue;
          }
          $result = $localObject->callController('load', $controllerName);
          if ($result['status']) {
            $localObject->save($controllerName);
          }
          else {
            // If we can't successfully load from the controller, then fail.
            $success = FALSE;
          }
        }
      }
      else {
        // If we don't find or create any associations, then fail.
        $success = FALSE;
      }
    }
    return $success;
  }

  /**
   * Get a houston objects given a controller and id.
   *  The trick is that we don't know the local object type.
   *
   * @param string $controller
   * @param mixed $externalId
   * @param string $type
   * @return array
   */
  public function findLocalObjects($controller, $externalId, $type) {
    $objects = array();
    foreach ($this->config->config['classes'] as $class) {
      $object = Houston_DataObject::factory($class, array('db' => $this->db)); 
      $controllers = $object->getControllers();
      // TODO: Add in settings to allow/disallow service updates/
      if (isset($controllers[$controller])) {
        if ($controllers[$controller]->getObjectType() == $type) {
          // We make sure to add a new entry if nothing is found.
          $object->loadWithExternalId($externalId, $controller, NULL, $saveNew = TRUE);
          $objects[$object->getId()] = $object;
        }
      }
    }
    return $objects;
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
  /**
   *  START TOTALLY NEW HOUSTON2 CODE.
   */

  /**
   *
   * @param string $type
   *   The name of the object type.
   */
  public function createDataObject($type, $object = FALSE) {
    $objects = &$this->objects;
    $id = count($objects) + 1;
    if (!$object) {
      $object = new DataObject();
    }
    $objects[$type . ':' . $id] = $object;
    return $object;
  }

  public function addConnector($name, \Houston\Connector\ConnectorInterface $connector) {
    $this->connectors[$name] = $connector;
    return $this;
  }

  public function addConnectorToObject($name, \Houston\DataObjectInterface $object) {
    if (isset($this->connectors[$name])) {
      $object->addConnector($name, $this->connectors[$name]);
    }
    return $this;
  }

  public function addPrototype($name, \Houston\DataObjectInterface $object) {
    $this->prototypes[$name] = $object;
  }

  public function getDataObject($name) {
    // TODO: We should also support class based objects that are centrally registered.
    if (isset($prototypes[$name])) {
      return $prototypes[$name];
    }
    else {
      throw new Exception(sprintf('Requested prototype data object %s does not exist.', $name));
    }
  }

}


