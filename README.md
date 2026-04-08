# GrowthLab Theme 01

**Custom WordPress theme** developed for GrowthLab SEO. Modular theme, optimized for performance and SEO, with complete Advanced Custom Fields (ACF) integration and support for dynamic Gutenberg blocks.

---

## 📋 System Requirements

- **PHP:** 8.1 or higher (compatible up to PHP 8.3)
- **WordPress:** 6.0 or higher (tested up to 6.7)
- **MySQL:** 5.7 or higher
- **Node.js:** 16.0 or higher (for asset building)
- **npm:** 7.0 or higher

**Required Plugins:**

- Advanced Custom Fields Pro (ACF)
- LuckyWP ACF Menu Field (for ACF Menus)
- Gravity Forms (for forms)
- Yoast SEO or similar (recommended)
- EWWW Image Optimizer (recommended)

---

## 🚀 Installation

### Local Development

1. Clone the repository:

```bash
git clone https://github.com/GrowthLabDevAdmin/growthlabseotheme01.git
cd wp-content/themes/growthlabseotheme02
```

2. Install Node dependencies:

```bash
npm install
```

3. Compile assets (CSS/JS):

```bash
npx gulp
```

4. Activate the theme from WordPress Admin:

- Dashboard → Appearance → Themes → Activate "GrowthLab Theme 01"

### Production

- Upload theme files to `/wp-content/themes/growthlabseotheme02/`
- Activate theme from WordPress Admin
- Import ACF JSON (if needed): Dashboard → ACF → Tools → Import
- Clear cache from plugins and CDN


# 📁 Project Structure

```text
growthlabseotheme02/
├── blocks/                      # Dynamic Gutenberg blocks
│   ├── block-contact-form/      # Contact form block
│   ├── block-cta-box/           # CTA block
│   ├── block-faq/               # FAQ block
│   ├── block-locations/         # Locations block
│   ├── block-logos-carousel/    # Logos carousel
│   ├── block-posts-carousel/    # Posts carousel
│   ├── block-posts-grid/        # Posts grid
│   ├── block-selling-points/    # Selling points
│   ├── block-trust-cards/       # Trust cards
│   └── block-content-intro/     # Content intro
├── theme-functions/             # Custom functions
│   ├── [acf-functions.php]        # ACF integrations
│   ├── color-scheme.php         # Dynamic color scheme
│   ├── [helpers.php]              # Helper functions
│   ├── [picture-optimization.php] # Image optimization
│   ├── [security-functions.php]   # Security & headers
│   ├── [svg-support.php]          # SVG support & validation
│   ├── [theme-optimization.php]   # Performance optimizations
│   └── [tiny-mce.php]             # Editor configuration
├── template-parts/              # Reusable components
│   ├── [hero-default.php]         # Default hero
│   ├── [hero-homepage.php]        # Homepage hero
│   ├── [location-card.php]        # Location card
│   ├── [posts-pagination.php]     # Posts pagination
│   ├── [social-networks.php]      # Social media links
│   ├── testimonial-card.php     # Testimonial card
│   └── [more components...]
├── styles/                      # Compiled SCSS
│   ├── main.scss                # Main SCSS
│   ├── main-min.css             # Minified CSS (production)
│   ├── globals/                 # Variables, mixins, globals
│   ├── template-parts/          # Component styles
│   └── page-templates/          # Template styles
├── assets/                      # Static assets
│   ├── icons/                   # SVG icons
│   └── img/                     # Images
├── fonts/                       # Web fonts
│   ├── fraunces-v38-latin/      # Fraunces font
│   └── open-sans-v44-latin/     # Open Sans font
├── js/                          # JavaScript
│   ├── main.js                  # Main JS
│   ├── main-min.js              # Minified JS
│   └── vendor/                  # Libraries (Splide, etc.)
├── page-templates/              # Page templates
│   └── template-full-width.php  # Full-width template
├── acf-json/                    # ACF exports (synchronization)
├── [functions.php]                # Main entry point
├── [header.php]                   # Header
├── [footer.php]                   # Footer
├── [index.php]                    # Fallback template
├── [home.php]                     # Blog template
├── [single.php]                   # Single post template
├── [page.php]                     # Page template
├── [archive.php]                  # Archive template
├── [404.php]                      # 404 template
├── [gulpfile.js]                  # Gulp configuration
├── [package.json]                 # Node dependencies
└── [README.md]                    # This file
```

# 🎨 Available Blocks
The theme includes 10+ dynamic blocks ready to use in the Gutenberg editor:

