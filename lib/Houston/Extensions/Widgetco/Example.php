<?php
/**
 * @file
 *   This file provides a very simple example of how
 *   A Houston data object may be configured to synchronize data.
 */
class Houston_Extensions_Widgetco_Example extends Houston_DataObject {

  /**
   * Base table for this object. 
   */
  protected $baseTable = 'contacts';

  /**
   * The controllers that this object is configured to work with
   */
  protected $controllers = array();
  /**
   * The controllers that this object is configured to work with
   */
  protected $controllerConfig = array(
    // In our example intranet is a drupal site
    // using the Drupal controller.
    'intranet' => array(
      'enabled' => TRUE,
      'controller' => 'Houston_Controllers_Salesforce_SalesForceClient',
      'operations' => array(
        'save',
        'insert',
        'update',
        'pull',
        'cron',
      ),
      // Controller specific
      'config' => array(
        'username' => 'foo@example.com',
        'password' => 'somepass',
        'securityToken' => 'sometoken',
        'wsdlFilename' => HOUSTON_GLOBAL_SALESFORCE_WSDL,
        'type' => 'Contact',
      ),
      // 'Heavier' items will go later while
      // 'Ligher items will float to the top
      // of the list.
      'weight' => 0,
    ),
  );

  protected $fieldMap = array(
    'id' => array(
      'db' => 'id',
      'unique' => TRUE,
      'companySalesforce' => 'houston_id__c',
    ),
    'intranetId' => array(
      'unique' => TRUE,
      'companySalesforce' => 'cms_id__c',
    ),
    'companySalesforceId' => array(
      'db' => 'salesforce_id',
      'unique' => TRUE,
      'companySalesforceId' => 'Id',
    ),
    'firstName' => array(
      'db' => 'first_name',
      'intranet' => 'first_name',
    ),
    'lastName' => array(
      'db' => 'last_name',
      'intranet' => 'last_name',
    ),
    'email' => array(
      'db' => 'email',
      'intranet' => 'email',
    ),
    'homePhone' => array(
      'db' => 'phone',
      'intranet' => 'phone',
    ),
    'organization' => array(
      'db' => 'organization',
      'intranet' => 'organization',
    ),
  );

  /**
   * Save the object to the database.
   */ 
  public function save() {
    $this->lastModified = time();
    parent::save();
  }

}
