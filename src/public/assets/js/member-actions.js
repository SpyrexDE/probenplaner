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
        if (group) html += `<optgroup label="${displayNames[group] || group}">`;
        for (const item of items) {
            html += `<option value="${item}">${displayNames[item] || item}</option>`;
        }
        if (group) html += '</optgroup>';
    }
    return html;
}

function buildRoleTagsHtml(roles, selectedIds) {
    const selected = selectedIds || [];
    let tagsHtml = '';
    for (const r of roles) {
        if (r.is_system || !selected.includes(r.id)) continue;
        const star = r.is_default ? '<i class="fas fa-star swal-role-default-star"></i>' : '';
        tagsHtml += `<span class="role-tag swal-role-tag" data-id="${r.id}" data-default="${r.is_default ? '1' : ''}" style="--role-color:${r.tag_color}">${star}${escHtml(r.name)} <span class="swal-role-tag-remove" data-id="${r.id}">&times;</span></span>`;
    }
    return tagsHtml;
}

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
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
                window.notifyError(data.error || 'Fehler beim Laden');
                return;
            }

            const initial = (data.display_name || 'U').charAt(0).toUpperCase();
            const roles = data.available_roles || [];
            const selectedRoleIds = data.role_ids || [];

            Swal.fire({
                html: `
            <style>
            .swal-role-tags-container { display:flex; flex-wrap:wrap; align-items:center; gap:6px; padding:8px 12px; min-height:44px; border:2px solid var(--color-border); border-radius:var(--radius-base); background:var(--color-bg-primary); cursor:text; transition:border-color .2s; }
            .swal-role-tags-container:focus-within { border-color:var(--color-primary); }
            .swal-role-tag { font-size:13px; padding:5px 10px; text-transform:none; animation:swalTagIn .15s ease-out; }
            .swal-role-default-star { font-size:10px; }
            @keyframes swalTagIn { from { transform:scale(.8); opacity:0; } to { transform:scale(1); opacity:1; } }
            .swal-role-tag-remove { display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; border-radius:50%; background:transparent; cursor:pointer; font-size:14px; -webkit-text-fill-color:#9ca3af; opacity:0.6; transition:opacity .15s; }
            .swal-role-tag-remove:hover { opacity:1; }
            .swal-role-input { border:none; outline:none; background:transparent; flex:1; min-width:80px; font-size:var(--font-size-sm); color:var(--color-text-primary); padding:2px 0; }
            .swal-role-input::placeholder { color:var(--color-text-tertiary); }
            .swal-role-dropdown { position:absolute; left:0; right:0; z-index:999; max-height:180px; overflow-y:auto; background:var(--color-bg-primary); border:2px solid var(--color-primary); border-top:none; border-radius:0 0 var(--radius-base) var(--radius-base); box-shadow:0 4px 12px rgba(0,0,0,.1); display:none; }
            .swal-role-dropdown.show { display:block; }
            .swal-role-opt { display:flex; align-items:center; gap:8px; padding:8px 12px; cursor:pointer; font-size:var(--font-size-sm); color:var(--color-text-primary); transition:background .1s; }
            .swal-role-opt:hover,.swal-role-opt.hl { background:var(--color-bg-tertiary); }
            .swal-role-opt.sel { opacity:.4; pointer-events:none; }
            .swal-role-opt .swal-role-default-star { font-size:10px; }
            .swal-role-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
            </style>
            <div class="swal-members-permissions">
                <div class="swal-member-header">
                    <div class="swal-member-avatar">${initial}</div>
                    <div class="swal-member-info">
                        <div class="swal-member-name">${data.display_name || data.email}</div>
                        <div class="swal-member-email">${data.email}</div>
                    </div>
                </div>

                <div class="swal-field-group">
                    <label class="swal-field-label">Register</label>
                    <select id="swalType" class="swal-select-modern">
                        ${buildSectionOptions(data.available_sections || {}, data.display_names || {})}
                    </select>
                </div>

                ${data.current_user_can_manage_permissions && roles.length ? `
                <div class="swal-field-group">
                    <label class="swal-field-label">Rollen</label>
                    <div style="position:relative">
                        <div class="swal-role-tags-container" id="swalRoleTags">
                            ${buildRoleTagsHtml(roles, selectedRoleIds)}
                            <input type="text" class="swal-role-input" id="swalRoleInput" placeholder="Rolle hinzufügen…" autocomplete="off">
                        </div>
                        <div class="swal-role-dropdown" id="swalRoleDropdown">
                            ${roles.filter(r => !r.is_system).map(r => `<div class="swal-role-opt ${selectedRoleIds.includes(r.id) ? 'sel' : ''}" data-id="${r.id}" data-name="${escHtml(r.name)}" data-color="${r.tag_color}" data-default="${r.is_default ? '1' : ''}"><span class="swal-role-dot" style="background:${r.tag_color}"></span>${r.is_default ? '<i class="fas fa-star swal-role-default-star"></i>' : ''}${escHtml(r.name)}</div>`).join('')}
                        </div>
                    </div>
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

                    initSwalRoleTagSelect(roles, selectedRoleIds);

                    const resetBtn = document.getElementById('swalResetPasswordBtn');
                    if (resetBtn) {
                        resetBtn.addEventListener('click', () => {
                            Swal.close();
                            resetPassword(data.user_id, data.email);
                        });
                    }
                }
            }).then(result => {
                if (result.isConfirmed) {
                    saveEditModal(userId);
                } else if (result.isDenied) {
                    confirmRemoveMember(userId, data.display_name || data.email);
                }
            });
        })
        .catch(err => {
            window.notifyError('Fehler beim Laden der Mitgliederdaten: ' + (err.message || 'Verbindung fehlgeschlagen'));
        });
}

