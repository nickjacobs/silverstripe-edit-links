<?php

namespace NickJacobs\EditLinks\Extensions;

use NickJacobs\EditLinks\EditLinkService;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * Gives every page an $EditLink template variable and tells the service
 * which page is being served so the middleware can inject badges.
 */
class SiteTreeExtension extends Extension
{
    public function contentcontrollerInit(ContentController $controller): void
    {
        if ($controller->getRequest()->getVar('CMSPreview')) {
            return;
        }
        EditLinkService::singleton()->setCurrentPage($this->getOwner());
    }

    /**
     * Rendered page badge, or null when badges are off for this visitor.
     * Usage in templates: $EditLink
     */
    public function EditLink(): ?DBHTMLText
    {
        $service = EditLinkService::singleton();
        $badge = $service->renderPageBadge($this->getOwner());
        if ($badge) {
            $service->requireCSS();
        }
        return $badge;
    }
}
