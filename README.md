Requirements
--------------

clone https://github.com/swagger-api/swagger-ui.git and move the "swagger-ui" folder to /libraries/
Check the path file: /libraries/swagger-ui/dist/swagger-ui.js:

Usage
-----

Configure the composer.json file and after to do it, you could execute composer install for download the swagger module dependences.

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
                "submodule/modules/custom/swagger/composer.json"
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