function initSwalRoleTagSelect(allRoles, selectedIds) {
    const container = document.getElementById('swalRoleTags');
    const input = document.getElementById('swalRoleInput');
    const dropdown = document.getElementById('swalRoleDropdown');
    if (!container || !input || !dropdown) return;

    // Store initial default role IDs for save-time comparison
    window._swalInitialDefaultRoleIds = allRoles
        .filter(r => r.is_default && selectedIds.includes(r.id))
        .map(r => String(r.id));

    let hlIdx = -1;

    function getOpts() { return [...dropdown.querySelectorAll('.swal-role-opt')]; }
    function getVisible() { return getOpts().filter(o => o.style.display !== 'none' && !o.classList.contains('sel')); }

    function addTag(id, name, color) {
        const opt = dropdown.querySelector(`.swal-role-opt[data-id="${id}"]`);
        const isDefault = opt?.dataset.default === '1';
        if (opt) opt.classList.add('sel');
        const tag = document.createElement('span');
        tag.className = 'role-tag swal-role-tag';
        tag.dataset.id = id;
        tag.dataset.default = isDefault ? '1' : '';
        tag.style.setProperty('--role-color', color);
        const star = isDefault ? '<i class="fas fa-star swal-role-default-star"></i>' : '';
        tag.innerHTML = `${star}${escHtml(name)} <span class="swal-role-tag-remove" data-id="${id}">&times;</span>`;
        tag.querySelector('.swal-role-tag-remove').addEventListener('click', e => { e.stopPropagation(); removeTag(id); });
        container.insertBefore(tag, input);
        input.value = '';
        filterOpts('');
    }

    function removeTag(id) {
        doRemoveTag(id);
    }

    function doRemoveTag(id) {
        const t = container.querySelector(`.swal-role-tag[data-id="${id}"]`);
        if (t) t.remove();
        const opt = dropdown.querySelector(`.swal-role-opt[data-id="${id}"]`);
        if (opt) opt.classList.remove('sel');
    }

    function filterOpts(q) {
        q = q.toLowerCase().trim();
        getOpts().forEach(o => { o.style.display = (!q || o.dataset.name.toLowerCase().includes(q)) ? '' : 'none'; });
        hlIdx = -1;
        updateHl();
    }

    function updateHl() {
        getVisible().forEach((o, i) => o.classList.toggle('hl', i === hlIdx));
    }

    // Wire up existing remove buttons
    container.querySelectorAll('.swal-role-tag-remove').forEach(btn => {
        btn.addEventListener('click', e => { e.stopPropagation(); removeTag(btn.dataset.id); });
    });

    container.addEventListener('click', () => { input.focus(); dropdown.classList.add('show'); });
    input.addEventListener('focus', () => { dropdown.classList.add('show'); filterOpts(input.value); });
    input.addEventListener('input', () => { filterOpts(input.value); dropdown.classList.add('show'); });
    input.addEventListener('keydown', e => {
        const vis = getVisible();
        if (e.key === 'ArrowDown') { e.preventDefault(); hlIdx = Math.min(hlIdx + 1, vis.length - 1); updateHl(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); hlIdx = Math.max(hlIdx - 1, 0); updateHl(); }
        else if (e.key === 'Enter') { e.preventDefault(); if (hlIdx >= 0 && vis[hlIdx]) addTag(vis[hlIdx].dataset.id, vis[hlIdx].dataset.name, vis[hlIdx].dataset.color); }
        else if (e.key === 'Escape') dropdown.classList.remove('show');
        else if (e.key === 'Backspace' && input.value === '') { const tags = container.querySelectorAll('.swal-role-tag'); if (tags.length) removeTag(tags[tags.length - 1].dataset.id); }
    });
    dropdown.addEventListener('mousedown', e => {
        const opt = e.target.closest('.swal-role-opt');
        if (opt && !opt.classList.contains('sel')) { e.preventDefault(); addTag(opt.dataset.id, opt.dataset.name, opt.dataset.color); }
    });
    document.addEventListener('click', e => { if (!container.closest('.swal-field-group')?.contains(e.target)) dropdown.classList.remove('show'); });
}

