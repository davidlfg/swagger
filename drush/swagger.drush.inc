<?php

/**
 * @file
 * Drush integration for the swagger module.
 */

use Drupal\swagger\SwaggerScan;

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
    drupal_set_message(t('---  SWAGGER WARNING ---'), 'warning');
    drupal_set_message(t('The command require: site URI example:'), 'warning');
    drupal_set_message(t('drush sws [site uri]'), 'warning');
    return;
  }
  //get drupal configurations
  $swagger = Drupal::service('config.swagger');
  $swagger->generateSwaggerFile($site_uri);
  
}
