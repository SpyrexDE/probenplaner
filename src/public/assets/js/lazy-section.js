/**
 * LazySection — IntersectionObserver-based lazy loading for content sections.
 *
 * Usage: Add `data-lazy-section`, `data-lazy-url`, and an optional `data-lazy-id`
 * to any element. The component auto-initialises on DOMContentLoaded and observes
 * matching elements. When an element scrolls into view, its `data-lazy-url` is
 * fetched. The skeleton inside is replaced with the response HTML.
 *
 * Manual reload: `LazySection.reload('myId')` re-fetches a section by its id.
 */
(function() {
    'use strict';

    const ROOT_MARGIN = '200px';
    const loaded = new Map();

    function fetchSection(el) {
        const url = el.dataset.lazyUrl;
        if (!url) return;

        const id = el.dataset.lazyId || '';
        if (el.dataset.lazyLoaded === '1') return;
        el.dataset.lazyLoaded = '1';

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(function(res) {
            if (!res.ok) throw new Error(res.status);
            return res.text();
        })
        .then(function(html) {
            var skeleton = el.querySelector('.lazy-skeleton');
            if (skeleton) {
                skeleton.classList.add('skeleton-exit');
                setTimeout(function() { insertContent(el, html); }, 250);
            } else {
                insertContent(el, html);
            }
            if (id) loaded.set(id, el);
        })
        .catch(function() {
            showError(el);
        });
    }

    function insertContent(el, html) {
        // Append new cards into existing content instead of replacing
        var existing = el.querySelector('.lazy-section-content');
        var isPrepend = el.getAttribute('data-lazy-prepend') === 'true';

        if (existing) {
            // Remove any previous sentinel
            var oldSentinel = existing.querySelector('.lazy-batch-sentinel');
            if (oldSentinel) oldSentinel.remove();
            
            // Re-fetch any prepended button to avoid duplicating or pushing it down
            var prependedBtn = existing.querySelector('.lazy-load-more-container');
            if (prependedBtn && isPrepend) {
                prependedBtn.remove();
            }

            // Append or prepend new cards
            var temp = document.createElement('div');
            temp.innerHTML = html;
            
            if (isPrepend) {
                while (temp.lastChild) {
                    existing.insertBefore(temp.lastChild, existing.firstChild);
                }
            } else {
                while (temp.firstChild) {
                    existing.appendChild(temp.firstChild);
                }
            }
        } else {
            el.innerHTML = '<div class="lazy-section-content">' + html + '</div>';
            existing = el.querySelector('.lazy-section-content');
        }

        // Execute inline <script> tags from newly loaded content
        var scripts = el.querySelectorAll('script:not([data-executed])');
        scripts.forEach(function(orig) {
            var s = document.createElement('script');
            s.setAttribute('data-executed', '1');
            if (orig.src) {
                s.src = orig.src;
            } else {
                s.textContent = orig.textContent;
            }
            orig.parentNode.replaceChild(s, orig);
        });

        // Check for next-batch signal
        var nextMarker = existing.querySelector('[data-lazy-next-url]');
        if (nextMarker) {
            var nextUrl = nextMarker.getAttribute('data-lazy-next-url');
            nextMarker.remove();
            chainNextBatch(el, nextUrl);
        }

        var btnMarker = existing.querySelector('[data-lazy-button-url]');
        if (btnMarker) {
            var btnUrl = btnMarker.getAttribute('data-lazy-button-url');
            btnMarker.remove();
            createLoadMoreButton(el, btnUrl);
        }

        if (typeof stretchTexts === 'function') {
            setTimeout(stretchTexts, 50);
        }

        el.dispatchEvent(new CustomEvent('lazy:loaded', { bubbles: true }));
    }

    function chainNextBatch(el, url) {
        // Create a sentinel at the bottom to trigger the next batch load
        var sentinel = document.createElement('div');
        sentinel.className = 'lazy-batch-sentinel';
        sentinel.style.minHeight = '1px';
        var content = el.querySelector('.lazy-section-content');
        if (content) content.appendChild(sentinel);

        if (!observer) {
            fetchBatch(el, url);
            return;
        }

        var batchObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    batchObserver.unobserve(entry.target);
                    entry.target.innerHTML = buildSkeleton('cards', 2);
                    fetchBatch(el, url);
                }
            });
        }, { rootMargin: ROOT_MARGIN });
        batchObserver.observe(sentinel);
    }

    function createLoadMoreButton(el, url) {
        var container = document.createElement('div');
        container.className = 'load-past-button-wrapper';
        
        var btn = document.createElement('button');
        btn.className = 'load-past-button';
        btn.innerHTML = '<i class="fas fa-history"></i> <span>5 weitere Proben laden</span>';
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> <span>Lade...</span>';
            btn.disabled = true;
            fetchBatch(el, url, container);
        });

        container.appendChild(btn);
        var content = el.querySelector('.lazy-section-content');
        var isPrepend = el.getAttribute('data-lazy-prepend') === 'true';
        if (content) {
            if (isPrepend) {
                content.insertBefore(container, content.firstChild);
            } else {
                content.appendChild(container);
            }
        }
    }

    function fetchBatch(el, url, removeEl) {
        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(function(res) {
            if (!res.ok) throw new Error(res.status);
            return res.text();
        })
        .then(function(html) {
            if (removeEl) removeEl.remove();
            insertContent(el, html);
        })
        .catch(function() {
            if (removeEl) removeEl.remove();
            var sentinel = el.querySelector('.lazy-batch-sentinel') || el.querySelector('.lazy-section-content');
            var errorHtml = 
                '<div class="lazy-error" style="padding: var(--space-3)">' +
                    '<button class="lazy-retry-btn" onclick="LazySection.retryBatch(this,\'' +
                        url.replace(/'/g, "\\'") + '\')">' +
                        '<i class="fas fa-rotate-right"></i> Erneut versuchen' +
                    '</button>' +
                '</div>';
            
            if (sentinel && sentinel.className === 'lazy-batch-sentinel') {
                sentinel.innerHTML = errorHtml;
            } else if (sentinel) {
                var errDiv = document.createElement('div');
                errDiv.innerHTML = errorHtml;
                sentinel.appendChild(errDiv);
            }
        });
    }

    function showError(el) {
        el.dataset.lazyLoaded = '0';
        el.innerHTML =
            '<div class="lazy-error">' +
                '<i class="fas fa-cloud-exclamation"></i>' +
                '<div>Inhalt konnte nicht geladen werden</div>' +
                '<button class="lazy-retry-btn" onclick="LazySection.retry(this)">' +
                    '<i class="fas fa-rotate-right"></i> Erneut versuchen' +
                '</button>' +
            '</div>';
    }

    var observer = null;

    function init() {
        if (!('IntersectionObserver' in window)) {
            // Fallback: load all sections immediately
            document.querySelectorAll('[data-lazy-section]').forEach(fetchSection);
            return;
        }

        observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    observer.unobserve(entry.target);
                    fetchSection(entry.target);
                }
            });
        }, { rootMargin: ROOT_MARGIN });

        document.querySelectorAll('[data-lazy-section]').forEach(function(el) {
            if (el.dataset.lazyLoaded !== '1') {
                observer.observe(el);
            }
        });
    }

    // Public API
    window.LazySection = {
        /** Re-fetch a section by its data-lazy-id */
        reload: function(id) {
            var el = loaded.get(id) || document.querySelector('[data-lazy-id="' + id + '"]');
            if (el) {
                el.dataset.lazyLoaded = '0';
                fetchSection(el);
            }
        },

        /** Retry handler for error button */
        retry: function(btn) {
            var el = btn.closest('[data-lazy-section]');
            if (el) {
                el.dataset.lazyLoaded = '0';
                // Restore skeleton
                var count = parseInt(el.dataset.lazySkeletonCount || '3');
                var type = el.dataset.lazySkeletonType || 'cards';
                el.innerHTML = buildSkeleton(type, count);
                fetchSection(el);
            }
        },

        /** Observe a dynamically added lazy section */
        observe: function(el) {
            if (observer && el.dataset.lazyLoaded !== '1') {
                observer.observe(el);
            }
        },

        /** Retry a failed batch load */
        retryBatch: function(btn, url) {
            var el = btn.closest('[data-lazy-section]');
            if (el) {
                var sentinel = btn.closest('.lazy-batch-sentinel');
                if (sentinel) sentinel.innerHTML = buildSkeleton('cards', 2);
                fetchBatch(el, url);
            }
        },

        /** Load all remaining batches for a section. Returns a Promise. */
        loadAll: function(id) {
            var el = id
                ? (loaded.get(id) || document.querySelector('[data-lazy-id="' + id + '"]'))
                : document.querySelector('[data-lazy-section]');
            if (!el) return Promise.resolve();

            // First load hasn't happened yet — trigger it
            if (el.dataset.lazyLoaded !== '1') {
                el.dataset.lazyLoaded = '1';
                var url = el.dataset.lazyUrl;
                if (!url) return Promise.resolve();
                return fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                .then(function(res) { return res.text(); })
                .then(function(html) {
                    insertContent(el, html);
                    return drainBatches(el);
                });
            }

            return drainBatches(el);
        }
    };

    function drainBatches(el) {
        var content = el.querySelector('.lazy-section-content');
        if (!content) return Promise.resolve();
        var sentinel = content.querySelector('.lazy-batch-sentinel');
        var nextMarker = content.querySelector('[data-lazy-next-url]');
        if (!sentinel && !nextMarker) return Promise.resolve();

        var url = nextMarker
            ? nextMarker.getAttribute('data-lazy-next-url')
            : null;
        if (nextMarker) nextMarker.remove();
        if (sentinel) sentinel.remove();
        if (!url) return Promise.resolve();

        return fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(function(res) { return res.text(); })
        .then(function(html) {
            insertContent(el, html);
            return drainBatches(el);
        });
    }

    function buildSkeleton(type, count) {
        var html = '<div class="lazy-skeleton">';
        for (var i = 0; i < count; i++) {
            if (type === 'rows') {
                html += '<div class="lazy-skeleton-row">' +
                    '<div class="lazy-skeleton-avatar"></div>' +
                    '<div class="lazy-skeleton-lines">' +
                        '<div class="lazy-skeleton-bar bar-subtitle"></div>' +
                        '<div class="lazy-skeleton-bar bar-short"></div>' +
                    '</div></div>';
            } else if (type === 'sections') {
                html += '<div class="lazy-skeleton-section">' +
                    '<div class="lazy-skeleton-section-header">' +
                        '<div class="lazy-skeleton-section-icon"></div>' +
                        '<div class="lazy-skeleton-bar bar-title" style="margin:0"></div>' +
                    '</div>' +
                    '<div class="lazy-skeleton-row"><div class="lazy-skeleton-avatar"></div>' +
                        '<div class="lazy-skeleton-lines"><div class="lazy-skeleton-bar bar-subtitle"></div><div class="lazy-skeleton-bar bar-short"></div></div></div>' +
                    '<div class="lazy-skeleton-row"><div class="lazy-skeleton-avatar"></div>' +
                        '<div class="lazy-skeleton-lines"><div class="lazy-skeleton-bar bar-text"></div><div class="lazy-skeleton-bar bar-short"></div></div></div>' +
                '</div>';
            } else {
                html += '<div class="lazy-skeleton-card">' +
                    '<div class="lazy-skeleton-bar bar-title"></div>' +
                    '<div class="lazy-skeleton-bar bar-text"></div>' +
                    '<div class="lazy-skeleton-bar bar-subtitle"></div>' +
                    '<div class="lazy-skeleton-bar bar-short"></div>' +
                '</div>';
            }
        }
        html += '</div>';
        return html;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
