# OBX ContentHub — WordPress Content Management Plugin

AI-powered content management and deployment for Elementor-built WordPress pages. Scrape, write, upload, or generate content with Claude AI — then deploy it directly to your Elementor page templates.

**Version:** 1.1.0
**Author:** OuterBox
**Requires:** WordPress 5.8+, PHP 7.4+, Elementor 3.0.0+

---

## What It Does

ContentHub bridges the gap between content creation and Elementor page building. Instead of editing pages one-by-one in Elementor, you define reusable **template types** backed by Elementor Library templates, then populate content through four sources:

- **Write** — Manual entry with live preview
- **Scrape** — Extract content from an existing URL
- **AI Generate** — Claude AI creates content from your business profile and page context
- **Upload** — Import content from JSON or plain text files

Once content is ready, **deploy** pushes it into the Elementor page data — no manual widget editing required. Need to undo? **Rollback** restores the previous version.

A **Client Portal** lets external users manage content without WordPress admin access.

---

## Installation

1. Download the latest release zip (`contenthub-wp-X.X.X.zip`)
2. In WordPress admin: **Plugins → Add New → Upload Plugin**
3. Upload the zip and activate
4. Navigate to **ContentHub** in the admin sidebar

> Elementor (free or Pro) must be installed and activated.

---

## Getting Started

### 1. Create Template Types

Go to **ContentHub → Templates** and create a template type (e.g., "Service Page", "Location Page"). Each type represents a category of pages that share the same layout.

### 2. Assign Elementor Templates

For each template type, select an **Elementor Library template** from the dropdown. This is the layout blueprint — ContentHub reads its widget structure to determine what content fields are needed.

> To create Elementor templates: **Elementor → My Templates → Add New**. Design your page layout with placeholder content, then save it as a template.

### 3. Review Field Mapping

ContentHub auto-maps Elementor widgets to content fields:

| Widget Type | Maps To |
|---|---|
| Heading | `hero_heading`, `section_heading_1`, etc. |
| Text Editor | `hero_description`, `about_content`, etc. |
| Icon/Image Box | `services`, `features` (grouped as repeaters) |
| Accordion/Toggle | `faqs` (repeater with title + content) |
| Button | `call_to_action` |
| CTA Widget | `cta_title`, `cta_description`, `cta_button_text` |
| Testimonial | `testimonials` (repeater) |

You can customize the mapping on the template type's mapping editor.

### 4. Assign Pages

On the **Dashboard**, assign a template type to each WordPress page using the dropdown. This tells ContentHub which layout and fields that page uses.

### 5. Add Content

Click **Edit Content** on any assigned page. You'll see four tabs:

- **Scrape** — Enter a URL and ContentHub extracts content into the template fields using AI
- **Write** — Edit fields manually with a live iframe preview alongside
- **AI Generate** — Configure page context (topic, keywords, audience, goal) and let Claude write the content
- **Upload** — Import a JSON or key:value text file

### 6. Deploy

Click **Deploy** to push content into the page's Elementor data. The page is now live with the new content rendered through the Elementor template.

Click **Rollback** to restore the previous Elementor data if needed (requires "Backup before deploy" setting enabled).

---

## AI Content Generation

ContentHub uses the **Anthropic Claude API** to generate and extract content.

### Setup

1. Go to **ContentHub → Settings**
2. Enter your Anthropic API key (stored encrypted using WordPress salts)
3. Optionally fill out the **Business Profile** — this gives Claude context about your company, brand voice, and preferences

### Business Profile

The business profile feeds into every AI generation request:

- **Company basics** — Name, website, tagline, one-sentence description, geography
- **Brand voice** — Tone (Professional, Conversational, etc.), customer description, differentiators
- **B-SMART dimensions** — Brand, Size, Material, Application, Requirements, Type (useful for product/service businesses)
- **Content preferences** — Keywords to always include, topics to avoid

### Per-Page AI Context

Before generating, you can configure page-specific context:

- **Page topic** — What this specific page is about
- **Target keywords** — SEO keywords to incorporate naturally
- **Page goal** — Inform, convert, educate, sell, or support
- **Target audience** — Who this page is for
- **Reference URL** — An existing page to use as a style reference
- **Notes to AI** — Special instructions
- **Reference file** — Upload a document (.txt, .pdf, .doc, .csv, .md) with content to draw from

---

## Client Portal

