<?php

use Drupal\swagger\SwaggerScan;

/**
 * Function swagger_batch().
 */
function swagger_batch($arg, &$context) {
  global $base_url;
  $swagger = new SwaggerScan();
  $swagger->generateSwaggerFile($base_url);
  $message = 'Scanning code...';
  $context['message'] = $message;
}

/**
 * Function swagger_batch_finished_callback().
 */
function swagger_batch_finished_callback($success, $results, $operations) {
  if (!$success) {
    drupal_set_message(t('Finished with an error.', 'error'));
  }
}
