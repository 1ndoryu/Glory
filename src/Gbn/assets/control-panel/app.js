(function ($) {
    'use strict';

    const App = {
        data: null,
        currentView: 'dashboard',
        currentSubView: null,

        init: function () {
            console.log('GBN Control Panel Initializing...');
            this.fetchData();
        },

        fetchData: function () {
            $.ajax({
                url: gbnControlData.apiUrl,
                method: 'POST',
                data: {
                    action: 'gbn_diagnostics_dump',
                    nonce: gbnControlData.nonce
                },
                success: response => {
                    if (response.success) {
                        this.data = response.data;
                        this.renderLayout();
                        this.navigate('dashboard');
                    } else {
                        this.renderError(response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    this.renderError('Error de conexión: ' + error);
                }
            });
        },

        renderLayout: function () {
            const $app = $('#gbn-control-app');
            $app.html(`
                <div class="gbn-cp-layout">
                    <aside class="gbn-cp-sidebar">
                        <div class="gbn-cp-sidebar-header">
                            <h2 class="gbn-cp-sidebar-title">GBN Control Center</h2>
                            <small style="color: var(--gbn-cp-text-muted); font-size: 11px;">v${this.data.version}</small>
                        </div>
                        <nav class="gbn-cp-nav" id="gbn-cp-nav">
                            <!-- Nav items will be injected here -->
                        </nav>
                    </aside>
                    <main class="gbn-cp-main" id="gbn-cp-main-content">
                        <!-- Content will be injected here -->
                    </main>
                </div>
            `);
            this.renderSidebar();
        },

        renderSidebar: function () {
            const $nav = $('#gbn-cp-nav');
            let html = '';

            // Dashboard
            html += `<a href="#" class="gbn-cp-nav-item ${this.currentView === 'dashboard' ? 'active' : ''}" data-view="dashboard">Dashboard</a>`;

            // Components Section
            html += `<div class="gbn-cp-nav-section">Components</div>`;
            Object.keys(this.data.components).forEach(role => {
                const isActive = this.currentView === 'component' && this.currentSubView === role;
                html += `<a href="#" class="gbn-cp-nav-item ${isActive ? 'active' : ''}" data-view="component" data-subview="${role}">${role}</a>`;
            });

            // System Section
            html += `<div class="gbn-cp-nav-section">System</div>`;
            html += `<a href="#" class="gbn-cp-nav-item ${this.currentView === 'theme' ? 'active' : ''}" data-view="theme">Theme Settings</a>`;
            html += `<a href="#" class="gbn-cp-nav-item ${this.currentView === 'health' ? 'active' : ''}" data-view="health">Health Check</a>`;
            html += `<a href="#" class="gbn-cp-nav-item ${this.currentView === 'logs' ? 'active' : ''}" data-view="logs">System Logs</a>`;

            $nav.html(html);

            // Bind events
            $nav.find('.gbn-cp-nav-item').on('click', e => {
                e.preventDefault();
                const view = $(e.currentTarget).data('view');
                const subView = $(e.currentTarget).data('subview');
                this.navigate(view, subView);
            });
        },

        navigate: function (view, subView = null) {
            this.currentView = view;
            this.currentSubView = subView;
            this.renderSidebar(); // Re-render to update active state
            this.renderContent();
        },

        renderContent: function () {
            const $main = $('#gbn-cp-main-content');

            switch (this.currentView) {
                case 'dashboard':
                    this.renderDashboard($main);
                    break;
                case 'component':
                    this.renderComponent($main, this.currentSubView);
                    break;
                case 'theme':
                    this.renderThemeSettings($main);
                    break;
                case 'health':
                    this.renderHealth($main);
                    break;
                case 'logs':
                    this.renderLogs($main);
                    break;
                default:
                    $main.html('<h1>404 - View Not Found</h1>');
            }
        },

        renderDashboard: function ($container) {
            const {components, traits, php_version, memory_limit} = this.data;
            const componentCount = Object.keys(components).length;

            const html = `
                <div class="gbn-cp-main-header">
                    <h1 class="gbn-cp-main-title">System Overview</h1>
                    <button id="btn-refresh" class="gbn-cp-btn gbn-cp-btn-secondary">Refresh Data</button>
                </div>
                
                <div class="gbn-cp-grid">
                    <div class="gbn-cp-card">
                        <h3>System Status</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <div style="color: var(--gbn-cp-text-muted); font-size: 12px;">PHP VERSION</div>
                                <div style="font-size: 1.2rem;">${php_version}</div>
                            </div>
                            <div>
                                <div style="color: var(--gbn-cp-text-muted); font-size: 12px;">MEMORY LIMIT</div>
                                <div style="font-size: 1.2rem;">${memory_limit}</div>
                            </div>
                            <div>
                                <div style="color: var(--gbn-cp-text-muted); font-size: 12px;">COMPONENTS</div>
                                <div style="font-size: 1.2rem;">${componentCount}</div>
                            </div>
                            <div>
                                <div style="color: var(--gbn-cp-text-muted); font-size: 12px;">STATUS</div>
                                <div style="color: var(--gbn-cp-success); font-weight: bold;">HEALTHY</div>
                            </div>
                        </div>
                    </div>

                    <div class="gbn-cp-card">
                        <h3>Quick Actions</h3>
                        <p style="color: var(--gbn-cp-text-muted);">Common maintenance tasks.</p>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <button class="gbn-cp-btn gbn-cp-btn-secondary" onclick="alert('Not implemented yet')">Clear Cache</button>
                            <button id="btn-quick-validate" class="gbn-cp-btn gbn-cp-btn-primary">Run Health Check</button>
                        </div>
                    </div>
                </div>
            `;

            $container.html(html);
            $('#btn-refresh').on('click', () => this.fetchData());
            $('#btn-quick-validate').on('click', () => this.navigate('health'));
        },

        renderHealth: function ($container) {
            $container.html(`
                <div class="gbn-cp-main-header">
                    <h1 class="gbn-cp-main-title">System Health Check</h1>
                    <button id="btn-run-validate" class="gbn-cp-btn gbn-cp-btn-primary">Run Validation</button>
                </div>
                <div id="health-content">
                    <div class="gbn-cp-loading" style="height: 200px;">
                        <p>Click "Run Validation" to start system diagnostics.</p>
                    </div>
                </div>
            `);

            $('#btn-run-validate').on('click', () => this.runValidation());

            // Auto-run if we have a cached report less than 1 minute old? No, let's be explicit.
        },

        runValidation: function () {
            $('#health-content').html('<div class="gbn-cp-loading" style="height: 200px;"><p>Running diagnostics...</p></div>');

            $.ajax({
                url: gbnControlData.apiUrl,
                method: 'POST',
                data: {
                    action: 'gbn_diagnostics_validate',
                    nonce: gbnControlData.nonce
                },
                success: response => {
                    if (response.success) {
                        this.renderHealthReport(response.data);
                    } else {
                        $('#health-content').html(`<div class="gbn-cp-text-error">Validation failed: ${response.data.message}</div>`);
                    }
                },
                error: (xhr, status, error) => {
                    $('#health-content').html(`<div class="gbn-cp-text-error">Connection error: ${error}</div>`);
                }
            });
        },

        renderHealthReport: function (report) {
            let scoreColor = 'var(--gbn-cp-success)';
            if (report.score < 90) scoreColor = '#e3b341'; // Yellow
            if (report.score < 70) scoreColor = 'var(--gbn-cp-error)'; // Red

            let issuesHtml = '';
            if (report.issues.length === 0) {
                issuesHtml = `
                    <div style="text-align:center; padding: 2rem; background: rgba(35, 134, 54, 0.1); border-radius: 6px; border: 1px solid rgba(35, 134, 54, 0.3);">
                        <h3 style="color: var(--gbn-cp-success); margin:0;">No Issues Found</h3>
                        <p style="color: var(--gbn-cp-text); margin-top:0.5rem;">Your system is perfectly healthy.</p>
                    </div>
                `;
            } else {
                issuesHtml = report.issues
                    .map(issue => {
                        let severityColor = '#8b949e';
                        if (issue.severity === 'high') severityColor = 'var(--gbn-cp-error)';
                        if (issue.severity === 'medium') severityColor = '#e3b341';

                        return `
                        <div class="gbn-cp-card gbn-cp-mb-4" style="border-left: 4px solid ${severityColor};">
                            <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                                <strong style="color: ${severityColor}; text-transform:uppercase; font-size:12px;">${issue.type}</strong>
                                <span class="gbn-cp-badge gbn-cp-badge-neutral">${issue.context}</span>
                            </div>
                            <div style="color: var(--gbn-cp-text);">${issue.message}</div>
                        </div>
                    `;
                    })
                    .join('');
            }

            const html = `
                <div class="gbn-cp-grid">
                    <div class="gbn-cp-card" style="text-align:center;">
                        <h3 style="margin-bottom:0;">Health Score</h3>
                        <div style="font-size: 4rem; font-weight:bold; color: ${scoreColor}; line-height:1.2;">${report.score}</div>
                        <span style="color: var(--gbn-cp-text-muted);">/ 100</span>
                    </div>
                    <div class="gbn-cp-card">
                        <h3>Validation Summary</h3>
                        <p>Scan completed at: ${report.timestamp}</p>
                        <p>Issues found: <strong>${report.issues.length}</strong></p>
                    </div>
                </div>
                
                <h3 class="gbn-cp-mt-4">Detailed Issues</h3>
                ${issuesHtml}
            `;

            $('#health-content').html(html);
        },

        renderComponent: function ($container, role) {
            const registryData = this.data.components[role];
            const schemaData = this.data.payload.schemas[role] || {schema: [], config: {}};
            const themeSettings = (this.data.themeSettings.components && this.data.themeSettings.components[role]) || {};
            const traits = this.data.traits[role] || [];

            if (!registryData) {
                $container.html(`<h1>Component "${role}" not found</h1>`);
                return;
            }

            // Helper to generate rows
            const rows = schemaData.schema
                .map(field => {
                    const varName = `--gbn-${role}-${field.id}`;
                    const defaultValue = schemaData.config[field.id];
                    const themeValue = themeSettings[field.id];

                    // Status determination
                    let status = '<span class="gbn-cp-badge gbn-cp-badge-neutral">Default</span>';
                    let valueDisplay = defaultValue !== undefined && defaultValue !== null ? defaultValue : '<span style="color:var(--gbn-cp-text-muted); font-style:italic;">None</span>';

                    if (themeValue !== undefined && themeValue !== null && themeValue !== '') {
                        status = '<span class="gbn-cp-badge gbn-cp-badge-success">Theme Override</span>';
                        valueDisplay = `<strong>${themeValue}</strong> <span style="color:var(--gbn-cp-text-muted); font-size:0.8em;">(Default: ${defaultValue})</span>`;
                    }

                    return `
                    <tr>
                        <td>
                            <div style="font-weight:600;">${field.label || field.id}</div>
                            <code style="font-size:11px; color:var(--gbn-cp-accent);">${field.id}</code>
                        </td>
                        <td>
                            <code style="font-size:11px;">${field.tipo}</code>
                        </td>
                        <td>
                            <code style="color: var(--gbn-cp-text-muted);">${varName}</code>
                        </td>
                        <td>
                            ${valueDisplay}
                        </td>
                        <td>
                            ${status}
                        </td>
                    </tr>
                `;
                })
                .join('');

            const html = `
                <div class="gbn-cp-main-header">
                    <div>
                        <h1 class="gbn-cp-main-title">${registryData.name || role}</h1>
                        <span style="color: var(--gbn-cp-text-muted);">Role: ${role}</span>
                    </div>
                    <div>
                         <span class="gbn-cp-badge gbn-cp-badge-primary">${traits.length} Traits</span>
                    </div>
                </div>

                <div class="gbn-cp-grid" style="grid-template-columns: 1fr;">
                    <!-- Traits & Config Summary -->
                    <div class="gbn-cp-card">
                        <h3>Architecture Overview</h3>
                        <div style="display:flex; gap: 2rem; flex-wrap: wrap;">
                            <div>
                                <strong style="display:block; margin-bottom:0.5rem; color:var(--gbn-cp-text-muted);">Active Traits</strong>
                                ${traits.length > 0 ? `<div style="display:flex; gap:0.5rem; flex-wrap:wrap;">${traits.map(t => `<span class="gbn-cp-tag">${t.split('\\').pop()}</span>`).join('')}</div>` : 'None'}
                            </div>
                            <div>
                                <strong style="display:block; margin-bottom:0.5rem; color:var(--gbn-cp-text-muted);">Schema Fields</strong>
                                <span style="font-size:1.2rem;">${schemaData.schema.length}</span>
                            </div>
                            <div>
                                <strong style="display:block; margin-bottom:0.5rem; color:var(--gbn-cp-text-muted);">DOM Observability</strong>
                                ${
                                    registryData.selector
                                        ? `<span class="gbn-cp-badge gbn-cp-badge-success">Ready</span> 
                                       <div style="font-size:11px; margin-top:4px; color:var(--gbn-cp-text-muted);">
                                         ${registryData.selector.attribute ? `Attr: [${registryData.selector.attribute}]` : ''}
                                         ${registryData.selector.class ? `Class: .${registryData.selector.class}` : ''}
                                       </div>
                                       <div style="font-size:10px; color:var(--gbn-cp-success); margin-top:2px;">✔ Supports Inline & Class Sync</div>`
                                        : `<span class="gbn-cp-badge gbn-cp-badge-error">Missing Selector</span>
                                       <div style="font-size:10px; color:var(--gbn-cp-error); margin-top:2px;">✖ Cannot sync inline/class styles</div>`
                                }
                            </div>
                        </div>
                    </div>

                    <!-- Deep Mapping Table -->
                    <div class="gbn-cp-card">
                        <h3>Property Radiography (CSS Vars & Inheritance)</h3>
                        <div style="overflow-x: auto;">
                            <table class="gbn-cp-table">
                                <thead>
                                    <tr>
                                        <th>Field / ID</th>
                                        <th>Type</th>
                                        <th>CSS Variable (Expected)</th>
                                        <th>Current Value (Resolved)</th>
                                        <th>Source</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${rows.length ? rows : '<tr><td colspan="5" style="text-align:center; padding:2rem;">No fields defined in schema</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Raw Debug -->
                    <details>
                        <summary style="cursor:pointer; color:var(--gbn-cp-text-muted); margin-bottom:1rem;">View Raw Schema JSON</summary>
                        <div class="gbn-cp-code-block">${JSON.stringify(schemaData, null, 2)}</div>
                    </details>
                </div>
            `;
            $container.html(html);
        },

        renderThemeSettings: function ($container) {
            const html = `
                <div class="gbn-cp-main-header">
                    <h1 class="gbn-cp-main-title">Theme Settings</h1>
                </div>
                <div class="gbn-cp-card">
                    <h3>Global Configuration (wp_options)</h3>
                    <div class="gbn-cp-code-block">${JSON.stringify(this.data.themeSettings, null, 2)}</div>
                </div>
            `;
            $container.html(html);
        },

        renderLogs: function ($container) {
            $container.html(`
                <div class="gbn-cp-main-header">
                    <h1 class="gbn-cp-main-title">System Logs</h1>
                    <button id="btn-refresh-logs" class="gbn-cp-btn gbn-cp-btn-secondary">Refresh Logs</button>
                </div>
                <div id="logs-content">Loading...</div>
            `);

            this.fetchLogs();
            $('#btn-refresh-logs').on('click', () => this.fetchLogs());
        },

        fetchLogs: function () {
            $.ajax({
                url: gbnControlData.apiUrl,
                method: 'POST',
                data: {
                    action: 'gbn_diagnostics_logs',
                    nonce: gbnControlData.nonce
                },
                success: response => {
                    if (response.success) {
                        let logsHtml = '';
                        if (Object.keys(response.data.logs).length === 0) {
                            logsHtml = '<div class="gbn-cp-card">No logs found.</div>';
                        } else {
                            for (const [filename, content] of Object.entries(response.data.logs)) {
                                logsHtml += `
                                    <div class="gbn-cp-card gbn-cp-mb-4">
                                        <h3>${filename}</h3>
                                        <div class="gbn-cp-code-block" style="white-space: pre-wrap;">${content}</div>
                                    </div>
                                `;
                            }
                        }
                        $('#logs-content').html(logsHtml);
                    } else {
                        $('#logs-content').html(`<div class="gbn-cp-text-error">Error loading logs: ${response.data.message}</div>`);
                    }
                }
            });
        },

        renderError: function (msg) {
            $('#gbn-control-app').html(`<div style="padding:2rem; color:red;">Error: ${msg}</div>`);
        }
    };

    $(document).ready(function () {
        App.init();
    });
})(jQuery);
