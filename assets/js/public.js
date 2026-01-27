/**
 * ICS Calendar Enhanced - Public JavaScript
 *
 * Provides JavaScript fallback for injecting category images into ICS Calendar events
 * and applies category color coding to date elements.
 *
 * @package ICSEnhanced
 */

(function() {
    'use strict';

    // Configuration from WordPress (via wp_localize_script)
    var config = window.icsEnhancedData || {
        fallbackImage: '',
        categoryImages: {},
        categoryColors: {},
        imageClass: 'ics-enhanced-event-category-image',
        wrapperClass: 'ics-enhanced-event-wrapper',
        selectors: {
            eventTitle: '.r34ics .title, .ics-calendar .title, .r34ics_event .title',
            eventItem: '.r34ics .event, .ics-calendar .event, .r34ics_event',
            eventDate: '.ics-calendar-date',
            eventWrapper: '.event[data-categories]'
        },
        colorSettings: {
            borderWidth: '3px',
            backgroundOpacity: 0.15
        },
        debug: false
    };

    /**
     * Debug logger
     */
    function log() {
        if (config.debug && console && console.log) {
            console.log.apply(console, ['[ICS Enhanced]'].concat(Array.prototype.slice.call(arguments)));
        }
    }

    /**
     * Initialize frontend functionality.
     */
    function init() {
        log('Initializing...');
        
        // Handle existing images
        handleImageErrors();
        lazyLoadImages();

        // Inject images if PHP hooks didn't work (fallback)
        injectCategoryImages();

        // Apply category colors to date elements
        applyCategoryColors();

        // Set up mutation observer for dynamically loaded content
        observeDynamicContent();
    }

    /**
     * Apply category colors to .ics-calendar-date elements.
     * Reads category from parent .event[data-categories] element.
     */
    function applyCategoryColors() {
        // Skip if no category colors configured
        if (!config.categoryColors || Object.keys(config.categoryColors).length === 0) {
            log('No category colors configured, skipping color application');
            return;
        }

        // Find all .event elements with data-categories attribute
        var eventWrappers = document.querySelectorAll(config.selectors.eventWrapper || '.event[data-categories]');
        log('Found', eventWrappers.length, 'events with data-categories');

        eventWrappers.forEach(function(eventEl) {
            applyColorToEvent(eventEl);
        });
    }

    /**
     * Apply color to a single event element.
     *
     * @param {HTMLElement} eventEl The event element with data-categories.
     */
    function applyColorToEvent(eventEl) {
        // Skip if already processed
        if (eventEl.hasAttribute('data-ics-color-applied')) {
            return;
        }

        // Get categories from data attribute
        var categories = eventEl.getAttribute('data-categories');
        if (!categories) {
            return;
        }

        // Split comma-separated categories and check each one
        var categoryList = categories.split(',').map(function(cat) {
            return cat.trim();
        }).filter(function(cat) {
            return cat.length > 0;
        });

        if (categoryList.length === 0) {
            return;
        }

        // Find the first category that has a color mapping
        var color = null;
        var matchedCategory = null;
        
        for (var i = 0; i < categoryList.length; i++) {
            var category = categoryList[i];
            var foundColor = getColorForCategory(category);
            if (foundColor) {
                color = foundColor;
                matchedCategory = category;
                break; // Use first match
            }
        }

        // If no color found, don't apply anything
        if (!color) {
            return;
        }

        // Find .ics-calendar-date element within this event
        var dateEl = eventEl.querySelector(config.selectors.eventDate || '.ics-calendar-date');
        if (!dateEl) {
            log('No .ics-calendar-date found in event:', matchedCategory);
            return;
        }

        // Apply border color
        dateEl.style.borderColor = color;
        dateEl.style.borderWidth = config.colorSettings.borderWidth || '3px';
        dateEl.style.borderStyle = 'solid';

        // Apply background color with opacity
        var bgColor = hexToRgba(color, config.colorSettings.backgroundOpacity || 0.15);
        dateEl.style.backgroundColor = bgColor;

        // Mark as processed
        eventEl.setAttribute('data-ics-color-applied', 'true');
        dateEl.setAttribute('data-category-color', color);

        log('Applied color', color, 'to event:', matchedCategory);
    }

    /**
     * Get color for a category string.
     *
     * @param {string} category Category string.
     * @return {string} Hex color or empty string.
     */
    function getColorForCategory(category) {
        if (!category || !config.categoryColors) {
            return '';
        }

        // Try exact match first
        if (config.categoryColors[category]) {
            return config.categoryColors[category];
        }

        // Try case-insensitive match
        var lowerCategory = category.toLowerCase();
        for (var key in config.categoryColors) {
            if (key.toLowerCase() === lowerCategory) {
                return config.categoryColors[key];
            }
        }

        return '';
    }

    /**
     * Convert hex color to rgba string.
     *
     * @param {string} hex Hex color (e.g., "#ff0000" or "#f00").
     * @param {number} alpha Alpha/opacity value (0-1).
     * @return {string} RGBA color string.
     */
    function hexToRgba(hex, alpha) {
        // Remove # if present
        hex = hex.replace(/^#/, '');

        // Handle shorthand hex (e.g., #f00)
        if (hex.length === 3) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }

        // Parse hex values
        var r = parseInt(hex.substring(0, 2), 16);
        var g = parseInt(hex.substring(2, 4), 16);
        var b = parseInt(hex.substring(4, 6), 16);

        // Validate
        if (isNaN(r) || isNaN(g) || isNaN(b)) {
            return '';
        }

        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
    }

    /**
     * Inject category images into ICS Calendar events.
     * This is a fallback in case PHP filters don't fire.
     */
    function injectCategoryImages() {
        // Skip if no fallback image configured
        if (!config.fallbackImage) {
            log('No fallback image configured, skipping injection');
            return;
        }

        // Find all event titles that don't already have images
        var eventTitles = document.querySelectorAll(config.selectors.eventTitle);
        log('Found', eventTitles.length, 'event titles');

        eventTitles.forEach(function(titleElement) {
            // Skip if already processed
            if (titleElement.querySelector('.' + config.imageClass)) {
                log('Title already has image, skipping');
                return;
            }

            // Skip if parent wrapper exists (PHP already handled it)
            if (titleElement.closest('.' + config.wrapperClass)) {
                log('PHP wrapper found, skipping');
                return;
            }

            // Try to extract category from event data
            var category = extractCategoryFromEvent(titleElement);
            var imageUrl = getImageForCategory(category);

            if (imageUrl) {
                injectImageIntoTitle(titleElement, imageUrl, category);
            }
        });
    }

    /**
     * Extract category from event element.
     *
     * Checks all categories in comma-separated list to find one with a mapping.
     *
     * @param {HTMLElement} titleElement The event title element.
     * @return {string} Category string or empty.
     */
    function extractCategoryFromEvent(titleElement) {
        // Try to find event container
        var eventElement = titleElement.closest('.event, .r34ics_event, [data-category], [data-categories]');
        
        if (!eventElement) {
            return '';
        }

        // Check for data-categories attribute (plural, comma-separated)
        if (eventElement.dataset && eventElement.dataset.categories) {
            var categories = eventElement.dataset.categories.split(',').map(function(cat) {
                return cat.trim();
            }).filter(function(cat) {
                return cat.length > 0;
            });
            
            // Check each category to find one with an image mapping
            for (var i = 0; i < categories.length; i++) {
                var category = categories[i];
                if (getImageForCategory(category)) {
                    return category; // Return first category with a mapping
                }
            }
            
            // If no mapping found, return first category as fallback
            return categories.length > 0 ? categories[0] : '';
        }

        // Check for data-category attribute (singular)
        if (eventElement.dataset && eventElement.dataset.category) {
            return eventElement.dataset.category;
        }

        // Check for category class (e.g., "category-meeting")
        var classes = eventElement.className.split(/\s+/);
        for (var i = 0; i < classes.length; i++) {
            if (classes[i].indexOf('category-') === 0) {
                var category = classes[i].replace('category-', '');
                // Check if this category has a mapping
                if (getImageForCategory(category)) {
                    return category;
                }
            }
            if (classes[i].indexOf('cat-') === 0) {
                var category = classes[i].replace('cat-', '');
                // Check if this category has a mapping
                if (getImageForCategory(category)) {
                    return category;
                }
            }
        }

        // Check for categories in a child element
        var categoryEl = eventElement.querySelector('.categories, .category, [data-categories]');
        if (categoryEl) {
            var categories = categoryEl.textContent.trim().split(',').map(function(cat) {
                return cat.trim();
            }).filter(function(cat) {
                return cat.length > 0;
            });
            
            // Check each category for a mapping
            for (var i = 0; i < categories.length; i++) {
                var category = categories[i];
                if (getImageForCategory(category)) {
                    return category;
                }
            }
            
            // If no mapping found, return first category as fallback
            return categories.length > 0 ? categories[0] : '';
        }

        return '';
    }

    /**
     * Get image URL for a category.
     *
     * @param {string} category Category string.
     * @return {string} Image URL or empty.
     */
    function getImageForCategory(category) {
        // Try specific category mapping first
        if (category && config.categoryImages[category]) {
            return config.categoryImages[category];
        }

        // Try case-insensitive match
        if (category) {
            var lowerCategory = category.toLowerCase();
            for (var key in config.categoryImages) {
                if (key.toLowerCase() === lowerCategory) {
                    return config.categoryImages[key];
                }
            }
        }

        // Use fallback image
        return config.fallbackImage || '';
    }

    /**
     * Inject image into event title.
     *
     * @param {HTMLElement} titleElement The event title element.
     * @param {string} imageUrl Image URL.
     * @param {string} category Category name for alt text.
     */
    function injectImageIntoTitle(titleElement, imageUrl, category) {
        var img = document.createElement('img');
        img.src = imageUrl;
        img.alt = category ? 'Image for category: ' + category : 'Event image';
        img.className = config.imageClass;
        img.loading = 'lazy';

        // Create wrapper span
        var wrapper = document.createElement('span');
        wrapper.className = config.wrapperClass;

        // Move title content into wrapper with image
        wrapper.appendChild(img);
        
        // Clone title content and add to wrapper
        while (titleElement.firstChild) {
            wrapper.appendChild(titleElement.firstChild);
        }

        // Replace title content with wrapper
        titleElement.appendChild(wrapper);

        log('Injected image for category:', category || '(fallback)');
    }

    /**
     * Handle image loading errors gracefully.
     */
    function handleImageErrors() {
        document.querySelectorAll('.' + config.imageClass).forEach(function(img) {
            img.addEventListener('error', function() {
                this.classList.add('ics-enhanced-image-error');
                
                // Try fallback image if not already using it
                if (this.src !== config.fallbackImage && config.fallbackImage) {
                    log('Image failed to load, trying fallback');
                    this.src = config.fallbackImage;
                } else {
                    this.alt = 'Image not available';
                    this.style.display = 'none';
                }
            });

            img.addEventListener('load', function() {
                this.classList.remove('loading');
                this.classList.add('ics-enhanced-image-loaded');
            });
        });
    }

    /**
     * Lazy load images for performance.
     */
    function lazyLoadImages() {
        // Use native lazy loading if available
        if ('loading' in HTMLImageElement.prototype) {
            document.querySelectorAll('.' + config.imageClass).forEach(function(img) {
                if (!img.hasAttribute('loading')) {
                    img.setAttribute('loading', 'lazy');
                }
            });
            return;
        }

        // Fallback: Intersection Observer for older browsers
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            document.querySelectorAll('.' + config.imageClass + '[data-src]').forEach(function(img) {
                observer.observe(img);
            });
        }
    }

    /**
     * Observe DOM for dynamically loaded calendar content.
     */
    function observeDynamicContent() {
        if (!('MutationObserver' in window)) {
            return;
        }

        var observer = new MutationObserver(function(mutations) {
            var shouldProcess = false;

            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            // Check if it's a calendar element or contains calendar elements
                            if (node.matches && (
                                node.matches('.r34ics, .ics-calendar, .r34ics_event, .event[data-categories]') ||
                                node.querySelector('.r34ics, .ics-calendar, .r34ics_event, .event[data-categories]')
                            )) {
                                shouldProcess = true;
                            }
                        }
                    });
                }
            });

            if (shouldProcess) {
                log('Dynamic content detected, re-processing');
                // Debounce the processing
                clearTimeout(observer.debounceTimer);
                observer.debounceTimer = setTimeout(function() {
                    injectCategoryImages();
                    applyCategoryColors();
                    handleImageErrors();
                }, 100);
            }
        });

        // Observe the entire document for changes
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        log('Mutation observer active');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Also run after window load (for dynamically loaded calendars)
    window.addEventListener('load', function() {
        // Re-run injection after full page load
        setTimeout(function() {
            log('Running post-load injection');
            injectCategoryImages();
            applyCategoryColors();
        }, 500);
    });

})();
