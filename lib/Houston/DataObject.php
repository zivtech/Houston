<?php

/**
 * This object provides shared functionality
 */

abstract class Houston_DataObject {

  /**
   * db
   *
   * @var Zend Db object
   */
  protected $db = FALSE;

  /**
   * cache
   *
   * @var Zend Cache object
   */
  protected $cache = FALSE;

  /**
   * log
   *
   * @var Zend Log object
   */
  protected $log = FALSE;

  /**
   * Base table for this object.
   */
  protected $baseTable = '';

  /**
   * The Houston applicaiton object type (should always be hardcoded).
   */
  protected $objectType = 'DataObject';

  /**
   * The local id for this object.
   */
  protected $id = FALSE;

  /**
   * A logical delete flag.
   */
  protected $deleted = FALSE;

  /**
   * A flag to specify whether files are enabled for this object.
   */
  protected $filesEnabled = FALSE;

  /**
   * Whether or not to save children when saving.
   */
  protected $saveChildren = TRUE;

  /**
   * An array of File objects.
   */
  protected $files = array();

  /**
   * Whether or not this object has just been inserted int the db.
   *  TODO: Think this through
   */
  protected $new = FALSE;

  /**
   * fieldMap
   *
   * An associative array of the fields of this object
   * with a mapping between houston, human readable and db field names.
   *
   * This is used as the basis for field maps in controllers.
   */
  protected $fieldMap = array();

  /**
   * Whether this ojbect can be a child of another object.
   */
  protected $childEnabled = FALSE;

  /**
   * A description of the child objects that can be attached
   * to this object.
   */
  protected $childObjectInfo = array();

  /**
   * An multi-dimensional associative array of child objects
   * belonging to this object (keyed by child type name).
   */
  protected $childObjects = array();

  /**
   * Info about parent object.
   * This may be passed in by the parent on load.
   * It should contain 'type' and 'id' for the queue.
   * TODO: We currently only support one parent.
   */
  protected $parentInfo = NULL;

  /**
   * An array of loaded controller ojbects.
   */
  protected $controllers = array();

  /**
   * An array providing the information
   */
  protected $controllerConfig = array();

  /**
   * An array of operation locking ids
   */
  protected $statusOperationIds = array(
    // When we can't save, we lock loading.
    'load' => array(
      'operation' => HOUSTON_STATUS_LOAD_LOCK,
      // TODO: This can't lock saving, if we don't have an external id.
      'locks' => HOUSTON_STATUS_SAVE_LOCK,
    ),
    // When we can't load, we lock saving.
    'save' => array(
      'operation' => HOUSTON_STATUS_SAVE_LOCK,
      'locks' => HOUSTON_STATUS_LOAD_LOCK,
    ),
    'delete' => array(
      'operation' => HOUSTON_STATUS_DELETE_LOCK,
      'locks' => 0,//HOUSTON_STATUS_LOAD_LOCK + HOUSTON_STATUS_SAVE_LOCK,
    ),
  );

  /**
   * setLog
   *
   * @param Zend_Log $log
   * @return void
   */
  public function setLog(Zend_Log $log) {

    $this->log = $log;
  }

  /**
   * setCache
   *
   * @param Zend_Cache $cache
   * @return void
   */
  public function setCache(Zend_Cache $cache) {

    $this->cache = $cache;
  }

  /**
   * setDb
   *
   * @param Zend_Db_Adapter_Abstract $db
   * @return void
   */
  public function setDb(Zend_Db_Adapter_Abstract $db) {

    $this->db = $db;
  }

  /**
   * __construct
   *
   * @param array $conf
   * @return void
   */
  protected final function __construct(array $conf = NULL) {

    if ($conf) {
      $this->processConfig($conf);
    }

    if (method_exists($this, 'init')) {
      $this->init($conf);
    }
  }

  /**
   * processConfig
   *
   * @param array $conf
   * @return void
   */
  private final function processConfig(array $conf) {

    if (isset($conf['db'])) {
      $this->setDb($conf['db']);
    }
    else if (isset($conf['db_registry_key'])) {
      $this->setDb(Zend_Registry::get($conf['db_registry_key']));
    }

    if (isset($conf['cache_registry_key'])) {
      $this->setCache(Zend_Registry::get($conf['cache_registry_key']));
    }

    if (isset($conf['log_registry_key'])) {
      $this->setLog(Zend_Registry::get($conf['log_registry_key']));
    }
  }

