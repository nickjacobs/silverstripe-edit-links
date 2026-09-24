<?php

namespace NickJacobs\EditLinks;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Model\ArrayData;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\Requirements;
use TractorCow\Fluent\Extension\FluentDirectorExtension;
use TractorCow\Fluent\State\FluentState;

/**
 * Decides who sees edit badges, builds the CMS links and renders the badge
 * markup. Everything else in the module is a thin wrapper around this.
 */
class EditLinkService
{
    use Injectable;
    use Configurable;
    use Extensible;

    public const MODULE = 'nickjacobs/silverstripe-edit-links';
    public const CSS = self::MODULE . ': client/dist/edit-links.css';
    public const JS = self::MODULE . ': client/dist/edit-links.js';
    public const TEMPLATE = 'NickJacobs/EditLinks/EditLink';

    private static bool $auto_inject = true;

    private static string $view_permission = 'CMS_ACCESS_CMSMain';

    private static string $edit_permission = 'ADMIN';

    private static string $settings_permission = 'ADMIN';

    private static bool $show_public_in_dev = true;

    private static bool $show_titles = true;

    private static bool $open_in_new_tab = true;

    private static bool $cms_buttons = true;

    /**
     * Carry the locale being viewed into the CMS link when Fluent is installed.
     */
    private static bool $carry_locale = true;

    /**
     * The page being served by the current request, captured on controller
     * init so the middleware can build the manifest after rendering.
     */
    protected ?SiteTree $currentPage = null;

    /**
     * SiteConfig and Versioned reading mode as they were while the page was
     * served. Later middlewares (Subsites, Versioned) reset global state on
     * the way out, so the injection step reuses these instead.
     */
    protected ?SiteConfig $siteConfig = null;

    protected ?string $readingMode = null;

    /**
     * Fluent's locale as it was while the page was served. Fluent restores
     * its state once the inner delegate returns, so by injection time the
     * locale is gone - same reason SiteConfig and the reading mode are held.
     */
    protected ?string $locale = null;

    public function setCurrentPage(?SiteTree $page): static
    {
        $this->currentPage = $page;
        $this->siteConfig = $page ? SiteConfig::current_site_config() : null;
        $this->readingMode = $page ? Versioned::get_reading_mode() : null;
        $this->locale = $page ? static::currentLocale() : null;
        return $this;
    }

    public function getCurrentPage(): ?SiteTree
    {
        return $this->currentPage;
    }

    public function getReadingMode(): ?string
    {
        return $this->readingMode;
    }

    public function getSiteConfig(): SiteConfig
    {
        return $this->siteConfig ?? SiteConfig::current_site_config();
    }

    public function getLocale(): ?string
    {
        return $this->locale ?? static::currentLocale();
    }

    /**
     * The locale Fluent is currently in, or null when Fluent is not installed.
     */
    protected static function currentLocale(): ?string
    {
        return class_exists(FluentState::class)
            ? (FluentState::singleton()->getLocale() ?: null)
            : null;
    }

    public static function elementalInstalled(): bool
    {
        return ClassInfo::exists('DNADesign\Elemental\Models\BaseElement');
    }

    public static function isElement(DataObject $record): bool
    {
        return static::elementalInstalled()
            && is_a($record, 'DNADesign\Elemental\Models\BaseElement');
    }

    /**
     * Can the current visitor see badges at all?
     */
    public function canView(): bool
    {
        if (Permission::check($this->config()->get('view_permission'))) {
            return true;
        }

        if ($this->config()->get('show_public_in_dev')
            && (Director::isDev() || Director::isTest())
            && $this->getSiteConfig()->ElementEditLinkShowPublic
        ) {
            return true;
        }

        return false;
    }

    /**
     * Can the current visitor see the "edit" link inside the badge?
     */
    public function canEdit(): bool
    {
        return (bool) Permission::check($this->config()->get('edit_permission'));
    }

    public function canViewSettings(): bool
    {
        return (bool) Permission::check($this->config()->get('settings_permission'));
    }

    public function pageBadgesEnabled(): bool
    {
        return $this->getSiteConfig()->PageEditLinkEnable && $this->canView();
    }

    public function elementBadgesEnabled(): bool
    {
        return static::elementalInstalled()
            && $this->getSiteConfig()->ElementEditLinkEnable
            && $this->canView();
    }

