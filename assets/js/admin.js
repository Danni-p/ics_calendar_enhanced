/**
 * ICS Calendar Enhanced - Admin JavaScript
 *
 * @package ICSEnhanced
 */

(function($) {
    'use strict';

    /**
     * Media frame instance.
     */
    let mediaFrame = null;

    /**
     * Current target element for image selection.
     */
    let currentTarget = null;

    /**
     * Row index counter for new mappings.
     */
    let rowIndex = 0;

    /**
     * Color picker options from localized data.
     */
    const colorPickerOptions = (window.icsEnhancedAdmin && window.icsEnhancedAdmin.colorPickerOptions) || {
        defaultColor: '',
        palettes: ['#e74c3c', '#e67e22', '#f1c40f', '#2ecc71', '#1abc9c', '#3498db', '#9b59b6', '#34495e']
    };

    /**
     * Initialize the admin functionality.
     */
    function init() {
        bindEvents();
        initializeEmptyState();
        initializeColorPickers();
    }

    /**
     * Bind event handlers.
     */
    function bindEvents() {
        // Add new mapping row
        $('#ics-enhanced-add-mapping').on('click', addMappingRow);

        // Remove mapping row
        $(document).on('click', '.ics-enhanced-remove-row', removeMappingRow);

        // Select image for mapping row
        $(document).on('click', '.ics-enhanced-select-row-image', selectRowImage);

        // Select image for fallback fields
        $(document).on('click', '.ics-enhanced-select-image', selectFallbackImage);

        // Remove fallback image
        $(document).on('click', '.ics-enhanced-remove-image', removeFallbackImage);
    }

    /**
     * Initialize empty state message.
     */
    function initializeEmptyState() {
        const tbody = $('#ics-enhanced-mappings-body');
        tbody.attr('data-empty-message', 
            'No category mappings yet. Click "Add New Mapping" to create one.'
        );
    }

    /**
     * Initialize color pickers on all existing elements.
     */
    function initializeColorPickers() {
        $('.ics-enhanced-color-picker').each(function() {
            initializeColorPicker($(this));
        });
    }

    /**
     * Initialize a single color picker.
     *
     * @param {jQuery} $element The input element to initialize.
     */
    function initializeColorPicker($element) {
        // Skip if already initialized
        if ($element.hasClass('wp-color-picker')) {
            return;
        }

        $element.wpColorPicker({
            defaultColor: colorPickerOptions.defaultColor,
            palettes: colorPickerOptions.palettes,
            change: function(event, ui) {
                // Update the input value when color changes
                $(this).val(ui.color.toString());
            },
            clear: function() {
                // Handle when color is cleared
                $(this).val('');
            }
        });
    }

    /**
     * Add a new mapping row.
     *
     * @param {Event} e Click event.
     */
    function addMappingRow(e) {
        e.preventDefault();

        const template = $('#ics-enhanced-row-template').html();
        const newRow = template.replace(/\{\{INDEX\}\}/g, 'new_' + (++rowIndex));
        
        const $row = $(newRow);
        $row.addClass('new-row');
        
        $('#ics-enhanced-mappings-body').append($row);

        // Initialize color picker for the new row
        const $colorPicker = $row.find('.ics-enhanced-color-picker');
        initializeColorPicker($colorPicker);

        // Focus on the category input
        $row.find('.ics-enhanced-category-input').focus();

        // Remove animation class after animation completes
        setTimeout(function() {
            $row.removeClass('new-row');
        }, 300);
    }

    /**
     * Remove a mapping row.
     *
     * @param {Event} e Click event.
     */
    function removeMappingRow(e) {
        e.preventDefault();

        if (!confirm(icsEnhancedAdmin.strings.removeConfirm)) {
            return;
        }

        const $row = $(this).closest('.ics-enhanced-mapping-row');
        
        $row.fadeOut(200, function() {
            $(this).remove();
        });
    }

    /**
     * Select image for a mapping row.
     *
     * @param {Event} e Click event.
     */
    function selectRowImage(e) {
        e.preventDefault();

        const $row = $(this).closest('.ics-enhanced-mapping-row');
        currentTarget = {
            type: 'row',
            $input: $row.find('.ics-enhanced-image-id'),
            $preview: $row.find('.ics-enhanced-row-preview')
        };

        openMediaFrame();
    }

    /**
     * Select image for fallback fields.
     *
     * @param {Event} e Click event.
     */
    function selectFallbackImage(e) {
        e.preventDefault();

        const targetId = $(this).data('target');
        
        currentTarget = {
            type: 'fallback',
            $input: $('#' + targetId),
            $preview: $('#' + targetId + '-preview'),
            $removeBtn: $(this).siblings('.ics-enhanced-remove-image')
        };

        openMediaFrame();
    }

    /**
     * Remove fallback image.
     *
     * @param {Event} e Click event.
     */
    function removeFallbackImage(e) {
        e.preventDefault();

        const targetId = $(this).data('target');
        const $input = $('#' + targetId);
        const $preview = $('#' + targetId + '-preview');

        $input.val('');
        $preview.empty();
        $(this).hide();
    }

    /**
     * Open the WordPress media frame.
     */
    function openMediaFrame() {
        // Create media frame if it doesn't exist
        if (!mediaFrame) {
            mediaFrame = wp.media({
                title: icsEnhancedAdmin.strings.selectImage,
                button: {
                    text: icsEnhancedAdmin.strings.useThisImage
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            // Handle image selection
            mediaFrame.on('select', onImageSelect);
        }

        mediaFrame.open();
    }

    /**
     * Handle image selection from media frame.
     */
    function onImageSelect() {
        const attachment = mediaFrame.state().get('selection').first().toJSON();

        if (!currentTarget) {
            return;
        }

        // Update input value
        currentTarget.$input.val(attachment.id);

        // Get thumbnail URL (fall back to full if no thumbnail)
        const imageUrl = attachment.sizes && attachment.sizes.thumbnail 
            ? attachment.sizes.thumbnail.url 
            : attachment.url;

        // Update preview
        currentTarget.$preview.html(
            '<img src="' + imageUrl + '" alt="' + (attachment.alt || '') + '" />'
        );
        currentTarget.$preview.addClass('has-image');

        // Show remove button for fallback fields
        if (currentTarget.type === 'fallback' && currentTarget.$removeBtn) {
            currentTarget.$removeBtn.show();
        }

        // Clear current target
        currentTarget = null;
    }

    // Initialize when document is ready
    $(document).ready(init);

})(jQuery);
