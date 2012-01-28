Feature: A simple mapping can translate one field name to another.
  In order to move data from a source to a destination
  Consuming code should be able to
  provide a source and a destination and do a mapping between them

  Scenario: Translate a field from one devinition to another
    Given I have a data object
      And I have one source and one destination
      And I have data from the destination
     When I ask to translate from one to the other
     Then I should get the same data in the new structure