/* Shared JavaScript functionality for promise views (admin and leader) */

// Initialize promise view functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeCollapseControls();
    initializeUserClickHandlers();
    initializeViewToggle();
});

// Initialize collapse controls for folder icons
function initializeCollapseControls() {
    const folderIcons = document.querySelectorAll('.tree a[data-toggle="collapse"]');
    folderIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            const expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !expanded);
        });
    });
}

// Initialize click handlers for user spans
function initializeUserClickHandlers() {
    const userSpans = document.querySelectorAll('.userSpan');
    userSpans.forEach(span => {
        span.style.cursor = 'pointer';
        
        span.addEventListener('click', function(e) {
            // Prevent click from affecting parent elements
            e.stopPropagation();
            
            // Extract user information
            const username = this.innerText.split('-')[0].trim();
            const stats = getUserStats(username);
            
            // Show SweetAlert with user statistics
            Swal.fire({
                title: username,
                html: `
                    <div style="text-align: center; margin-bottom: 15px;">
                        <div style="display: inline-block; margin: 0 10px;">
                            <i class="fas fa-check-circle" style="color: #50dc36; font-size: 24px;"></i>
                            <div><strong>${stats.attending}</strong></div>
                        </div>
                        <div style="display: inline-block; margin: 0 10px;">
                            <i class="fas fa-times-circle" style="color: #dc3836; font-size: 24px;"></i>
                            <div><strong>${stats.notAttending}</strong></div>
                        </div>
                        <div style="display: inline-block; margin: 0 10px;">
                            <i class="fas fa-question-circle" style="color: gray; font-size: 24px;"></i>
                            <div><strong>${stats.noResponse}</strong></div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'Passwort zurücksetzen',
                confirmButtonColor: '#3085d6',
                denyButtonText: 'Account löschen',
                denyButtonColor: '#d33',
                cancelButtonText: 'Abbrechen',
            }).then((result) => {
                if (result.isDenied) {
                    deleteAccount(username);
                } else if (result.isConfirmed) {
                    resetPassword(username);
                }
            });
        });
    });
}

// Get user attendance statistics
function getUserStats(username) {
    // Find all instances of this username in the document
    const userSpans = Array.from(document.querySelectorAll('.userSpan')).filter(span => 
        span.textContent.includes(username)
    );
    
    // Count each status type
    let attending = 0;
    let notAttending = 0;
    let noResponse = 0;
    
    userSpans.forEach(span => {
        if (span.querySelector('.fa-check-circle')) {
            attending++;
        } else if (span.querySelector('.fa-times-circle')) {
            notAttending++;
        } else if (span.querySelector('.fa-question-circle')) {
            noResponse++;
        }
    });
    
    return { attending, notAttending, noResponse };
}

// Delete user account
function deleteAccount(username) {
    Swal.fire({
        title: "Account Löschen",
        html: "Willst du den Account von " + username + " wirklich löschen?<br>Wir können keine Daten wiederherstellen!",
        showCancelButton: true,
        confirmButtonText: "Löschen",
        confirmButtonColor: '#d33', // Red button for deletion
        cancelButtonText: "Abbrechen",
        icon: 'warning'
    }).then((result) => {
        if (result.isConfirmed) {
            // Use the MVC controller endpoint
            fetch('/user/deleteUser?username=' + encodeURIComponent(username))
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.error || 'Server returned ' + response.status);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    // Use toast notification for success
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'bottom-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        didClose: () => {
                            // Reload the page to reflect the account deletion
                            window.location.reload();
                        }
                    });
                    
                    Toast.fire({
                        icon: "success",
                        title: data.message
                    });
                })
                .catch(error => {
                    console.error('Error deleting account:', error);
                    showErrorToast(error.message || "Die Anfrage konnte nicht verarbeitet werden.");
                });
        }
    });
}

// Reset user password
function resetPassword(username) {
    Swal.fire({
        title: "Passwort zurücksetzen",
        text: "Willst du das Passwort von " + username + " wirklich zurücksetzen?\nWir können keine Daten wiederherstellen!",
        showCancelButton: true,
        confirmButtonText: "Zurücksetzen",
        confirmButtonColor: '#3085d6',
        cancelButtonText: "Abbrechen",
    }).then((result) => {
        if (result.isConfirmed) {
            // Use the MVC controller endpoint
            fetch('/user/resetPassword?username=' + encodeURIComponent(username))
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.error || 'Server returned ' + response.status);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    // Use toast notification for success
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'bottom-end',
                        showConfirmButton: false,
                        timer: 10000,
                        timerProgressBar: true
                    });
                    
                    Toast.fire({
                        icon: "success",
                        title: data.message
                    });
                })
                .catch(error => {
                    console.error('Error resetting password:', error);
                    showErrorToast(error.message || "Die Anfrage konnte nicht verarbeitet werden.", 10000);
                });
        }
    });
}

// Show error toast notification
function showErrorToast(message, timer = 3000) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: timer,
        timerProgressBar: true
    });
    
    Toast.fire({
        icon: "error",
        title: message
    });
}

// Initialize view toggle functionality (leader view only)
function initializeViewToggle() {
    const viewToggle = document.getElementById('viewToggle');
    if (!viewToggle) return; // Only exists on leader view
    
    const simpleViews = document.querySelectorAll('.simple-view');
    const sectionalViews = document.querySelectorAll('.sectional-view');
    const toggleLabels = document.querySelectorAll('.toggle-label');
    
    // Update label styling based on toggle state
    function updateLabels(isChecked) {
        // Single label variant: highlight when ON
        if (toggleLabels[0]) {
            toggleLabels[0].classList.toggle('active', isChecked);
        }
    }
    
    // Toggle views
    function toggleViews(showSectional) {
        simpleViews.forEach(view => {
            view.style.display = showSectional ? 'none' : 'block';
        });
        
        sectionalViews.forEach(view => {
            view.style.display = showSectional ? 'block' : 'none';
        });
        
        updateLabels(showSectional);
        
        // Store preference in localStorage
        localStorage.setItem('leaderViewMode', showSectional ? 'sectional' : 'simple');
    }
    
    // Load saved preference
    const savedMode = localStorage.getItem('leaderViewMode');
    if (savedMode === 'sectional') {
        viewToggle.checked = true;
        toggleViews(true);
    } else {
        updateLabels(false);
    }
    
    // Handle toggle change
    viewToggle.addEventListener('change', function() {
        toggleViews(this.checked);
        
        // Add smooth transition effect
        const container = document.querySelector('.container-fluid');
        container.style.opacity = '0.7';
        setTimeout(() => {
            container.style.opacity = '1';
        }, 200);
    });
}
