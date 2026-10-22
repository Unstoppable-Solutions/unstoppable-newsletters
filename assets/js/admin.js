/**
 * Unstoppable Newsletter Archive - Admin JavaScript
 * Version: 1.0.0
 *
 * @package Unstoppable_Newsletter_Archive
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Initialize color pickers
        if ($.fn.wpColorPicker) {
            $('.unstne-color-picker').wpColorPicker();
        }

        // PDF Upload Button
        $(document).on('click', '.unstne-upload-pdf', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $input = $button.prev('input');

            // Create media frame
            var frame = wp.media({
                title: 'Select or Upload PDF',
                button: {
                    text: 'Use this PDF'
                },
                library: {
                    type: 'application/pdf'
                },
                multiple: false
            });

            // When PDF is selected
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.url);
            });

            // Open media frame
            frame.open();
        });

        // Events Repeater - Add Event
        var eventIndex = $('.unstne-event-row').length;

        $(document).on('click', '.unstne-add-event', function(e) {
            e.preventDefault();

            var template = $('#unstne-event-template').html();
            template = template.replace(/\{\{INDEX\}\}/g, eventIndex);
            eventIndex++;

            $('#unstne-events-container').append(template);
        });

        // Events Repeater - Remove Event
        $(document).on('click', '.unstne-remove-event', function(e) {
            e.preventDefault();
            $(this).closest('.unstne-event-row').fadeOut(200, function() {
                $(this).remove();
            });
        });

        // Auto-generate title preview
        var $monthSelect = $('#unstne_month');
        var $yearSelect = $('#unstne_year');

        function updateTitlePreview() {
            var month = $monthSelect.find('option:selected').text();
            var year = $yearSelect.val();

            if (month && year && month !== 'Select Month' && year !== '') {
                var title = month + ' ' + year + ' Newsletter';
                $('#title').val(title);
            }
        }

        $monthSelect.on('change', updateTitlePreview);
        $yearSelect.on('change', updateTitlePreview);

    });

})(jQuery);
