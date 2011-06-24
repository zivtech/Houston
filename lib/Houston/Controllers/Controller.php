<?php

/**
 * @file
 *   Provides Housotn Controllers Controller.
 *   This interface must be implemented by all controllers.
 *
 *   TODO: wtf does 'controller' mean in this context?
 */
interface Houston_Controllers_Controller_Interface {

  /**
   * Get an instance of this Controller.
   */
  public static function getInstance();

  /**
   * Process a configuration
   */
  public function processConfig(array $config);

  /**
   * Save (create or update) an item in the remote system.
   *
   * Essentially performs and 'upsert' operation.
   */
  public function save(stdClass $data);

  /**
   *
   */
  // TODO: decide what this function definition should look like
  public function load(stdClass $data);

  /**
   *
   */
  // TODO: stdclass so you can give it whatever data is relevant
  // to the controller to perform a delete on this kind of object?
  public function delete(stdClass $data);

  // TODO: Map data should be in interface

  /**
   * Get the external object type for this particular connection.
   */
  public function getObjectType();

}

/**
 *
 */
abstract class Houston_Controllers_Controller implements Houston_Controllers_Controller_Interface {

  private $fieldMap = array();

  private $operations = array();

  /**
   * processConfig
   *
   * @param array $conf
   * @return void
   */
  public function processConfig(array $conf) {
    foreach ($conf as $name => $value) {
      if (isset($this->$name)) {
        $this->$name = $value;
      }
    }
  }

  /**
   * getInstance
   *
   * TODO: make this final.
   *
   * @return SalesForceClient
   */
  public static function getInstance() {

    if (is_null(self::$instance)) {
      self::$instance = new self(array('db' => Zend_Registry::get('drupal_db')));
    }
    return self::$instance;
  }

  /**
   * Map data from the controller into the data object.
   *
   * @param $dataObject
   * @param $data
   * @param $controllerMapping
   */
  public function mapData($input) {
    $data = new stdClass;
    $controllerMapping = $this->fieldMap;
    foreach ($controllerMapping as $localName => $fieldData) {
      if (isset($fieldData['load']) && !$fieldData['load']) {
        continue;
      }

      $controllerName = $fieldData['field'];
      if (isset($input->$controllerName)) {
        $data->$localName = $input->$controllerName;
      }
    }
    return $data;
  }

  public function save(stdClass $data) {
  }

  // TODO: decide what this function definition should look like
  public function load(stdClass $data) {
  }

  public function update(stdClass $data) {
  }

  public function delete(stdClass $data) {
  }

  /**
   * Get the external object type for this particular connection.
   */
  public function getObjectType() {
    return NULL;
  }

}