The portal lets clients manage page content without logging into WordPress.

### Setup

1. Go to **ContentHub → Business Profile** and click **Generate Share Link**
2. On the **Dashboard**, check the **Client Portal** checkbox for each page you want clients to access
3. Send the share link to your client

### What Clients Can Do

- Edit the business profile
- Write, scrape, upload, or AI-generate content for visible pages
- Preview pages with live rendered Elementor output

### What Clients Cannot Do

- Deploy content to live pages
- Access WordPress admin
- See pages not enabled for the portal

The portal runs as a standalone React app at the share URL — no WordPress admin chrome. It displays your site icon and name in the header.

---

## Live Preview

The Write tab shows a **side-by-side layout**: content fields on the left, a live iframe preview on the right. As you type, the preview auto-updates after a 1-second debounce.

The preview renders the actual Elementor template with your content merged in — not a wireframe. It forces desktop viewport width (1280px) and scales to fit the panel.

Preview data is stored as a WordPress transient (5-minute TTL) and doesn't write to the database.

---

## Settings

| Setting | Description |
|---|---|
| **API Key** | Anthropic Claude API key for AI features |
| **Backup before deploy** | Save current Elementor data before overwriting (enables rollback) |
| **Clear cache after deploy** | Purge Elementor CSS cache after deployment |

---

## Architecture Overview

```
contenthub-wp/
├── contenthub-wp.php              # Plugin entry point
├── includes/
│   ├── class-contenthub-wp.php    # Bootstrap, preview rendering, portal routing
│   ├── class-rest-api.php         # All REST endpoints (admin + public)
│   ├── class-page-discovery.php   # WordPress page queries + metadata
│   ├── class-template-registry.php # Template type CRUD
│   ├── class-template-mapper.php  # Field mapping + content application
│   ├── class-elementor-parser.php # Elementor JSON tree walker
│   ├── class-content-deployer.php # Deploy/rollback Elementor data
│   ├── class-content-generator.php # Claude AI generation + extraction
│   ├── class-content-scraper.php  # URL scraping + HTML parsing
│   ├── class-business-profile.php # Profile schema + sanitization
│   ├── class-settings.php         # Settings + encrypted API key storage
│   └── class-share-token.php      # Portal token management
├── admin-ui/                      # React admin interface (@wordpress/scripts)
├── portal-ui/                     # React client portal (standalone, bundles React)
├── public/
│   └── portal.php                 # Portal HTML shell
├── assets/css/admin.css           # Admin styles
└── scripts/version-bump.sh        # Version bump + build + zip utility
```

### Key Data Storage

**Post meta (per page):**
- `_contenthub_template_type` — Assigned template slug
- `_contenthub_content_data` — Content field values (JSON)
- `_contenthub_content_source` — How content was created
- `_contenthub_content_status` — Current status (ready/deployed)
- `_contenthub_page_context` — AI generation context (JSON)
- `_contenthub_backup_elementor_data` — Pre-deploy backup

**Options (global):**
- `contenthub_api_key` — Encrypted API key
- `contenthub_settings` — Plugin settings
- `contenthub_template_types` — Template type registry
- `contenthub_template_mapping_{slug}` — Per-template field mapping
- `contenthub_business_profile` — Business profile data
- `contenthub_share_token` — Portal access token

---

## Development

### Prerequisites

- Node.js 16+
- npm

### Build

```bash
# Admin UI
cd admin-ui && npm install && npm run build

# Client Portal
cd portal-ui && npm install && npm run build
```

### Dev Mode (with watch)

```bash
cd admin-ui && npm start
# or
cd portal-ui && npm start
```

### Version Bump + Zip

```bash
./scripts/version-bump.sh 1.2.0 --zip
```

This updates the version in `contenthub-wp.php`, builds both UIs, and creates a deployable zip (excluding source files, node_modules, and dev files).

### Portal Build Note

The portal UI uses a custom `webpack.config.js` that removes `DependencyExtractionWebpackPlugin` from the default `@wordpress/scripts` config. This is required because the portal runs outside WordPress admin where React/ReactDOM globals don't exist — React must be bundled into the output.

---

## Uninstall

Deactivating the plugin leaves all data intact. **Deleting** the plugin (via WordPress admin) runs `uninstall.php`, which removes all ContentHub options and post meta from the database.

---

## License

GPL-2.0-or-later
