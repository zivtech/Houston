<?php

require_once 'Houston/Controllers/Controller.php';
require_once 'phptoolkit/soapclient/SforceEnterpriseClient.php';
require_once 'Houston/Application.php';

class Houston_Controllers_Salesforce_SalesForceClient extends SforceEnterpriseClient implements Houston_Controllers_Controller_Interface {
  
  /**
   * Base table for this object. 
   */
  const BASE_TABLE = '.houston_salesforce_query_log';
  const VARIABLES_TABLE = '.houston_variables';

  /**
   * username 
   * 
   * @var string
   */
  private $username = '';

  /**
   * password 
   * 
   * @var string
   */
  private $password = '';

  /**
   * securityToken 
   * 
   * @var string
   */
  private $securityToken = '';

  /**
   * wsdlFilename 
   * 
   * @var string
   */
  private $wsdlFilename = '';

  /**
   * The type of data in salesforce that this object will write to.
   *
   * @var string
   */
  private $type = '';

  /**
   * instance 
   * 
   * @var mixed
   */
  protected  static $instance;

  /**
   * Whether or not we have a connection.
   *
   * NULL --> we haven't tried.
   * TRUE --> we tried, it was ok
   * FALSE --> we tried and failed
   */
  private $connected = NULL;

  /**
   *
   */
  private $db = NULL;

  /** 
   * The fieldmap for this particular controller.
   */
  private $fieldMap = NULL;
  
  /**
   * getInstance 
   * 
   * @return SalesForceClient
   */
  public static function getInstance() {

    if (is_null(self::$instance)) {
      self::$instance = new self(array('db' => Zend_Registry::get('drupal_db'), 'wsdlFilename' => Zend_Registry::get('wsdlFilename')));
    }
    return self::$instance;
  }

  /**
   * Get the external object type for this particular connection.
   */
  public function getObjectType() {
    return $this->type;
  }

  /**
   * Save 
   */
  public function save(stdClass $data) {
    $result = array('status' => FALSE);
    if (!isset($data->Id) || !$data->Id) {
      $data->Id = '';
      unset($data->Id);
      $response = $this->create(array($data));
    }
    else {
      $response = $this->update(array($data));
    }

    // Determine data to return.
    if (is_object($response) && isset($response->success) && $response->success) {
      $result['data'] = array('Id' => $response->id);
      $result['status'] = TRUE;
    }
    else if (is_object($response)) {
      $result['error'] = $response;
    }
    else {
      $result['error'] = 'NO_SALESFORCE_CONNECTION';
    }
    return $result;
  }

  /**
   * Load a single record.
   *
   * @return
   *   An error array.
   */
  public function load(stdClass $data) {
    $result = array();
    if (!isset($data->Id)) {
      // Success is true, since there is no id to load.
      return array(
        'status' => TRUE,
        'message' => 'No ID specified',
      );
    }
    $fields = array();
    foreach ($this->fieldMap as $fieldName => $fieldData) {
      if (isset($fieldData['field'])) {
        if (!isset($fieldData['load']) || $fieldData['load']) {
          $fields[] = $fieldData['field'];
        }
      }
    }

    $response = $this->retrieve(implode($fields, ', '), $this->type, array($data->Id));
    if (is_object($response)) {
      $responseData = new stdClass;
      foreach ($response as $field => $value) {
        // Salesforce allows us to retrieve information
        // about realted objects. They are returned as
        // stdClass objects.
        if (is_object($value)) {
          foreach ($value as $objectField => $objectValue) {
            $responseData->{$field . '.' . $objectField} = $objectValue;
          }
        }
        else {
          $responseData->{$field} = $value;
        }
      }
      $result = array(
        'status' => TRUE,
        'data' => $responseData,
        'result' => $responseData,
      );
    }
    else {
      $result = array(
        'status' => FALSE,
        'message' => 'Data not found in salesforce',
      );
    }
    return $result;
  }

  public function create($data) {

    // TODO: decide if we're going to use the Zend debugLog and move this
    // into the Controller object if necessary.
    $type = $this->type;
    if ($this->connect() && !$this->reachedMaxSalesForceApiHits()) {
      $this->debugLog("Creating SF object $type with " . print_r($data, TRUE));
      try {
        $result = parent::create($data, $type);
        //$this->logSaleforceQuery('create', $result);
        return $result;
      }
      catch (Exception $e) {
        $this->debugLog($e);
        $result = new StdClass;
        $result->error = $e;
        return $result;
      }
    }
    return FALSE;
  }

