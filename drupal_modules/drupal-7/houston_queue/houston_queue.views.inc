<?php

/**
 * Implementation of hook_views_data().
 */
function houston_queue_views_data() {
  $data = array();
  $schema['houston_queue'] = array(
    'description' => 'Stores Queue of Objects for Processing',
    'fields' => array(
      'data' => array(
        'description' => 'A serialized array containing data for the object in queue',
        'type' => 'text',
        'not null' => FALSE,
        'size' => 'big'
      ),
    ),
    'indexes' => array(
      'qid' => array('qid'),
      'timestamp' => array('timestamp'),
      ),
    'primary key' => array('qid'),
  );
  $data['houston_queue'] = array(
    'table' => array(
      'base' => array(
        'field' => 'qid',
        'title' => t('Houston Queue Items'),
        'help' => t('Houston queue item listing.'),
        'weight' => 2,
      ),
      'group' => t('Houston'),
    ),
    'qid' => array(
      'title' => t('qid'),
      'help' => t('primary key: Queue id'),
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => TRUE,
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_numeric',
        'allow empty' => TRUE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_numeric',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
    ),
    'type' => array(
      'title' => t('Type'),
      'help' => t('Type of item in queue'),
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => TRUE,
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_string',
        'allow empty' => TRUE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_string',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
    ),
    'operation' => array(
      'title' => t('Operation'),
      'help' => t('What needs to happen to this item'),
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => TRUE,
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_string',
        'allow empty' => TRUE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_string',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
    ),
    'controller' => array(
      'title' => t('Controller'),
      'help' => t('The controller being acted upon'),
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => TRUE,
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_string',
        'allow empty' => TRUE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_string',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
    ),
    'local_id' => array(
      'title' => t('Local Object Id'),
      'help' => t('Local associated object ID'),
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => TRUE,
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_numeric',
        'allow empty' => TRUE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_numeric',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
    ),
    'local_blocker_id' => array(
      'title' => t('Local Blocker Id'),
      'help' => t('Local blocker / parent ID'),
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => TRUE,
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_numeric',
        'allow empty' => TRUE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_numeric',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
    ),
    'local_blocker_type' => array(
      'title' => t('Local Blocker Type'),
      'help' => t('Local blocker / parent type'),
      'field' => array(
        'handler' => 'views_handler_field',
        'click sortable' => TRUE,
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_string',
        'allow empty' => TRUE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_string',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
    ),
    'timestamp' => array(
      'title' => t('Time this was put into queue'),
      'help' => t('UNIX timestamp for when the item was updated'),
      'field' => array(
        'handler' => 'views_handler_field_date',
        'click sortable' => TRUE,
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_date',
        'allow empty' => TRUE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_date',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort_date',
      ),
    ),
    'process_count' => array(
      'title' => t('Attempts'),
      'help' => t('Number of attempts'),
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => TRUE,
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_numeric',
        'allow empty' => TRUE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_numeric',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
    ),
    'data' => array(
      'title' => t('Other Data'),
      'help' => t('A serialized array containing data for the object in queue'),
      'field' => array(
        // TODO: New handler o deal with error data?
        'handler' => 'views_handler_field',
        'click sortable' => FALSE,
      ),
      'filter' => array(
        'handler' => 'views_handler_filter_string',
        'allow empty' => TRUE,
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_string',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
    ),
    'delete_button' => array(
      'field' => array(
        'title' => t('Delete Button'),
        'help' => t('Link to delete the queue item'),
        'handler' => 'houston_queue_handler_field_queue_item_remove',
      ),
    ),
  );
  return $data;
}

