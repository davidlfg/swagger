<?php

namespace Drupal\swagger;

use Swagger\Analysis;
use Swagger\Processors\BuildPaths;
use Swagger\Processors\MergeIntoSwagger;
use \JsonSerializable;

use Swagger\Annotations\Get;

/**
 * Defines a factory for logging channels.
 */
class SwaggerAnalysisFactory {
  
  private $swaggerInfoAnnotation;
  
  private $swaggerRootAnnotation;
  
  public function __construct(JsonSerializable $info_annotation, JsonSerializable $root_annotation) {
    $this->swaggerInfoAnnotation = $info_annotation;
    $this->swaggerRootAnnotation = $root_annotation;
  }
  
  public function getAnalysis() {
   /* $data = [
      /*'/user/{user}' => [
        'get' => [
          "description" => "Returns all pets from the system that the user has access to",
          "produces" => [
            "application/json",
          ],
        ],
      ],
    ];*/
    $data['path'] ='/user/{user}';
    $data['summary'] = 'ccsss';
    $data['responses'] = [];
    $data['tags'] = ['REST resources'];
    $data['method'] = 'ger';
    
    $analysis = new Analysis([
      $this->swaggerRootAnnotation,
      $this->swaggerInfoAnnotation,
      new Get($data),
    ]);
    $analysis->process([
      new MergeIntoSwagger(),
      new BuildPaths(),
    ]);
    $analysis->validate();
    return $analysis;
  }
}