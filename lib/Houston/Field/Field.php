<?php
namespace Houston\Field;

class Field implements FieldInterface {
  protected $dataObject;
  protected $fieldName;
  public function __construct($fieldName, \Houston\DataObjectInterface $dataObject) {
    $this->fieldName = $fieldName;
    $this->dataObject = $dataObject;
  }
  public function mapToConnector($connectorName, $field) {
    $this->dataObject->addFieldMappingToConnector($this->fieldName, $connectorName, $field);
  }
}
