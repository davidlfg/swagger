<?php

namespace Drupal\swagger;

/**
 * Provides an interface defining a custom scan code.
 */
interface SwaggerScanInterface {

  /**
   * Execute the scan and save
   *
   * @param string $site_uri
   *   necessary by Drush command.
   * @param array $output_swagger_message
   *   Contains the messages for the batch
   */
  public function generateSwaggerFile($base_url);
  
}