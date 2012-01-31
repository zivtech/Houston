<?php

/**
 * Field for removing a queue item.
 */
class houston_queue_handler_field_queue_item_remove extends views_handler_field {
  function construct() {
    parent::construct();

    $this->additional_fields['queue_id'] = 'qid';
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    $qid = $this->get_value($values, 'qid');
    return l(t('Delete'), 'admin/structure/houston/queue/' . $qid . '/delete', array('query' => array('destination' => current_path())));
  }
}