function saveEditModal(userId) {
    const roleContainer = document.getElementById('swalRoleTags');

    if (roleContainer && window._swalInitialDefaultRoleIds) {
        const currentIds = [...roleContainer.querySelectorAll('.swal-role-tag')].map(t => t.dataset.id);
        const removedDefaults = window._swalInitialDefaultRoleIds.filter(id => !currentIds.includes(id));
        if (removedDefaults.length > 0) {
            Swal.fire({
                title: 'Standardrolle entfernt',
                html: '<p style="color:var(--color-text-secondary)">Dieses Mitglied hat keine Standardrolle mehr zugewiesen. Trotzdem speichern?</p>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ja, speichern',
                cancelButtonText: 'Abbrechen',
                confirmButtonColor: '#478cf4',
                cancelButtonColor: '#6b7280',
                reverseButtons: true,
            }).then(result => {
                if (result.isConfirmed) doSaveEditModal(userId);
            });
            return;
        }
    }
    doSaveEditModal(userId);
}

function doSaveEditModal(userId) {
    const orchestraBase = getOrchestraBase();
    const typeEl = document.getElementById('swalType');

    const params = new URLSearchParams({
        csrf_token: getCsrfToken(),
        type: typeEl ? typeEl.value : '',
    });

    const roleContainer = document.getElementById('swalRoleTags');
    if (roleContainer) {
        const roleIds = [...roleContainer.querySelectorAll('.swal-role-tag')].map(t => t.dataset.id);
        params.set('role_ids', JSON.stringify(roleIds));
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
                if (typeof refreshMembersPage === 'function') refreshMembersPage();
                else setTimeout(() => location.reload(), 600);
            } else {
                window.notifyError(data.error || 'Speichern fehlgeschlagen');
            }
        })
        .catch(err => {
            window.notifyError('Speichern fehlgeschlagen: ' + (err.message || 'Verbindung fehlgeschlagen'));
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
                        if (typeof refreshMembersPage === 'function') refreshMembersPage();
                        else setTimeout(() => location.reload(), 600);
                    } else {
                        window.notifyError(data.error || 'Entfernen fehlgeschlagen');
                    }
                })
                .catch(err => {
                    window.notifyError('Entfernen fehlgeschlagen: ' + (err.message || 'Verbindung fehlgeschlagen'));
                });
        }
    });
}

function resetPassword(userId, email) {
    const orchestraBase = getOrchestraBase();
    Swal.fire({
        title: "Passwort zurücksetzen",
        html: "Willst du das Passwort von <strong>" + escHtml(email) + "</strong> wirklich zurücksetzen?",
        showCancelButton: true,
        confirmButtonText: "Zurücksetzen",
        confirmButtonColor: '#3085d6',
        cancelButtonText: "Abbrechen",
        icon: 'question'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/' + orchestraBase + '/user/resetPassword?user_id=' + encodeURIComponent(userId), {
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
                    window.notifyError(error.message || 'Fehler beim Zurücksetzen');
                });
        }
    });
}
