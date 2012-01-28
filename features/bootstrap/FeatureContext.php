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
   * @Given /^I have one source and one destination$/
   */
  public function iHaveOneSourceAndOneDestination()
  {
      $houston = $this->houston;
      $dataObject = $this->dataObject;

      $houston->
  }

  /**
   * @Given /^I have data from the destination$/
   */
  public function iHaveDataFromTheDestination()
  {
      throw new PendingException();
  }

  /**
   * @When /^I ask to translate from one to the other$/
   */
  public function iAskToTranslateFromOneToTheOther()
  {
      throw new PendingException();
  }

  /**
   * @Then /^I should get the same data in the new structure$/
   */
  public function iShouldGetTheSameDataInTheNewStructure()
  {
      throw new PendingException();
  }
}
