<?php

require_once 'Houston/DataObject.php';
require_once 'Houston/Application.php';

/**
 * This object provides our basic functionality for handling Queue's of items that need to be processed.
 *
 * TODO JR: type hinting throughout.
 */
class Houston_Queue extends Houston_DataObject {

  /**
   * TODO: don't use constants for things that could be tunable.
   */
  // The number of items to load into the queue.
  const NUMBER_ITEMS_TO_PROCESS = 120;

  // The maximum amount of time we should let a process measured in seconds.
  const MAXIMUM_PROCESSING_TIME = 300;

  // The maximum number of items to reasonably have in the queue.
  const MAXIMUM_QUEUE_ITEMS = 300;

  // The base table where these queue items reside.
  const BASE_TABLE = '.houston_queue';

  // The maximum number of times to process a queue item.
  const MAX_PROCESS = 1000;

  // The status of a queue item.
  const READY = 0;
  const PROCESSING = 1;

  /**
   * Called by base object contstructor.
   */
  public function init() {
    $this->table = HOUSTON_DB . self::BASE_TABLE;
  }

  /**
   * array of queue items to process
   *
   * @var string
   */
  protected $queueObjects = array();

  /**
   * Return the count of stalled items.
   *
   * @return int
   */
  public function getStalledItemCount() {

    try {
      $sql = "SELECT COUNT(*) AS num FROM {$this->table} WHERE status = ? AND timestamp < ?";
      $params = array(self::PROCESSING, time() - self::MAXIMUM_PROCESSING_TIME);
      return $this->db->fetchRow($sql, $params)->num;
    }
    catch (Exception $e) {
      return FALSE;
    }
  }

  /**
   * Return the count of ready items.
   *
   * @return int
   */
  public function getReadyItemCount() {

    try {
      $sql = "SELECT COUNT(*) AS num FROM {$this->table} WHERE status = ?";
      return $this->db->fetchRow($sql, self::READY)->num;
    }
    catch (Exception $e) {
      return FALSE;
    }
  }

  /**
   * Load Items
   */
  public function loadQueueItems() {

    $sql = "SELECT *
            FROM {$this->table}
            WHERE status_flag = " . self::READY . "
            AND process_count < " . self::MAX_PROCESS . "
            ORDER BY timestamp
            LIMIT 0, " . self::NUMBER_ITEMS_TO_PROCESS;
    foreach($this->db->fetchAll($sql, array()) as $item) {
      $this->queueObjects[$item->type . '_' . $item->local_id] = $item;
    }
    // TODO JR: don't do this here. failure at this point can lead to normal
    //          queue processing failure. kind of ironic yeah?
    $this->resetStalledItems();
  }

  /**
   * Cleanup items that are stalled in the processing state.
   */
  public function resetStalledItems() {
    // Reset all items that have been processing longer than the maximum allowed time
    // (indicating whatever process was running failed fatally) and set status flag
    // back to ready state.
    // TODO: Update process count - this can't be done with update()
    $expireTime = time() - self::MAXIMUM_PROCESSING_TIME;
    $update_data = array(
      'timestamp' => time(),
      'status_flag' => self::READY
    );
    $where = array(
      'timestamp < ' . $expireTime,
      'status_flag = ' . self::PROCESSING,
    );
    return $this->db->update($this->table, $update_data, $where);
  }