  /**
   * factory
   *
   * @param mixed $className
   * @param array $config
   * @return void
   */
  public final static function factory($className, array $conf = NULL) {

    $classFile = str_replace('_', '/', $className) . '.php';
    require_once $classFile;
    return new $className($conf);
  }

  /**
   * Get all data from this object in a serializable format.
   *
   * @return StdClass
   */
  public function getData($controller = NULL) {

    $data = new stdClass;
    foreach($this->getFieldMap() as $name => $field) {
      $data->$name = $this->$name;
    }
    if (!is_null($controller)) {
      $output = new stdClass;
      $controllerFieldMap = $this->getControllerFieldmap($controller);
      foreach ($data as $name => $value) {
        if (isset($controllerFieldMap[$name])) {
          $output->$controllerFieldMap[$name] = $value;
        }
      }
    }
    else {
      $output = $data;
    }
    return $output;
  }

  /**
   *
   * @param $data
   *   (mixed) The data to be translated
   * @param $sourceController
   *   (string) The name of the controller the data is coming from.
   * @param $destController
   *   (string) The name of the controller the data should be converted to.
   * @return
   *   (stdClass) The converted data.
   */
  public function translateData($sourceController = '', $destController = '', $data) {
    $output = new stdClass;
    if (!count($data)) {
      return $output;
    }

    $fieldMap = $this->getFieldMap();
    // Get the source fieldmap (keyed by local fields with values of source fields, then flip it)
    $sourceFieldMap = array_flip($this->getControllerFieldmap($sourceController));
    // Get the destination fieldmap (keyed by local fields with values of source fields)
    $destFieldMap = $this->getControllerFieldmap($destController);
    // Generate a simple translation array with from_name => to_name.
    $translation = array();
    foreach ($sourceFieldMap as $name => $value) {
      if (isset($destFieldMap[$value])) {
        $translation[$name]['controllerField'] = $destFieldMap[$value];
        $translation[$name]['localField'] = $value;
      }
    }
    foreach ($data as $name => $value) {
      if (isset($translation[$name])) {
        if (isset($fieldMap[$translation[$name]['localField']]['reference'])) {
          // If this is a reference field, then we need to map the data
          // Load referenced object with source value, then lookup destination value
          // TODO: Cache this data or object?
          $fieldData = $fieldMap[$translation[$name]['localField']];
          $object = Houston_DataObject::factory($fieldData['reference']['objectType'], array('db' => $this->db));
          if ($object->loadWithExternalId($value, $sourceController)) {
            $objectData = $object->getData();
            $value = $objectData->{$fieldData['reference'][$destController]};
          }
          else {
            $value = FALSE;
          }
        }

        // Data may or may not need to be altered for translation.
        if (isset($fieldMap[$translation[$name]['localField']][$destController]['data alter'])) {
          $output->{$translation[$name]['controllerField']} = $this->{$fieldMap[$translation[$name]['localField']][$destController]['data alter']}('transmit', $value);
        }
        else if (isset($fieldMap[$translation[$name]['localField']][$sourceController]['data alter'])) {
          $output->{$translation[$name]['controllerField']} = $this->{$fieldMap[$translation[$name]['localField']][$sourceController]['data alter']}('retrieve', $value);
        }
        else {
          $output->{$translation[$name]['controllerField']} = $value;
        }
      }
    }
    return $output;
  }

  /**
   * Try to log a debug message.
   *
   * @param mixed $message
   */
  public static function debugLog($message) {
    print_r($message);
    if ($this->log) {
      try {
        Zend_Registry::get('debug_log')->log($message, Zend_Log::INFO);
      }
      catch (Exception $e) { }
    }
  }

  /**
   * DPD - Debug Print Die
   *
   * This debugging function lets you print_r the
   * contents of an object from the inside.
   *
   * @param mixed $item
   *   (optional) The item to to be print_red.  Can
   */
  public function dPD($item = NULL) {
    if (!$item) {
      $item = $this;
    }
    $prefix = '<html><body><pre>';
    $suffix .= '</pre></body></html>';
    die($prefix . print_r($item, TRUE) . $suffix);
  }

 /**
  * This method is called by the Zivtech_DataObject constructor.
  *
  * @return void
  */
  public function init() {

    $this->getControllers();
    $this->baseTable = HOUSTON_DB . $this->baseTable;
  }

