<?php

/**
 * @file
 * Contains \Drupal\swagger\Controller\SwaggerBatchController.
 */

namespace Drupal\swagger\Controller;

class SwaggerBatchController {

  public function runBatch() {
    $batch = array(
      'title' => t('Processing'),
      'operations' => array(
        array('swagger_batch', array('arg'))),
      'finished' => 'swagger_batch_finished_callback',
      'file' => drupal_get_path('module', 'swagger') . '/swagger.batch.inc',
    );

    batch_set($batch);
    $path = \Drupal::service('url_generator')->getPathFromRoute('swagger.admin');
    return batch_process($path);
  }
}
