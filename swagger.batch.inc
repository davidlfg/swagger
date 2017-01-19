<?php

/**
 * Function swagger_batch().
 */
function swagger_batch($arg, &$context) {
  global $base_root;
  $output_swagger_message = array();
  $output_swagger_message[] = t('----  SWAGGER SCANED ---');
  $config = \Drupal::config('swagger.settings');
  $scan_folder = './' . $config->get('swagger_scan_folder');
  $file_path = './' . $config->get('swagger_scan_output');
  $json_file = $file_path . '/swagger.json';
  $swagger = _swagger_scan_and_prepare($scan_folder, $file_path, $base_root, $output_swagger_message);
  //save swagger json file
  $is_write = file_put_contents($json_file, $swagger);
  $output_swagger_message[] = t('Written to: ') . realpath($json_file);
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