  public function update($data) {
    $type = $this->type;
    // Some fields can only be created, not updated.
    foreach($this->fieldMap as $field => $info) {
      if (isset($info['update']) && !$info['update']) {
        // We are only updating one at a time, so we only
        // worry about the first item in the array.
        unset($data[0]->{$info['field']});
      }
    }

    if ($this->connect() && !$this->reachedMaxSalesForceApiHits()) {
      try {
        $this->debugLog("Updating SF object $type with " . print_r($data, TRUE));
        $result = parent::update($data, $type);
        //$this->logSaleforceQuery('update', $result);
        return $result;
      }
      catch (Exception $e) {
        $this->debugLog($e);
        $result = new StdClass;
        $result->error = $e;
        return $result;
      }
    }
    return FALSE;
  }

  /**
   * This method is a thin wrapper around the method by the same name ont 
   * the SforceEnterpriseClient class provided by the Salesforce library.
   */
  public function retrieve($fields, $type, $ids) {

    if ($this->connect() && !$this->reachedMaxSalesForceApiHits()) {
      $this->debugLog("Retrieving SF objects $type with " . print_r($ids, TRUE));
      try {
        $result = parent::retrieve($fields, $type, $ids);
        //$this->logSaleforceQuery('retrieve', $result);

        // Retrieve doesn't grab deleted entries.  If IsDeleted
        // is a field and nothing was retrieved then see if the
        // object was actually deleted.
        $field_list = explode(', ', $fields);
        if (!$result && in_array('IsDeleted', $field_list)) {
          $result = new stdClass();
          $result->IsDeleted = $this->isDeleted(reset($ids));
        }
        return $result;
      }
      catch (Exception $e) {
        $this->debugLog($e);
      }
    }
    return FALSE;
  }

	/**
	 * Deletes one or more new individual objects to your organization's data.
	 *
	 * @param array $ids    Array of fields
	 * @return DeleteResult
	 */
	public function delete(stdClass $data) {
    $result = array(
      'status' => FALSE,
      'data' => array()
    );

    $type = $this->type;
    $ids = array($data->Id);
    if ($this->connect() && !$this->reachedMaxSalesForceApiHits()) {
      $this->debugLog("Deleting SF objects $type with " . print_r($ids, TRUE));
      try {
        $response = parent::delete($ids);
        if ($response->success) {
          $result['status'] = TRUE;
        }
        else {
          $result['error'] = $response;
        }
        //$this->logSaleforceQuery('retrieve', $result);
      }
      catch (Exception $e) {
        $result['error'] = $e;
        $this->debugLog($e);
      }
    }
    return $result;
	}

