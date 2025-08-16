// Unified notification helpers using SweetAlert2 toasts
// Usage: window.notifySuccess('Saved'), window.notifyError('Error message'), window.notifyInfo('FYI')
(function() {
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
        const Toast = createToast(opts);
        Toast.fire({ icon: icon, title: title });
    }

    window.notifySuccess = function(message, opts) { fire('success', message, opts); };
    window.notifyError = function(message, opts) { fire('error', message, opts); };
    window.notifyInfo = function(message, opts) { fire('info', message, opts); };
})();


