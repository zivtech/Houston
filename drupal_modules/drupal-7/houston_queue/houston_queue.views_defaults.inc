<?php

/**
 * @file Provides housotn queue default views.
 */

/**
 * Implements hook_views_default_views().
 */
function houston_queue_views_default_views() {
  $views = array();

  $view = new view;
  $view->name = 'houston_queue';
  $view->description = '';
  $view->tag = 'default';
  $view->base_table = 'houston_queue';
  $view->human_name = 'Houston Queue';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Houston Queue';
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'administer houston';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '50';
  $handler->display->display_options['pager']['options']['offset'] = '0';
  $handler->display->display_options['pager']['options']['id'] = '0';
  $handler->display->display_options['pager']['options']['expose']['items_per_page_options_all'] = 0;
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['group_rendered'] = 1;
  $handler->display->display_options['style_options']['columns'] = array(
    'qid' => 'qid',
    'local_id' => 'local_id',
    'type' => 'type',
    'operation' => 'operation',
    'attempts' => 'attempts',
    'timestamp' => 'timestamp',
    'local_blocker_id' => 'local_blocker_id',
    'local_blocker_type' => 'local_blocker_type',
    'data' => 'data',
  );
  $handler->display->display_options['style_options']['default'] = 'qid';
  $handler->display->display_options['style_options']['info'] = array(
    'qid' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'local_id' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'type' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'operation' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'attempts' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'timestamp' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'local_blocker_id' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'local_blocker_type' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'data' => array(
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
  );
  $handler->display->display_options['style_options']['override'] = 1;
  $handler->display->display_options['style_options']['sticky'] = 0;
  $handler->display->display_options['style_options']['empty_table'] = 0;
  /* Field: Houston: qid */
  $handler->display->display_options['fields']['qid']['id'] = 'qid';
  $handler->display->display_options['fields']['qid']['table'] = 'houston_queue';
  $handler->display->display_options['fields']['qid']['field'] = 'qid';
  $handler->display->display_options['fields']['qid']['label'] = 'Id';
  $handler->display->display_options['fields']['qid']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['qid']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['qid']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['qid']['alter']['external'] = 0;
  $handler->display->display_options['fields']['qid']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['qid']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['qid']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['qid']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['qid']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['qid']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['qid']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['qid']['alter']['html'] = 0;
  $handler->display->display_options['fields']['qid']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['qid']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['qid']['hide_empty'] = 0;
  $handler->display->display_options['fields']['qid']['empty_zero'] = 0;
  $handler->display->display_options['fields']['qid']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['qid']['format_plural'] = 0;
  /* Field: Houston: Local Object Id */
  $handler->display->display_options['fields']['local_id']['id'] = 'local_id';
  $handler->display->display_options['fields']['local_id']['table'] = 'houston_queue';
  $handler->display->display_options['fields']['local_id']['field'] = 'local_id';
  $handler->display->display_options['fields']['local_id']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['local_id']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['local_id']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['local_id']['alter']['external'] = 0;
  $handler->display->display_options['fields']['local_id']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['local_id']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['local_id']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['local_id']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['local_id']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['local_id']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['local_id']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['local_id']['alter']['html'] = 0;
  $handler->display->display_options['fields']['local_id']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['local_id']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['local_id']['hide_empty'] = 0;
  $handler->display->display_options['fields']['local_id']['empty_zero'] = 0;
  $handler->display->display_options['fields']['local_id']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['local_id']['format_plural'] = 0;
  /* Field: Houston: Type */
  $handler->display->display_options['fields']['type']['id'] = 'type';
  $handler->display->display_options['fields']['type']['table'] = 'houston_queue';
  $handler->display->display_options['fields']['type']['field'] = 'type';
  $handler->display->display_options['fields']['type']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['type']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['type']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['type']['alter']['external'] = 0;
  $handler->display->display_options['fields']['type']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['type']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['type']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['type']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['type']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['type']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['type']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['type']['alter']['html'] = 0;
  $handler->display->display_options['fields']['type']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['type']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['type']['hide_empty'] = 0;
  $handler->display->display_options['fields']['type']['empty_zero'] = 0;
  $handler->display->display_options['fields']['type']['hide_alter_empty'] = 1;
  /* Field: Houston: Operation */
  $handler->display->display_options['fields']['operation']['id'] = 'operation';
  $handler->display->display_options['fields']['operation']['table'] = 'houston_queue';
  $handler->display->display_options['fields']['operation']['field'] = 'operation';
  $handler->display->display_options['fields']['operation']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['operation']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['operation']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['operation']['alter']['external'] = 0;
  $handler->display->display_options['fields']['operation']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['operation']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['operation']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['operation']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['operation']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['operation']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['operation']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['operation']['alter']['html'] = 0;
  $handler->display->display_options['fields']['operation']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['operation']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['operation']['hide_empty'] = 0;
  $handler->display->display_options['fields']['operation']['empty_zero'] = 0;
  $handler->display->display_options['fields']['operation']['hide_alter_empty'] = 1;
  /* Field: Houston: Controller */
  $handler->display->display_options['fields']['controller']['id'] = 'controller';
  $handler->display->display_options['fields']['controller']['table'] = 'houston_queue';
  $handler->display->display_options['fields']['controller']['field'] = 'controller';
  $handler->display->display_options['fields']['controller']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['controller']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['controller']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['controller']['alter']['external'] = 0;
  $handler->display->display_options['fields']['controller']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['controller']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['controller']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['controller']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['controller']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['controller']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['controller']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['controller']['alter']['html'] = 0;
  $handler->display->display_options['fields']['controller']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['controller']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['controller']['hide_empty'] = 0;
  $handler->display->display_options['fields']['controller']['empty_zero'] = 0;
  $handler->display->display_options['fields']['controller']['hide_alter_empty'] = 1;
  /* Field: Houston: Attempts */
  $handler->display->display_options['fields']['process_count']['id'] = 'process_count';
  $handler->display->display_options['fields']['process_count']['table'] = 'houston_queue';
  $handler->display->display_options['fields']['process_count']['field'] = 'process_count';
  $handler->display->display_options['fields']['process_count']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['process_count']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['process_count']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['process_count']['alter']['external'] = 0;
  $handler->display->display_options['fields']['process_count']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['process_count']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['process_count']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['process_count']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['process_count']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['process_count']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['process_count']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['process_count']['alter']['html'] = 0;
  $handler->display->display_options['fields']['process_count']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['process_count']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['process_count']['hide_empty'] = 0;
  $handler->display->display_options['fields']['process_count']['empty_zero'] = 0;
  $handler->display->display_options['fields']['process_count']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['process_count']['format_plural'] = 0;
  /* Field: Houston: Time this was put into queue */
  $handler->display->display_options['fields']['timestamp']['id'] = 'timestamp';
  $handler->display->display_options['fields']['timestamp']['table'] = 'houston_queue';
  $handler->display->display_options['fields']['timestamp']['field'] = 'timestamp';
  $handler->display->display_options['fields']['timestamp']['label'] = 'Time';
  $handler->display->display_options['fields']['timestamp']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['external'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['timestamp']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['timestamp']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['html'] = 0;
  $handler->display->display_options['fields']['timestamp']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['timestamp']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['timestamp']['hide_empty'] = 0;
  $handler->display->display_options['fields']['timestamp']['empty_zero'] = 0;
  $handler->display->display_options['fields']['timestamp']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['timestamp']['date_format'] = 'long';
  /* Field: Houston: Local Blocker Id */
  $handler->display->display_options['fields']['local_blocker_id']['id'] = 'local_blocker_id';
  $handler->display->display_options['fields']['local_blocker_id']['table'] = 'houston_queue';
  $handler->display->display_options['fields']['local_blocker_id']['field'] = 'local_blocker_id';
  $handler->display->display_options['fields']['local_blocker_id']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['local_blocker_id']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['local_blocker_id']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['local_blocker_id']['alter']['external'] = 0;
  $handler->display->display_options['fields']['local_blocker_id']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['local_blocker_id']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['local_blocker_id']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['local_blocker_id']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['local_blocker_id']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['local_blocker_id']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['local_blocker_id']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['local_blocker_id']['alter']['html'] = 0;
  $handler->display->display_options['fields']['local_blocker_id']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['local_blocker_id']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['local_blocker_id']['hide_empty'] = 0;
  $handler->display->display_options['fields']['local_blocker_id']['empty_zero'] = 0;
  $handler->display->display_options['fields']['local_blocker_id']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['local_blocker_id']['format_plural'] = 0;
  /* Field: Houston: Local Blocker Type */
  $handler->display->display_options['fields']['local_blocker_type']['id'] = 'local_blocker_type';
  $handler->display->display_options['fields']['local_blocker_type']['table'] = 'houston_queue';
  $handler->display->display_options['fields']['local_blocker_type']['field'] = 'local_blocker_type';
  $handler->display->display_options['fields']['local_blocker_type']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['local_blocker_type']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['local_blocker_type']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['local_blocker_type']['alter']['external'] = 0;
  $handler->display->display_options['fields']['local_blocker_type']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['local_blocker_type']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['local_blocker_type']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['local_blocker_type']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['local_blocker_type']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['local_blocker_type']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['local_blocker_type']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['local_blocker_type']['alter']['html'] = 0;
  $handler->display->display_options['fields']['local_blocker_type']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['local_blocker_type']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['local_blocker_type']['hide_empty'] = 0;
  $handler->display->display_options['fields']['local_blocker_type']['empty_zero'] = 0;
  $handler->display->display_options['fields']['local_blocker_type']['hide_alter_empty'] = 1;
  /* Field: Houston: Other Data */
  $handler->display->display_options['fields']['data']['id'] = 'data';
  $handler->display->display_options['fields']['data']['table'] = 'houston_queue';
  $handler->display->display_options['fields']['data']['field'] = 'data';
  $handler->display->display_options['fields']['data']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['data']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['data']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['data']['alter']['external'] = 0;
  $handler->display->display_options['fields']['data']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['data']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['data']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['data']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['data']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['data']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['data']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['data']['alter']['html'] = 0;
  $handler->display->display_options['fields']['data']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['data']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['data']['hide_empty'] = 0;
  $handler->display->display_options['fields']['data']['empty_zero'] = 0;
  $handler->display->display_options['fields']['data']['hide_alter_empty'] = 1;
  /* Field: Houston: Delete Button */
  $handler->display->display_options['fields']['delete_button']['id'] = 'delete_button';
  $handler->display->display_options['fields']['delete_button']['table'] = 'houston_queue';
  $handler->display->display_options['fields']['delete_button']['field'] = 'delete_button';
  $handler->display->display_options['fields']['delete_button']['label'] = '';
  $handler->display->display_options['fields']['delete_button']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['delete_button']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['delete_button']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['delete_button']['alter']['external'] = 0;
  $handler->display->display_options['fields']['delete_button']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['delete_button']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['delete_button']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['delete_button']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['delete_button']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['delete_button']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['delete_button']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['delete_button']['alter']['html'] = 0;
  $handler->display->display_options['fields']['delete_button']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['delete_button']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['delete_button']['hide_empty'] = 0;
  $handler->display->display_options['fields']['delete_button']['empty_zero'] = 0;
  $handler->display->display_options['fields']['delete_button']['hide_alter_empty'] = 1;

  /* Display: Page */
  $handler = $view->new_display('page', 'Page', 'page');
  $handler->display->display_options['path'] = 'admin/structure/houston/queue';
  $handler->display->display_options['menu']['type'] = 'tab';
  $handler->display->display_options['menu']['title'] = 'Houston Queue';
  $handler->display->display_options['menu']['weight'] = '0';
  $views[$view->name] = $view;

  return $views;
}
