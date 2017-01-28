INTRODUCTION
------------

Swagger(http://swagger.io/) is a powerful open source framework backed by a large ecosystem of tools that helps you design, build, document, and consume your RESTful APIs. The most popular specifications for REST APIs for a number of reasons:

* Swagger generates an interactive API console for people to quickly learn about and try the API.
* The Swagger file can be auto-generated from code annotations on a lot of different platforms.
* Swagger has a strong community with helpful contributors.

The Swagger Drupal module allows your to describe your API using a specific JSON schema that outlines the names, order, and other details of the API.
The purpose of the module is to generate a json file that can be read and interpreted by Swagger UI(http://swagger.io/swagger-ui/).


REQUIREMENTS
------------

* Swagger UI
* Swagger-php


INSTALLATION
------------

* Require Swagger UI

  Download the swagger-ui and move the swagger-ui folder under /libraries folder. So your file structure should look like this: [drupal_root]/libraries/libraries/swagger-ui/dist/swagger-ui.js
  clone https://github.com/swagger-api/swagger-ui.git

* Require Swagger-php
  
  You need to include the dependences but the swagger module already this in its composer.json.
  
  For a correct installation, you need to require in your global composer.json the packages: Composer wikimedia/composer-merge-plugin.
  
  After you need to config the merge-plugin in the composer.json example:
  
```json
{
    "require": {
        "wikimedia/composer-merge-plugin": "choose version"
    },
    "extra": {
        "merge-plugin": {
            "include": [
                "composer.local.json"
            ],
            "require": [
                "[drupal_root]/modules/[folder]/swagger/composer.json"
            ],
            "recurse": true,
            "replace": false,
            "merge-dev": true,
            "merge-extra": false,
            "merge-extra-deep": false
        }
    }
}
```

* Run composer.json

  composer [drupal_root]/composer.json


CONFIGURATION
-------------

/admin/structure/swagger-scan
Config Scan folder. A local folder system path where swagger will scan the code

/admin/structure/swagger-basic-documentation


MAINTAINERS
-----------

Current maintainers:
 * David Flórez Garcia(CO) - https://drupal.org/user/1358858