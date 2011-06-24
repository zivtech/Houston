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
        'delete',
        'update',
        'cron',
      ),
      // Controller specific
      'config' => array(
        'username' => 'someone',
        'password' => 'somepass',
        'securityToken' => 'sometoken',
        'wsdlFilename' => '/absolute-path',
        'type' => 'Contact',
      ),
      // 'Heavier' items will go later while
      // 'Ligher items will float to the top
      // of the list.
      'weight' => 0,
    ),
    'companySalesforce' => array(
      'enabled' => TRUE,
      'controller' => 'Houston_Controllers_Salesforce_SalesForceClient',
      'operations' => array(
      ),
      'config' => array(
        'username' => 'someotherperson',
        'password' => 'someotherpass',
        'securityToken' => 'someothertoken',
        'wsdlFilename' => '/other/absolute-path',
        'type' => 'Contact',
      ),
      'weight' => 1,
    ),
  );

  protected $fieldMap = array(
    'id' => array(
      'db' => array(
        'field' => 'id',
      ),
      'unique' => TRUE,
      'companySalesforce' => array(
        'field' => 'houston_id__c',
      ),
    ),
    'intranetId' => array(
      'unique' => TRUE,
      'id' => 'intranet',
      'companySalesforce' => array(
        'field' => 'cms_id__c',
      ),
    ),
    'companySalesforceId' => array(
      'db' => 'salesforce_id',
      'unique' => TRUE,
      'id' => 'companySalesforce',
      'companySalesforceId' => array(
        'field' => 'Id',
      ),
    ),
    'firstName' => array(
      'db' => array(
        'field' => 'first_name',
      ),
      'intranet' => array(
        'field' => 'first_name',
      ),
    ),
    'lastName' => array(
      'db' => array(
        'field' => 'last_name',
      ),
      'intranet' => array(
        'field' => 'last_name',
      ),
    ),
    'email' => array(
      'db' => array(
        'field' => 'email',
      ),
      'intranet' => array(
        'field' => 'email',
      ),
    ),
    'homePhone' => array(
      'db' => array(
        'field' => 'phone',
      ),
      'intranet' => array(
        'field' => 'phone',
      ),
    ),
    'organization' => array(
      'db' => array(
        'field' => 'organization',
      ),
      'intranet' => array(
        'field' => 'organization',
      ),
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