  /**
   * Perform initialization tasks on controllers.
   */
  public function getControllers() {

    // If we have already have populated the
    // controllers for this object, return them.
    if (count($this->controllers)) {
      return $this->controllers;
    }
    // Initialize all of the controllers.
    else {
      // Sort the controllers by their weight.
      foreach ($this->controllerConfig as $key => $row) {
        $tmp[$key]  = $row['weight'];
      }
      array_multisort($tmp, SORT_ASC, $this->controllerConfig);

      // Initiallize each controller.
      foreach ($this->controllerConfig as $controller => $controllerData) {
        if ($controllerData['enabled']) {
          // Load controller code and instantiate the controller.
          $className = $controllerData['controller'];
          $globalConfig = Zend_Registry::get('Houston_Application')->getControllerConfig($controller);
          // Use the global configuration, but allow overrides.
          $config = array_merge($globalConfig, $controllerData['config']);
          // is this right?
          $config['db'] = $this->db;
          $config['fieldMap'] = $this->getControllerFieldmap($controller, $fullFieldData = TRUE);
          $this->controllers[$controller] = Houston_DataObject::factory($className, $config);
        }
      }
    }
    return $this->controllers;
  }

  /**
   * Run a controller operation on any applicable controllers.
   *
   * @param Operation
   *   (string) The operation being performed.
   */
  public function callControllers($operation, $callerController) {
    // We load in the opposite order than we save or delete.
    $controllerConfig = $operation == 'load' ? array_reverse($this->controllerConfig) : $this->controllerConfig;
    // Trigger controllers
    foreach ($controllerConfig as $controller => $controllerData) {
      // Do not update the controller that initiated the operation.
      if ($controller == $callerController) {
        continue;
      }
      // If we are locked from this operation, don't do it.
      // TODO: The whole status thing may be overkill - we may just want to lock loading.
      if ($this->getDataStatus($operation, $controller)) {
        continue;
      }
      if (in_array($operation, $controllerData['operations'])) {
        // Build the data to be handed to the controller.
        // Foreach attribute of this object, if the field name
        // corresponds to a field in a given controller, populate
        // the data object with that attribute.
        $data = $this->translateData('local', $controller, $this);
        $result = $this->controllers[$controller]->$operation($data, $this->getFieldmap());
        // If result status is TRUE the operation succeeded, merge any data back into our op.
        if ($result['status']) {
          $data = $this->translateData($controller, 'local', $result['data']);
          $this->setData($data);
        }
        else {
          // An error has occured, do something!
          // If we are saving, add it to the queue
          // If we fail loading, then we don't need to lock the others, just lock saving.
          // Adding to the queue without a houston id is a problem.  If used withing the
          // flow of the application, there should already be one here.
          $this->addToQueue($operation, $controller);
        }
        // TODO: The status only needs to be set on certain operations (ie save)
        $this->setDataStatus($operation, $controller, !$result['status']);
        $this->saveToHouston();
      }
    }
  }

  /**
   * Run a controller operation on a specific controller.
   *
   * @param $operation
   *  (string) The operation being performed.
   * @param $controller
   *  (string) The controller to perform the operation.
   * @param $queue
   *  (boolean) Whether to queue the item or not.  (Used by the queue)
   * @return $result
   *  (array) Data result of the operation.
   */
  public function callController($operation, $controller, $queue = TRUE) {
    $result = array();
    $data = $this->translateData('local', $controller, $this);
    if (isset($this->controllers[$controller])) {
      // If we are locked from this operation, don't do it.
      // TODO: The whole status thing may be overkill - we may just want to lock loading.
      if ($this->getDataStatus($operation, $controller)) {
        return $result;
      }
      $result = $this->controllers[$controller]->$operation($data, $this->getFieldmap());
      if ($result['status']) {
        $result_data = isset($result['data']) ? $result['data'] : NULL;
        $data = $this->translateData($controller, 'local', $result_data);
        $this->setData($data);
      }
      else {
        // If we are saving, add it to the queue
        // The Queue needs:
        //   1.  The houston id
        //   2.  The object type
        //   3.  The blocker id and type (if exists)
        //   4.  The operation
        //   5.  The controller name
        // An error has occured, do something!
        // If we are saving, add it to the queue
        // TODO: If we are loading, do we lock the rest, or just add to the queue. or nothing?
        // Adding to the queue without a houston id is a problem.  If used within the
        // flow of the application, there should already be one here.
        if ($queue) {
          $this->addToQueue($operation, $controller);
        }
      }
      $this->setDataStatus($operation, $controller, !$result['status']);
      $this->saveToHouston();
    }
    return $result;
  }