  /**
   * Check for blockers in the queue.
   *
   * TODO: a paragraph about how this fits into the application.
   */
  public function checkForBlockerInQueue($type, $id) {

    $sql = "SELECT qid
            FROM {$this->table}
            WHERE type = ?
            AND local_id = ?";
    if ($result = $this->db->fetchRow($sql, array($type, $id))) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Process all items in the queueObjects array.
   */
  public function processQueue() {
    foreach ($this->queueObjects as $key => $item) {

      // See if this item's local blocker ID matches any other item's local ID, if so skip it.
      if ($this->checkForBlockerInQueue($item->local_blocker_type, $item->local_blocker_id)) {
        // Skip this item and move on to the next one.
        continue;
      }

      // set status flag to processing
      // TODO: ouch, this is a big race waiting to happen.
      $this->updateStatusFlag($item, self::PROCESSING);

      $application = Zend_Registry::get('Houston_Application', Houston_DataObject::factory('Houston_Application', array('db' => $this->db)));
      $queueItemObject = $application->getLoadedObject($item->local_id, $item->type);

      $queueItemData = unserialize($item->data);

      // invoke queue processing on object
      // the process will return the next operation, 'COMPLETE', or the same operation (indicating failure)
      $result = $queueItemObject->queueProcess($item->operation, $item->controller, $queueItemData);

      if ($result['complete']) {
        // if it is complete, delete the item
        $this->deleteItemFromQueue($item->qid);
        unset($this->queueObjects[$item->type . '_' . $item->local_id]);
      }
      else {
        // if not complete, requeue. this can be because of failure (WTF) or
        // because the result of processing this queue item is that it gets
        // requeued as ready (WTF++).
        $this->updateItemInQueue($item->qid, $result['operation'], $result['controller'], $result['data'], self::READY, $item);
        // TODO: Why do we reprocess this item?
        $queueItemObject->queueProcess($result['operation'], $result['controller'], $queueItemData);
      }
    }
  }

  /**
   * Add an item to the Application's queue for processing.
   *  this is an objects entry point into the queue, so if the same object (local id) gets added in again,
   *  the first one is obsolete and gets deleted
   */
  public function addObjectToQueue(stdClass $item) {

    // TODO JR: yep, this could use a transaction

    $table = HOUSTON_DB . self::BASE_TABLE;

    // see if this item (local id) is already in the DB
    $sql = "SELECT *
            FROM $table
            WHERE local_id = ?
            AND type = ?";

    // delete the item if it is here
    // TODO JR: ouch! what if the other item is half way through
    // being processed?
    if ($result = $this->db->fetchRow($sql, array($item->localId, $item->type))){
      $this->deleteItemFromQueue($result->qid);
    }

    $data = array(
      'type' => $item->type,
      'operation' => $item->operation,
      'controller' => $item->controller,
      'local_id' => $item->localId,
      'local_blocker_id' => $item->localBlockerId,
      'timestamp' => time(),
      'data' => serialize($item->data),
      'local_blocker_type' => $item->localBlockerType,
      'status_flag' => self::READY,
    );

    $this->db->insert($table, $data);
  }

  /**
   * Delete an item from the queue.
   *
   * TODO: don't delete items. ever. mark them as finished or whatever, and 
   * if you need to cut down rowcount, archive them off to another table.
   */
  public function deleteItemFromQueue($qid) {

    return $this->db->delete($this->table, "qid = $qid");
  }

  /**
   * Delete an item from the queue given local id and object type.
   * This method can be called from an object if it needs to remove itself from queue.
   */
  public function deleteItemFromQueueWithLocalId($objectType, $localId) {

    $sql = "DELETE
            FROM {$this->table}
            WHERE local_id = ?
            AND type = ?";
    return $this->db->query($sql, array($localId, $objectType));
  }

  /**
   * update an item operation and data
   */
  private function updateItemInQueue($qid, $operation, $controller, $data, $statusFlag = self::READY, $item = NULL) {

    $update_data = array(
      'data' => serialize($data),
      'operation' => $operation,
      'controller' => $controller,
      'timestamp' => time(),
      'status_flag' => $statusFlag,
      'process_count' => is_object($item) ? ++$item->process_count : 0,
    );
    return $this->db->update($this->table, $update_data, 'qid = ' . $qid);
  }

  /**
   * Called from the Cron script this runs the Queue process.
   */
  public function cronProcessQueue() {

    $this->loadQueueItems();
    $this->processQueue();
  }

  public function isItemInQueue($objectType, $localId) {

    if ($id = $this->db->fetchOne("SELECT qid FROM {$this->table} WHERE type = ? AND local_id = ?", array($objectType, $localId))) {
      return $id;
    }
    return FALSE;
  }

  /**
   * change the status flag
   */
  private function updateStatusFlag($item, $statusFlag) {

    $data = array(
      'status_flag' => $statusFlag,
      'timestamp' => time(),
    );
    return $this->db->update($this->table, $data, 'qid = ' . $item->qid);
  }

}

