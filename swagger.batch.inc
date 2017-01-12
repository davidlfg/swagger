<?php

function swagger_batch($arg, &$context) {
  header('Content-Type: application/json');
  $config = \Drupal::config('swagger.settings');
  $swagger = \Swagger\scan('./' . $config->get('swagger_scan_folder'));
  $file_path = './' . $config->get('swagger_scan_output');
  if (file_prepare_directory($file_path, FILE_CREATE_DIRECTORY)) {
    $json_file = $file_path . '/swagger.json';
    $is_write = file_put_contents($json_file, $swagger);
  }

  // Do heavy coding here...
  $message = 'Ready spaghetti...';

  $context['message'] = $message;
}

function swagger_batch_finished_callback($success, $results, $operations) {
  // The 'success' parameter means no fatal PHP errors were detected. All
  // other error management should be handled using 'results'.
  if ($success) {
    $message = \Drupal::translation()->formatPlural(
      count($results),
      'One post processed.', '@count posts processed.'
    );
  }
  else {
    $message = t('Finished with an error.');
  }
  drupal_set_message($message);
}