  /**
   * Determine if this item has been deleted.
   *
   * @param $id
   */
  public function isDeleted($id) {
    if ($this->connect() && !$this->reachedMaxSalesForceApiHits()) {
      $soql = "SELECT Id, isDeleted From %s WHERE Id = '%s' AND IsDeleted = true";
      $soql = sprintf($soql, $this->type, $id);
      $results = $this->queryAll($soql);
      if (isset($results->records) && count($results->records)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Map data from the controller into the data object.
   *
   * @param $data
   */
  public function mapData($input, $salesforceFields = TRUE) {
    $data = new stdClass;
    $controllerMapping = $this->fieldMap;
    foreach ($controllerMapping as $localName => $fieldData) {
      if (isset($fieldData['load']) && !$fieldData['load']) {
        continue;
      }

      $controllerName = $fieldData['field'];
      if (isset($input->$controllerName)) {
        if ($salesforceFields) {
          $data->$controllerName = $input->$controllerName;
        }
        else {
          $data->$localName = $input->$controllerName;
        }
      }
    }
    return $data;
  }

  /**
   * __construct 
   * 
   * @return void
   */
  public function __construct(array $config = NULL) {

    // TODO: This is a bloody sloppy way of doing this.
    // We should really be passing around a config object here.
    /*
    $requiredConstants = array('HOUSTON_SFDC_WSDL', 'HOUSTON_SF_USER', 'HOUSTON_SF_PASS', 'HOUSTON_SF_TOKEN');
    foreach ($requiredConstants as $constantName) {
      if (!defined($constantName)) {
        throw new Exception("Missing required constant '$constantName'");
      }
    }
    */
    // TODO: test this approach!
    // Populate the fields
    foreach ($this as $name => $value) {
      if (isset($config[$name])) {
        $this->$name = $config[$name];
      }
    }

    if (!file_exists($this->wsdlFilename)) {
      throw new Exception("No wsdl file at '{$this->wsdlFilename}'");
    }
    if (!is_readable($this->wsdlFilename)) {
      throw new Exception("Can't read wsdl file at '{$this->wsdlFilename}'");
    }
    
    if ($config) {
      $this->processConfig($config);
    }
  }

  /**
   * connect 
   * 
   * @return void
   */
  public function connect() {
   
    // Find out if the connection should be enabled.
    $application = Zend_Registry::get('Houston_Application');
    $sfEnabled = $application->variableGet('salesforceConnection', TRUE);
    if ($this->connected === NULL && $sfEnabled) {
      try {
        $this->SforceEnterpriseClient();
        $this->createConnection($this->wsdlFilename);
        $this->connected = $this->login($this->username, $this->password . $this->securityToken);
      }
      catch (Exception $e) {
        $this->connected = FALSE;
      }
    }
    return $this->connected;
  }
  
  /**
   * processConfig 
   * 
   * @param array $config 
   * @return void
   */
  public function processConfig(array $config) {

    if (isset($config['wsdlFilename'])) {
      $this->setWsdlFileName($config['wsdlFilename']);
    }
    if (isset($config['username'])) {
      $this->setUsername($config['username']);
    }
    if (isset($config['password'])) {
      $this->setPassword($config['password']);
    }
    if (isset($config['securityToken'])) {
      $this->setSecurityToken($config['securityToken']);
    }
    if (isset($config['db'])) {
      $this->db = $config['db'];
    }
    if (isset($config['fieldMap'])) {
      $this->fieldMap = $config['fieldMap'];
    }
  }

  /**
   * setWsdlFileName 
   * 
   * @param mixed $wsdlFilename 
   * @return void
   */
  public function setWsdlFileName($wsdlFilename) {

    if (!file_exists($this->wsdlFilename)) {
      throw new Exception("File '{$this->wsdlFilename}' doesn't exist in '" . HOUSTON_BASE_DIR . "wsdl' directory");
    }
  }

  /**
   * setPassword 
   * 
   * @param mixed $password 
   * @return void
   */
  public function setPassword($password) {

    $this->password = $password;
  }

  /**
   * setUsername 
   * 
   * @param mixed $username 
   * @return void
   */
  public function setUsername($username) {

    $this->username = $username;
  }

  /**
   * setSecurityToken 
   * 
   * @param mixed $securityToken 
   * @return void
   */
  public function setSecurityToken($securityToken) {

    $this->securityToken = $securityToken;
  }

  /**
   * Execute a SOQL query and return the result array or FALSE on failure
   */
  public function runSoqlQuery($soql, $queryAll = FALSE) {

    if ($this->connect() && !$this->reachedMaxSalesForceApiHits()) {
      try {
        // Query all allows querying deleted objects.
        $result = $queryAll ? $this->queryAll("$soql") : $this->query("$soql");
        //$this->logSaleforceQuery('soql', $result);
        $this->debugLog("sent SOQL '$soql', got result:\n" . print_r($result, TRUE));
        return $result;
      }
      catch (SoapFault $e) {
        $this->debugLog($e);
        return  FALSE;
      }
    }
    return  FALSE;
  }

  /**
   * Build and run a basic soql query.
   *
   * @param $type
   *  (string) type of Salesforce object.
   * @param $fields
   *  (array) keyed by salesforce fields and has values to search for.
   */
  function buildAndRunSoqlQuery($type, $fields) {
    $where = array();
    $soql = "SELECT Id, " . implode(array_keys($fields));
    $soql .= " FROM $type WHERE ";
    foreach ($fields as $field => $value) {
      $where[] = "$field = '$value' ";
    }
    $soql .= implode(" AND ", $where);
    return $this->runSoqlQuery($soql);
  }

  /**
   * Check if the maximum number of salesforce api hits has been reached
   *
   * @return boolean
   */
  public function reachedMaxSalesForceApiHits() {

    // TODO: Update this so it doesn't always return FALSE
    return FALSE;
    $application = Zend_Registry::get('Houston_Application');
    $dailyQueries = $application->variableGet('salesforceQueries', 60000);
    $todate = $this->getSalesForceApiHits();
    if ($todate < $dailyQueries) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get the number of recorded salesforce api hits for the day
   *
   * @return int
   */
  public function getSalesForceApiHits() {

    $baseTable = self::BASE_TABLE;
    $sql = 'SELECT COUNT(*) AS num FROM ' . houston_legacy_DB . self::BASE_TABLE;
    $result = $this->db->fetchRow($sql);
    $number = $result->num;
    if ($number) {
      return $number; 
    }
    return 0;
  }

  /**
   * Reset the number of API hits for the day
   *
   * @return void
   */
  public function resetSalesForceApiHits() {

  }

  /**
   * Log this Salesforce query to the db query log.
   */
  public function logSaleforceQuery($method, $result) {
  
    $row = array(
      'method' => $method,
      'response' => serialize($result),
      'timestamp' => time(),
    );
    $this->db->insert(HOUSTON_DB . self::BASE_TABLE, $row);
  }

  /**
   * Deletes all entries from the Salesforce query log.
   * This operation should be performed daily by a cron job.
   */
  public function clearSalesforceQueryLog() {

    $this->db->query('DELETE FROM ' . HOUSTON_DB . self::BASE_TABLE);
  }

  /**
   * Try to log a debug message.
   * 
   * @param mixed $message 
   */
  public function debugLog($message) {

    try {
      Zend_Registry::get('debug_log')->log($message, Zend_Log::INFO);
    }
    catch (Exception $e) { }
  }

}

