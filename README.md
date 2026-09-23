# SilverStripe Edit Links

Front-end edit badges for SilverStripe pages and Elemental blocks. Editors browsing the live site see a small pencil badge on the page and on every block; hovering reveals the class name, record ID and title, and admins get a link straight to the CMS edit form.

By default the module **auto-injects** the badges into every HTML response, so no template changes are needed. A `$EditLink` template variable is available if you'd rather place them yourself.

## Requirements

- SilverStripe Framework and CMS 6
- `dnadesign/silverstripe-elemental` 6 (optional, enables block badges and CMS buttons)

## Installation

```bash
composer require nickjacobs/silverstripe-edit-links
```

Run `dev/build?flush=1`, then go to **Settings > Edit links** in the CMS and tick the badges you want on. The tab is only shown to members with the `settings_permission` (ADMIN by default).

## How it works

1. A `SiteTree` extension captures the page being served.
2. After the page has rendered, an HTTP middleware builds a manifest: the page badge HTML plus one badge per Elemental block keyed by the block's anchor id, the same `id="$Anchor"` Elemental writes on every holder.
3. It appends the manifest, the stylesheet and a small script before `</body>`.
4. The script drops the page badge into `<body>` and each block badge into its holder, adding `position: relative` where needed.

Nothing is written to the response for visitors who can't see badges, and CMS preview requests are skipped.

Blocks are walked recursively, so nested areas (an element list, for example) get badges too. A block rendered without its holder, or with a custom holder that drops `id="$Anchor"`, is simply skipped.

## Manual placement

Set `auto_inject: false` and put `$EditLink` where you want the badge:

```yaml
NickJacobs\EditLinks\EditLinkService:
  auto_inject: false
```

```html
<%-- Page.ss --%>
<body>
    $EditLink
    ...

<%-- DNADesign/Elemental/Layout/ElementHolder.ss --%>
<div class="element $SimpleClassName.LowerCase" id="$Anchor">
    $Element
    $EditLink
</div>
```

`$EditLink` renders nothing when badges are disabled or the visitor can't see them, and pulls in the stylesheet via `Requirements` when it does render. The badge is absolutely positioned; the stylesheet already makes an `.element` holder relative when it contains one.

## Configuration

```yaml
NickJacobs\EditLinks\EditLinkService:
  auto_inject: true                     # inject via middleware, no template edits
  view_permission: CMS_ACCESS_CMSMain   # who sees the badge
  edit_permission: ADMIN                # who sees the "edit" link inside it
  settings_permission: ADMIN            # who sees Settings > Edit links
  show_public_in_dev: true              # honour the SiteConfig "show to public" flag in dev/test
  show_titles: true                     # include the record Title in the badge
  open_in_new_tab: true                 # edit links open in a new tab
  cms_buttons: true                     # Back to page / View live / Preview draft on block edit forms
```

### SiteConfig switches

| Field | Purpose |
| --- | --- |
| `PageEditLinkEnable` | Turn page badges on |
| `ElementEditLinkEnable` | Turn block badges on (Elemental only) |
| `ElementEditLinkShowPublic` | In dev or test mode, show badges to logged-out visitors. The edit link still requires `edit_permission`. |

### Styling

Colours and sizing are CSS custom properties. Override them in your own stylesheet:

```css
:root {
    --ss-edit-link-bg: #0ea5e9;
    --ss-edit-link-page-bg: #0369a1;
    --ss-edit-link-fg: #fff;
    --ss-edit-link-pill-bg: #fff;
    --ss-edit-link-pill-fg: #1f2937;
    --ss-edit-link-font-size: 11px;
    --ss-edit-link-offset: 12px;
    --ss-edit-link-z: 10;
    --ss-edit-link-page-z: 999;
}
```

### Extension hooks

- `EditLinkService::updateBadgeData(ArrayData $data, string $type, DataObject $record)` to add fields to the badge template.
- `EditLinkService::updateManifest(array &$manifest, SiteTree $page)` to add or remove entries before injection.
- Override the template at `templates/NickJacobs/EditLinks/EditLink.ss` in your project.

## CMS buttons

With Elemental installed and `cms_buttons` on, a block's edit form in the CMS gains three actions: **Back to page**, **View live** and **Preview draft**. Set `cms_buttons: false` to drop them.

## Development

The stylesheet is compiled from `client/src/edit-links.scss`; the built file is committed.

```bash
sass --no-source-map --style=compressed client/src/edit-links.scss client/dist/edit-links.css
```

## Licence

BSD-3-Clause.
