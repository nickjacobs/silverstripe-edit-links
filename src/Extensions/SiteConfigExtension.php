<?php

namespace NickJacobs\EditLinks\Extensions;

use NickJacobs\EditLinks\EditLinkService;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;

/**
 * Runtime on/off switches for edit badges, kept in SiteConfig so they can be
 * toggled without a deploy. Column names match the original altus-corporate
 * implementation so existing settings carry over on dev/build.
 */
class SiteConfigExtension extends Extension
{
    private static array $db = [
        'PageEditLinkEnable' => 'Boolean',
        'ElementEditLinkEnable' => 'Boolean',
        'ElementEditLinkShowPublic' => 'Boolean',
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $service = EditLinkService::singleton();

        if (!$service->canViewSettings()) {
            return;
        }

        $tab = 'Root.EditLinks';
        $fields->findOrMakeTab($tab, 'Edit links');

        $fields->addFieldsToTab($tab, [
            HeaderField::create('EditLinksPageHeader', 'Page edit links', 2),
            CheckboxField::create('PageEditLinkEnable', 'Enable page edit badges')
                ->setDescription('Shows a page info badge with a CMS edit link to logged-in editors'),
        ]);

        if (EditLinkService::elementalInstalled()) {
            $fields->addFieldsToTab($tab, [
                HeaderField::create('EditLinksElementHeader', 'Block edit links', 2),
                CheckboxField::create('ElementEditLinkEnable', 'Enable block edit badges')
                    ->setDescription('Shows a badge on every block with a CMS edit link to logged-in editors'),
            ]);
        }

        if ($service->config()->get('show_public_in_dev')) {
            $fields->addFieldsToTab($tab, [
                HeaderField::create('EditLinksDevHeader', 'Development', 2),
                CheckboxField::create('ElementEditLinkShowPublic', 'Show badges to logged-out visitors in dev and test mode')
                    ->setDescription('Never applies in live mode. The edit link itself still requires permission.'),
            ]);
        }
    }
}
