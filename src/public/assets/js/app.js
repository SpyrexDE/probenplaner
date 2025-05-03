/**
 * JSO-APP
 * App object and icon handlers
 */

// Create app object for global functions
window.app = {
    show_history: function() {
        window.location.href = window.location.href + (window.location.href.indexOf('?') > -1 ? '&' : '?') + 'showOld=true';
    },
    
    help: function() {
        Swal.fire({
            title: 'Hilfe',
            html: '<p>Durch <span style="color: green;">grüne</span> und <span style="color: red;">rote</span> Einträge kannst du uns rückmelden, ob du an der Probe teilnimmst oder nicht.</p>' +
                  '<p>Du kannst auch Anmerkungen hinzufügen, indem du über einen Eintrag fährst und das Plus-Symbol anklickst.</p>' +
                  '<p>Bei Fragen kannst du dich gerne an mich wenden!</p>',
            icon: 'info',
            confirmButtonColor: '#478cf4'
        });
    }
};

// Initialize when document is ready
$(document).ready(function() {
    // Add event handlers for history and help links
    $(".history-link").click(function(e) {
        e.preventDefault();
        app.show_history();
    });
    
    $(".help-link").click(function(e) {
        e.preventDefault();
        app.help();
    });
}); 