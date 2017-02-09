<?php

namespace Drupal\swagger;

use Swagger\Analysis;
use Swagger\Processors\BuildPaths;
use Swagger\Processors\MergeIntoSwagger;
use \JsonSerializable;
use \Drupal\swagger\SwaggerResourcesFactory;

/**
 * Defines a factory for logging channels.
 */
class SwaggerAnalysisFactory {

  /**
   * Structure swagger info annotation.
   *
   * @var \Swagger\Annotations\Info
   */
  private $swaggerInfoAnnotation;

  /**
   * Structure swagger root annotation.
   *
   * @var \Swagger\Annotations\Swagger
   */
  private $swaggerRootAnnotation;

  /**
   * Structure swagger info annotation.
   *
   * @var \Drupal\swagger\SwaggerResourcesFactory
   */
  private $swaggerResourcesAnnotation;

  /**
   * Class constructor.
   */
  public function __construct(JsonSerializable $info_annotation, JsonSerializable $root_annotation, SwaggerResourcesFactory $resources_annotation) {
    $this->swaggerInfoAnnotation = $info_annotation;
    $this->swaggerRootAnnotation = $root_annotation;
    $this->swaggerResourcesAnnotation = $resources_annotation;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnalysis() {
    $objects_to_analysis = array_merge(
      [$this->swaggerRootAnnotation, $this->swaggerInfoAnnotation],
      $this->swaggerResourcesAnnotation->swaggerPathsResourcesObject()
    );
    $analysis = new Analysis($objects_to_analysis);
    $analysis->process([
      new MergeIntoSwagger(),
      new BuildPaths(),
    ]);
    $analysis->validate();
    return $analysis;
  }

}
