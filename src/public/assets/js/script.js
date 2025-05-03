/**
 * JSO-Planer
 * Main JavaScript file
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

$(document).ready(function() {
    // Add event handlers for history and help links
    $(".history-link").click(function(e) {
        e.preventDefault();
        window.location.href = window.location.href + (window.location.href.indexOf('?') > -1 ? '&' : '?') + 'showOld=true';
    });
    
    $(".help-link").click(function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Hilfe',
            html: '<p>Durch <span style="color: green;">grüne</span> und <span style="color: red;">rote</span> Einträge kannst du uns rückmelden, ob du an der Probe teilnimmst oder nicht.</p>' +
                  '<p>Du kannst auch Anmerkungen hinzufügen, indem du über einen Eintrag fährst und das Plus-Symbol anklickst.</p>' +
                  '<p>Bei Fragen kannst du dich gerne an mich wenden!</p>',
            icon: 'info',
            confirmButtonColor: '#478cf4'
        });
    });
    
    // Toggle sidebar menu
    $("#menu-toggle").click(function(e) {
        e.preventDefault();
        
        if ($("#wrapper").length) {
            var wrapper = $("#wrapper");
            var sidebarWrapper = $("#sidebar-wrapper");
            var isToggled = wrapper.hasClass("toggled");
            
            // Instead of toggling class, directly manage the sidebar visibility
            if ($(window).width() >= 768) {
                // Desktop behavior (sidebar visible by default)
                if (!isToggled) {
                    // Hide sidebar
                    wrapper.addClass("toggled");
                    wrapper.css("padding-left", "0");
                    sidebarWrapper.css("width", "0");
                } else {
                    // Show sidebar 
                    wrapper.removeClass("toggled");
                    wrapper.css("padding-left", "250px");
                    sidebarWrapper.css("width", "250px");
                }
            } else {
                // Mobile behavior (sidebar hidden by default)
                if (!isToggled) {
                    // Show sidebar
                    wrapper.addClass("toggled");
                    wrapper.css("padding-left", "250px");
                    sidebarWrapper.css("width", "250px");
                } else {
                    // Hide sidebar
                    wrapper.removeClass("toggled");
                    wrapper.css("padding-left", "0");
                    sidebarWrapper.css("width", "0");
                }
            }
            
            // Force browser to repaint
            sidebarWrapper[0].offsetHeight;
            wrapper[0].offsetHeight;
        }
    });
    
    // Tooltip implementation
    $("#toolTip1").click(function() {
        Swal.fire({
            title: 'Hilfe',
            html: '<p>Durch <span style="color: green;">grüne</span> und <span style="color: red;">rote</span> Einträge kannst du uns rückmelden, ob du an der Probe teilnimmst oder nicht.</p>' +
                  '<p>Du kannst auch Anmerkungen hinzufügen, indem du über einen Eintrag fährst und das Plus-Symbol anklickst.</p>' +
                  '<p>Bei Fragen kannst du dich gerne an mich wenden!</p>',
            icon: 'info',
            confirmButtonColor: '#478cf4'
        });
    });
    
    // Handle rehearsal promise buttons
    $(".checkBtn").click(function() {
        const id = $(this).attr("id");
        if (!$(this).hasClass("deselected")) {
            return;
        }
        
        $(this).removeClass("deselected");
        $(this).siblings(".crossBtn").addClass("deselected");
        
        updatePromise(id, true);
    });
    
    $(".crossBtn").click(function() {
        const id = $(this).attr("id");
        if (!$(this).hasClass("deselected")) {
            return;
        }
        
        $(this).removeClass("deselected");
        $(this).siblings(".checkBtn").addClass("deselected");
        
        updatePromise(id, false);
    });
    
    // Note buttons
    $(".greenOut").hover(
        function() {
            $(this).find(".hideIcon").removeClass("hideIcon").addClass("showIcon");
        },
        function() {
            $(this).find(".showIcon").removeClass("showIcon").addClass("hideIcon");
        }
    );
    
    $(".addNoteBtn").click(function() {
        const iconId = $(this).attr("id");
        const rehearsalId = iconId.replace("icon", "");
        
        // Get current note if available
        let currentNote = "";
        if ($(this).hasClass("fa-pen-square")) {
            // Find the note by checking data attributes or other means
            // For now, we'll leave it empty as we don't have access to existing notes
        }
        
        Swal.fire({
            title: 'Anmerkung hinzufügen',
            input: 'textarea',
            inputValue: currentNote,
            inputPlaceholder: 'Deine Anmerkung hier eingeben...',
            showCancelButton: true,
            confirmButtonText: 'Speichern',
            cancelButtonText: 'Abbrechen',
            confirmButtonColor: '#478cf4',
            inputValidator: (value) => {
                // No validation necessary, empty notes are allowed
            }
        }).then((result) => {
            if (result.isConfirmed) {
                saveNote(rehearsalId, result.value);
            }
        });
    });
    
    // Open old version functionality
    window.openOld = function() {
        window.location.href = window.location.href + (window.location.href.indexOf('?') > -1 ? '&' : '?') + 'showOld=true';
    }
});

/**
 * Update rehearsal promise
 * @param {string} id Rehearsal ID
 * @param {boolean} status Promise status (true for attending, false for not attending)
 */
