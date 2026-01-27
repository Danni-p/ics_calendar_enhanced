=== ICS Calendar Enhanced ===
Contributors: daniel
Tags: calendar, ics, events, category images, icalendar
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Extends the ICS Calendar plugin with category images mapping. Assign custom images to calendar event categories through an intuitive admin interface.

== Description ==

ICS Calendar Enhanced is an extension plugin for the popular ICS Calendar plugin. It allows you to map category strings from your ICS calendar feeds to custom images, creating a more visually appealing calendar display.

= Key Features =

* **Category Image Mapping**: Assign images to specific category strings from your ICS feeds
* **Two-Tier Fallback System**: 
  * Specific category mapping
  * Fallback for unmapped categories (or bundled default if not set)
* **Easy Admin Interface**: User-friendly settings page with drag-and-drop image selection
* **WordPress Media Library Integration**: Select images directly from your media library
* **Shortcode Support**: Display category images anywhere using `[ics_category_image]`
* **Developer Friendly**: Hooks and filters for customization

= How It Works =

1. Install and activate the plugin (requires ICS Calendar or ICS Calendar Pro)
2. Go to Settings → ICS Calendar Enhanced
3. Add category mappings by entering the category string and selecting an image
4. Set optional fallback images for unmapped categories
5. The images will automatically display alongside events in your calendar

= Shortcode Usage =

Display a category image anywhere on your site:

`[ics_category_image category="Meeting" size="thumbnail"]`

= For Developers =

Get a category image programmatically:

`<?php
$image_url = \ICSEnhanced\Includes\Helpers::get_category_image( 'Meeting' );
$image_html = \ICSEnhanced\Includes\Helpers::get_category_image_html( 'Meeting', 'thumbnail' );
?>`

Use the filter hook:

`<?php
$image_url = apply_filters( 'ics_enhanced_get_category_image', '', 'Meeting', 'full' );
?>`

== Installation ==

1. Ensure you have the ICS Calendar or ICS Calendar Pro plugin installed and activated
2. Upload the `ics-calendar-enhanced` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings → ICS Calendar Enhanced to configure your category mappings

That's it! No additional setup required. The plugin uses a built-in autoloader and requires no external dependencies.

== Frequently Asked Questions ==

= Does this plugin require ICS Calendar? =

Yes, this plugin extends the ICS Calendar plugin and requires it to be installed and activated.

= What happens if a category doesn't have a mapped image? =

The plugin uses a three-tier fallback system:
1. First, it checks for a specific category mapping
2. If not found, it uses the fallback image (if set)
3. If no fallback is set, it uses the bundled default image

= Can I use multiple images per category? =

Currently, each category can have one image assigned. The category string must match exactly (case-insensitive) what appears in your ICS feed.

= How do I find the correct category string? =

The category string should match what's in your ICS calendar feed. You can view your feed source or check the event details in ICS Calendar's output.

== Screenshots ==

1. Settings page with category mappings table
2. Adding a new category mapping with image selection
3. Fallback image configuration
4. Category images displayed in calendar view

== Changelog ==

= 1.0.0 =
* Initial release
* Category string to image mapping
* Three-tier fallback system
* Admin settings page with media library integration
* Shortcode support
* Developer hooks and filters

== Upgrade Notice ==

= 1.0.0 =
Initial release of ICS Calendar Enhanced.




Visual & Display Enhancements
1. Category color coding
Assign colors to categories (in addition to images)
Color-coded event borders, backgrounds, or badges
Use case: Quick visual category identification

2. Category icons library
Built-in icon set (Font Awesome, Dashicons, or custom SVG)
Icon + image options per category
Use case: Lightweight alternative to images

3. Image size presets
Per-view size settings (month/list/week)
Responsive image sizes
Use case: Better display across views

4. Image positioning options
Above title, beside title, as background, or in description
Use case: Layout flexibility
Functionality Enhancements

5. Multiple images per category
Rotate images or show different images by date/context
Use case: Seasonal or context-specific images

6. Category-based styling
Custom CSS classes per category
Custom styling options in admin
Use case: Category-specific styling

7. Category filtering
Filter calendar by category
Category badges with click-to-filter
Use case: Focused event views

8. Category grouping/aliases
Map multiple category strings to one image
Case-insensitive matching option
Use case: Handle variations in feed data

9. Conditional image display
Show/hide images based on event properties (date range, location, etc.)
Use case: Context-aware display
Admin Experience

10. Bulk import/export
CSV import/export of category mappings
Use case: Manage many mappings efficiently

11. Category auto-discovery
Scan ICS feeds and suggest categories
Use case: Easier setup

12. Preview mode
Preview how images look in different calendar views
Use case: Test before publishing

13. Image optimization
Auto-generate thumbnails
WebP conversion option
Lazy loading controls
Use case: Performance
Developer Features

14. REST API endpoints
Get category mappings via REST API
Use case: Headless/JS integrations

15. Advanced hooks
More granular hooks for customization
Event-specific image override hooks
Use case: Theme/plugin integration

16. Template system
Customizable templates for category image display
Use case: Design control
Integration Features

17. WooCommerce integration
Link categories to product categories
Display product images for event categories
Use case: E-commerce calendar events

18. ACF (Advanced Custom Fields) integration
Map ACF fields to category images
Use case: Custom field-driven images

19. Event calendar plugin compatibility
Support for other calendar plugins
Use case: Broader compatibility
Performance & UX

20. Caching system
Cache category mappings and image URLs
Transient-based caching
Use case: Faster page loads

21. Lazy loading controls
Admin toggle for lazy loading
Intersection Observer configuration
Use case: Performance tuning

22. Accessibility improvements
ARIA labels for category images
Screen reader support
Keyboard navigation
Use case: Better accessibility
Analytics & Insights

23. Category usage statistics
Track which categories appear most
Unmapped category detection
Use case: Data-driven setup

24. Image usage tracking
Track which images are used most
Identify unused images
Use case: Optimization insights

Recommended Priority Order
High priority (quick wins, high impact):

Category color coding (#1)
Image positioning options (#4)
Category grouping/aliases (#8)
Image size presets (#3)
Medium priority (valuable features):
Category-based styling (#6)
Category filtering (#7)
Bulk import/export (#10)
Preview mode (#12)
Lower priority (nice to have):
Multiple images per category (#5)
REST API endpoints (#14)
Category usage statistics (#23)