<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//
// We use mockery as a mock object framework so we inclue it here.
require 'vendor/.composer/autoload.php';

/**
 * Features context.
 */
class FeatureContext extends BehatContext {

  protected $houston = FALSE;

  /**
   * Initializes context.
   * Every scenario gets it's own context object.
   *
   * @param   array   $parameters     context parameters (set them up through behat.yml)
   */
  public function __construct(array $parameters) {
    // Create a Houston instance.
    $this->houston = new \Houston\Houston();
  }

  /**
   * @Given /^I have a data object$/
   */
  public function iHaveADataObject() {
    $this->dataObject = $this->houston->createDataObject('User');
  }

  /**
   * @Given /^I have two connectors configured with a field mapping between them$/
   */
  public function iHaveTwoConnectorsConfiguredWithAFieldMappingBetweenThem() {
    $houston = $this->houston;
    $dataObject = $this->dataObject;
    $connector = new Houston\Connector\ObjectConnector();
    $houston
      // Register the connector in Houston for reuse.
      ->addConnector('system2', $connector)
      // Add the connector to this object.
      ->addConnectorToObject('system2', $dataObject);
    $field = $dataObject->addField('foo');
    $field->mapToConnector('system2', 'bar');
  }

  /**
   * @Given /^I have populated the data object with a destination$/
   */
  public function iHavePopulatedTheDataObjectWithADestination() {
    //throw new PendingException();
    $inputData = new stdClass;
    $inputData->foo = 'baz';
    // Set data with our native structure.
    $this->dataObject->setData($inputData);
  }

  /**
   * @When /^I ask to translate from one to the other$/
   */
  public function iAskToTranslateFromOneToTheOther() {
    // Retrieve the data with the structure for system2.
    $this->data = $this->dataObject->getData('system2');
  }

  /**
   * @Then /^I should get the same data in the new structure$/
   */
  public function iShouldGetTheSameDataInTheNewStructure() {
    if ($this->data->bar !== 'baz') {
      throw new Exception();
    }
  }
}