  /**
   * Get the Field Mapping for a particular controller.
   *
   * @param string $controller
   *   The name of the controller for which to get the fieldmap.
   *
   * @return array
   *   An associative array keyed by the name of the field for that controller
   *   with values for the names of attributes on this object.
   */
  public function getControllerFieldmap($controller, $fullFieldData = FALSE) {

    $fieldMap = $this->getFieldMap();
    $controllerFieldMap = array();
    foreach ($fieldMap as $fieldName => $fieldData) {
      if (isset($fieldData[$controller])) {
        $controllerFieldMap[$fieldName] = $fullFieldData ? $fieldData[$controller] : $fieldData[$controller]['field'];
      }
    }
    return $controllerFieldMap;
  }

  /**
   * Save this object to all controllers.
   *
   * @param $callerController
   *   (string) The name of the controller calling this method.
   *   We don't want to save there, because it has already been updated.
   */
  public function save($callerController = FALSE, $saveChildren = NULL) {
  
    if (!is_null($saveChildren)) {
      $this->saveChildren = (boolean) $saveChildren;
    }
    $result = $this->saveToHouston($callerController);
    $this->callControllers('save', $callerController);
  }

  /**
   * Save this object to Houston's database
   *
   * @return array
   */
  public function saveToHouston($callerController = FALSE) {

    $data = array();
    $fieldmap = $this->getFieldMap();
    foreach ($fieldmap as $name => $field) {
      if (isset($field['db'])) {
        $data[$field['db']['field']] = $this->$name;
      }
    }
    unset($data['id']);
    $table = $this->baseTable;
    if (is_numeric($this->id)) {
      $sql = "id = $this->id";
      $result = $this->db->update($table, $data, $sql);
    }
    else {
      $result = $this->db->insert($table, $data);
      $this->id = $this->db->lastInsertId();
      $this->new = TRUE;
    }
    if ($this->saveChildren) {
      $this->saveChildren($callerController);
    }
    return $result;
  }

  /**
   * Load an object from a controller.
   *   By default, it will load from the local db.
   *
   * @return object
   */
  public function load($id = NULL, $controller = NULL) {

    if (!is_null($id)) {
      $this->id = $id;
    }
    if ($this->id) {
      if (!$this->loadWithHoustonId($this->id)) {
        return FALSE;
      }
      if (!is_null($controller)) {
        if ($controller == 'all') {
          $this->callControllers('load');
        }
        else {
          $this->callController('load', $controller);
        }
      }
    }

    return $this;
  }

  /**
   * Load an object using the Houston id.
   *
   * @return object
   */
  protected function loadWithHoustonId($id) {
    $this->loadFromHouston($id);
    return $this;
  }

