/*
 * CONSISTENT PATTERNS FOR AI:
 * - Use window.notifySuccess/Error/Info() for all notifications
 * - Use existing .btn-base classes for buttons
 * - Use existing form validation patterns
 * - Never change CSS class names that JavaScript depends on
 */

/* Shared JavaScript functionality for promise views (admin and leader) */

// Initialize promise view functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeCollapseControls();
    initializeUserClickHandlers();
    initializeViewToggle();
});

// Initialize collapse controls for tree nodes
function initializeCollapseControls() {
    const treeHeaders = document.querySelectorAll('.tree-node-header[data-toggle="collapse"]');
    treeHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const expanded = this.getAttribute('aria-expanded') === 'true';
            const newExpanded = !expanded;
            this.setAttribute('aria-expanded', newExpanded);
            
            // Toggle the parent tree node expanded class
            const treeNode = this.closest('.tree-node');
            if (treeNode) {
                treeNode.classList.toggle('tree-node-expanded', newExpanded);
            }
            
            // Get target content element
            const targetId = this.getAttribute('href');
            if (targetId) {
                const target = document.querySelector(targetId);
                if (target) {
                    // Toggle content visibility
                    if (newExpanded) {
                        target.classList.remove('collapsed');
                        target.classList.add('expanded', 'expanding');
                        // Remove expanding class after animation
                        setTimeout(() => {
                            target.classList.remove('expanding');
                        }, 200);
                    } else {
                        target.classList.remove('expanded', 'expanding');
                        target.classList.add('collapsed');
                    }
                }
            }
        });
    });
}

// Initialize click handlers for user spans
function initializeUserClickHandlers() {
    const userSpans = document.querySelectorAll('.userSpan');
    userSpans.forEach(span => {
        if (span) {
            span.style.cursor = 'pointer';
            
            span.addEventListener('click', function(e) {
                // Prevent click from affecting parent elements
                e.stopPropagation();
                
                // Extract user information (only username, exclude note)
                const usernameElement = this.querySelector('.tree-user-item-name');
                const username = usernameElement ? usernameElement.textContent.trim() : this.innerText.split('-')[0].trim();
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
        }
    });
}

// Get user attendance statistics
function getUserStats(username) {
    // Find all instances of this username in the document
    const userSpans = Array.from(document.querySelectorAll('.userSpan')).filter(span => {
        const usernameElement = span.querySelector('.tree-user-item-name');
        return usernameElement ? usernameElement.textContent.trim() === username : span.textContent.includes(username);
    });
    
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
                    window.notifySuccess(data.message, { timer: 2000 });
                    setTimeout(() => window.location.reload(), 600);
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
                    // Extract password from message and show in a modal that can be copied
                    const msg = data.message || 'Passwort zurückgesetzt';
                    const passwordMatch = msg.match(/zurückgesetzt:\s*(\S+)$/);
                    const password = passwordMatch ? passwordMatch[1] : '';
                    const userMatch = msg.match(/Nutzers\s+(\S+)\s+wurde/);
                    const username = userMatch ? userMatch[1] : '';
                    
                    Swal.fire({
                        title: 'Passwort zurückgesetzt',
                        html: '<div style="text-align: left; margin: 15px 0;">' +
                                  '<p style="margin-bottom: 15px;">Das Passwort des Nutzers <strong>' + username + '</strong> wurde zurückgesetzt</p>' +
                                  '<div style="background: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px solid #dee2e6; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">' +
                                      '<div style="font-family: monospace; font-size: 18px; font-weight: bold; color: #495057;">' +
                                          password +
                                      '</div>' +
                                      '<button id="copyPasswordBtn" style="background: #478cf4; border: none; border-radius: 4px; color: white; padding: 8px 12px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s ease; display: flex; align-items: center; gap: 4px; margin-left: 15px;" onmouseover="this.style.background=\'#3b7ae0\'" onmouseout="this.style.background=\'#478cf4\'">' +
                                          '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                                              '<rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>' +
                                              '<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>' +
                                          '</svg>' +
                                          'Kopieren' +
                                      '</button>' +
                                  '</div>' +
                                  '<small style="color: #6c757d;">Teile dieses Passwort sicher mit dem Benutzer</small>' +
                               '</div>',
                        icon: 'success',
                        confirmButtonText: 'Verstanden',
                        confirmButtonColor: '#478cf4',
                        allowOutsideClick: false,
                        didOpen: () => {
                            // Add copy functionality
                            const copyBtn = document.getElementById('copyPasswordBtn');
                            if (copyBtn) {
                                copyBtn.addEventListener('click', () => {
                                    navigator.clipboard.writeText(password).then(() => {
                                        copyBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20,6 9,17 4,12"></polyline></svg> Kopiert!';
                                        copyBtn.style.background = '#28a745';
                                        setTimeout(() => {
                                            copyBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg> Kopieren';
                                            copyBtn.style.background = '#478cf4';
                                        }, 2000);
                                    }).catch(err => {
                                        console.error('Failed to copy password:', err);
                                        // Fallback for older browsers
                                        const textArea = document.createElement('textarea');
                                        textArea.value = password;
                                        document.body.appendChild(textArea);
                                        textArea.select();
                                        document.execCommand('copy');
                                        document.body.removeChild(textArea);
                                        
                                        copyBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20,6 9,17 4,12"></polyline></svg> Kopiert!';
                                        copyBtn.style.background = '#28a745';
                                        setTimeout(() => {
                                            copyBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg> Kopieren';
                                            copyBtn.style.background = '#478cf4';
                                        }, 2000);
                                    });
                                });
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error resetting password:', error);
                    showErrorToast(error.message || "Die Anfrage konnte nicht verarbeitet werden.", 10000);
                });
        }
    });
}

// Standardized API call helper
function standardApiCall(url, options = {}) {
    return fetch(url, {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' },
        ...options
    }).then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.error || `Server returned ${response.status}`);
            });
        }
        return response.json();
    });
}

// Show error toast notification
function showErrorToast(message, timer = 3000) {
    window.notifyError(message, { timer: timer });
}

// Initialize view toggle functionality (leader view only)
function initializeViewToggle() {
    const viewToggle = document.getElementById('viewToggle');
    if (!viewToggle) return; // Only exists on leader view
    if (viewToggle.disabled) return; // Disabled by settings: leave both views in default (simple) and gray out
    
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
            if (view) {
                view.style.display = showSectional ? 'none' : 'block';
            }
        });
        
        sectionalViews.forEach(view => {
            if (view) {
                view.style.display = showSectional ? 'block' : 'none';
            }
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
        if (container) {
            container.style.opacity = '0.7';
            setTimeout(() => {
                container.style.opacity = '1';
            }, 200);
        }
    });
}
