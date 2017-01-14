<?php

use Drupal\Core\Url;

/**
 * Function swagger_batch().
 */
function swagger_batch($arg, &$context) {
  header('Content-Type: application/json');
  $config = \Drupal::config('swagger.settings');
  $swagger = \Swagger\scan('./' . $config->get('swagger_scan_folder'));
  $file_path = './' . $config->get('swagger_scan_output');
  if (file_prepare_directory($file_path, FILE_CREATE_DIRECTORY)) {
    //header swagger
    global $base_root;
    $url = $base_root . '/' . Url::fromRoute('swagger.swagger')->getInternalPath() . '?_format=json';
    $header_swagger = file_get_contents($url);
    //merge base swagger annotations and custom swagger annotations
    $swagger = json_encode(array_merge(json_decode($header_swagger, true),json_decode($swagger, true)), JSON_PRETTY_PRINT);
    
    $json_file = $file_path . '/swagger.json';
    $is_write = file_put_contents($json_file, $swagger);
  }

  // Do heavy coding here...
  $message = 'Ready spaghetti...';

  $context['message'] = $message;
}

/**
 * Function swagger_batch_finished_callback().
 */
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
