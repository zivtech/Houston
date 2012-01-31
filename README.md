# Houston #

Houston is a data transformation framework intended for use in middleware layers.

This branch is seriously incomplete and is intended to modernize and rething Houston
rearchitecting around deficiencies in the original codebase.  It is not an entire
rewrite but is a major refactor.

The goals:

- Provide an actual API, don't require developers to create their own subclass with a big manually written and largely undocumented data structure.
- Stop requiring you to duplicate your data.  Sometimes it makes sense for your middleware to keep a copy, but often it doesn't.
- PSR-0 compliance for integration with other modern PHP projects.
- Remove the dependency on the Zend framework.
- The ability to use Houston without a canonical database (any Connector can be treated as the canonical store).
- Testing.  BDD style testing for every feature.
- Stop conflating data transformation for an external source and data transmission to an external source.

## Testing ##

Testing is done using [BDD](http://dannorth.net/introducing-bdd) via [Behat](http://behat.org).  All testing dependencies can be installed using
composer by running composer from inside this directory.  If you are not running the unit tests, you don't need to run composer. While in the 
process of updating Houston from version 1.x to 2.x, there are many features that may be in some state of disrepair but tests should reflect the
code that has been implemented/ported.

## New Syntax ##

The following **is not implmeneted and does not work**, however it does show how we'd like our new syntax to work in an ideal world. 

<?php
  $houston = new \Houston\Houston;
  $drupal_connector = $houston
    ->addConnector('drupal', new \Houston\Connector\Drupal\7\Local);
  $salesforce_connector = $houston
    ->addConnector('salesforce', new \Houston\Connector\Salesforce)
    ->configure(array('username' => 'foo', 'password' => 'bar', 'token' => 'baz'));

  $contact = $houston->createDataObject();
  $contact 
    // TODO: This isn't right... How do we add the `drupal` connector without it being a call on the Houston object?
    ->addConnector('drupal')
    // In some way indicate Drupal is canonical?
    ->setDefaultConnector('drupal')
    ->addConnector('salesforce');
  // Create a new field on this object.
  $firstName = $contact->addField('firstName');
  $firstName
    ->setLabel('First Name')
    ->setType('string')
    ->mapToConnector('drupal', 'field_first_name')
    ->mapToConnector('salesforce', 'firstName__c');
  // Tell Houston that this is a reusable type that we'll want again.
  $houston->addPrototype('Contact', $contact);
  $contact
    ->setData(array('firstName' => 'John'))
    ->save('drupal')
    ->save('salesforce');
  $contact
    ->load('drupal')
  // Get another contact record based on the one registered as a prototype.
  $william = $houston->getDataObject('Contact');
  $william->setData(array('firstName' => 'William'))
    ->save('drupal');
?>