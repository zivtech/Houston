<?php
namespace Houston\Connector;

class ObjectConnector implements ConnectorInterface {
  /**
   * Map data from the controller into the data object.
   *
   * @param $dataObject
   * @param $data
   * @param $controllerMapping
   */
  public function mapData($input, $fieldMap) {
    $data = new \stdClass;
    $controllerMapping = $fieldMap;
    foreach ($controllerMapping as $localName => $fieldData) {
      if (isset($fieldData['load']) && !$fieldData['load']) {
        continue;
      }

      $controllerName = $fieldData['field'];
      if (isset($input->$controllerName)) {
        $data->$localName = $input->$controllerName;
      }
    }
    return $data;
  }
}