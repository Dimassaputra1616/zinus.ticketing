<x-app-layout>
    <style>
        .automation-page { min-height: 100vh; padding-bottom: 2rem; }
        .automation-stack > * + * { margin-top: 1.25rem; }
        .automation-header { border-bottom: 1px solid #e2e8f0; padding: .25rem 0 1.25rem; }
        .automation-header-row { display: flex; flex-direction: column; gap: 1rem; }
        .automation-breadcrumb { display: flex; align-items: center; gap: .5rem; margin-bottom: .5rem; color: #64748b; font-size: .75rem; font-weight: 500; }
        .automation-title-row { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
        .automation-title { margin: 0; color: #020617; font-size: 1.875rem; font-weight: 650; line-height: 1.15; }
        .automation-status { display: inline-flex; align-items: center; gap: .5rem; border-radius: .375rem; padding: .25rem .625rem; font-size: .75rem; font-weight: 700; border: 1px solid; }
        .automation-status::before { content: ""; width: .375rem; height: .375rem; border-radius: 999px; background: currentColor; }
        .automation-status--ready { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .automation-status--warning { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .automation-env-grid { display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: .5rem; color: #475569; font-size: .75rem; }
        .automation-env-card, .automation-panel { border: 1px solid #e2e8f0; border-radius: .5rem; background: #fff; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
        .automation-env-card { padding: .5rem .75rem; }
        .automation-env-label, .automation-label { color: #475569; font-size: .75rem; font-weight: 700; }
        .automation-env-value { margin-top: .25rem; color: #0f172a; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .automation-grid { display: grid; gap: 1.25rem; }
        .automation-panel { padding: 1rem; }
        .automation-main > * + *, .automation-side > * + *, .automation-command-list > * + *, .automation-output-list > * + *, .automation-form > * + * { margin-top: 1.25rem; }
        .automation-panel-title { margin: 0; color: #0f172a; font-size: .875rem; font-weight: 800; }
        .automation-panel-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .75rem; }
        .automation-badge { border-radius: .375rem; background: #f1f5f9; color: #475569; padding: .25rem .5rem; font-size: .6875rem; font-weight: 800; }
        .automation-command { width: 100%; border: 1px solid #e2e8f0; border-radius: .5rem; background: #fff; padding: .75rem; text-align: left; transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease; }
        .automation-command:hover { border-color: #cbd5e1; background: #f8fafc; }
        .automation-command--active { border-color: #6ee7b7; background: #ecfdf5; box-shadow: 0 1px 2px rgba(15, 23, 42, .05); }
        .automation-command-row { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; }
        .automation-command-title { color: #0f172a; font-size: .875rem; font-weight: 800; }
        .automation-command-summary { margin-top: .25rem; color: #64748b; font-size: .75rem; line-height: 1.45; }
        .automation-script-badge { margin-top: .125rem; border: 1px solid #e2e8f0; border-radius: .375rem; background: #fff; color: #64748b; padding: .25rem .5rem; font-size: .625rem; font-weight: 800; text-transform: uppercase; white-space: nowrap; }
        .automation-output-row { border: 1px solid #f1f5f9; border-radius: .5rem; background: #f8fafc; padding: .625rem .75rem; }
        .automation-output-main { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .automation-file-name { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #334155; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: .75rem; }
        .automation-file-status { border-radius: .375rem; padding: .125rem .375rem; font-size: .625rem; font-weight: 800; }
        .automation-file-status--ready { background: #d1fae5; color: #047857; }
        .automation-file-status--missing { background: #e2e8f0; color: #64748b; }
        .automation-file-meta { margin-top: .25rem; color: #64748b; font-size: .6875rem; }
        .automation-form-panel { padding: 1.25rem; }
        .automation-form-grid { display: grid; gap: 1rem; }
        .automation-form-grid-2, .automation-form-grid-3, .automation-token-grid { display: grid; gap: 1rem; }
        .automation-field > label { display: block; margin-bottom: .375rem; }
        .automation-input { width: 100%; border: 1px solid #cbd5e1; border-radius: .5rem; background: #fff; color: #0f172a; font-size: .875rem; padding: .625rem .75rem; }
        .automation-input:focus { border-color: #10b981; outline: 0; box-shadow: 0 0 0 3px rgba(16, 185, 129, .15); }
        .automation-input:disabled { background: #f1f5f9; color: #94a3b8; }
        .automation-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .automation-option-list { display: flex; flex-wrap: wrap; gap: .5rem; }
        .automation-option { display: inline-flex; height: 2.25rem; align-items: center; gap: .5rem; border: 1px solid #e2e8f0; border-radius: .5rem; background: #fff; padding: 0 .75rem; color: #334155; font-size: .75rem; font-weight: 700; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
        .automation-submit-row { display: flex; flex-direction: column; gap: .75rem; border-top: 1px solid #f1f5f9; padding-top: 1rem; }
        .automation-script-name { color: #64748b; font-size: .75rem; }
        .automation-run { display: inline-flex; height: 2.5rem; align-items: center; justify-content: center; gap: .5rem; border: 0; border-radius: .5rem; background: #059669; color: #fff; padding: 0 1rem; font-size: .875rem; font-weight: 800; box-shadow: 0 1px 2px rgba(15, 23, 42, .08); }
        .automation-run:hover { background: #047857; }
        .automation-run:disabled { cursor: not-allowed; background: #cbd5e1; }
        .automation-run svg { width: 1rem; height: 1rem; }
        .automation-spinner { animation: automation-spin .7s linear infinite; }
        .automation-terminal { overflow: hidden; border: 1px solid #0f172a; border-radius: .5rem; background: #020617; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
        .automation-terminal-head { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255, 255, 255, .1); background: #0f172a; padding: .5rem 1rem; }
        .automation-terminal-title { margin-left: .5rem; color: #cbd5e1; font-size: .75rem; font-weight: 800; }
        .automation-dot { display: inline-block; width: .625rem; height: .625rem; border-radius: 999px; }
        .automation-dot--red { background: #fb7185; }
        .automation-dot--green { background: #34d399; }
        .automation-terminal-actions { display: flex; align-items: center; gap: .5rem; }
        .automation-terminal-button { border: 1px solid rgba(255, 255, 255, .1); border-radius: .375rem; background: transparent; color: #cbd5e1; padding: .25rem .5rem; font-size: .75rem; font-weight: 800; }
        .automation-terminal-button:hover { background: rgba(255, 255, 255, .1); }
        .automation-terminal-body { min-height: 360px; max-height: 620px; overflow: auto; white-space: pre-wrap; margin: 0; padding: 1rem; color: #d1fae5; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: .75rem; line-height: 1.5rem; }
        @media (min-width: 640px) {
            .automation-submit-row { flex-direction: row; align-items: center; justify-content: space-between; }
            .automation-env-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) {
            .automation-header-row { flex-direction: row; align-items: flex-end; justify-content: space-between; }
            .automation-env-grid { min-width: 520px; }
            .automation-form-grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .automation-form-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .automation-token-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1.25fr); }
            .automation-span-full { grid-column: 1 / -1; }
        }
        @media (min-width: 1280px) {
            .automation-grid { grid-template-columns: 360px minmax(0, 1fr); }
        }
        @keyframes automation-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>

    <div
        x-data="assetAutomationConsole({
            runUrl: @js(route('admin.assets.automation-console.run')),
            statusUrlTemplate: @js(route('admin.assets.automation-console.runs.show', ['runId' => '__RUN_ID__'])),
            csrfToken: @js(csrf_token()),
            commands: @js($commands),
            environment: @js($environment),
        })"
        class="automation-page automation-stack"
    >
        <header class="automation-header">
            <div class="automation-header-row">
                <div>
                    <div class="automation-breadcrumb">
                        <span>Asset Management</span>
                        <svg style="width:.875rem;height:.875rem;color:#cbd5e1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span style="color:#334155">Automation Console</span>
                    </div>
                    <div class="automation-title-row">
                        <h1 class="automation-title">Automation Console</h1>
                        <span
                            class="automation-status"
                            :class="environment.can_execute ? 'automation-status--ready' : 'automation-status--warning'"
                        >
                            <span x-text="environment.can_execute ? 'Ready' : 'Needs setup'"></span>
                        </span>
                    </div>
                </div>

                <div class="automation-env-grid">
                    <div class="automation-env-card">
                        <div class="automation-env-label">PowerShell</div>
                        <div class="automation-env-value automation-mono" x-text="environment.powershell || 'Not detected'"></div>
                    </div>
                    <div class="automation-env-card">
                        <div class="automation-env-label">Installer</div>
                        <div class="automation-env-value" x-text="environment.installer_exists ? 'Ready' : 'Not found'"></div>
                    </div>
                    <div class="automation-env-card">
                        <div class="automation-env-label">Max timeout</div>
                        <div class="automation-env-value automation-mono"><span x-text="environment.timeout_seconds"></span>s</div>
                    </div>
                </div>
            </div>
        </header>

        <section class="automation-grid">
            <aside class="automation-side">
                <div class="automation-panel">
                    <div class="automation-panel-head">
                        <h2 class="automation-panel-title">Command</h2>
                        <span class="automation-badge" x-text="selectedCommand.group"></span>
                    </div>

                    <div class="automation-command-list">
                        @foreach ($commands as $command)
                            <button
                                type="button"
                                @click="selectCommand(@js($command['key']))"
                                class="automation-command"
                                :class="selectedKey === @js($command['key']) ? 'automation-command--active' : ''"
                            >
                                <div class="automation-command-row">
                                    <div>
                                        <div class="automation-command-title">{{ $command['label'] }}</div>
                                        <div class="automation-command-summary">{{ $command['summary'] }}</div>
                                    </div>
                                    <span class="automation-script-badge">{{ Str::replaceLast('.ps1', '', $command['script']) }}</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="automation-panel">
                    <div class="automation-panel-head">
                        <h2 class="automation-panel-title">Output Files</h2>
                        <button type="button" @click="refreshOutputs()" class="automation-terminal-button" style="color:#475569;border-color:#e2e8f0">Refresh</button>
                    </div>
                    <div class="automation-output-list">
                        <template x-for="file in selectedOutputFiles" :key="file.name">
                            <div class="automation-output-row">
                                <div class="automation-output-main">
                                    <span class="automation-file-name" x-text="file.name"></span>
                                    <span class="automation-file-status" :class="file.exists ? 'automation-file-status--ready' : 'automation-file-status--missing'" x-text="file.exists ? 'Ready' : 'Missing'"></span>
                                </div>
                                <div class="automation-file-meta" x-text="file.modified_at || 'No run yet'"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </aside>

            <main class="automation-main">
                <section class="automation-panel automation-form-panel">
                    <form @submit.prevent="runCommand()" class="automation-form">
                        <div class="automation-form-grid automation-form-grid-2" x-show="selectedCommand.needs_segments">
                            <div class="automation-field automation-span-full">
                                <label for="automation-segments" class="automation-label">IP segments</label>
                                <textarea
                                    id="automation-segments"
                                    x-model="form.segments"
                                    rows="3"
                                    class="automation-input automation-mono"
                                    placeholder="10.62.38, 10.62.39, 10.62.36"
                                ></textarea>
                            </div>
                            <div class="automation-field">
                                <label for="automation-start-host" class="automation-label">Start host</label>
                                <input id="automation-start-host" type="number" min="1" max="254" x-model.number="form.start_host" class="automation-input">
                            </div>
                            <div class="automation-field">
                                <label for="automation-end-host" class="automation-label">End host</label>
                                <input id="automation-end-host" type="number" min="1" max="254" x-model.number="form.end_host" class="automation-input">
                            </div>
                        </div>

                        <div class="automation-field" x-show="selectedCommand.needs_targets">
                            <label for="automation-targets" class="automation-label">Hostname / IP targets</label>
                            <textarea
                                id="automation-targets"
                                x-model="form.targets"
                                rows="5"
                                class="automation-input automation-mono"
                                placeholder="PC-001&#10;LAPTOP-002&#10;10.62.38.25"
                            ></textarea>
                        </div>

                        <div class="automation-form-grid automation-form-grid-3" x-show="selectedCommand.uses_asset_sync">
                            <div class="automation-field">
                                <label for="automation-factory" class="automation-label">Factory</label>
                                <input id="automation-factory" type="text" x-model="form.factory" class="automation-input">
                            </div>
                            <div class="automation-field">
                                <label for="automation-department" class="automation-label">Department</label>
                                <input id="automation-department" type="text" x-model="form.department" class="automation-input">
                            </div>
                            <div class="automation-field">
                                <label for="automation-max-parallel" class="automation-label">Max parallel</label>
                                <input id="automation-max-parallel" type="number" min="1" max="50" x-model.number="form.max_parallel" class="automation-input">
                            </div>
                            <div class="automation-field automation-span-full">
                                <label for="automation-server-url" class="automation-label">Asset sync API</label>
                                <input id="automation-server-url" type="url" x-model="form.server_url" class="automation-input automation-mono">
                            </div>
                        </div>

                        <div class="automation-token-grid" x-show="selectedCommand.requires_token">
                            <label class="automation-option">
                                <input type="checkbox" x-model="form.use_config_token" :disabled="!environment.has_config_token">
                                <span x-text="environment.has_config_token ? 'Use server token' : 'Server token not configured'"></span>
                            </label>
                            <input
                                type="password"
                                x-model="form.token"
                                :disabled="form.use_config_token"
                                class="automation-input"
                                placeholder="Manual asset sync token"
                            >
                        </div>

                        <div class="automation-option-list" x-show="optionEntries.length > 0">
                            <template x-for="[key, option] in optionEntries" :key="key">
                                <label class="automation-option">
                                    <input type="checkbox" x-model="form[key]">
                                    <span x-text="option.label"></span>
                                </label>
                            </template>
                        </div>

                        <div class="automation-submit-row">
                            <div class="automation-script-name automation-mono">
                                <span x-text="selectedCommand.script"></span>
                            </div>
                            <button
                                type="submit"
                                :disabled="running || !environment.enabled"
                                class="automation-run"
                            >
                                <svg x-show="!running" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="m8 5 11 7-11 7V5Z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <svg x-show="running" class="automation-spinner" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span x-text="running ? 'Running...' : 'Run command'"></span>
                            </button>
                        </div>
                    </form>
                </section>

                <section class="automation-terminal">
                    <div class="automation-terminal-head">
                        <div>
                            <span class="automation-dot automation-dot--red"></span>
                            <span class="automation-dot automation-dot--green"></span>
                            <span class="automation-terminal-title">Terminal Output</span>
                        </div>
                        <div class="automation-terminal-actions">
                            <button type="button" @click="copyTerminal()" class="automation-terminal-button">Copy</button>
                            <button type="button" @click="clearTerminal()" class="automation-terminal-button">Clear</button>
                        </div>
                    </div>
                    <pre class="automation-terminal-body" x-text="terminalText"></pre>
                </section>
            </main>
        </section>
    </div>

    <script>
        function assetAutomationConsole({ runUrl, statusUrlTemplate, csrfToken, commands, environment }) {
            const commandList = Object.values(commands);
            const defaultCommand = commandList[0] || {};

            return {
                runUrl,
                statusUrlTemplate,
                csrfToken,
                commands,
                commandList,
                environment,
                selectedKey: defaultCommand.key,
                running: false,
                pollTimer: null,
                result: null,
                terminalText: 'zinus> select a command and press Run command',
                form: {
                    segments: defaultCommand.default_segments || '',
                    targets: '',
                    start_host: 1,
                    end_host: 254,
                    max_parallel: 20,
                    factory: environment.default_factory || 'GCI-HWANG',
                    department: environment.default_department || 'IT',
                    server_url: environment.default_server_url || '',
                    use_config_token: !!environment.has_config_token,
                    token: '',
                },
                get selectedCommand() {
                    return this.commands[this.selectedKey] || defaultCommand;
                },
                get optionEntries() {
                    return Object.entries(this.selectedCommand.options || {});
                },
                get selectedOutputFiles() {
                    const selectedNames = this.selectedCommand.output_files || [];
                    return selectedNames.map((name) => {
                        return (this.environment.recent_outputs || []).find((file) => file.name === name) || {
                            name,
                            exists: false,
                            modified_at: null,
                            size: null,
                        };
                    });
                },
                canRunSelectedCommand() {
                    if (this.selectedCommand.native) {
                        return !!this.environment.can_run_native;
                    }

                    return !!this.environment.can_run_powershell;
                },
                selectedCommandSetupMessage() {
                    if (this.canRunSelectedCommand()) {
                        return 'ready';
                    }

                    if (this.selectedCommand.native) {
                        return 'automation console disabled';
                    }

                    if (!this.environment.powershell) {
                        return 'PowerShell runtime not detected on app server';
                    }

                    if (!this.environment.installer_exists) {
                        return 'installer folder not found on app server';
                    }

                    return 'needs setup';
                },
                selectCommand(key) {
                    this.selectedKey = key;
                    this.form.segments = this.selectedCommand.default_segments || this.form.segments || '';
                    this.form.max_parallel = Number(this.selectedCommand.default_max_parallel || (this.selectedCommand.key === 'sync_local_printers' ? 8 : 20));
                    Object.entries(this.selectedCommand.options || {}).forEach(([optionKey, option]) => {
                        this.form[optionKey] = !!option.default;
                    });

                    if (!this.canRunSelectedCommand()) {
                        this.terminalText = `zinus> ${this.selectedCommand.script}\n${this.selectedCommandSetupMessage()}\npress Run command to see server validation details`;
                        return;
                    }

                    this.terminalText = `zinus> ${this.selectedCommand.script}\nready`;
                },
                payload() {
                    const payload = {
                        command_key: this.selectedKey,
                        segments: this.form.segments,
                        targets: this.form.targets,
                        start_host: this.form.start_host,
                        end_host: this.form.end_host,
                        max_parallel: this.form.max_parallel,
                        factory: this.form.factory,
                        department: this.form.department,
                        server_url: this.form.server_url,
                        use_config_token: this.form.use_config_token,
                        token: this.form.use_config_token ? '' : this.form.token,
                    };

                    Object.keys(this.selectedCommand.options || {}).forEach((key) => {
                        payload[key] = !!this.form[key];
                    });

                    return payload;
                },
                async runCommand() {
                    this.stopPolling();
                    this.running = true;
                    this.result = null;
                    this.terminalText = `zinus> starting ${this.selectedCommand.label}...\n`;

                    try {
                        const response = await fetch(this.runUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify(this.payload()),
                        });
                        const data = await this.parseJsonResponse(response);

                        if (!response.ok) {
                            const messages = data.errors
                                ? Object.values(data.errors).flat().join('\n')
                                : (data.message || 'Command failed before execution.');
                            throw new Error(messages);
                        }

                        this.result = data;
                        this.terminalText = this.formatTerminal(data);
                        this.environment.recent_outputs = data.output_files || this.environment.recent_outputs;

                        if (data.async && data.running && data.run_id) {
                            this.startPolling(data.run_id);
                            return;
                        }
                    } catch (error) {
                        this.terminalText += `\nerror: ${error.message}`;
                        this.running = false;
                    }
                },
                async parseJsonResponse(response) {
                    const text = await response.text();
                    if (!text) {
                        return {};
                    }

                    try {
                        return JSON.parse(text);
                    } catch (error) {
                        return {
                            message: response.ok ? text : `HTTP ${response.status}: ${text.slice(0, 300)}`,
                        };
                    }
                },
                formatTerminal(data) {
                    const status = data.running
                        ? 'running'
                        : (data.successful ? 'success' : 'failed');

                    return [
                        `zinus> ${data.command}`,
                        `status: ${status} | exit: ${data.exit_code ?? '-'} | duration: ${data.duration_ms ?? 0}ms`,
                        data.run_id ? `run: ${data.run_id}` : null,
                        data.log_file ? `log: storage/logs/asset-automation/${data.log_file}` : null,
                        '',
                        '[stdout]',
                        data.stdout || (data.running ? '(waiting for output)' : '(empty)'),
                        '',
                        '[stderr]',
                        data.stderr || '(empty)',
                        '',
                        '[outputs]',
                        ...(data.output_files || []).map((file) => `${file.exists ? 'ok' : '--'} ${file.name}${file.modified_at ? ' | ' + file.modified_at : ''}`),
                    ].filter((line) => line !== null).join('\n');
                },
                statusUrl(runId) {
                    return this.statusUrlTemplate.replace('__RUN_ID__', encodeURIComponent(runId));
                },
                startPolling(runId) {
                    this.stopPolling();
                    this.pollTimer = window.setInterval(() => this.pollRun(runId), 2000);
                    this.pollRun(runId);
                },
                stopPolling() {
                    if (this.pollTimer) {
                        window.clearInterval(this.pollTimer);
                        this.pollTimer = null;
                    }
                },
                async pollRun(runId) {
                    try {
                        const response = await fetch(this.statusUrl(runId), {
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await this.parseJsonResponse(response);

                        if (!response.ok) {
                            throw new Error(data.message || 'Failed to read run status.');
                        }

                        this.result = data;
                        this.terminalText = this.formatTerminal(data);
                        this.environment.recent_outputs = data.output_files || this.environment.recent_outputs;

                        if (!data.running) {
                            this.stopPolling();
                            this.running = false;
                        }
                    } catch (error) {
                        this.stopPolling();
                        this.running = false;
                        this.terminalText += `\nstatus error: ${error.message}`;
                    }
                },
                refreshOutputs() {
                    if (this.result && this.result.output_files) {
                        this.environment.recent_outputs = this.result.output_files;
                    }
                },
                clearTerminal() {
                    this.stopPolling();
                    this.running = false;
                    this.terminalText = 'zinus> cleared';
                },
                async copyTerminal() {
                    if (!navigator.clipboard) {
                        return;
                    }

                    await navigator.clipboard.writeText(this.terminalText);
                },
                init() {
                    this.selectCommand(this.selectedKey);
                },
            };
        }
    </script>
</x-app-layout>
