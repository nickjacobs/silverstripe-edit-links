<?php

namespace NickJacobs\EditLinks\Extensions;

use NickJacobs\EditLinks\EditLinkService;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * Gives every Elemental block an $EditLink template variable.
 * Only applied when dnadesign/silverstripe-elemental is installed.
 */
class ElementExtension extends Extension
{
    /**
     * Rendered block badge, or null when badges are off for this visitor.
     * Usage in ElementHolder.ss: $EditLink
     */
    public function EditLink(): ?DBHTMLText
    {
        $service = EditLinkService::singleton();
        $badge = $service->renderElementBadge($this->getOwner());
        if ($badge) {
            $service->requireCSS();
        }
        return $badge;
    }
}
