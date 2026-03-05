// Unified notification helpers using SweetAlert2 toasts
// Usage: window.notifySuccess('Saved'), window.notifyError('Message'), window.notifyError('Message', { details: stackTrace }), window.notifyInfo('FYI')
(function () {
    function createToast(options) {
        return Swal.mixin(Object.assign({
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        }, options || {}));
    }

    function fire(icon, title, opts) {
        if (icon === 'error') {
            const stackTrace = opts && opts.details ? opts.details : null;
            let htmlContent = escapeHtml(title);
            let uniqueId = null;

            // Only show expandable details for stack traces
            if (stackTrace) {
                uniqueId = 'error-' + Math.random().toString(36).substr(2, 9);
                htmlContent += `<br>
                       <button id="btn-${uniqueId}" style="margin-top:10px;" class="swal2-styled">Details anzeigen</button>
                       <div id="details-${uniqueId}" style="display:none; margin-top:10px; text-align:left; font-size:12px; color:#a94442; background:#f9f2f4; border:1px solid #ebccd1; padding:10px; border-radius:4px; white-space:pre-wrap; max-height: 300px; overflow-y: auto;">${escapeHtml(stackTrace)}</div>`;
            }

            return Swal.fire({
                title: 'Fehler',
                text: stackTrace ? null : title,
                html: stackTrace ? htmlContent : null,
                icon: icon,
                confirmButtonColor: '#478cf4',
                showConfirmButton: true,
                allowOutsideClick: false,
                didOpen: () => {
                    if (stackTrace && uniqueId) {
                        const btn = document.getElementById(`btn-${uniqueId}`);
                        const detailsEl = document.getElementById(`details-${uniqueId}`);
                        if (btn && detailsEl) {
                            btn.onclick = function () {
                                if (detailsEl.style.display === 'none') {
                                    detailsEl.style.display = 'block';
                                    btn.textContent = 'Details ausblenden';
                                } else {
                                    detailsEl.style.display = 'none';
                                    btn.textContent = 'Details anzeigen';
                                }
                            };
                        }
                    }
                }
            });
        } else {
            const Toast = createToast(opts);
            return Toast.fire({ icon: icon, title: title });
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        if (typeof text !== 'string') text = JSON.stringify(text, null, 2);
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    window.notifySuccess = function (message, opts) { fire('success', message, opts); };
    window.notifyError = function (message, opts) { fire('error', message, opts); };
    window.notifyInfo = function (message, opts) { fire('info', message, opts); };

    // Stack-trace errors only (used by global error handlers)
    window.notifyErrorWithDetails = function (message, stackTrace) {
        fire('error', message, { details: stackTrace });
    };

    // Global Error Handlers
    let isHandlingError = false; // Guard against infinite loops

    // Catch JS errors
    window.onerror = function (message, source, lineno, colno, error) {
        // Ignore extension errors or external script errors
        if (!source || source.indexOf('http') !== 0 && source.indexOf('/') !== 0) return;

        // Prevent infinite loops if notification system itself fails
        if (isHandlingError) {
            console.error('Critical: Error occurred while handling an error.', message);
            return;
        }

        isHandlingError = true;

        try {
            const details = `Error: ${message}\nSource: ${source}:${lineno}:${colno}\nStack: ${error ? error.stack : 'N/A'}`;
            console.error('Global Error Caught:', details);

            // Only show if not already handled/prevented
            window.notifyErrorWithDetails('Ein unerwarteter JavaScript-Fehler ist aufgetreten.', details);
        } catch (e) {
            console.error('Failed to display error notification:', e);
        } finally {
            isHandlingError = false;
        }

        return false; // Let default handler run too (logging to console)
    };

    // Catch Unhandled Promise Rejections
    window.onunhandledrejection = function (event) {
        // Prevent infinite loops
        if (isHandlingError) return;

        isHandlingError = true;

        try {
            const reason = event.reason;
            let details = 'Unknown Promise Error';

            if (reason instanceof Error) {
                details = `${reason.message}\nStack: ${reason.stack}`;
            } else {
                details = JSON.stringify(reason);
            }

            console.error('Unhandled Promise Rejection:', details);
            window.notifyErrorWithDetails('Ein asynchroner Fehler ist aufgetreten.', details);
        } catch (e) {
            console.error('Failed to display promise error notification:', e);
        } finally {
            isHandlingError = false;
        }
    };

})();


