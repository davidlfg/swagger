/**
 * @file
 * Provides Swagger integration.
 */

(function ($, Drupal, drupalSettings) {

  // SwaggerUI expects $ to be defined as the jQuery object.
  // @todo File a patch to Swagger UI to not require this.
  window.$ = $;

  Drupal.behaviors.AJAX = {
    attach: function (context, settings) {
      var url = drupalSettings.swagger.swagger_json_url;
      /*
       hljs.configure({
       highlightSizeThreshold: 5000
       });
       */

      // Pre load translate...
      if (window.SwaggerTranslator) {
        window.SwaggerTranslator.translate();
      }
      window.swaggerUi = new SwaggerUi({
        url: url,
        validatorUrl: undefined,
        dom_id: "swagger-ui-container",
        supportedSubmitMethods: ['get', 'post', 'put', 'delete', 'patch'],
        onComplete: function (swaggerApi, swaggerUi) {
          if (typeof initOAuth == "function") {
            initOAuth({
              clientId: "your-client-id",
              clientSecret: "your-client-secret-if-required",
              realm: "your-realms",
              appName: "your-app-name",
              scopeSeparator: " ",
              additionalQueryStringParams: {}
            });
          }

          if (window.SwaggerTranslator) {
            window.SwaggerTranslator.translate();
          }
        },
        onFailure: function (data) {
          log("Unable to Load SwaggerUI");
        },
        docExpansion: "list",
        jsonEditor: false,
        defaultModelRendering: 'schema',
        showRequestHeaders: false
      });

      window.swaggerUi.load();

      function log() {
        if ('console' in window) {
          console.log.apply(console, arguments);
        }
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