function updatePromise(id, status) {
    $.ajax({
        url: "/promises/update",
        type: "POST",
        data: {
            id: id,
            status: status ? 1 : 0
        },
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Gespeichert!',
                    text: status ? 'Deine Zusage wurde gespeichert.' : 'Deine Absage wurde gespeichert.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false,
                    confirmButtonColor: '#478cf4'
                });
                
                // Update UI if needed
                if (status) {
                    // Promised to attend
                    $(`#${id}`).closest(".greenOut").removeClass("redOut yellowOut");
                } else {
                    // Promised not to attend
                    $(`#${id}`).closest(".greenOut").addClass("redOut").removeClass("yellowOut");
                }
            } else {
                Swal.fire({
                    title: 'Fehler!',
                    text: 'Fehler beim Speichern: ' + response.message,
                    icon: 'error',
                    confirmButtonColor: '#478cf4'
                });
            }
        },
        error: function() {
            Swal.fire({
                title: 'Fehler!',
                text: 'Ein Fehler ist aufgetreten. Bitte versuche es später erneut.',
                icon: 'error',
                confirmButtonColor: '#478cf4'
            });
        }
    });
}

/**
 * Save rehearsal note
 * @param {string} id Rehearsal ID
 * @param {string} note Note text
 */
function saveNote(id, note) {
    $.ajax({
        url: "/promises/note",
        type: "POST",
        data: {
            id: id,
            note: note
        },
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Gespeichert!',
                    text: 'Deine Anmerkung wurde gespeichert.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false,
                    confirmButtonColor: '#478cf4'
                });
                
                // Update UI
                if (note.trim() !== "") {
                    $(`#icon${id}`).removeClass("fa-plus-square").addClass("fa-pen-square").css("color", "");
                } else {
                    $(`#icon${id}`).removeClass("fa-pen-square").addClass("fa-plus-square").css("color", "lightgrey");
                }
            } else {
                Swal.fire({
                    title: 'Fehler!',
                    text: 'Fehler beim Speichern: ' + response.message,
                    icon: 'error',
                    confirmButtonColor: '#478cf4'
                });
            }
        },
        error: function() {
            Swal.fire({
                title: 'Fehler!',
                text: 'Ein Fehler ist aufgetreten. Bitte versuche es später erneut.',
                icon: 'error',
                confirmButtonColor: '#478cf4'
            });
        }
    });
}

// Helper functions for cookies (used in the original codebase)
function getCookie(name) {
    var dc = document.cookie;
    var prefix = name + "=";
    var begin = dc.indexOf("; " + prefix);
    if (begin == -1) {
        begin = dc.indexOf(prefix);
        if (begin != 0) return null;
    }
    else {
        begin += 2;
        var end = document.cookie.indexOf(";", begin);
        if (end == -1) {
            end = dc.length;
        }
    }
    return decodeURI(dc.substring(begin + prefix.length, end));
} 