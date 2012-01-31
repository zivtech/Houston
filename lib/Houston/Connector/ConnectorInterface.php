<?php

namespace Houston\Connector;

interface ConnectorInterface {

  /**
   * Map data from the controller into the data object.
   *
   * @param $dataObject
   * @param $data
   * @param $controllerMapping
   */
  public function mapData($input, $fieldMap);

}