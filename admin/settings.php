<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin(); requireRole('admin'); initPage();
admin_header('Settings', 'settings');
?>

<div class="page-header"><h1>System Settings</h1><p>Configure system-wide preferences.</p></div>

<div class="card">
    <div class="card-body">
        <form id="settingsForm">
            <div class="flex flex-wrap gap-4">
                <div class="form-group" style="flex:1; min-width:250px;">
                    <label>Company Name</label>
                    <input type="text" id="s_company_name" class="form-control">
                </div>
                <div class="form-group" style="flex:1; min-width:250px;">
                    <label>Display Title</label>
                    <input type="text" id="s_display_title" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label>Announcement Message (TV Display Ticker)</label>
                <textarea id="s_announcement_message" class="form-control" rows="2"></textarea>
            </div>
            <div class="flex flex-wrap gap-4">
                <div class="form-group" style="flex:1; min-width:150px;">
                    <label>Enable Audio</label>
                    <select id="s_enable_audio" class="form-control">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1; min-width:150px;">
                    <label>Voice</label>
                    <select id="s_announcement_voice" class="form-control">
                        <option value="female">Female</option>
                        <option value="male">Male</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1; min-width:150px;">
                    <label>Voice Speed</label>
                    <input type="number" id="s_announcement_speed" class="form-control" step="0.1" min="0.5" max="2">
                </div>
            </div>
            <div class="flex flex-wrap gap-4">
                <div class="form-group" style="flex:1; min-width:150px;">
                    <label>Auto Reset Queue</label>
                    <select id="s_auto_reset_queue" class="form-control">
                        <option value="1">Yes (daily)</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1; min-width:150px;">
                    <label>Kiosk Reset Time (seconds)</label>
                    <input type="number" id="s_kiosk_reset_time" class="form-control" min="3" max="30">
                </div>
                <div class="form-group" style="flex:1; min-width:150px;">
                    <label>Session Timeout (minutes)</label>
                    <input type="number" id="s_session_timeout" class="form-control" min="5" max="120">
                </div>
            </div>
            <div class="divider"></div>
            <button type="submit" class="btn btn-primary btn-lg" id="btnSaveSettings">Save Settings</button>
        </form>
    </div>
</div>

<script>
const settingKeys = ['company_name','display_title','announcement_message','enable_audio','announcement_voice','announcement_speed','auto_reset_queue','kiosk_reset_time','session_timeout'];

async function loadSettings() {
    try {
        const res = await SFI.get('/api/settings/get.php');
        if (res.success) {
            const s = res.data.settings;
            settingKeys.forEach(k => {
                const el = document.getElementById('s_' + k);
                if (el && s[k] !== undefined) el.value = s[k];
            });
        }
    } catch (e) { SFI.toast('Failed to load settings.', 'error'); }
}

document.getElementById('settingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const data = {};
    settingKeys.forEach(k => {
        const el = document.getElementById('s_' + k);
        if (el) data[k] = el.value;
    });
    const btn = document.getElementById('btnSaveSettings');
    SFI.setButtonLoading(btn, true);
    try {
        const res = await fetch(SFI.baseUrl + '/api/settings/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(data),
            credentials: 'same-origin'
        });
        const json = await res.json();
        SFI.toast(json.message, json.success ? 'success' : 'error');
    } catch (e) { SFI.toast('Failed to save settings.', 'error'); }
    SFI.setButtonLoading(btn, false, 'Save Settings');
});

window.refreshAll = loadSettings;
document.addEventListener('DOMContentLoaded', loadSettings);
</script>

<?php admin_footer(); ?>
