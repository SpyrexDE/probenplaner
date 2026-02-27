// Member Actions (Shared between Members Page and Promises Dashboard)

function getOrchestraBase() {
    const pathParts = window.location.pathname.split('/').filter(p => p);
    if (pathParts.length >= 2) {
        return pathParts[0] + '/' + pathParts[1];
    }
    return '';
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function buildSectionOptions(availableSections, displayNames) {
    let html = '';
    for (const [group, items] of Object.entries(availableSections)) {
        html += `<optgroup label="${displayNames[group] || group}">`;
        for (const item of items) {
            html += `<option value="${item}">${displayNames[item] || item}</option>`;
        }
        html += '</optgroup>';
    }
    return html;
}

function buildRoleOptions(roles) {
    return roles.map(r => {
        const dot = `<span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: ${r.tag_color}; margin-right: 6px; vertical-align: middle;"></span>`;
        return `<option value="${r.id}" data-color="${r.tag_color}">${r.name}</option>`;
    }).join('');
}

function openEditModal(userId) {
    const orchestraBase = getOrchestraBase();
    fetch('/' + orchestraBase + '/members/' + userId + '/details', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                window.notifyErrorWithDetails('Fehler beim Laden', data.error);
                return;
            }

            const initial = (data.display_name || 'U').charAt(0).toUpperCase();
            const roles = data.available_roles || [];

            Swal.fire({
                html: `
            <div class="swal-members-permissions">
                <div class="swal-member-header">
                    <div class="swal-member-avatar">${initial}</div>
                    <div class="swal-member-info">
                        <div class="swal-member-name">${data.display_name || data.username}</div>
                        <div class="swal-member-username">@${data.username}</div>
                    </div>
                </div>

                <div class="swal-field-group">
                    <label class="swal-field-label">Register</label>
                    <select id="swalType" class="swal-select-modern">
                        ${buildSectionOptions(data.available_sections || {}, data.display_names || {})}
                    </select>
                </div>

                <div class="swal-perm-row" style="margin-bottom: var(--space-3);">
                    <input type="checkbox" id="swalSmallGroup" ${data.is_small_group ? 'checked' : ''}>
                    <label for="swalSmallGroup">Kleingruppe</label>
                </div>

                ${data.current_user_can_manage_permissions && roles.length ? `
                <div class="swal-field-group">
                    <label class="swal-field-label">Rolle</label>
                    <select id="swalRole" class="swal-select-modern">
                        ${buildRoleOptions(roles)}
                    </select>
                </div>
                ` : ''}

                <div class="swal-field-group" style="margin-top: 1.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; border-top: 1px solid var(--color-gray-200); padding-top: 1.5rem;">
                    <button type="button" id="swalResetPasswordBtn" class="btn-modern" style="padding: 0.375rem 0.75rem; border-radius: 0.375rem; border: 1px solid #d1d5db; background: white; color: #4b5563; font-weight: 500; font-size: 0.875rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; transition: background-color 0.15s, border-color 0.15s;" onmouseover="this.style.backgroundColor='#f9fafb'; this.style.borderColor='#9ca3af'" onmouseout="this.style.backgroundColor='white'; this.style.borderColor='#d1d5db'">
                        <i class="fas fa-key" style="color: #f59e0b;"></i> Passwort zurücksetzen
                    </button>
                    <small style="color: #6b7280; font-size: 0.75rem; margin-top: 0.375rem;">Setzt das Passwort auf ein neues, zufälliges Passwort zurück.</small>
                </div>
            </div>
        `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '<i class="fas fa-save"></i> Speichern',
                cancelButtonText: 'Abbrechen',
                denyButtonText: '<i class="fas fa-user-minus"></i> Entfernen',
                confirmButtonColor: '#478cf4',
                cancelButtonColor: '#6b7280',
                denyButtonColor: '#ef4444',
                reverseButtons: true,
                focusConfirm: false,
                didOpen: () => {
                    const sel = document.getElementById('swalType');
                    if (sel && data.type) sel.value = data.type;

                    const roleSel = document.getElementById('swalRole');
                    if (roleSel && data.role_id) roleSel.value = data.role_id;

                    const resetBtn = document.getElementById('swalResetPasswordBtn');
                    if (resetBtn) {
                        resetBtn.addEventListener('click', () => {
                            Swal.close();
                            resetPassword(data.username);
                        });
                    }
                }
            }).then(result => {
                if (result.isConfirmed) {
                    saveEditModal(userId);
                } else if (result.isDenied) {
                    confirmRemoveMember(userId, data.display_name || data.username);
                }
            });
        })
        .catch(err => {
            window.notifyErrorWithDetails('Fehler beim Laden der Mitgliederdaten', err.message || String(err));
        });
}