  /**
   * Load up the object using an external id (from one of the controllers).
   *
   * @param $externalId
   *   (string) The id to use for loading.
   * @param $controller
   *   (string) The controller from which the id belongs.
   * @param $loadingController
   *   (string) The controller from which to load.
   * @return mixed
   */
  public function loadWithExternalId($externalId, $controller, $loadingController = NULL, $saveNew = FALSE) {
    if ($field = $this->getControllerIdField($controller)) {
      $fieldMap = $this->getFieldMap();
      if (isset($fieldMap[$field]['db']['field'])) {
        $this->$field = $externalId;
        if ($id = $this->getHoustonIdFromExternalId($externalId, $fieldMap[$field]['db']['field'])) {
          // TODO: It would be nice to be able to load from the static cache from the Application.
          return $this->load($id, $loadingController);
        }
        else if ($saveNew) {
          // If we don't have this locally, then this will try to to grab it from the designated controller
          //  and add the data to houston.
          $result = $this->callController('load', $controller);
          if ($result['status']) {
            $this->save($controller);
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Given an external id, get the Houston Id.
   *
   * @param $externalId
   *   (string) The id to use for loading.
   * @param $field
   *   (string) The local field in which the id is stored.
   */
  public function getHoustonIdFromExternalId($externalId, $field) {
    $sql = "SELECT id FROM $this->baseTable WHERE $field = ? AND deleted = 0";
    return $this->db->fetchOne($sql, $externalId);
  }

  /**
   * Given a controller, find it's local id field.
   *
   * @param $controller
   *   (string) The controller for which to find the id field.
   * @return mixed
   */
  public function getControllerIdField($controller) {
    foreach ($this->getFieldMap() as $field => $info) {
      if (isset($info['id']) && $info['id'] == $controller) {
        return $field;
      }
    }
    return FALSE;
  }

  /**
   * Load the children that belong to this object.
   */
  public function loadChildren($type = NULL, $mode = FALSE) {
    // If mode is not specified and mode is set, load only the children
    // with the corresponding mode.
    if (!$mode && $this->mode) {
      $mode = $this->mode;
    }
    $children = $this->getChildObjectList();
    if (!is_null($type) && isset($children[$type])) {
      $this->loadChildType($type, $mode);
    }
    else {
      foreach ($children as $name => $childData) {
        $this->loadChildType($name, $mode);
      }
    }
  }

  /**
   * Load all children of a specific type.
   *  Always use loadChildren($type) instead
   *  of loadChildType.
   *
   * @param $type - the type of child to load.
   * @param $mode - the mode of the child to load.
   */
  protected function loadChildType($type, $mode) {
    $children = $this->getChildObjectList();
    if (!isset($children[$type])) {
      return FALSE;
    }

    $childData = $children[$type];
    if (!is_array($this->$type)) {
      $child = Houston_DataObject::factory($childData['object'], array('db' => $this->db));
      $fieldMap = $child->getFieldMap();
      $sql = "SELECT id FROM " . HOUSTON_DB . $childData['table'] . "
          WHERE " . $fieldMap[$childData['reference field']]['db']['field'] . "  = ?";
      $arguments = array($this->getId());
      if ($mode) {
        $sql .= " AND " . $fieldMap[$childData['mode field']]['db']['field'] . " = ?";
        $arguments[] = $this->mode;
      }
      $results = $this->db->fetchAll($sql, $arguments);
      if (is_array($results) && count($results)) {
        foreach ($results as $result) {
          // TODO: getLoadedObject here
          $child = Houston_DataObject::factory($childData['object'], array('db' => $this->db));
          $child->load($result->id);
          $child->parentInfo = array(
            'type' => $this->objectType,
            'id' => $this->getId(),
            'data' => $this->getData(),
          );
          $this->{$type}[$child->getUniqueName()] = $child;
        }
      }
    }
  }

  /**
   * Get all of the children of an object of a given type.
   */
  public function getChildren($childType) {
    if (is_array($this->$childType)) {
      return $this->$childType;
    }
    elseif (array_key_exists($childType, $this->getChildObjectList())) {
      $this->loadChildren($childType);
      return $this->$childType;
    }
    return FALSE;
  }

  /**
   * Save all loaded children associated with this object.
   */
  public function saveChildren($callerController) {

    $children = $this->getChildObjectList();
    if (count($children) && is_array($children)) {
      foreach ($children as $name => $child_data) {
        if (is_array($this->$name) && count($this->$name)) {
          foreach ($this->$name as $child) {
            $child->save($callerController);
          }
        }
      }
    }
  }

  /**
   * Add a child to this object.
   */
  public function addChild($child_type, $child) {

    $children = $this->getChildObjectList();
    if ($id = $this->getId()) {
      $child->{$children[$child_type]['reference field']} = $id;
    }
    if (isset($children[$child_type]['mode field'])) {
      if ($mode = $this->getMode()) {
        $child->{$children[$child_type]['mode field']} = $mode;
      }
    }
    $uniqueFields = $child->getUniqueFields();
    if (!empty($uniqueFields)) {
      $sql = "SELECT id
              FROM " . $child->getBaseTable() . "
              WHERE mode = ?";
      $arguments = array($this->mode);
      foreach ($uniqueFields as $name => $value) {
        $sql .= " AND $name = ?";
        $arguments[] = $value;
      }
      if ($result = $this->db->fetchRow($sql, $arguments)) {
        $child->setHoustonId($result->id);
      }
    }
    $this->{$child_type}[] = $child;
  }

  /**
   * Get an associative array of the information about the children this object knows about..
   */
  public function getChildObjectList() {
    if (isset($this->childObjectInfo) && is_array($this->childObjectInfo)) {
      return $this->childObjectInfo;
    }
    return FALSE;
  }

  /**
   * Load the object from the Houston Application database.
   *
   * @var int $id
   *
   * @return
   *   this object
   */
  public function loadFromHouston($id, $mode = NULL) {

    if (!$id) {
      return $this;
    }
    $table = $this->baseTable;
    $sql = "SELECT *
            FROM $table
            WHERE id = ?";
    $arguments = array($id);
    if (isset($this->mode) && $this->mode) {
      $sql .= " AND mode = ?";
      $arguments[] = $mode;
    }

    $results = $this->db->fetchRow($sql, $arguments);
    $this->id = $id;

    $fieldmap = $this->getFieldMap();
    foreach ($fieldmap as $name => $field) {
      if (isset($field['db'])) {
        $this->$name = $results->{$field['db']['field']};
      }
    }

    return $this;
  }

  /**
   * Get the applicable files from the database.
   *
   * TODO: generalize this so that it works with stuff other than the funding request.
   */
  public function getFiles() {

    // If this object does not have files enabled, return FALSE.
    if (!$this->filesEnabled) {
      return FALSE;
    }

    $sql = "SELECT id FROM " . HOUSTON_DB . ".houston_legacy_files
        WHERE referenced_object_local_id = ?
        AND  referenced_object_local_type= ?
        AND referenced_object_mode = ?";
    $results = $this->db->fetchAll($sql, array($this->id, $this->getObjectType(), $this->mode));
    $this->files = array();
    if (count($results) && is_array($results)) {
      foreach ($results as $result) {
        $file = Zivtech_DataObject::factory('Houston_File', array('db' => $this->db));
        $file->load($result->id);
        $this->files[] = $file;
      }
    }
    if ($this->salesforceId != '' && !$this->locked) {
      $results = array();
      $soql = "SELECT Id
            FROM Attachment__c
            WHERE funding_request__c='" . $this->salesforceId . "'";
      $result = $this->salesForceClient->runSoqlQuery($soql);
      if (isset($result->records)) {
        $results = $result->records;
      }
      // TODO: This needs to be revisited before calling it done.
      if (count($results)) {
        foreach ($results as $result) {
          $fileAlreadLoaded = FALSE;
          foreach ($this->files as $file) {
            if ($file->getSalesforceId() == $result->Id) {
              $file->updateFromSalesforce();
              $fileAlreadLoaded = TRUE;
              break;
            }
          }
          if (!$fileAlreadLoaded) {
            $file = Zivtech_DataObject::factory('Houston_File', array('db' => $this->db));
            $data = new stdClass;
            $data->referencedObjectType = 'FundingRequest';
            $data->referencedObjectMode = $this->getMode();
            $data->referencedObjectHoustonId = $this->getId();
            $file->setData($data);
            $file->updateFromSalesforce($result->Id);
            $file->save();
            $this->files[] = $file;
          }
        }
      }
    }
    return $this->files;
  }

  /**
   * Save all of the files to the database.
   */
  public function saveFiles() {

    if (!$filesEnabled) {
      return FALSE;
    }
  }

  /**
   * Update files information from Salesforce.
   */
  public function updateFilesFromSalesforce() {

    if (!$filesEnabled) {
      return FALSE;
    }
  }


  /**
   * Receives the results from Salesforce
   * and uses the keymap to populate the object.
   */
  public function populateFromSalesforceResult(stdClass $result) {

    $fields = $this->getFieldMap();
    foreach($fields as $key => $value) {
      if (property_exists($result, $key)) {
        $fieldName = $fields[$key]['field'];
        $this->$fieldName = $result->$key;
      }
    }
  }

  /**
   * Populate the object from an array of information
   *
   * @param $input stdClass
   *   An object with properties named for the
   *   attributes on this data object.
   *
   * @param $controller string
   *
   */
  public function setData(stdClass $input, $controller = FALSE) {
    $controllers = $this->getControllers();
    $fieldMap = $this->getFieldMap();
    if ($controller == FALSE) {
      $data = $input;
      foreach ($data as $field => $value) {
        if (isset($fieldMap[$field])) {
          $this->$field = $value;
        }
      }
    }
    else if (isset($controllers[$controller])) {
      // Map data is controller specific, where as translate data is for passing
      // arrays of data as related to the fieldmap.
      $map = $this->controllers[$controller]->mapData($input);
      $map = $this->translateData($controller, 'local', $map);
      foreach ($map as $field => $value) {
        $this->$field = $value;
      }
    }
    else {
      throw new Exception("Nonexistant controller '$controller' requested.");
    }
  }

  /**
   * Return the fieldmap for this object.
   */
  protected function getFieldMap() {

   // Infer the 'local' fieldmap from the top level
   // field names.
   foreach($this->fieldMap as $name => $value) {
     $this->fieldMap[$name]['local']['field'] = $name;
   }
   return $this->fieldMap;
  }

  /**
   * Return the mode this object is in.
   */
  public function getMode() {

    return $this->mode;
  }

  /**
   * Set the mode for this object.
   */
  public function setMode($mode) {

    $this->mode = $mode;
  }

  /**
   * Get the title and value from this object to be displayed to the user.
   */
  public function getHumanReadableData() {
    $fieldmap = $this->getFieldMap();
    $humanData = array();
    foreach ($fieldmap as $name => $field) {
      if ($field['visible']) {
        $humanData[$name] = array(
          'title' => $field['title'],
          'value' => $this->$name,
        );
      }
    }
    return $humanData;
  }

  /**
   * Determines whether a field is required by salesforce for saving
   *
   * @param string $fieldName
   */
  public function fieldIsRequired($fieldName) {

    $fields = $this->getFieldMap();
    if (isset($fields[$fieldName])) {
      if (isset($fields[$fieldName]['required'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get a list of the salesforceFields for this object.
   *
   * @return
   *   A linear array of the salesforceFields for this object.
   */
  public function getSalesforceFields() {
    $salesforceFields = array();
    foreach ($this->getFieldMap() as $field) {
      if (isset($field['salesforceField'])) {
        $salesforceFields[] = $field['salesforceField'];
      }
    }
    return $salesforceFields;
  }


  /**
   * Given a field name, get the field title
   *
   * @param string $fieldName
   */
  public function getFieldTitle($fieldName) {

    $fields = $this->getFieldMap();
    if (isset($fields[$fieldName]) && isset($fields[$fieldName]['title'])) {
      return $fields[$fieldName]['title'];
    }
    return FALSE;
  }

  /**
   * Given a field name, get the field db column
   *
   * @param string $fieldName
   */
  public function getFieldDb($fieldName) {

    $fields = $this->getFieldMap();
    if (isset($fields[$fieldName]) && isset($fields[$fieldName]['db'])) {
      return $fields[$fieldName]['db']['field'];
    }
    return FALSE;
  }


  /**
   * Check whether a given user has access to this object.
   *
   * @param $uid
   *   (int) The uid of the user in question.
   */
  public function checkUserAccess($uid) {

    return FALSE;
  }

  /**
   * Get an object's object type.
   */
  public function getObjectType() {

    return $this->objectType;
  }

  /**
   * Get's this object's Houston Id.
   *
   * @return
   *   this object's Houston Id.
   */
  public function getHoustonId() {
    return $this->id;
  }

  /**
   *
   */
  public function getId($controller = NULL) {
    if (is_null($controller)) {
      return $this->getHoustonId();
    }
    $field = $this->getControllerIdField($controller);
    if (isset($this->{$field})) {
      return $this->{$field};
    }
    return FALSE;
  }

  /**
   *
   */
  public function setParentId($refField, $id) {
    if (!$this->childEnabled) {
      return FALSE;
    }
    $this->$refField = $id;
  }

  public function getBaseTable() {
    return $this->baseTable;
  }

  public function getSalesforceIdFromHoustonId($id) {
    return FALSE;
  }

  public function getSalesforceStatus() {
    return FALSE;
  }

  public function getUniqueFields() {
    $uniques = array();
    foreach ($this->getFieldMap() as $name => $field) {
      if (isset($field['unique']) && $field['unique']) {
        if (!is_null($this->$name)) {
          $uniques[$field['db']['field']] = $this->$name;
        }
      }
    }
    return $uniques;
  }

  public function setHoustonId($id) {
    $this->id = $id;
  }

  /**
   * Save the status of whether this info has been sent to salesforce
   *
   * @var boolean $status
   * @return void
   */
  public function saveSalesforceStatus($status) {

    $this->salesforceStatus = (boolean) $status;
    if (isset($this->id) && is_numeric($this->id)) {
      $data = array(
        'salesforce_status' => $this->salesforceStatus,
      );
      $sql = "id = $this->id";
      $this->db->update($this->baseTable, $data, $sql);
    }
  }

  /**
   * Save the id from salesforce.
   *
   * @var boolean $status
   * @return void
   */
  public function saveSalesforceId($salesforceId) {

    $this->salesforceId = $salesforceId;
    if (isset($this->id) && is_numeric($this->id) && $salesforceId) {
      $data = array(
        'salesforce_id' => $this->salesforceId,
      );
      $sql = "id = $this->id";
      $this->db->update($this->baseTable, $data, $sql);
    }
  }

  /**
   * Based on the unique fields for this object (aside from ID)
   * build a unique id for this child object.
   *
   * @return
   *   A unique string identifying this child.
   */
  public function getUniqueName() {

    $name = '';
    foreach ($this->getUniqueFields() as $field => $fieldValue) {
      if ($name == '' && $fieldValue) {
        $name = $fieldValue;
      }
      elseif ($fieldValue) {
        $name .= '_' . $fieldValue;
      }
    }
    return $name;
  }

  /**
   * Permanently delete an object from the local Houston application.
   */
  public function delete($logical = TRUE) {
    // TODO: Implement child/relation object deleting
    $this->callControllers('delete', 'local');
    if (!$logical) {
      $sql = "id = $this->id";
      $this->db->delete($this->baseTable, $sql);
    }
    else {
      $this->deleted = TRUE;
      $this->saveToHouston();
    }
  }

  /**
   * Check to see if this User has been deleted.
   */
  public function isDeleted() {
    return $this->deleted ? TRUE : FALSE;
  }

  /**
   * Check to see if this User has been deleted.
   */
  public function isNew() {
    return $this->new ? TRUE : FALSE;
  }

  /**
   * Set the status for a particular controller.
   *
   * @param $operation
   *   (string) The operation on which to set the status.
   *
   * @param $controller
   *   (string) The controller on which to set the status.
   */
  public function setDataStatus($operation, $controller, $status) {
    $controllerConfig = $this->controllerConfig;
    if (isset($controllerConfig[$controller]['status'])) {
      $statusInfo = $controllerConfig[$controller]['status'];
      if (in_array($operation, $statusInfo['statusEnabledOps'])) {
        $operationStatus = (int) $this->statusOperationIds[$operation]['operation'];
        $currentStatus = (int) $this->{$statusInfo['statusField']};
        if ($status) {
          // Locking
          if (!($operationStatus & $currentStatus)) {
            $this->{$statusInfo['statusField']} = $currentStatus + $operationStatus;
          }
        }
        else {
          // Unlocking
          if ($operationStatus & $currentStatus) {
            $this->{$statusInfo['statusField']} = $currentStatus - $operationStatus;
          }
        }
      }
    }
  }

  /**
   * Get the status of the data for a particular controller.
   *
   * @param $operation
   *   (string) For operation for which the status is needed.
   *
   * @param $controller
   *   (string) The controller for which the status is needed.
   *
   * @return
   *   (boolean) Whether or not the operation is locked.
   */
  public function getDataStatus($operation, $controller) {
    $controllerConfig = $this->controllerConfig;
    if (isset($controllerConfig[$controller]['status'])) {
      $statusInfo = $controllerConfig[$controller]['status'];
      if (in_array($operation, $statusInfo['statusEnabledOps'])) {
        $operationStatus = (int) $this->statusOperationIds[$operation]['locks'];
        $currentStatus = (int) $this->{$controllerConfig[$controller]['status']['statusField']};
        return ($operationStatus & $currentStatus) ? HOUSTON_STATUS_LOCKED : HOUSTON_STATUS_UNLOCKED;
      }
    }
    return HOUSTON_STATUS_UNLOCKED;
  }

  /**
   * Add this item to the queue.
   *
   * @param $operation
   *   (string) The operation that needs to be repeated.
   *
   * @param $controller
   *   (string) The controller on which to run the operation.
   */
  public function addToQueue($operation, $controller) {
    $queueItem = new stdClass();
    $queueItem->type = $this->objectType;
    // If there is no id, then this will fail spectacularly.
    $queueItem->localId = $this->getId();
    $queueItem->operation = $operation;
    $queueItem->controller = $controller;

    // Add blockers
    // TODO: Currently we only support one blocker.
    foreach ($this->fieldMap as $field => $info) {
      if (isset($info['reference']['parent']) && $info['reference']['parent']) {
        $queueItem->localBlockerType = $info['reference']['objectType'];
        $queueItem->localBlockerId = $this->$field;
        break;
      }
    }
    
    // TODO: Should a queue object be put into the registry?
    $queue = Houston_DataObject::factory('Houston_Queue', array('db' => $this->db));
    $queue->addObjectToQueue($queueItem);
  }

  /**
   * Generally, this will be overidden by objects that need queue processing though simple operations can be performed here.
   *
   * @param $operation
   *   (string) The operation to be performed.  With this default implementation this
   *   should always be one of the base methods on the controllers.
   *
   * @param $controller
   *   (string) The controller on which the operation should be performed.
   *
   * @param $data
   *   (array) Data necessary to perform the queued operation.  This is serialized before
   *   it is stored so it may be complex.
   *
   * @return
   *   (array) The next processing operation, which the queue object will update
   *   using the modified $data which is passed by reference allowing the object
   *   to change the data and the queue will save it on update.
   *
   */
  public function queueProcess($operation, $controller, $data) {
    $result = $this->callController($operation, $controller, $queue = FALSE);
    if ($result['status']) {
      return array('complete' => TRUE);
    }
    return array(
      'complete' => FALSE,
      'operation' => $operation,
      'controller' => $controller,
      'data' => $result,
    );
  }

}
