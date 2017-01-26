<?php

/**
 * Function swagger_batch().
 */
function swagger_batch($arg, &$context) {
  global $base_url;
  $output_swagger_message = array();
  $output_swagger_message[] = t('----  SWAGGER SCANED ---');
  _swagger_scan_and_save($base_url, $output_swagger_message);
  $message = 'Scanning code...';
  $context['message'] = $message;
  $context['results'] = $output_swagger_message;
}

/**
 * Function swagger_batch_finished_callback().
 */
function swagger_batch_finished_callback($success, $results, $operations) {
  if ($success) {
    foreach ($results as $line_message) {
      drupal_set_message($line_message);
    }
  }
  else {
    $message = t('Finished with an error.');
  }
}