Block               Description        	                            Location
Contact Form	Contact form with Gravity Forms	            blocks/block-contact-form/
CTA Box	        Call-to-action box	                        blocks/block-cta-box/
FAQ	            Accordion with FAQs	                        blocks/block-faq/
Locations	    Interactive locations map	                blocks/block-locations/
Logos Carousel	Client logos carousel	                    blocks/block-logos-carousel/
Posts Carousel	Posts/testimonials/Results carousel	        blocks/block-posts-carousel/
Posts Grid	    Filterable posts grid	                    blocks/block-posts-grid/
Selling Points	Featured selling points	                    blocks/block-selling-points/
Trust Cards	    Trust cards	                                blocks/block-trust-cards/
Content Intro	Content introduction	                    blocks/block-content-intro/

# Each block includes:

ACF support
Modular SCSS styles
Optional JavaScript (sliders, interactivity)
JSON registration
⚙️ Configuration & Usage
ACF (Advanced Custom Fields)
All ACF fields are automatically synchronized in /acf-json/.

# To import/synchronize:

Dashboard → ACF → Tools → Import
Select JSON files from /acf-json/
ACF will auto-detect and synchronize
Global Options (ACF Options):

options / options_es — Site-wide settings
hero_properties — Hero properties per page
form_section — Form section configuration
locations_section — Locations section configuration

# Dynamic Colors
The theme supports a dynamic color scheme controlled from the WordPress Customizer:

Primary Color — Main color (dark blue)
Primary Color Dark — Dark variant
Primary Color Light — Light variant
Secondary Color — Secondary color (beige)
Tertiary Color — Tertiary color (gold)
Text Color — Text color
Colors are automatically injected into CSS via --primary, --secondary, etc.

# 🖼️ Images & Media
Registered Image Sizes
The theme automatically registers these sizes:

<!--
'cover-desktop'    => 1920x1080 (no crop)
'cover-tablet'     => 1280x720 (no crop)
'cover-mobile'     => 800x533 (no crop)
'featured-small'   => 400x267 (no crop)
-->

# Optimization & Lazy Loading
Responsive images with automatic <picture> element
Progressive WebP support (if available)
Lazy loading by default on outputs
Metadata caching for better performance

Helper function:

<!-- <?php
img_print_picture_tag(
    img: $image_array,
    tablet_img: $tablet_image,
    mobile_img: $mobile_image,
    is_cover: true,
    classes: 'my-class',
    is_priority: false
);
?> -->

# 🔒 Security & SVG
SVG Validation
The theme automatically validates and sanitizes SVG uploads:

Rejects SVGs with <script> tags
Whitelist of allowed elements (path, circle, rect, g, polygon)
Whitelist of attributes (viewBox, width, height, fill, stroke, etc.)
Location: svg-support.php

Output Escaping
All dynamic outputs must be escaped by type:

<!-- 
<?= esc_html($plain_text) ?>           // Plain text
<?= esc_attr($attribute_value) ?>      // HTML attributes
<?= esc_url($link) ?>                  // URLs
<?= wp_kses_post($html_content) ?>
 -->

# ⚡ Performance & Optimization
Critical CSS
The theme injects critical CSS inline in <head> to improve First Contentful Paint (FCP):

<!-- <?php
inline_main_critical_css(); // In functions.php
?> -->

In development: Comment this function for faster CSS reloading.
In production: Activate to inject minified CSS.

JavaScript
jQuery moved to footer (safe with Gravity Forms)
jQuery Migrate disabled
Splide.js for carousels (4.1.4)
Defer and async on scripts where applicable
Active Optimizations
✅ Disable WordPress emojis (reduce load)
✅ Disable dashicons on frontend
✅ Remove RSS feeds (if not used)
✅ Lazy load Google Maps
✅ Web font preloading
✅ Disable unused core blocks

# 🛠️ Development
Asset Compilation

# Install dependencies
npm install

### Gulp Task Runner

The theme uses **Gulp** to automate asset compilation, minification, and optimization. All build tasks are configured in `gulpfile.js`.

### Available Gulp Commands

```bash
# Run all tasks (compile SCSS, minify CSS/JS, optimize images)
npx gulp

# Watch for file changes and auto-compile (recommended for development)
npx gulp watch

# Only compile SCSS to CSS
npx gulp sass

# Only minify CSS files
npx gulp minify-css

# Only minify JavaScript files
npx gulp minify-js

# Only optimize images
npx gulp images

# Clean generated files (removes all minified/compiled assets)
npx gulp clean

# Run all tasks once (no watch)
npx gulp default
```

# Gulpfile Configuration
Location: gulpfile.js (root directory)

What it does:

1. SCSS Compilation (gulp sass)

Compiles all .scss files in styles/ to .css
Generates source maps for debugging
Auto-prefixes CSS for browser compatibility
Output: styles/main-min.css

2. CSS Minification (gulp minify-css)

Minifies compiled CSS files
Reduces file size for production
Preserves comments with /*!
JavaScript Minification (gulp minify-js)

3. Minifies JS files in js/ and blocks/*/
Generates .min.js versions
Preserves code functionality

