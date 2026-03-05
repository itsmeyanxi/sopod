@extends('layouts.app')
@section('title', 'RBAC Management')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');

    .rbac-wrap { font-family: 'DM Sans', sans-serif; background: #111827; color: #e5e7eb; }
    .rbac-grid { display: grid; grid-template-columns: 240px 240px 1fr 300px; height: calc(100vh - 64px); }

    /* Column 1 – Departments */
    .dept-panel { background: #1f2937; border-right: 1px solid #374151; overflow-y: auto; padding: 16px 12px; }
    .dept-card { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 10px; cursor: pointer; margin-bottom: 6px; transition: all .15s; border: 1px solid transparent; color: #d1d5db; }
    .dept-card:hover { background: #374151; }
    .dept-card.active { color: #fff; border-color: transparent; box-shadow: 0 4px 14px rgba(0,0,0,.3); }
    .dept-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .dept-card .dept-name { font-weight: 600; font-size: 13px; }
    .dept-card .dept-count { font-size: 11px; opacity: .7; margin-left: auto; }

    /* Column 2 – Sub-departments */
    .subdept-panel { background: #1a2332; border-right: 1px solid #374151; overflow-y: auto; padding: 16px 12px; }
    .subdept-card { padding: 11px 14px; border-radius: 8px; cursor: pointer; margin-bottom: 4px; font-size: 13px; font-weight: 500; color: #9ca3af; transition: all .15s; }
    .subdept-card:hover { background: #374151; color: #e5e7eb; }
    .subdept-card.active { color: #fff; }

    /* Column 3 – Main panel */
    .main-panel { overflow-y: auto; padding: 0; display: flex; flex-direction: column; background: #111827; }
    .main-header { display: flex; align-items: center; gap: 12px; padding: 18px 24px; background: #1f2937; border-bottom: 1px solid #374151; }
    .main-header h2 { font-size: 16px; font-weight: 700; color: #f9fafb; margin: 0; flex: 1; }
    .tab-btn { padding: 7px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; border: 1px solid #4b5563; background: #1f2937; cursor: pointer; color: #9ca3af; transition: all .15s; }
    .tab-btn.active { background: #60a5fa; color: #fff; border-color: #60a5fa; }

    /* Matrix table */
    .matrix-wrap { padding: 20px 24px; flex: 1; }
    .matrix-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .matrix-table th { text-align: left; padding: 10px 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; border-bottom: 2px solid #374151; white-space: nowrap; }
    .matrix-table td { padding: 10px 12px; border-bottom: 1px solid #1f2937; vertical-align: middle; }
    .matrix-table tr:hover td { background: #1f2937; }

    /* User cell */
    .user-cell { display: flex; align-items: center; gap: 10px; cursor: pointer; }
    .user-avatar { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0; }
    .user-name { font-size: 13px; font-weight: 600; color: #e5e7eb; }
    .multi-badge { font-size: 9px; background: #78350f; color: #fde68a; padding: 1px 6px; border-radius: 10px; font-weight: 600; margin-left: 4px; }

    /* Toggle button */
    .perm-toggle { width: 30px; height: 30px; border-radius: 50%; border: 2px solid; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12px; font-weight: 700; transition: all .15s; background: none; }
    .perm-toggle.granted { border-color: #22c55e; color: #22c55e; background: rgba(34,197,94,.1); }
    .perm-toggle.revoked { border-color: #ef4444; color: #ef4444; background: rgba(239,68,68,.1); }
    .perm-toggle.locked { border-color: #4b5563; color: #4b5563; background: #1f2937; cursor: default; opacity: .6; }

    /* Level select */
    .level-select { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; border: 1px solid; cursor: pointer; outline: none; appearance: auto; }
    .level-admin { background: #78350f; color: #fde68a; border-color: #92400e; }
    .level-editor { background: #1e3a5f; color: #93c5fd; border-color: #1e40af; }
    .level-viewer { background: #374151; color: #9ca3af; border-color: #4b5563; }

    /* Role type badge */
    .role-badge { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
    .role-primary { background: rgba(34,197,94,.15); color: #4ade80; border: 1px solid rgba(34,197,94,.3); }
    .role-secondary { background: rgba(249,115,22,.15); color: #fb923c; border: 1px solid rgba(249,115,22,.3); }

    /* User Detail View */
    .user-detail-wrap { padding: 20px 24px; flex: 1; }
    .user-pills { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
    .user-pill { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid #4b5563; background: #1f2937; color: #9ca3af; transition: all .15s; position: relative; }
    .user-pill.active { color: #fff; border-color: transparent; }
    .user-pill .multi-dot { position: absolute; top: -2px; right: -2px; width: 8px; height: 8px; border-radius: 50%; background: #eab308; }

    .user-header-card { border-radius: 12px; padding: 20px; color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 16px; }
    .user-header-avatar { width: 48px; height: 48px; border-radius: 50%; background: rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 700; }
    .user-header-info h3 { margin: 0 0 4px 0; font-size: 16px; font-weight: 700; }
    .user-header-info p { margin: 0; font-size: 12px; opacity: .85; }
    .add-role-btn { margin-left: auto; padding: 8px 16px; border-radius: 8px; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.2); color: #fff; font-size: 12px; font-weight: 600; cursor: pointer; transition: all .15s; }
    .add-role-btn:hover { background: rgba(255,255,255,.25); }

    /* Permission cards grid */
    .perm-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
    .perm-card { border-radius: 10px; padding: 14px; cursor: pointer; transition: all .15s; border: 2px solid; }
    .perm-card.granted { background: rgba(34,197,94,.1); border-color: rgba(34,197,94,.3); }
    .perm-card.revoked { background: rgba(239,68,68,.1); border-color: rgba(239,68,68,.3); }
    .perm-card.locked { background: #1f2937; border-color: #374151; cursor: default; }
    .perm-card-icon { font-size: 18px; margin-bottom: 6px; }
    .perm-card-label { font-size: 13px; font-weight: 600; color: #e5e7eb; }
    .perm-card-hint { font-size: 11px; color: #6b7280; margin-top: 2px; }

    /* All roles summary */
    .roles-summary { margin-top: 16px; }
    .roles-summary h4 { font-size: 13px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 10px; }
    .role-row { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: #1f2937; border-radius: 8px; margin-bottom: 6px; border: 1px solid #374151; }
    .role-row .dept-dot { width: 8px; height: 8px; }
    .role-row .role-sub { font-size: 13px; font-weight: 600; color: #e5e7eb; }
    .role-row .role-dept { font-size: 11px; color: #6b7280; margin-left: 4px; }
    .role-row .role-info { display: flex; align-items: center; gap: 8px; margin-left: auto; }
    .perm-bar { display: flex; gap: 2px; }
    .perm-bar-seg { width: 14px; height: 14px; border-radius: 3px; }
    .perm-bar-seg.on { background: #22c55e; }
    .perm-bar-seg.off { background: #374151; }
    .perm-count { font-size: 11px; color: #6b7280; font-weight: 600; }
    .remove-btn { width: 26px; height: 26px; border-radius: 6px; border: 1px solid rgba(239,68,68,.3); background: rgba(239,68,68,.1); color: #ef4444; font-size: 11px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .15s; }
    .remove-btn:hover { background: #ef4444; color: #fff; }

    .section-header { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
    .section-header h4 { font-size: 13px; font-weight: 700; color: #e5e7eb; margin: 0; flex: 1; }

    /* Column 4 – Changelog */
    .changelog-panel { background: #0f172a; border-left: 1px solid #1e293b; overflow-y: auto; padding: 16px; }
    .changelog-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #475569; margin-bottom: 12px; }
    .log-entry { padding: 10px 12px; border-left: 3px solid; border-radius: 0 6px 6px 0; margin-bottom: 6px; background: #1e293b; }
    .log-entry.grant { border-color: #22c55e; }
    .log-entry.revoke { border-color: #ef4444; }
    .log-text { font-size: 12px; color: #cbd5e1; line-height: 1.4; }
    .log-time { font-size: 10px; color: #475569; margin-top: 4px; }

    /* Modal */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.6); z-index: 1000; display: flex; align-items: center; justify-content: center; }
    .modal-box { background: #1f2937; border-radius: 14px; padding: 28px; width: 420px; max-width: 90vw; box-shadow: 0 20px 60px rgba(0,0,0,.4); border: 1px solid #374151; }
    .modal-box h3 { font-size: 16px; font-weight: 700; margin: 0 0 16px 0; color: #f9fafb; }
    .modal-field { margin-bottom: 14px; }
    .modal-label { display: block; font-size: 12px; font-weight: 600; color: #9ca3af; margin-bottom: 6px; }
    .modal-select, .modal-input { width: 100%; padding: 10px 12px; border: 1px solid #4b5563; border-radius: 8px; font-size: 13px; font-family: 'DM Sans', sans-serif; outline: none; background: #111827; color: #e5e7eb; }
    .modal-select:focus, .modal-input:focus { border-color: #60a5fa; box-shadow: 0 0 0 3px rgba(96,165,250,.15); }
    .modal-note { font-size: 12px; color: #fbbf24; background: rgba(251,191,36,.1); border: 1px solid rgba(251,191,36,.2); padding: 10px 12px; border-radius: 8px; margin-bottom: 14px; }
    .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
    .btn-cancel { padding: 9px 18px; border-radius: 8px; border: 1px solid #4b5563; background: #374151; color: #9ca3af; font-size: 13px; font-weight: 600; cursor: pointer; }
    .btn-cancel:hover { background: #4b5563; color: #e5e7eb; }
    .btn-primary { padding: 9px 18px; border-radius: 8px; border: none; background: #60a5fa; color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; }
    .btn-primary:hover { background: #3b82f6; }
    .btn-danger { padding: 9px 18px; border-radius: 8px; border: none; background: #ef4444; color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; }
    .btn-danger:hover { background: #dc2626; }

    /* Action buttons for user list */
    .action-btn { padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; border: none; transition: all .15s; }
    .action-btn.edit { background: #ca8a04; color: #fff; }
    .action-btn.edit:hover { background: #a16207; }
    .action-btn.lock { background: #ea580c; color: #fff; }
    .action-btn.lock:hover { background: #c2410c; }
    .action-btn.unlock { background: #16a34a; color: #fff; }
    .action-btn.unlock:hover { background: #15803d; }
    .action-btn.reset { background: #2563eb; color: #fff; }
    .action-btn.reset:hover { background: #1d4ed8; }
    .action-btn.delete { background: #dc2626; color: #fff; }
    .action-btn.delete:hover { background: #b91c1c; }

    /* Status badges */
    .status-badge { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
    .status-active { background: rgba(34,197,94,.15); color: #4ade80; }
    .status-locked { background: rgba(239,68,68,.15); color: #f87171; }
    .status-warning { background: rgba(234,179,8,.15); color: #facc15; }

    /* User list search */
    .search-input { padding: 8px 14px; border: 1px solid #4b5563; border-radius: 8px; background: #111827; color: #e5e7eb; font-size: 13px; font-family: 'DM Sans', sans-serif; outline: none; width: 280px; }
    .search-input:focus { border-color: #60a5fa; }
    .search-input::placeholder { color: #6b7280; }

    /* Multi-select for roles in modals */
    .roles-checkboxes { display: flex; flex-wrap: wrap; gap: 6px; max-height: 160px; overflow-y: auto; padding: 8px; background: #111827; border: 1px solid #4b5563; border-radius: 8px; }
    .role-checkbox-label { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; background: #374151; color: #9ca3af; transition: all .15s; user-select: none; }
    .role-checkbox-label:hover { background: #4b5563; }
    .role-checkbox-label.selected { background: #60a5fa; color: #fff; }

    /* Toast */
    .toast { position: fixed; bottom: 24px; right: 24px; padding: 12px 20px; border-radius: 10px; color: #fff; font-size: 13px; font-weight: 600; z-index: 2000; opacity: 0; transform: translateY(10px); transition: all .3s; font-family: 'DM Sans', sans-serif; }
    .toast.show { opacity: 1; transform: translateY(0); }
    .toast.success { background: #16a34a; }
    .toast.error { background: #dc2626; }

    .panel-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; margin-bottom: 12px; }

    /* User list wrap */
    .user-list-wrap { padding: 20px 24px; flex: 1; }
</style>

<div class="rbac-wrap">
    <div class="rbac-grid">
        <!-- Column 1: Departments -->
        <div class="dept-panel">
            <div class="panel-title">Departments</div>
            <div id="deptList"></div>
        </div>

        <!-- Column 2: Sub-departments -->
        <div class="subdept-panel">
            <div class="panel-title">Sub-departments</div>
            <div id="subdeptList"></div>
        </div>

        <!-- Column 3: Main Panel -->
        <div class="main-panel">
            <div class="main-header">
                <h2 id="panelTitle">User Management</h2>
                <button class="tab-btn active" id="tabUsers" onclick="switchTab('users')">User List</button>
                <button class="tab-btn" id="tabMatrix" onclick="switchTab('matrix')">Matrix View</button>
                <button class="tab-btn" id="tabDetail" onclick="switchTab('detail')">User Detail</button>
            </div>
            <div id="userListView" class="user-list-wrap"></div>
            <div id="matrixView" class="matrix-wrap" style="display:none;"></div>
            <div id="userDetailView" class="user-detail-wrap" style="display:none;"></div>
        </div>

        <!-- Column 4: Changelog -->
        <div class="changelog-panel">
            <div class="changelog-title">Activity Log</div>
            <div id="changelogList"></div>
        </div>
    </div>
</div>

<!-- Modals -->
<div id="addRoleModal" style="display:none;"></div>
<div id="removeRoleModal" style="display:none;"></div>
<div id="userModal" style="display:none;"></div>
<div class="toast" id="toast"></div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const departments = @json($departments);
let allUsers = @json($users);

const isAdminIT = @json(auth()->user()->isAdminUser());
const currentAuthId = @json(auth()->id());

let currentDeptId = null;
let currentSubId = null;
let currentUserId = null;
let currentTab = 'users';
let deptColor = '#60a5fa';
let logs = [];
let userSearchTerm = '';

// ─── Init ───
document.addEventListener('DOMContentLoaded', () => {
    renderDepts();
    renderUserList();
    if (departments.length > 0) {
        selectDept(departments[0].id, departments[0].color);
    }
});

// ─── Department Selection ───
function renderDepts() {
    const html = departments.map(d => {
        const subCount = d.sub_departments ? d.sub_departments.length : 0;
        return `<div class="dept-card" id="dept-${d.id}" onclick="selectDept(${d.id}, '${d.color}')">
            <span class="dept-dot" style="background:${d.color}"></span>
            <span class="dept-name">${d.name}</span>
            <span class="dept-count">${subCount}</span>
        </div>`;
    }).join('');
    document.getElementById('deptList').innerHTML = html;
}

function selectDept(id, color) {
    currentDeptId = id;
    deptColor = color;
    document.querySelectorAll('.dept-card').forEach(el => {
        el.classList.remove('active');
        el.style.background = '';
        el.style.color = '';
        el.style.boxShadow = '';
    });
    const el = document.getElementById('dept-' + id);
    if (el) {
        el.classList.add('active');
        el.style.background = color;
        el.style.color = '#fff';
        el.style.boxShadow = `0 4px 14px ${color}40`;
    }
    const dept = departments.find(d => d.id === id);
    renderSubDepts(dept ? dept.sub_departments : []);
    if (dept && dept.sub_departments && dept.sub_departments.length > 0) {
        selectSub(dept.sub_departments[0].id);
    } else {
        currentSubId = null;
        if (currentTab !== 'users') {
            document.getElementById('panelTitle').textContent = dept ? dept.name : '';
        }
        document.getElementById('matrixView').innerHTML = '<p style="color:#6b7280;padding:20px;font-size:13px;">No sub-departments found.</p>';
        if (currentTab === 'users') renderUserList();
    }
}

function renderSubDepts(subs) {
    document.getElementById('subdeptList').innerHTML = subs.map(s =>
        `<div class="subdept-card" id="sub-${s.id}" onclick="selectSub(${s.id})">${s.name}</div>`
    ).join('');
}

function selectSub(id) {
    currentSubId = id;
    document.querySelectorAll('.subdept-card').forEach(el => {
        el.classList.remove('active');
        el.style.background = '';
        el.style.color = '';
    });
    const el = document.getElementById('sub-' + id);
    if (el) { el.classList.add('active'); el.style.background = deptColor; el.style.color = '#fff'; }
    const dept = departments.find(d => d.id === currentDeptId);
    const sub = dept ? dept.sub_departments.find(s => s.id === id) : null;
    if (currentTab !== 'users') {
        document.getElementById('panelTitle').textContent = sub ? `${dept.name} — ${sub.name}` : '';
    }
    if (currentTab === 'users') renderUserList();
    renderMatrix();
    if (currentUserId) renderUserDetail();
}

// ─── Tab Switching ───
function switchTab(tab) {
    currentTab = tab;
    document.getElementById('tabUsers').classList.toggle('active', tab === 'users');
    document.getElementById('tabMatrix').classList.toggle('active', tab === 'matrix');
    document.getElementById('tabDetail').classList.toggle('active', tab === 'detail');
    document.getElementById('userListView').style.display = tab === 'users' ? '' : 'none';
    document.getElementById('matrixView').style.display = tab === 'matrix' ? '' : 'none';
    document.getElementById('userDetailView').style.display = tab === 'detail' ? '' : 'none';

    if (tab === 'users') {
        document.getElementById('panelTitle').textContent = currentDeptId ? 'User Management' : 'User Management — All Users';
        renderUserList();
    } else if (tab === 'matrix') {
        const dept = departments.find(d => d.id === currentDeptId);
        const sub = dept ? dept.sub_departments.find(s => s.id === currentSubId) : null;
        document.getElementById('panelTitle').textContent = sub ? `${dept.name} — ${sub.name}` : 'Select a department';
        renderMatrix();
    } else if (tab === 'detail') {
        if (!currentUserId) {
            const usersInSub = getUsersInSub(currentSubId);
            if (usersInSub.length > 0) currentUserId = usersInSub[0].id;
        }
        renderUserDetail();
    }
}

// ─── Helpers ───
function getUsersInSub(subId) { return allUsers.filter(u => u.user_roles && u.user_roles.some(r => r.sub_department_id === subId)); }
function getUserRole(userId, subId) { const u = allUsers.find(u => u.id === userId); return u && u.user_roles ? u.user_roles.find(r => r.sub_department_id === subId) : null; }
function getInitials(name) { return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase(); }
function hashColor(str) { let h = 0; for (let i = 0; i < str.length; i++) h = str.charCodeAt(i) + ((h << 5) - h); return `hsl(${Math.abs(h) % 360}, 55%, 50%)`; }
function levelClass(level) { return level === 'Admin' ? 'level-admin' : level === 'Editor' ? 'level-editor' : 'level-viewer'; }
function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function showAllUsers() {
    // Deselect department and sub-department
    currentDeptId = null;
    currentSubId = null;
    document.querySelectorAll('.dept-card').forEach(el => { el.classList.remove('active'); el.style.background = ''; el.style.color = ''; el.style.boxShadow = ''; });
    document.querySelectorAll('.subdept-card').forEach(el => { el.classList.remove('active'); el.style.background = ''; el.style.color = ''; });
    document.getElementById('subdeptList').innerHTML = '';
    renderUserList();
}

const permFlags = ['can_view', 'can_create', 'can_edit', 'can_delete', 'can_approve', 'can_export', 'can_manage'];
const permLabels = { can_view: 'View', can_create: 'Create', can_edit: 'Edit', can_delete: 'Delete', can_approve: 'Approve', can_export: 'Export', can_manage: 'Manage' };
const permIcons = { can_view: 'fa-eye', can_create: 'fa-plus', can_edit: 'fa-pen', can_delete: 'fa-trash', can_approve: 'fa-check-double', can_export: 'fa-file-export', can_manage: 'fa-cog' };

// ═══════════════════════════════════════════════════
// ─── USER LIST TAB ───
// ═══════════════════════════════════════════════════
function getUsersInDept(deptId) {
    const dept = departments.find(d => d.id === deptId);
    if (!dept || !dept.sub_departments) return [];
    const subIds = dept.sub_departments.map(s => s.id);
    return allUsers.filter(u => u.user_roles && u.user_roles.some(r => subIds.includes(r.sub_department_id)));
}

function renderUserList() {
    // Filter by selected department/sub-department
    let baseUsers = allUsers;
    let filterLabel = 'All Users';
    if (currentSubId) {
        baseUsers = getUsersInSub(currentSubId);
        const dept = departments.find(d => d.id === currentDeptId);
        const sub = dept ? dept.sub_departments.find(s => s.id === currentSubId) : null;
        filterLabel = sub ? `${dept.name} → ${sub.name}` : 'Sub-department';
    } else if (currentDeptId) {
        baseUsers = getUsersInDept(currentDeptId);
        const dept = departments.find(d => d.id === currentDeptId);
        filterLabel = dept ? dept.name : 'Department';
    }

    const term = userSearchTerm.toLowerCase();
    const filtered = term ? baseUsers.filter(u =>
        u.name.toLowerCase().includes(term) ||
        u.email.toLowerCase().includes(term) ||
        (u.roles_display || '').toLowerCase().includes(term)
    ) : baseUsers;

    let html = `<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
        <input class="search-input" type="text" placeholder="Search name / email / role..." value="${escHtml(userSearchTerm)}" oninput="userSearchTerm=this.value;renderUserList();">
        <span style="font-size:12px;color:#6b7280;"><i class="fas fa-filter" style="margin-right:4px;"></i>${filterLabel} <span style="color:#9ca3af;">(${filtered.length})</span></span>
        ${currentDeptId ? `<button style="padding:4px 12px;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;border:1px solid #4b5563;background:#374151;color:#9ca3af;" onclick="showAllUsers()">Show All</button>` : ''}
        ${isAdminIT ? `<div style="margin-left:auto;display:flex;gap:8px;">
            ${currentSubId ? `<button class="btn-cancel" style="border-color:#60a5fa;color:#60a5fa;" onclick="openAddModal()"><i class="fas fa-user-plus" style="margin-right:6px;"></i>Add Existing User</button>` : ''}
            <button class="btn-primary" onclick="openCreateUserModal()"><i class="fas fa-plus" style="margin-right:6px;"></i>Create New User</button>
        </div>` : ''}
    </div>
    <table class="matrix-table"><thead><tr>
        <th>Name</th><th>Email</th><th>Roles</th><th>Status</th><th>Attempts</th>${isAdminIT ? '<th>Actions</th>' : ''}
    </tr></thead><tbody>`;

    filtered.forEach(u => {
        const isLocked = u.is_locked || (u.login_attempts && u.login_attempts >= 6);
        const rolesArr = u.roles || (u.role ? [u.role] : []);
        const rolesBadges = rolesArr.map(r => `<span style="display:inline-block;padding:2px 8px;border-radius:12px;font-size:10px;font-weight:600;background:#1e3a5f;color:#93c5fd;margin:1px 2px;">${r}</span>`).join('');

        let statusHtml;
        if (u.is_locked) {
            statusHtml = `<span class="status-badge status-locked">LOCKED</span>`;
        } else if (u.login_attempts && u.login_attempts >= 6) {
            statusHtml = `<span class="status-badge status-locked">LOCKED (Max)</span>`;
        } else {
            statusHtml = `<span class="status-badge status-active">Active</span>`;
        }

        let attemptsHtml;
        if (u.login_attempts >= 6) {
            attemptsHtml = `<span class="status-badge status-locked">${u.login_attempts}</span>`;
        } else if (u.login_attempts > 0) {
            attemptsHtml = `<span class="status-badge status-warning">${u.login_attempts}</span>`;
        } else {
            attemptsHtml = `<span class="status-badge status-active">0</span>`;
        }

        let actionsHtml = '';
        if (isAdminIT) {
            actionsHtml = `<td style="white-space:nowrap;">
                <div style="display:flex;gap:4px;flex-wrap:wrap;">`;
            if (u.id !== currentAuthId) {
                if (isLocked) {
                    actionsHtml += `<button class="action-btn unlock" onclick="toggleLockUser(${u.id}, '${escHtml(u.name)}', true)"><i class="fas fa-lock-open"></i> Unlock</button>`;
                } else {
                    actionsHtml += `<button class="action-btn lock" onclick="toggleLockUser(${u.id}, '${escHtml(u.name)}', false)"><i class="fas fa-lock"></i> Lock</button>`;
                }
            }
            actionsHtml += `<button class="action-btn edit" onclick="openEditUserModal(${u.id})"><i class="fas fa-edit"></i> Edit</button>`;
            if (u.login_attempts > 0) {
                actionsHtml += `<button class="action-btn reset" onclick="resetAttempts(${u.id}, ${u.login_attempts})"><i class="fas fa-sync"></i> Reset</button>`;
            }
            if (u.id !== currentAuthId) {
                actionsHtml += `<button class="action-btn delete" onclick="deleteUser(${u.id}, '${escHtml(u.name)}')"><i class="fas fa-trash"></i></button>`;
            }
            actionsHtml += `</div></td>`;
        }

        html += `<tr>
            <td><div class="user-cell"><div class="user-avatar" style="background:${hashColor(u.name)}">${getInitials(u.name)}</div><span class="user-name">${escHtml(u.name)}</span></div></td>
            <td style="font-size:13px;color:#9ca3af;">${escHtml(u.email)}</td>
            <td>${rolesBadges}</td>
            <td>${statusHtml}</td>
            <td>${attemptsHtml}</td>
            ${actionsHtml}
        </tr>`;
    });

    html += `</tbody></table>`;
    document.getElementById('userListView').innerHTML = html;
}

// ─── User CRUD Actions ───
function openCreateUserModal() {
    document.getElementById('userModal').innerHTML = `<div class="modal-overlay" onclick="if(event.target===this)closeUserModal()">
        <div class="modal-box">
            <h3>Add New User</h3>
            <div class="modal-field"><label class="modal-label">Name</label><input class="modal-input" id="cuName" type="text" placeholder="Full name"></div>
            <div class="modal-field"><label class="modal-label">Email</label><input class="modal-input" id="cuEmail" type="email" placeholder="Email address"></div>
            <div class="modal-field"><label class="modal-label">Password</label><input class="modal-input" id="cuPassword" type="password" placeholder="Min 6 characters"></div>
            <div class="modal-note"><i class="fas fa-info-circle" style="margin-right:6px;"></i>After creating the user, assign them to a sub-department using the "Add Role" button to grant module access.</div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeUserModal()">Cancel</button>
                <button class="btn-primary" onclick="submitCreateUser()">Create User</button>
            </div>
        </div>
    </div>`;
    document.getElementById('userModal').style.display = '';
}

function openEditUserModal(userId) {
    const user = allUsers.find(u => u.id === userId);
    if (!user) return;
    document.getElementById('userModal').innerHTML = `<div class="modal-overlay" onclick="if(event.target===this)closeUserModal()">
        <div class="modal-box">
            <h3>Edit User</h3>
            <input type="hidden" id="euId" value="${user.id}">
            <div class="modal-field"><label class="modal-label">Name</label><input class="modal-input" id="euName" type="text" value="${escHtml(user.name)}"></div>
            <div class="modal-field"><label class="modal-label">Email</label><input class="modal-input" id="euEmail" type="email" value="${escHtml(user.email)}"></div>
            <div class="modal-field"><label class="modal-label">New Password <span style="color:#6b7280;font-weight:400;">(leave blank to keep)</span></label><input class="modal-input" id="euPassword" type="password" placeholder="Min 6 characters"></div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeUserModal()">Cancel</button>
                <button class="btn-primary" onclick="submitEditUser()">Save Changes</button>
            </div>
        </div>
    </div>`;
    document.getElementById('userModal').style.display = '';
}


function closeUserModal() { document.getElementById('userModal').innerHTML = ''; document.getElementById('userModal').style.display = 'none'; }

async function submitCreateUser() {
    const name = document.getElementById('cuName').value;
    const email = document.getElementById('cuEmail').value;
    const password = document.getElementById('cuPassword').value;
    if (!name || !email || !password) { showToast('Please fill all fields', 'error'); return; }
    try {
        const res = await fetch('/rbac/user', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify({ name, email, password }) });
        const data = await res.json();
        if (!res.ok) { showToast(data.error || data.message || 'Error', 'error'); return; }
        allUsers.push(data.user);
        closeUserModal();
        renderUserList();
        addLog('grant', `User created: ${name}`);
        showToast('User created successfully', 'success');
    } catch (e) { showToast('Network error', 'error'); }
}

async function submitEditUser() {
    const id = document.getElementById('euId').value;
    const name = document.getElementById('euName').value;
    const email = document.getElementById('euEmail').value;
    const password = document.getElementById('euPassword').value;
    if (!name || !email) { showToast('Please fill name and email', 'error'); return; }
    const body = { name, email };
    if (password) body.password = password;
    try {
        const res = await fetch(`/rbac/user/${id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify(body) });
        const data = await res.json();
        if (!res.ok) { showToast(data.error || data.message || 'Error', 'error'); return; }
        const idx = allUsers.findIndex(u => u.id == id);
        if (idx >= 0) allUsers[idx] = data.user;
        closeUserModal();
        renderUserList();
        addLog('grant', `User updated: ${name}`);
        showToast('User updated successfully', 'success');
    } catch (e) { showToast('Network error', 'error'); }
}

async function deleteUser(userId, userName) {
    const result = await Swal.fire({ title: 'Delete User?', html: `Are you sure you want to delete <strong>${userName}</strong>?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#4b5563', confirmButtonText: 'Yes, delete!', background: '#1f2937', color: '#fff' });
    if (!result.isConfirmed) return;
    try {
        const res = await fetch(`/rbac/user/${userId}`, { method: 'DELETE', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
        const data = await res.json();
        if (!res.ok) { showToast(data.error || 'Error', 'error'); return; }
        allUsers = allUsers.filter(u => u.id !== userId);
        renderUserList();
        addLog('revoke', `User deleted: ${userName}`);
        showToast('User deleted', 'success');
    } catch (e) { showToast('Network error', 'error'); }
}

async function toggleLockUser(userId, userName, isCurrentlyLocked) {
    const action = isCurrentlyLocked ? 'unlock' : 'lock';
    const result = await Swal.fire({
        title: `${isCurrentlyLocked ? 'Unlock' : 'Lock'} Account?`,
        html: `${isCurrentlyLocked ? 'Unlock' : 'Lock'} account for <strong>${userName}</strong>?`,
        icon: isCurrentlyLocked ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: isCurrentlyLocked ? '#16a34a' : '#ea580c',
        cancelButtonColor: '#4b5563',
        confirmButtonText: isCurrentlyLocked ? 'Unlock' : 'Lock',
        background: '#1f2937', color: '#fff'
    });
    if (!result.isConfirmed) return;
    try {
        const res = await fetch(`/rbac/user/${userId}/toggle-lock`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
        const data = await res.json();
        if (!res.ok) { showToast(data.error || 'Error', 'error'); return; }
        const user = allUsers.find(u => u.id === userId);
        if (user) {
            user.is_locked = data.locked;
            if (!data.locked) { user.login_attempts = 0; user.locked_at = null; user.locked_by = null; }
        }
        renderUserList();
        addLog(data.locked ? 'revoke' : 'grant', data.message);
        showToast(data.message, 'success');
    } catch (e) { showToast('Network error', 'error'); }
}

async function resetAttempts(userId, attempts) {
    const result = await Swal.fire({ title: 'Reset Login Attempts?', html: `Reset <strong>${attempts} failed attempts</strong>?`, icon: 'question', showCancelButton: true, confirmButtonColor: '#2563eb', cancelButtonColor: '#4b5563', confirmButtonText: 'Reset', background: '#1f2937', color: '#fff' });
    if (!result.isConfirmed) return;
    try {
        const res = await fetch(`/rbac/user/${userId}/reset-attempts`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
        const data = await res.json();
        if (!res.ok) { showToast(data.error || 'Error', 'error'); return; }
        const user = allUsers.find(u => u.id === userId);
        if (user) user.login_attempts = 0;
        renderUserList();
        addLog('grant', data.message);
        showToast(data.message, 'success');
    } catch (e) { showToast('Network error', 'error'); }
}

// ═══════════════════════════════════════════════════
// ─── MATRIX VIEW ───
// ═══════════════════════════════════════════════════
function renderMatrix() {
    const users = getUsersInSub(currentSubId);
    if (users.length === 0) {
        document.getElementById('matrixView').innerHTML = `<div style="text-align:center;padding:40px;color:#6b7280;">
            <i class="fas fa-users" style="font-size:32px;margin-bottom:12px;display:block;"></i>
            <p style="font-size:14px;font-weight:600;">No users assigned</p>
            <p style="font-size:12px;">Use "Add Role" to assign users to this sub-department.</p>
            ${isAdminIT ? '<button class="btn-primary" style="margin-top:12px;" onclick="openAddModal()">+ Add Role</button>' : ''}
        </div>`;
        return;
    }
    let html = `${isAdminIT ? '<div style="margin-bottom:12px;text-align:right;"><button class="btn-primary" onclick="openAddModal()">+ Add Role</button></div>' : ''}
    <table class="matrix-table"><thead><tr><th>User</th><th>Level</th><th>Type</th>`;
    permFlags.forEach(f => { html += `<th style="text-align:center;">${permLabels[f]}</th>`; });
    html += `</tr></thead><tbody>`;
    users.forEach(u => {
        const role = getUserRole(u.id, currentSubId);
        if (!role) return;
        const totalRoles = u.user_roles ? u.user_roles.length : 0;
        const multiBadge = totalRoles > 1 ? `<span class="multi-badge">${totalRoles} roles</span>` : '';
        const disabledLevel = !isAdminIT ? 'disabled style="opacity:.6;cursor:default;"' : '';
        html += `<tr>
            <td><div class="user-cell" onclick="selectUserDetail(${u.id})"><div class="user-avatar" style="background:${hashColor(u.name)}">${getInitials(u.name)}</div><span class="user-name">${escHtml(u.name)}${multiBadge}</span></div></td>
            <td><select class="level-select ${levelClass(role.level)}" onchange="changeLevel(${u.id}, ${currentSubId}, this.value)" ${disabledLevel}>
                <option value="Viewer" ${role.level==='Viewer'?'selected':''}>Viewer</option>
                <option value="Editor" ${role.level==='Editor'?'selected':''}>Editor</option>
                <option value="Admin" ${role.level==='Admin'?'selected':''}>Admin</option>
            </select></td>
            <td><span class="role-badge ${role.role_type==='primary'?'role-primary':'role-secondary'}" style="${isAdminIT?'cursor:pointer;':''}font-size:10px;" ${isAdminIT?`onclick="changeRoleType(${role.id}, '${role.role_type}')"`:''} title="${isAdminIT?'Click to toggle primary/secondary':''}">${role.role_type}${isAdminIT?' <i class="fas fa-exchange-alt" style="font-size:9px;margin-left:3px;"></i>':''}</span></td>`;
        permFlags.forEach(f => {
            if (f === 'can_view') {
                html += `<td style="text-align:center;"><button class="perm-toggle locked" disabled><i class="fas fa-lock" style="font-size:10px;"></i></button></td>`;
            } else {
                const granted = role[f];
                const clickAttr = isAdminIT ? `onclick="togglePerm(${u.id}, ${currentSubId}, '${f}')"` : '';
                const disabledAttr = !isAdminIT ? 'style="opacity:.6;cursor:default;"' : '';
                html += `<td style="text-align:center;"><button class="perm-toggle ${granted?'granted':'revoked'}" ${clickAttr} ${disabledAttr}>${granted?'<i class="fas fa-check"></i>':'<i class="fas fa-xmark"></i>'}</button></td>`;
            }
        });
        html += `</tr>`;
    });
    html += `</tbody></table>`;
    document.getElementById('matrixView').innerHTML = html;
}

// ═══════════════════════════════════════════════════
// ─── USER DETAIL VIEW ───
// ═══════════════════════════════════════════════════
function selectUserDetail(userId) { currentUserId = userId; switchTab('detail'); }

function renderUserDetail() {
    const usersInSub = getUsersInSub(currentSubId);
    if (usersInSub.length === 0 || !currentUserId) {
        document.getElementById('userDetailView').innerHTML = '<p style="color:#6b7280;padding:20px;font-size:13px;">No users to display.</p>';
        return;
    }
    const user = allUsers.find(u => u.id === currentUserId);
    if (!user) return;
    const role = getUserRole(user.id, currentSubId);
    let pillsHtml = usersInSub.map(u => {
        const isActive = u.id === currentUserId;
        const totalRoles = u.user_roles ? u.user_roles.length : 0;
        const dot = totalRoles > 1 ? '<span class="multi-dot"></span>' : '';
        return `<div class="user-pill ${isActive?'active':''}" style="${isActive?`background:${deptColor};color:#fff;border-color:transparent;`:''}" onclick="currentUserId=${u.id};renderUserDetail();">${escHtml(u.name)}${dot}</div>`;
    }).join('');
    const totalRoles = user.user_roles ? user.user_roles.length : 0;
    let headerHtml = `<div class="user-header-card" style="background:${deptColor};"><div class="user-header-avatar">${getInitials(user.name)}</div><div class="user-header-info"><h3>${escHtml(user.name)}</h3><p>${totalRoles} role${totalRoles!==1?'s':''} assigned</p></div>${isAdminIT?`<button class="add-role-btn" onclick="openAddModal(${user.id})">+ Add Role</button>`:''}</div>`;
    let permHtml = '';
    if (role) {
        const disabledLevel = !isAdminIT ? 'disabled style="opacity:.6;cursor:default;"' : '';
        permHtml += `<div class="section-header"><h4>Permissions in this sub-department</h4>
            <select class="level-select ${levelClass(role.level)}" onchange="changeLevel(${user.id}, ${currentSubId}, this.value)" ${disabledLevel}>
                <option value="Viewer" ${role.level==='Viewer'?'selected':''}>Viewer</option>
                <option value="Editor" ${role.level==='Editor'?'selected':''}>Editor</option>
                <option value="Admin" ${role.level==='Admin'?'selected':''}>Admin</option>
            </select>
            ${isAdminIT?`<button class="role-badge ${role.role_type==='primary'?'role-primary':'role-secondary'}" style="cursor:pointer;font-size:10px;" onclick="changeRoleType(${role.id}, '${role.role_type}')">${role.role_type} <i class="fas fa-exchange-alt" style="font-size:9px;margin-left:3px;"></i></button><button class="remove-btn" onclick="openRemoveModal(${role.id}, '${escHtml(user.name)}', ${currentSubId})"><i class="fas fa-trash"></i></button>`:''}</div><div class="perm-grid">`;
        permFlags.forEach(f => {
            const granted = role[f];
            const isView = f === 'can_view';
            const cls = isView ? 'locked' : (granted ? 'granted' : 'revoked');
            const hint = isView ? 'Always on' : (granted ? 'Click to revoke' : 'Click to grant');
            const onclick = (!isView && isAdminIT) ? `onclick="togglePerm(${user.id}, ${currentSubId}, '${f}')"` : '';
            permHtml += `<div class="perm-card ${cls}" ${onclick} ${!isAdminIT&&!isView?'style="opacity:.7;cursor:default;"':''}>
                <div class="perm-card-icon"><i class="fas ${permIcons[f]}"></i></div>
                <div class="perm-card-label">${permLabels[f]}</div>
                <div class="perm-card-hint">${!isAdminIT && !isView ? 'View only' : hint}</div>
            </div>`;
        });
        permHtml += `</div>`;
    }
    let rolesHtml = `<div class="roles-summary"><h4>All Roles</h4>`;
    if (user.user_roles && user.user_roles.length > 0) {
        user.user_roles.forEach(r => {
            const sub = r.sub_department;
            const dept = sub ? sub.department : null;
            const dColor = dept ? dept.color : '#6B7280';
            const permCount = permFlags.filter(f => r[f]).length;
            const barHtml = permFlags.map(f => `<div class="perm-bar-seg ${r[f]?'on':'off'}"></div>`).join('');
            rolesHtml += `<div class="role-row"><span class="dept-dot" style="background:${dColor}"></span><span class="role-sub">${sub ? sub.name : '?'}</span><span class="role-dept">${dept ? dept.name : ''}</span><div class="role-info"><span class="role-badge ${r.role_type==='primary'?'role-primary':'role-secondary'}" style="${isAdminIT?'cursor:pointer;':''}font-size:10px;" ${isAdminIT?`onclick="changeRoleType(${r.id}, '${r.role_type}')"`:''} title="${isAdminIT?'Click to toggle primary/secondary':''}">${r.role_type}${isAdminIT?' <i class="fas fa-exchange-alt" style="font-size:9px;margin-left:3px;"></i>':''}</span><span class="role-badge level-select ${levelClass(r.level)}" style="border:none;">${r.level}</span><div class="perm-bar">${barHtml}</div><span class="perm-count">${permCount}/6</span>${isAdminIT?`<button class="remove-btn" onclick="openRemoveModal(${r.id}, '${escHtml(user.name)}', ${r.sub_department_id})"><i class="fas fa-trash"></i></button>`:''}</div></div>`;
        });
    }
    rolesHtml += `${isAdminIT?`<div style="margin-top:10px;"><button class="btn-primary" onclick="openAddModal(${user.id})">+ Add another role</button></div>`:''}</div>`;
    document.getElementById('userDetailView').innerHTML = `<div class="user-pills">${pillsHtml}</div>${headerHtml}${permHtml}${rolesHtml}`;
}

// ═══════════════════════════════════════════════════
// ─── RBAC ACTIONS ───
// ═══════════════════════════════════════════════════
async function togglePerm(userId, subId, perm) {
    if (!isAdminIT) { showToast('Only Admin/IT can change permissions', 'error'); return; }
    try {
        const res = await fetch('/rbac/toggle-permission', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify({ user_id: userId, sub_department_id: subId, permission: perm }) });
        const data = await res.json();
        if (!res.ok) { showToast(data.error || 'Error', 'error'); return; }
        const user = allUsers.find(u => u.id === userId);
        if (user) { const role = user.user_roles.find(r => r.sub_department_id === subId); if (role) { role[perm] = data.newValue; role.level = data.level; } }
        renderMatrix();
        if (currentTab === 'detail') renderUserDetail();
        addLog(data.newValue ? 'grant' : 'revoke', `${permLabels[perm]} ${data.newValue ? 'granted to' : 'revoked from'} ${user ? user.name : 'user'}`);
        showToast(`${permLabels[perm]} ${data.newValue ? 'granted' : 'revoked'}`, 'success');
    } catch (e) { showToast('Network error', 'error'); }
}

async function changeLevel(userId, subId, level) {
    if (!isAdminIT) { showToast('Only Admin/IT can change levels', 'error'); return; }
    try {
        const res = await fetch('/rbac/change-level', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify({ user_id: userId, sub_department_id: subId, level }) });
        const data = await res.json();
        if (!res.ok) { showToast(data.error || 'Error', 'error'); return; }
        const user = allUsers.find(u => u.id === userId);
        if (user) { const role = user.user_roles.find(r => r.sub_department_id === subId); if (role) { role.level = data.level; permFlags.forEach(f => { role[f] = data[f]; }); } }
        renderMatrix();
        if (currentTab === 'detail') renderUserDetail();
        addLog('grant', `Level changed to ${level} for ${user ? user.name : 'user'}`);
        showToast(`Level set to ${level}`, 'success');
    } catch (e) { showToast('Network error', 'error'); }
}

// ─── Change Role Type ───
async function changeRoleType(roleId, currentType) {
    if (!isAdminIT) { showToast('Only Admin/IT can change role types', 'error'); return; }
    const newType = currentType === 'primary' ? 'secondary' : 'primary';
    if (currentType === 'primary') {
        if (!confirm(`Change this role from Primary to Secondary? The user must keep at least one primary role.`)) return;
    }
    try {
        const res = await fetch(`/rbac/change-role-type/${roleId}`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
        const data = await res.json();
        if (!res.ok) { showToast(data.error || 'Error', 'error'); return; }
        allUsers.forEach(u => { if (u.user_roles) u.user_roles.forEach(r => { if (r.id === roleId) r.role_type = data.new_type; }); });
        renderMatrix();
        if (currentTab === 'detail') renderUserDetail();
        addLog('grant', `Role type changed to ${data.new_type}`);
        showToast(`Role type changed to ${data.new_type}`, 'success');
    } catch (e) { showToast('Network error', 'error'); }
}

// ─── Add Role Modal ───
function openAddModal(preselectedUserId) {
    if (!isAdminIT) { showToast('Only Admin/IT can add roles', 'error'); return; }
    const allSubs = [];
    departments.forEach(d => { if (d.sub_departments) d.sub_departments.forEach(s => { allSubs.push({ id: s.id, label: `${d.name} → ${s.name}` }); }); });
    let userId = preselectedUserId || '';
    let html = `<div class="modal-overlay" onclick="if(event.target===this)closeAddModal()"><div class="modal-box"><h3>Add User to Sub-department</h3>
        <div class="modal-field"><label class="modal-label">User</label><select class="modal-select" id="addRoleUser" onchange="filterAddModalSubs()"><option value="">Select user...</option>${allUsers.map(u => `<option value="${u.id}" ${u.id==userId?'selected':''}>${escHtml(u.name)}</option>`).join('')}</select></div>
        <div class="modal-field"><label class="modal-label">Sub-department</label><select class="modal-select" id="addRoleSub"><option value="">Select sub-department...</option>${allSubs.map(s => `<option value="${s.id}">${s.label}</option>`).join('')}</select></div>
        <div class="modal-field"><label class="modal-label">Role Type</label><select class="modal-select" id="addRoleType"><option value="primary">Primary</option><option value="secondary" selected>Secondary</option></select></div>
        <div class="modal-field"><label class="modal-label">Level</label><select class="modal-select" id="addRoleLevel"><option value="Viewer">Viewer</option><option value="Editor">Editor</option><option value="Admin">Admin</option></select></div>
        <div class="modal-actions"><button class="btn-cancel" onclick="closeAddModal()">Cancel</button><button class="btn-primary" onclick="submitAddRole()">Add Role</button></div></div></div>`;
    document.getElementById('addRoleModal').innerHTML = html;
    document.getElementById('addRoleModal').style.display = '';
    if (preselectedUserId) filterAddModalSubs();
}


function filterAddModalSubs() {
    const userId = parseInt(document.getElementById('addRoleUser').value);
    const user = allUsers.find(u => u.id === userId);
    const existingSubs = user && user.user_roles ? user.user_roles.map(r => r.sub_department_id) : [];
    const allSubs = [];
    departments.forEach(d => { if (d.sub_departments) d.sub_departments.forEach(s => { if (!existingSubs.includes(s.id)) allSubs.push({ id: s.id, label: `${d.name} → ${s.name}` }); }); });
    document.getElementById('addRoleSub').innerHTML = `<option value="">Select sub-department...</option>` + allSubs.map(s => `<option value="${s.id}">${s.label}</option>`).join('');
}

async function submitAddRole() {
    const userId = document.getElementById('addRoleUser').value;
    const subId = document.getElementById('addRoleSub').value;
    const roleType = document.getElementById('addRoleType').value;
    const level = document.getElementById('addRoleLevel').value;
    if (!userId) { showToast('Please select a user', 'error'); return; }
    if (!subId) { showToast('Please select a sub-department', 'error'); return; }

    try {
        const res = await fetch('/rbac/add-role', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify({ user_id: parseInt(userId), sub_department_id: parseInt(subId), role_type: roleType, level }) });
        const data = await res.json();
        if (!res.ok) { showToast(data.error || 'Error adding department role', 'error'); return; }
        const user = allUsers.find(u => u.id === parseInt(userId));
        if (user) { if (!user.user_roles) user.user_roles = []; user.user_roles.push(data.role); }
    } catch (e) { showToast('Network error', 'error'); return; }

    closeAddModal();
    renderMatrix();
    renderUserList();
    if (currentTab === 'detail') renderUserDetail();
    const user = allUsers.find(u => u.id === parseInt(userId));
    addLog('grant', `Added to sub-department: ${user ? user.name : 'user'}`);
    showToast('Sub-department role added', 'success');
}

function closeAddModal() { document.getElementById('addRoleModal').innerHTML = ''; document.getElementById('addRoleModal').style.display = 'none'; }

// ─── Remove Role Modal ───
function openRemoveModal(roleId, userName, subId) {
    if (!isAdminIT) { showToast('Only Admin/IT can remove roles', 'error'); return; }
    const dept = departments.find(d => d.id === currentDeptId);
    const sub = dept ? dept.sub_departments.find(s => s.id === subId) : null;
    // Find the role to check if primary
    let isPrimary = false;
    allUsers.forEach(u => { if (u.user_roles) u.user_roles.forEach(r => { if (r.id === roleId && r.role_type === 'primary') isPrimary = true; }); });
    const warningHtml = isPrimary ? `<div class="modal-note" style="margin-bottom:14px;"><i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>This is a <strong>primary</strong> role. If it is the user's only primary role, removal will be blocked.</div>` : '';
    document.getElementById('removeRoleModal').innerHTML = `<div class="modal-overlay" onclick="if(event.target===this)closeRemoveModal()"><div class="modal-box"><h3>Remove Role</h3><p style="font-size:13px;color:#9ca3af;margin-bottom:16px;">Remove <strong>${userName}</strong> from <strong>${sub ? sub.name : 'this sub-department'}</strong>?</p>${warningHtml}<div class="modal-actions"><button class="btn-cancel" onclick="closeRemoveModal()">Cancel</button><button class="btn-danger" onclick="submitRemoveRole(${roleId})">Remove Role</button></div></div></div>`;
    document.getElementById('removeRoleModal').style.display = '';
}

async function submitRemoveRole(roleId) {
    try {
        const res = await fetch(`/rbac/remove-role/${roleId}`, { method: 'DELETE', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
        const data = await res.json();
        if (!res.ok) { showToast(data.error || 'Error', 'error'); return; }
        allUsers.forEach(u => { if (u.user_roles) u.user_roles = u.user_roles.filter(r => r.id !== roleId); });
        closeRemoveModal();
        renderMatrix();
        if (currentTab === 'detail') renderUserDetail();
        addLog('revoke', 'Role removed');
        showToast('Role removed', 'success');
    } catch (e) { showToast('Network error', 'error'); }
}

function closeRemoveModal() { document.getElementById('removeRoleModal').innerHTML = ''; document.getElementById('removeRoleModal').style.display = 'none'; }

// ─── Toast ───
function showToast(msg, type) { const el = document.getElementById('toast'); el.textContent = msg; el.className = `toast ${type} show`; setTimeout(() => el.classList.remove('show'), 2600); }

// ─── Changelog ───
function addLog(type, text) { logs.unshift({ type, text, time: new Date().toLocaleTimeString() }); if (logs.length > 12) logs.pop(); renderChangelog(); }
function renderChangelog() {
    document.getElementById('changelogList').innerHTML = logs.length ? logs.map(l => `<div class="log-entry ${l.type}"><div class="log-text">${l.text}</div><div class="log-time">${l.time}</div></div>`).join('') : '<p style="color:#475569;font-size:12px;">No activity yet.</p>';
}
renderChangelog();
</script>
@endsection
