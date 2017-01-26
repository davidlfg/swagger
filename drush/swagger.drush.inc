<?php

/**
 * @file
 * Drush integration for the swagger module.
 */

/**
 * Implements hook_drush_command().
 */
function swagger_drush_command() {
  $items = array();
  $items['swagger-scan'] = [
    'description' => 'Run the scan code.',
    'arguments' => [
      'site_uri' => ''  
    ],
    'drupal dependencies' => ['swagger'],
    'aliases' => ['sws'],
  ];

  return $items;
}

/**
 * Command callback to run the scan code.
 */
function drush_swagger_scan($site_uri = NULL) {
  if (is_null($site_uri)) {
    drush_print('Enter site URI');
    drush_print('drush sws [site uri]');
    return;
  }
  $output_swagger_message = array();
  $output_swagger_message[] = t('----  SWAGGER SCANED ---');
  //get drupal configurations
  $config = \Drupal::config('swagger.settings');
  $scan_folder = './' . $config->get('swagger_scan_folder');
  $file_path = './' . $config->get('swagger_scan_output');
  $json_file = $file_path . '/swagger.json';
  _swagger_scan_and_save($site_uri, $output_swagger_message);
  
  foreach ($output_swagger_message as $line_message) {
   drupal_set_message($line_message);
  }
  
}
