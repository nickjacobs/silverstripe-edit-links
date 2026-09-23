<?php

namespace NickJacobs\EditLinks\Middleware;

use NickJacobs\EditLinks\EditLinkService;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Manifest\ModuleResourceLoader;

/**
 * After the page has rendered, append the badge manifest plus the module's
 * CSS and JS just before </body>. Runs post-render so element anchors are
 * exactly those already written into the HTML.
 */
class EditLinkMiddleware implements HTTPMiddleware
{
    public function process(HTTPRequest $request, callable $delegate)
    {
        /** @var HTTPResponse $response */
        $response = $delegate($request);

        if (!EditLinkService::config()->get('auto_inject')) {
            return $response;
        }

        if (!$response instanceof HTTPResponse || $response->getStatusCode() !== 200 || $request->isAjax()) {
            return $response;
        }

        if (!str_contains((string) $response->getHeader('Content-Type'), 'text/html')) {
            return $response;
        }

        $service = EditLinkService::singleton();
        $page = $service->getCurrentPage();
        if (!$page) {
            return $response;
        }

        $body = $response->getBody();
        $pos = strripos($body, '</body>');
        if ($pos === false) {
            return $response;
        }

        $manifest = $service->buildManifest($page);
        if (!$manifest) {
            return $response;
        }

        $loader = ModuleResourceLoader::singleton();
        $json = json_encode($manifest, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES);

        $tags = sprintf(
            "\n<link rel=\"stylesheet\" href=\"%s\">\n<script type=\"application/json\" id=\"ss-edit-links-data\">%s</script>\n<script src=\"%s\" defer></script>\n",
            htmlspecialchars($loader->resolveURL(EditLinkService::CSS), ENT_QUOTES),
            $json,
            htmlspecialchars($loader->resolveURL(EditLinkService::JS), ENT_QUOTES)
        );

        $response->setBody(substr($body, 0, $pos) . $tags . substr($body, $pos));

        return $response;
    }
}