function saveEditModal(userId) {
    const orchestraBase = getOrchestraBase();
    const typeEl = document.getElementById('swalType');
    const smallGrpEl = document.getElementById('swalSmallGroup');
    const roleEl = document.getElementById('swalRole');

    const params = new URLSearchParams({
        csrf_token: getCsrfToken(),
        type: typeEl ? typeEl.value : '',
        is_small_group: (smallGrpEl && smallGrpEl.checked) ? '1' : '0',
    });

    if (roleEl) {
        params.set('role_id', roleEl.value);
    }

    fetch('/' + orchestraBase + '/members/' + userId + '/update', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: params,
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.notifySuccess('Gespeichert');
                setTimeout(() => location.reload(), 600);
            } else {
                window.notifyErrorWithDetails('Speichern fehlgeschlagen', data.debug_message || data.error || JSON.stringify(data));
            }
        })
        .catch(err => {
            window.notifyErrorWithDetails('Speichern fehlgeschlagen', err.message || String(err));
        });
}

function confirmRemoveMember(userId, displayName) {
    const orchestraBase = getOrchestraBase();
    Swal.fire({
        title: 'Mitglied entfernen?',
        html: `<p style="color: var(--color-text-secondary);">
            <strong>${displayName}</strong> wird aus dem Ensemble entfernt. Diese Aktion kann nicht rückgängig gemacht werden.
        </p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ja, entfernen',
        cancelButtonText: 'Abbrechen',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        focusCancel: true,
    }).then(result => {
        if (result.isConfirmed) {
            fetch('/' + orchestraBase + '/members/' + userId + '/remove', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'csrf_token=' + encodeURIComponent(getCsrfToken())
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.notifySuccess('Mitglied entfernt');
                        setTimeout(() => location.reload(), 600);
                    } else {
                        window.notifyErrorWithDetails('Entfernen fehlgeschlagen', data.error || JSON.stringify(data));
                    }
                })
                .catch(err => {
                    window.notifyErrorWithDetails('Fehler beim Entfernen', err.message || String(err));
                });
        }
    });
}

function resetPassword(username) {
    const orchestraBase = getOrchestraBase();
    Swal.fire({
        title: "Passwort zurücksetzen",
        html: "Willst du das Passwort von <strong>" + username + "</strong> wirklich zurücksetzen?",
        showCancelButton: true,
        confirmButtonText: "Zurücksetzen",
        confirmButtonColor: '#3085d6',
        cancelButtonText: "Abbrechen",
        icon: 'question'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/' + orchestraBase + '/user/resetPassword?username=' + encodeURIComponent(username), {
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(async response => {
                    const text = await response.text();
                    let parseJson = null;
                    try { parseJson = JSON.parse(text); } catch (e) { }
                    if (!response.ok) {
                        const message = (parseJson && (parseJson.error || parseJson.message)) || text;
                        throw new Error(message);
                    }
                    return parseJson || {};
                })
                .then(data => {
                    const password = data.password || '';

                    Swal.fire({
                        title: 'Passwort gesetzt',
                        html: '<div style="text-align: left; margin: 15px 0;">' +
                            '<p style="margin-bottom: 15px;">Das Passwort wurde zurückgesetzt.</p>' +
                            '<div style="background: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px solid #dee2e6; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">' +
                            '<div style="font-family: monospace; font-size: 18px; font-weight: bold; color: #495057;">' +
                            password +
                            '</div>' +
                            '<button id="copyPasswordBtn" style="background: #478cf4; border: none; border-radius: 4px; color: white; padding: 8px 12px; cursor: pointer; font-size: 12px; font-weight: 500; display: flex; align-items: center; gap: 4px; margin-left: 15px;">' +
                            '<i class="fas fa-copy"></i> Kopieren' +
                            '</button>' +
                            '</div>' +
                            '</div>',
                        icon: 'success',
                        confirmButtonText: 'Verstanden',
                        confirmButtonColor: '#478cf4',
                        allowOutsideClick: false,
                        didOpen: () => {
                            const copyBtn = document.getElementById('copyPasswordBtn');
                            if (copyBtn) {
                                copyBtn.addEventListener('click', () => {
                                    navigator.clipboard.writeText(password).then(() => {
                                        copyBtn.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
                                        copyBtn.style.background = '#28a745';
                                        setTimeout(() => {
                                            copyBtn.innerHTML = '<i class="fas fa-copy"></i> Kopieren';
                                            copyBtn.style.background = '#478cf4';
                                        }, 2000);
                                    }).catch(() => {
                                        const ta = document.createElement('textarea');
                                        ta.value = password;
                                        document.body.appendChild(ta);
                                        ta.select();
                                        document.execCommand('copy');
                                        document.body.removeChild(ta);
                                        copyBtn.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
                                        copyBtn.style.background = '#28a745';
                                    });
                                });
                            }
                        }
                    });
                })
                .catch(error => {
                    window.notifyErrorWithDetails("Fehler beim Zurücksetzen.", error.message);
                });
        }
    });
}
