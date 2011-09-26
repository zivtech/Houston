<?php

/**
 * Works with a local instance of Drupal.
 * The assumption is that it is already bootstrapped,
 * so we can access any drupal functions we'd like.
 */

require_once 'Houston/Controllers/Controller.php';

class Houston_Controllers_Drupal_Drupal7Local implements Houston_Controllers_Controller_Interface {

  /**
   * The drupal object type eg node, user, entity, other...
   */
  public $type = '';

  /**
   * The type of node (if type of node).
   */
  private $nodeType = '';

  /**
   * IF this is an entity, then the type of entity.
   *  Use the options array for special info per entity type.
   */
  private $entityType = '';

  /**
   * Special options for the object type.
   */
  private $options = array();


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
    if (in_array($this->type, array('node', 'user'))) {
      $this->entityType = $this->type;
    }
    else if (isset($config['entity-type'])) {
      // Note: This is required for most types.
      $this->entityType = $config['entity-type'];
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
    if (isset($config['options'])) {
      $this->options = $config['options'];
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
      case 'entity':
        // Note: this depends on entity api module.
        if (isset($data->entity_id) && is_int($data->entity_id)) {
          $result = $this->updateDrupalEntity($data);
        }
        else {
          $result = $this->createDrupalEntity($data);
        }
        break;
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
        break;
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
    // TODO: Default language?
    $node->language = 'und';
    $node->type = $this->nodeType;
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
  public function createDrupalEntity(&$data) {
    $result = array('status' => FALSE);

    // Note: This depends upon entity api module.
    $entity = entity_create($this->entityType, array());
    $entity->savingFromHouston = TRUE;
    $this->mapDataToDrupalObject('entity', $entity, $data);
    $status = entity_save($entity);
 
    if ($id = entity_id($this->entityType, $entity)) {
      $data = new stdClass;
      $data->entity_id = $id;
      $result['status'] = TRUE;
      $result['object'] = $entity;
      $result['data'] = $data;
    }
    return $result;
  }

  /**
   * updates an entity with houston data.
   */
  public function updateDrupalEntity(&$data) {
    $result = array('status' => false);
    $entity = entity_load_single($data->entity_id);
    $entity->savingfromhouston = true;
    $this->mapDataToDrupaOobject('entity', $entity, $data);
 
    $status = entity_save($entity);
    if ($id = entity_id($this->entityType, $entity)) {
      $data = new stdclass;
      $data->entity_id = $id;

      $result['status'] = $status;
      $result['object'] = $entity;
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
   * updates o node with houston data.
   */
  public function updateDrupalNode(&$data) {
    $result = array('status' => false);
    $node = node_load($data->nid);
    $node->savingfromhouston = true;
    $this->mapDataToDrupaOobject('node', $node, $data);
 
    $status = node_save($node);
    if ($node->nid) {
      $data = new stdclass;
      $data->nid= $node->nid;

      $result['status'] = $status;
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
      if (!in_array($type, array('node', 'user', 'entity'))) {
        if (!isset($fieldData['fieldType'])) {
          $object->{$fieldData['field']} = $data->{$fieldData['field']};
        }
      }
      // Ensure that the field is an array
      else if (isset($fieldData['fieldType']) && (!isset($object->{$fieldData['field']}) || is_array($object->{$fieldData['field']}))) {
        $language = isset($object->language) ? $object->language : 'und';
        if (!isset($object->{$fieldData['field']})) {
          $object->{$fieldData['field']} = array();
        }
        switch ($fieldData['fieldType']) {
          case 'node_reference':
            $object->{$fieldData['field']}[$language][0]['nid'] = $data->{$fieldData['field']};
            break;
          case 'user_reference':
            $object->{$fieldData['field']}[$language][0]['uid'] = $data->{$fieldData['field']};
            break;
          default:
            $object->{$fieldData['field']}[$language][0]['value'] = $data->{$fieldData['field']};
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
          $values = field_get_items($this->entityType, $object, $controllerName);
          switch ($fieldData['fieldType']) {
            case 'node_reference':
              $data->$field = $values[0]['nid'];
              break;
            case 'user_reference':
              $data->$field = $values[0]['uid'];
              break;
            default:
              $data->$field = $values[0]['value'];
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

