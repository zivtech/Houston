<?php

/**
 * Works with a local instance of Drupal.
 * The assumption is that it is already bootstrapped,
 * so we can access any drupal functions we'd like.
 */

require_once 'Houston/Controllers/Controller.php';

class Houston_Controllers_Drupal_Drupal7Local implements Houston_Controllers_Controller_Interface {

  /**
   * The drupal object type.
   */
  public $type = '';

  /**
   * The type of node (if type is node).
   */
  private $nodeType = '';

  /**
   * The fieldmap for this particular controller.
   */
  private $fieldMap = NULL;

  /**
   * Get an instance of this Controller.
   */
  public static function getInstance() {
    if (is_null(self::$instance)) {
      self::$instance = new self(array('db' => Zend_Registry::get('drupal_db')));
    }
    return self::$instance;
  }

  /**
   * __construct 
   * 
   * @return void
   */
  public function __construct(array $config = NULL) {
    if ($config) {
      $this->processConfig($config);
    }
  }
  
  /**
   * processConfig 
   * 
   * @param array $config 
   * @return void
   */
  public function processConfig(array $config) {
    if (isset($config['type'])) {
      $this->type = $config['type'];
    }
    if (isset($config['node-type'])) {
      $this->nodeType = $config['node-type'];
    }
    if (isset($config['db'])) {
      $this->db = $config['db'];
    }
    if (isset($config['fieldMap'])) {
      $this->fieldMap = $config['fieldMap'];
    }
    if (isset($config['dataCallback'])) {
      $this->dataCallback = $config['dataCallback'];
    }
  }

  /**
   * Get the external object type for this particular connection.
   */
  public function getObjectType() {
    // TODO: There's more logic to determining the type.
    return $this->type;
  }

  /**
   * Save (create or update) an item in the remote system.
   *
   * Essentially performs an 'upsert' operation.
   */
  public function save(stdClass $data) {
    switch ($this->type) {
      case 'node':
        if (isset($data->nid) && is_int($data->nid)) {
          $result = $this->updateDrupalNode($data);
        }
        else {
          $result = $this->createDrupalNode($data);
        }
        //$result = array('status' => FALSE, 'type' => 'node');
        break;
      case 'user':
        if (isset($data->uid) && is_int($data->uid)) {
          $result = $this->updateDrupalUser($data);
        }
        else {
          $result = $this->createDrupalUser($data);
        }
        break;
      case 'order':
        $result = array('status' => TRUE, 'type' => 'order');
        break;
      case 'order-item':
        // TODO: Each product within an order can be synced.
        break;
      case 'other':
      $result = $this->saveDrupalWithCallback($data);
    }
    return $result;
  }

  /**
   *
   */
  // TODO: decide what this function definition should look like
  public function load(stdClass $data) {
    $result = array('status' => FALSE);
    // This does something
    switch ($this->type) {
      case 'node':
        if (isset($data->nid) && is_int($data->nid)) {
          $node = node_load($data->nid);
          $mappedData = $this->mapDataFromDrupal($node, TRUE);
        }
        break;
      case 'user':
        if (isset($data->uid) && is_int($data->uid)) {
          $account = user_load($data->uid);
          $mappedData = $this->mapDataFromDrupal($account, TRUE);
          if (!is_null($this->contentProfile)) {
            $profile = content_profile_load($this->contentProfile, $data->uid);
            $mappedProfile = $this->mapDataFromDrupal($profile, TRUE);
            $mappedData = (object) array_merge((array) $mappedProfile, (array) $mappedData);
          }
        }
        break;
      case 'order':
        // TODO: add in ubercart order support.
        $result = array('status' => TRUE, 'data' => new StdClass);
        break;
    }
    if (isset($mappedData)) {
      $result['status'] = TRUE;  
      $result['data'] = $mappedData;
    }
    return $result;
  } 

  /**
   *
   */
  // TODO: stdclass so you can give it whatever data is relevant 
  // to the controller to perform a delete on this kind of object?
  public function delete(stdClass $data) {
    // This does something
  }

  public function mapData($input) {
    return $this->mapDataFromDrupal($input);
  }

  /**
   * Create a brand new user in Drupal.
   */
  public function createDrupalUser(&$data) {
    $result = array('status' => FALSE);
    $edit = new stdClass;
    
    $this->mapDataToDrupalObject('user', $edit, $data);
    $edit->savingFromHouston = TRUE;
    if (!isset($edit->mail) || !isset($edit->name)) {
      return $result;
    }

    if (!isset($edit->init)) {
      $edit->init = $data->mail;
    }

    if (!isset($edit->pass)) {
      $edit->pass = user_password(); 
    }

    if (!isset($edit->status)) {
      $edit->status = 1;
    }

    $success = user_save(FALSE, (array) $edit);
 
    if ($success) {
      // If we're dealing with a drupal user, we must have a uid field.
      // There can only be one uid, since this is a one-off Drupal connection.
      $data = new stdClass;
      $data->uid = $success->uid;
      $result['status'] = TRUE;
      $result['object'] = $success;
      $result['data'] = $data;
    }
    return $result;
  }

