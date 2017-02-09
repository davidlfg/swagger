<?php

namespace Drupal\swagger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Swagger\Annotations\Info;
use Swagger\Annotations\Swagger;

/**
* Defines a factory for SwaggerAnnotationFactory.
*/
class SwaggerAnnotationFactory {

  private $config;
  
  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config->getEditable('swagger.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getSwaggerInfo() {
    $swagger_info = [
      "title" => $this->config->get('swagger_info_title'),
      "description" => $this->config->get('swagger_info_description'),
      "termsOfService" => $this->config->get('swagger_info_terms_service'),
      "version" => $this->config->get('swagger_info_version'),
    ];
    if ($this->config->get('contact_needs')) {
      $swagger_info["contact"] = [
      "name" => $this->config->get('swagger_info_contact_name'),
      "url" => $this->config->get('swagger_info_contact_url'),
      "email" => $this->config->get('swagger_info_contact_email'),
      ];
    }
    if ($this->config->get('license_needs')) {
      $swagger_info["license"] = [
      "name" => $this->config->get('swagger_info_license_name'),
      "url" => $this->config->get('swagger_info_license_url'),
      ];
    }
    return new Info($swagger_info);
  }

  /**
   * {@inheritdoc}
   */
  public function getSwaggerRoot() {
    $resources = \Drupal::service('swagger.annotation.resources');
    $swagger_root = [
      'swagger' => $this->config->get('swagger_swagger_version'),
      'schemes' => $this->config->get('swagger_swagger_schemes'),
      'consumes' => $this->config->get('swagger_swagger_consumes'),
      'produces' => $this->config->get('swagger_swagger_produces'),
    ];
    $swagger = new Swagger($swagger_root);
    return $swagger;
  }

}