    public function renderPageBadge(SiteTree $page): ?DBHTMLText
    {
        if (!$this->pageBadgesEnabled()) {
            return null;
        }

        return $this->renderBadge('page', $page, $page->getCMSEditLink());
    }

    public function renderElementBadge(DataObject $element): ?DBHTMLText
    {
        if (!$this->elementBadgesEnabled() || !static::isElement($element)) {
            return null;
        }

        return $this->renderBadge('element', $element, $element->getCMSEditLink(true));
    }

    protected function renderBadge(string $type, DataObject $record, ?string $link): DBHTMLText
    {
        // Elemental returns links relative to the base URL; make them safe
        // to use from any page depth.
        $link = $link ? Director::absoluteURL($this->withLocale($link)) : null;
        $canEdit = $this->canEdit() && $link;

        $data = ArrayData::create([
            'Type' => $type,
            'ClassShortName' => ClassInfo::shortName($record),
            'RecordID' => $record->ID,
            'Title' => $this->config()->get('show_titles') ? $record->Title : null,
            'Link' => $canEdit ? $link : null,
            'CanEdit' => $canEdit,
            'NewTab' => (bool) $this->config()->get('open_in_new_tab'),
            'Record' => $record,
        ]);

        $this->extend('updateBadgeData', $data, $type, $record);

        return $data->renderWith(static::TEMPLATE);
    }

    /**
     * Carry the locale being viewed into a CMS link.
     *
     * Fluent resolves the CMS locale from the query param first, then from a
     * persisted cookie. Without the param, an editor clicking through from a
     * non-default locale lands in whichever locale they last used in the CMS,
     * where the record usually will not resolve at all. Worse, Fluent persists
     * whatever it resolved, so they can carry on editing the wrong locale with
     * no obvious signal.
     *
     * No-ops when Fluent is not installed, so the module keeps working without
     * it - Fluent is not a dependency.
     */
    protected function withLocale(string $link): string
    {
        if (!$this->config()->get('carry_locale')) {
            return $link;
        }

        $locale = $this->getLocale();

        if (!$locale) {
            return $link;
        }

        $param = FluentDirectorExtension::config()->get('query_param') ?: 'l';

        // join_links merges with any query string the CMS link already carries
        return Controller::join_links($link, '?' . $param . '=' . urlencode($locale));
    }

    /**
     * Pull in the stylesheet when badges are placed via $EditLink in templates.
     * In auto-inject mode the middleware writes the tags itself.
     */
    public function requireCSS(): void
    {
        if (!$this->config()->get('auto_inject')) {
            Requirements::css(static::CSS);
        }
    }

    /**
     * Everything the front-end script needs to attach badges to the DOM.
     *
     * @return array{page: ?string, elements: array<int, array{anchor: string, id: int, html: string}>}|null
     */
    public function buildManifest(SiteTree $page): ?array
    {
        $manifest = ['page' => null, 'elements' => []];

        if ($badge = $this->renderPageBadge($page)) {
            $manifest['page'] = $badge->forTemplate();
        }

        if ($this->elementBadgesEnabled()) {
            foreach ($this->collectElements($page) as $element) {
                $badge = $this->renderElementBadge($element);
                if (!$badge) {
                    continue;
                }
                $manifest['elements'][] = [
                    'anchor' => $element->getAnchor(),
                    'id' => (int) $element->ID,
                    'html' => $badge->forTemplate(),
                ];
            }
        }

        $this->extend('updateManifest', $manifest, $page);

        if (!$manifest['page'] && !$manifest['elements']) {
            return null;
        }

        return $manifest;
    }

    /**
     * Walk every elemental area on the owner, recursing into blocks that
     * themselves hold areas (for example an element list).
     *
     * @return DataObject[]
     */
    protected function collectElements(DataObject $owner, array &$seen = []): array
    {
        $elements = [];

        if (!$owner->hasMethod('getElementalRelations')) {
            return $elements;
        }

        foreach ((array) $owner->getElementalRelations() as $relation) {
            $area = $owner->getComponent($relation);
            if (!$area || !$area->exists()) {
                continue;
            }
            foreach ($area->Elements() as $element) {
                if (isset($seen[$element->ID])) {
                    continue;
                }
                $seen[$element->ID] = true;
                $elements[] = $element;
                foreach ($this->collectElements($element, $seen) as $nested) {
                    $elements[] = $nested;
                }
            }
        }

        return $elements;
    }
}