  /**
   *
   */
  public function createDrupalNode(&$data) {
    $result = array('status' => FALSE);

    $node = new StdClass;
    $node->language = 'und';
    $node->type =  $this->nodeType;
    $node->savingFromHouston = TRUE;
    $this->mapDataToDrupalObject('node', $node, $data);
    node_save($node);
 
    if ($node->nid) {
      // If we're dealing with a drupal user, we must have a uid field.
      // There can only be one uid, since this is a one-off Drupal connection.
      $data = new stdClass;
      $data->nid = $node->nid;
      $result['status'] = TRUE;
      $result['object'] = $node;
      $result['data'] = $data;
    }
    return $result;
  }

  /**
   *
   */
  public function updateDrupalUser(&$data) {
    $result = array('status' => FALSE);
    $account = user_load($data->uid, $reset = TRUE);
    $edit = new stdClass;
    $this->mapDataToDrupalObject('user', $edit, $data);
    $edit->savingFromHouston = TRUE;
 
    $success = user_save($account, (array) $edit);
    if ($success) {
      $data = new stdClass;
      $data->uid = $success->uid;

      $result['status'] = TRUE;
      $result['object'] = $success;
      $result['data'] = $data; 
    }
    return $result;
  }

  /**
   * Updates a node with houston data.
   */
  public function updateDrupalNode(&$data) {
    $result = array('status' => FALSE);
    $node = node_load($data->nid);
    $node->savingFromHouston = TRUE;
    $this->mapDataToDrupalObject('node', $node, $data);
 
    node_save($node);
    if ($node->nid) {
      $data = new stdClass;
      $data->nid = $node->nid;

      $result['status'] = TRUE;
      $result['object'] = $node;
      $result['data'] = $data; 
    }
    return $result;
  }

  /**
   *
   */
  public function saveDrupalWithCallback($data) {
    $result = array('status' => FALSE);
    if (isset($this->dataCallback)) {
      $function = $this->dataCallback;
      if (function_exists($function)) {
        $result = $function($data);
      }
    }
    return $result;
  }

  /**
   * Map local data to drupal objects.
   */
  function mapDataToDrupalObject($type, &$object, $data) {
    // TODO: Break out the types of objects into separate functions.
    // TODO: This can be changed around to be more generic for drupal 7.
    $fieldMap = $this->fieldMap;
    foreach ($fieldMap as $localName => $fieldData) {
      if (!isset($data->{$fieldData['field']})) {
        continue;
      }
      if (!in_array($type, array('node', 'user'))) {
        // TODO: Deal with user roles elswhere, because it is not run here anymore.
        if ($type == 'user' && isset($fieldData['role'])) {
          if ($data->{$fieldData['field']}) {
            $object['roles'][$fieldData['role']] == $fieldData['field'];
          }
          else {
            unset($object['roles'][$fieldData['role']]);
          }
        }
        else if (!isset($fieldData['fieldType'])) {
          $object->{$fieldData['field']} = $data->{$fieldData['field']};
        }
      }
      // Ensure that the field is an array
      else if (isset($fieldData['fieldType']) && (!isset($object->{$fieldData['field']}) || is_array($object->{$fieldData['field']}))) {
        if (!isset($object->{$fieldData['field']})) {
          $object->{$fieldData['field']} = array();
        }
        switch ($fieldData['fieldType']) {
          case 'node_reference':
            $object->{$fieldData['field']}['und'][0]['nid'] = $data->{$fieldData['field']};
            break;
          case 'user_reference':
            $object->{$fieldData['field']}['und'][0]['uid'] = $data->{$fieldData['field']};
            break;
          default:
            $object->{$fieldData['field']}['und'][0]['value'] = $data->{$fieldData['field']};
            break;
        }
      }
      else {
        $object->{$fieldData['field']} = $data->{$fieldData['field']};
      }
    }
  }

  /**
   * Map Drupal data to local fields.
   */
  public function mapDataFromDrupal($object, $drupalFields = TRUE) {
    $data = new stdClass;
    $controllerMapping = $this->fieldMap;
    foreach ($controllerMapping as $localName => $fieldData) {
      $field = $drupalFields ? $fieldData['field'] : $localName;
      // Make sure we are allowed to load this field from this controller.
      if (isset($fieldData['load']) && !$fieldData['load']) {
        continue;
      }

      $controllerName = $fieldData['field'];
      if (isset($object->$controllerName)) {
        if (isset($fieldData['fieldType']) && is_array($object->$controllerName)) {
          // TODO: Multi-valued fields
          switch ($fieldData['fieldType']) {
            case 'node_reference': 
              $data->$field = $object->{$controllerName}['und'][0]['nid'];
              break;
            case 'user_reference':
              $data->$field = $object->{$controllerName}['und'][0]['uid'];
              break;
            default:
              $data->$field = $object->{$controllerName}['und'][0]['value'];
              break;
          }
        }
        else {
          // TODO: Deal with user roles.
          $data->$field = $object->$controllerName;
        }
      }
    }
    return $data;
  }
}

