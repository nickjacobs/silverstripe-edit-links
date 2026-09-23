<?php

namespace NickJacobs\EditLinks\Extensions;

use NickJacobs\EditLinks\EditLinkService;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;

/**
 * The reverse direction: from a block's CMS edit form, jump back to its page
 * or open the live / draft front end at that block.
 * Only applied when dnadesign/silverstripe-elemental is installed.
 */
class GridFieldItemRequestExtension extends Extension
{
    public function updateFormActions(FieldList $actions): void
    {
        if (!EditLinkService::config()->get('cms_buttons')) {
            return;
        }

        $record = $this->getOwner()->getRecord();
        if (!$record || !$record->exists() || !EditLinkService::isElement($record)) {
            return;
        }

        $page = $record->getPage();
        if ($page && $page->hasMethod('getCMSEditLink') && ($pageLink = $page->getCMSEditLink())) {
            $actions->push($this->button(
                'EditLinksBackToPage',
                $pageLink,
                'Back to page',
                'btn-outline-secondary font-icon-left-open-big'
            ));
        }

        $previewLink = $record->PreviewLink();
        if (!$previewLink) {
            return;
        }

        $actions->push($this->button(
            'EditLinksViewLive',
            $previewLink,
            'View live',
            'btn-outline-primary font-icon-eye',
            true
        ));

        $actions->push($this->button(
            'EditLinksPreviewDraft',
            Controller::join_links($previewLink, '?stage=Stage'),
            'Preview draft',
            'btn-outline-primary font-icon-eye',
            true
        ));
    }

    protected function button(string $name, string $href, string $label, string $classes, bool $newTab = false): LiteralField
    {
        return LiteralField::create($name, sprintf(
            '<a href="%s" class="btn action %s"%s>%s</a>',
            htmlspecialchars($href, ENT_QUOTES),
            htmlspecialchars($classes, ENT_QUOTES),
            $newTab ? ' target="_blank" rel="noopener"' : '',
            htmlspecialchars($label, ENT_QUOTES)
        ));
    }
}
