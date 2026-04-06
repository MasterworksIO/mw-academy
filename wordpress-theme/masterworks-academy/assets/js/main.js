/**
 * Masterworks Academy - Main JavaScript
 *
 * Handles: navigation, search overlay, AJAX filtering, load more, copy link, scroll behavior.
 *
 * @package Masterworks_Academy
 * @since 1.0.0
 */

(function () {
    'use strict';

    /**
     * DOM Ready
     */
    document.addEventListener('DOMContentLoaded', function () {
        initStickyHeader();
        initMobileMenu();
        initSearchOverlay();
        initFilterBar();
        initLoadMore();
        initCopyLink();
    });

    /**
     * Sticky Header - add shadow on scroll
     */
    function initStickyHeader() {
        const header = document.getElementById('site-header');
        if (!header) return;

        let lastScrollY = 0;
        let ticking = false;

        function onScroll() {
            lastScrollY = window.scrollY;
            if (!ticking) {
                window.requestAnimationFrame(function () {
                    header.classList.toggle('is-scrolled', lastScrollY > 10);
                    ticking = false;
                });
                ticking = true;
            }
        }

        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /**
     * Mobile Menu Toggle
     */
    function initMobileMenu() {
        const toggle = document.getElementById('menu-toggle');
        const nav = document.getElementById('academy-nav');
        if (!toggle || !nav) return;

        toggle.addEventListener('click', function () {
            const isOpen = nav.classList.toggle('is-open');
            toggle.classList.toggle('is-active', isOpen);
            toggle.setAttribute('aria-expanded', isOpen.toString());

            // Trap focus within mobile menu when open
            if (isOpen) {
                const firstLink = nav.querySelector('a');
                if (firstLink) firstLink.focus();
            }
        });

        // Close on Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && nav.classList.contains('is-open')) {
                nav.classList.remove('is-open');
                toggle.classList.remove('is-active');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.focus();
            }
        });

        // Close on click outside
        document.addEventListener('click', function (e) {
            if (nav.classList.contains('is-open') && !nav.contains(e.target) && !toggle.contains(e.target)) {
                nav.classList.remove('is-open');
                toggle.classList.remove('is-active');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /**
     * Search Overlay
     */
    function initSearchOverlay() {
        const searchToggle = document.getElementById('search-toggle');
        const overlay = document.getElementById('search-overlay');
        const closeBtn = document.getElementById('search-close');
        if (!searchToggle || !overlay) return;

        searchToggle.addEventListener('click', function () {
            overlay.classList.add('is-open');
            const input = overlay.querySelector('input[type="search"]');
            if (input) {
                setTimeout(function () { input.focus(); }, 100);
            }
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                overlay.classList.remove('is-open');
                searchToggle.focus();
            });
        }

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                overlay.classList.remove('is-open');
                searchToggle.focus();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay.classList.contains('is-open')) {
                overlay.classList.remove('is-open');
                searchToggle.focus();
            }
        });
    }

    /**
     * AJAX Filter Bar
     */
    function initFilterBar() {
        const filterBar = document.querySelector('.filter-bar');
        if (!filterBar) return;

        const buttons = filterBar.querySelectorAll('.filter-bar__item');
        const grid = document.getElementById('articles-grid');
        const loadMoreContainer = document.getElementById('load-more-container');

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                // Update active state
                buttons.forEach(function (b) {
                    b.classList.remove('is-active');
                    b.setAttribute('aria-selected', 'false');
                });
                btn.classList.add('is-active');
                btn.setAttribute('aria-selected', 'true');

                const category = btn.getAttribute('data-category');
                fetchPosts(category, 1, grid, loadMoreContainer);
            });
        });
    }

    /**
     * Fetch posts via AJAX
     */
    function fetchPosts(category, page, grid, loadMoreContainer) {
        if (!grid || typeof mwAcademy === 'undefined') return;

        // Show loading state
        grid.style.opacity = '0.5';
        grid.style.pointerEvents = 'none';

        var formData = new FormData();
        formData.append('action', 'mw_filter_posts');
        formData.append('nonce', mwAcademy.nonce);
        formData.append('category', category);
        formData.append('page', page);

        fetch(mwAcademy.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                if (page === 1) {
                    grid.innerHTML = data.data.html;
                } else {
                    grid.insertAdjacentHTML('beforeend', data.data.html);
                }

                // Update load more button
                if (loadMoreContainer) {
                    loadMoreContainer.setAttribute('data-current-page', page.toString());
                    loadMoreContainer.setAttribute('data-max-pages', data.data.max_pages.toString());
                    loadMoreContainer.setAttribute('data-category', category);

                    if (page >= data.data.max_pages) {
                        loadMoreContainer.style.display = 'none';
                    } else {
                        loadMoreContainer.style.display = '';
                    }
                }
            }
        })
        .catch(function (error) {
            console.error('Filter error:', error);
        })
        .finally(function () {
            grid.style.opacity = '1';
            grid.style.pointerEvents = '';
        });
    }

    /**
     * Load More Button
     */
    function initLoadMore() {
        const loadMoreBtn = document.getElementById('load-more-btn');
        const loadMoreContainer = document.getElementById('load-more-container');
        const grid = document.getElementById('articles-grid');
        if (!loadMoreBtn || !loadMoreContainer || !grid) return;

        loadMoreBtn.addEventListener('click', function () {
            var currentPage = parseInt(loadMoreContainer.getAttribute('data-current-page') || '1', 10);
            var maxPages = parseInt(loadMoreContainer.getAttribute('data-max-pages') || '1', 10);
            var category = loadMoreContainer.getAttribute('data-category') || 'all';

            if (currentPage >= maxPages) return;

            // Show loading state
            loadMoreContainer.classList.add('is-loading');
            loadMoreBtn.disabled = true;

            var nextPage = currentPage + 1;

            var formData = new FormData();
            formData.append('action', 'mw_filter_posts');
            formData.append('nonce', mwAcademy.nonce);
            formData.append('category', category);
            formData.append('page', nextPage);

            fetch(mwAcademy.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success) {
                    grid.insertAdjacentHTML('beforeend', data.data.html);
                    loadMoreContainer.setAttribute('data-current-page', nextPage.toString());

                    if (nextPage >= data.data.max_pages) {
                        loadMoreBtn.textContent = mwAcademy.i18n.noMore;
                        loadMoreBtn.disabled = true;
                    }
                }
            })
            .catch(function (error) {
                console.error('Load more error:', error);
            })
            .finally(function () {
                loadMoreContainer.classList.remove('is-loading');
                if (parseInt(loadMoreContainer.getAttribute('data-current-page'), 10) < maxPages) {
                    loadMoreBtn.disabled = false;
                }
            });
        });
    }

    /**
     * Copy Link Button
     */
    function initCopyLink() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.js-copy-link');
            if (!btn) return;

            var url = btn.getAttribute('data-url');
            if (!url) return;

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function () {
                    showCopyTooltip(btn, mwAcademy.i18n.linkCopied);
                }).catch(function () {
                    fallbackCopy(url, btn);
                });
            } else {
                fallbackCopy(url, btn);
            }
        });
    }

    function fallbackCopy(text, btn) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            showCopyTooltip(btn, mwAcademy.i18n.linkCopied);
        } catch (err) {
            showCopyTooltip(btn, mwAcademy.i18n.copyFailed);
        }
        document.body.removeChild(textarea);
    }

    function showCopyTooltip(btn, message) {
        var tooltip = document.createElement('span');
        tooltip.textContent = message;
        tooltip.style.cssText = 'position:absolute;bottom:calc(100% + 8px);left:50%;transform:translateX(-50%);padding:4px 10px;background:#1A1A2E;color:#fff;font-size:12px;border-radius:4px;white-space:nowrap;pointer-events:none;z-index:10;';
        btn.style.position = 'relative';
        btn.appendChild(tooltip);
        setTimeout(function () {
            if (tooltip.parentNode) tooltip.parentNode.removeChild(tooltip);
        }, 2000);
    }

})();
