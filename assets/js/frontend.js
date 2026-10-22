/**
 * Unstoppable Newsletter Archive - Frontend JavaScript
 * Version: 1.0.0
 *
 * @package Unstoppable_Newsletter_Archive
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Year Tab Filtering
        var $yearTabs = $('.unstne-year-tab');
        var $yearContents = $('.unstne-year-content');

        $yearTabs.on('click', function() {
            var targetYear = $(this).data('year');

            // Remove active class from all tabs
            $yearTabs.removeClass('active').attr('aria-selected', 'false');

            // Add active class to clicked tab
            $(this).addClass('active').attr('aria-selected', 'true');

            // Hide all year content sections
            $yearContents.removeClass('active');

            // Show the selected year content
            $('#unstne-year-' + targetYear).addClass('active');

            // Smooth scroll to section on mobile
            if ($(window).width() < 768) {
                $('html, body').animate({
                    scrollTop: $('.unstne-newsletter-section').offset().top - 20
                }, 300);
            }
        });

        // Lazy load animation for cards
        if ('IntersectionObserver' in window) {
            var cardObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        $(entry.target).addClass('visible');
                        cardObserver.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1
            });

            $('.unstne-newsletter-card').each(function() {
                cardObserver.observe(this);
            });
        } else {
            // Fallback for older browsers
            $('.unstne-newsletter-card').addClass('visible');
        }

        // Keyboard navigation for tabs
        $yearTabs.on('keydown', function(e) {
            var $current = $(this);
            var $tabs = $('.unstne-year-tab');
            var currentIndex = $tabs.index($current);

            switch(e.key) {
                case 'ArrowLeft':
                    e.preventDefault();
                    if (currentIndex > 0) {
                        $tabs.eq(currentIndex - 1).focus().click();
                    }
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    if (currentIndex < $tabs.length - 1) {
                        $tabs.eq(currentIndex + 1).focus().click();
                    }
                    break;
                case 'Home':
                    e.preventDefault();
                    $tabs.first().focus().click();
                    break;
                case 'End':
                    e.preventDefault();
                    $tabs.last().focus().click();
                    break;
            }
        });

        // Track PDF downloads (for analytics if needed)
        $('.unstne-card-link:not(.coming-soon), .unstne-btn-primary, .unstne-btn-secondary').on('click', function() {
            var pdfUrl = $(this).attr('href');
            var title = $(this).closest('.unstne-newsletter-card, .unstne-featured-section').find('.unstne-card-month, .unstne-featured-title').first().text();

            // Trigger custom event for analytics
            $(document).trigger('unstne:pdf-download', {
                url: pdfUrl,
                title: title
            });

            // If Google Analytics 4 is available
            if (typeof gtag !== 'undefined') {
                gtag('event', 'file_download', {
                    'file_name': pdfUrl.split('/').pop(),
                    'file_extension': 'pdf',
                    'link_url': pdfUrl
                });
            }

            // If Google Analytics Universal is available
            if (typeof ga !== 'undefined') {
                ga('send', 'event', 'Newsletter', 'Download', title);
            }
        });

        // Subscribe form handling
        $('.unstne-subscribe-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $form.find('.unstne-subscribe-btn');
            var $message = $form.find('.unstne-form-message');
            var originalText = $btn.text();

            // Get form data
            var email = $form.find('input[name="unstne_email"]').val();
            var name = $form.find('input[name="unstne_name"]').val() || '';

            // Validate email
            if (!email || !isValidEmail(email)) {
                showMessage($message, 'Please enter a valid email address.', 'error');
                return;
            }

            // Disable button and show loading
            $btn.prop('disabled', true).text(unstneAjax.strings.subscribing);

            $.ajax({
                url: unstneAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'unstne_subscribe',
                    nonce: unstneAjax.subscribe_nonce,
                    email: email,
                    name: name
                },
                success: function(response) {
                    if (response.success) {
                        showMessage($message, response.data.message, 'success');
                        $form.find('input[type="email"], input[type="text"]').val('');
                    } else {
                        showMessage($message, response.data.message, 'error');
                    }
                },
                error: function() {
                    showMessage($message, unstneAjax.strings.error, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });

        // Search form handling
        var searchTimeout;
        $('.unstne-search-form').on('submit', function(e) {
            e.preventDefault();
            performSearch($(this));
        });

        $('.unstne-search-input').on('input', function() {
            var $input = $(this);
            var $form = $input.closest('.unstne-search-form');
            
            clearTimeout(searchTimeout);
            
            if ($input.val().length >= 2) {
                searchTimeout = setTimeout(function() {
                    performSearch($form);
                }, 300);
            } else {
                $('.unstne-search-results').hide().empty();
            }
        });

        function performSearch($form) {
            var $input = $form.find('.unstne-search-input');
            var $results = $form.siblings('.unstne-search-results');
            var searchTerm = $input.val();

            if (searchTerm.length < 2) {
                $results.hide().empty();
                return;
            }

            $results.html('<div class="unstne-search-loading">' + unstneAjax.strings.searching + '</div>').show();

            $.ajax({
                url: unstneAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'unstne_search_newsletters',
                    nonce: unstneAjax.search_nonce,
                    search: searchTerm
                },
                success: function(response) {
                    if (response.success && response.data.results.length > 0) {
                        var html = '<ul class="unstne-search-list">';
                        $.each(response.data.results, function(i, item) {
                            html += '<li class="unstne-search-item">';
                            html += '<a href="' + item.url + '" target="_blank">';
                            html += '<strong>' + item.title + '</strong>';
                            html += '<span class="unstne-search-date">' + item.date + '</span>';
                            if (item.desc) {
                                html += '<span class="unstne-search-desc">' + item.desc + '</span>';
                            }
                            html += '</a></li>';
                        });
                        html += '</ul>';
                        $results.html(html);
                    } else {
                        $results.html('<div class="unstne-no-results">' + unstneAjax.strings.no_results + '</div>');
                    }
                },
                error: function() {
                    $results.html('<div class="unstne-search-error">' + unstneAjax.strings.error + '</div>');
                }
            });
        }

        // Close search results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.unstne-search-wrapper').length) {
                $('.unstne-search-results').hide();
            }
        });

        // Helper functions
        function isValidEmail(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        function showMessage($el, message, type) {
            $el.removeClass('success error').addClass(type).text(message).fadeIn();
            
            if (type === 'success') {
                setTimeout(function() {
                    $el.fadeOut();
                }, 5000);
            }
        }

    });

})(jQuery);