4. Watch Mode (gulp watch)

Monitors file changes in:
    styles/**/*.scss
    js/**/*.js
    blocks/**/*.{js,scss}
Auto-runs appropriate tasks on changes
Speeds up development workflow

# Development Workflow with Gulp

Step 1: Initial Setup

Navigate to theme directory
    cd wp-content/themes/growthlabseotheme02

Install dependencies (one time)
    npm install

Run Gulp to compile all assets
    npx gulp

Step 2: Start Development (Watch Mode)
    npx gulp watch (or just npx gulp)

Step 3: Make Changes
    Edit .scss files → Gulp auto-compiles to .css
    Edit .js files → Gulp auto-minifies

Step 4: Check Changes
    Reload browser (F5) to see CSS/JS changes
    Browser caching may require Ctrl+F5 or cache plugin clear

Step 5: Prepare for Production
    All minified files are ready for deployment

Gulp Task Dependencies

The gulpfile is organized with task dependencies:

    default (or 'gulp')
    ├── sass (SCSS → CSS)
    ├── minify-css (CSS → minified)
    ├── minify-js (JS → minified)
    ├── images (IMG → optimized)
    └── clean (removes old files)

    watch
    ├── Monitors styles/**/*.scss → runs sass
    ├── Monitors js/**/*.js → runs minify-js
    ├── Monitors blocks/**/*.scss → runs sass
    └── Monitors assets/img/** → runs images

File Structure for Gulp Processing

INPUT (Source)                      OUTPUT (Generated)
────────────────────────────────────────────────────
styles/main.scss                    → styles/main-min.css
styles/template-parts/_*.scss       → (compiled into main.scss)
js/main.js                          → js/main-min.js
blocks/block-*/block.js             → blocks/block-*/block-min.js
blocks/block-*/block.scss           → blocks/block-*/block-min.css


# SCSS Structure
globals — Variables, mixins, reset
template-parts — Component styles
page-templates — Template styles
Each block includes modular block.scss.

Naming Conventions

CSS Classes (BEM):

.block-name { }
.block-name__element { }
.block-name--modifier { }

SCSS Variables:
<!-- $tablet: 768px; -->

# 🔄 Recommended Workflow
For New Blocks
Create folder in blocks/block-name/
Create files:
block.json — Block registration
block.php — Template
block.scss — Styles
block.js — JavaScript (if needed)
Register in functions.php or similar
Add to ACF if it needs dynamic fields
For New Functions
Create file in theme-functions/my-function.php
Include in $includes array in functions.php
Document with PHPDoc

# 📝 Security Checklist
 Validate/sanitize all inputs (ACF, forms, etc.)
 Escape all outputs (esc_html, esc_url, etc.)
 Use wp_kses_post for HTML content
 Verify nonces in AJAX
 Use current_user_can() for permissions
 Don't store sensitive data in JavaScript
 Update dependencies regularly (npm audit)

# 🐛 Common Issues
CSS doesn't update in development
Solution: Clear browser cache (Ctrl+F5 or Cmd+Shift+R) and WP cache plugin.

Blocks don't appear in editor
Solution:
Verify ACF is activated
Reload editor page (F5)
Check block.json in each block

Images don't optimize
Solution:
Verify img_print_picture_tag() is being used
Check that image sizes are registered
Run media optimization (recommended: EWWW Image Optimizer plugin)

404 doesn't work
Solution:
Check rewrite rules (.htaccess on Apache, config on Nginx)
Verify 404.php exists in theme root
Check redirect plugins (may intercept 404)

# 📚 Resources & Documentation
ACF Pro: https://www.advancedcustomfields.com/
Gravity Forms: https://www.gravityforms.com/
Splide JS: https://splidejs.com/
WordPress Theme Handbook: https://developer.wordpress.org/themes/
WordPress Security: https://developer.wordpress.org/plugins/security/

# 📋 Improvement Areas (Roadmap)
 Add linters (ESLint, Stylelint, PHPCS)
 Set up CI/CD (GitHub Actions)
 Add unit tests (PHPUnit / Jest)
 Document individual blocks (README in each folder)
 Create demo site
 Add theme.json for Full Site Editing (FSE)
 Improve accessibility (WCAG 2.1 AA)
 Add fallback for CSS :has() selector on older browsers

# 📄 License
Proprietary — Exclusive use of GrowthLab SEO. Consult with development team for permissions or distribution.

# 👥 Support & Contact
Development Team: GrowthLab SEO Development Team
Email: arturo@growthlabseo.com
GitHub Issues: https://github.com/GrowthLabDevAdmin/growthlabseotheme01/issues

Version: 1.0.0
Last Updated: December 4, 2025
Author: GrowthLab SEO Development Team