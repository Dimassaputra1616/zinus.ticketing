<x-app-layout>
    <div
        x-data="assetAutomationConsole({
            runUrl: @js(route('admin.assets.automation-console.run')),
            csrfToken: @js(csrf_token()),
            commands: @js($commands),
            environment: @js($environment),
        })"
        class="min-h-screen space-y-5 pb-8"
    >
        <header class="border-b border-slate-200 pb-5 pt-1">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <div class="mb-2 flex items-center gap-2 text-xs font-medium text-slate-500">
                        <span>Asset Management</span>
                        <svg class="h-3.5 w-3.5 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span class="text-slate-700">Automation Console</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold tracking-normal text-slate-950 sm:text-3xl">Automation Console</h1>
                        <span
                            class="inline-flex items-center gap-2 rounded-md px-2.5 py-1 text-xs font-semibold ring-1"
                            :class="environment.can_execute ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200'"
                        >
                            <span class="h-1.5 w-1.5 rounded-full" :class="environment.can_execute ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                            <span x-text="environment.can_execute ? 'Ready' : 'Needs setup'"></span>
                        </span>
                    </div>
                </div>

                <div class="grid gap-2 text-xs text-slate-600 sm:grid-cols-3 lg:min-w-[520px]">
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                        <div class="font-semibold text-slate-500">PowerShell</div>
                        <div class="mt-1 truncate font-mono text-slate-900" x-text="environment.powershell || 'Not detected'"></div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                        <div class="font-semibold text-slate-500">Installer</div>
                        <div class="mt-1 font-semibold text-slate-900" x-text="environment.installer_exists ? 'Ready' : 'Not found'"></div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                        <div class="font-semibold text-slate-500">Max timeout</div>
                        <div class="mt-1 font-mono text-slate-900"><span x-text="environment.timeout_seconds"></span>s</div>
                    </div>
                </div>
            </div>
        </header>

        <section class="grid gap-5 xl:grid-cols-[360px_minmax(0,1fr)]">
            <aside class="space-y-3">
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900">Command</h2>
                        <span class="rounded-md bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600" x-text="selectedCommand.group"></span>
                    </div>

                    <div class="space-y-2">
                        @foreach ($commands as $command)
                            <button
                                type="button"
                                @click="selectCommand(@js($command['key']))"
                                class="w-full rounded-lg border px-3 py-3 text-left transition"
                                :class="selectedKey === @js($command['key']) ? 'border-emerald-300 bg-emerald-50/80 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-bold text-slate-900">{{ $command['label'] }}</div>
                                        <div class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">{{ $command['summary'] }}</div>
                                    </div>
                                    <span class="mt-0.5 rounded-md bg-white px-2 py-1 text-[10px] font-bold uppercase text-slate-500 ring-1 ring-slate-200">{{ Str::replaceLast('.ps1', '', $command['script']) }}</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900">Output Files</h2>
                        <button type="button" @click="refreshOutputs()" class="rounded-md border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50">Refresh</button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="file in selectedOutputFiles" :key="file.name">
                            <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="truncate font-mono text-xs text-slate-700" x-text="file.name"></span>
                                    <span class="rounded-md px-1.5 py-0.5 text-[10px] font-bold" :class="file.exists ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500'" x-text="file.exists ? 'Ready' : 'Missing'"></span>
                                </div>
                                <div class="mt-1 text-[11px] text-slate-500" x-text="file.modified_at || 'No run yet'"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </aside>

            <main class="space-y-5">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <form @submit.prevent="runCommand()" class="space-y-5">
                        <div class="grid gap-4 lg:grid-cols-2" x-show="selectedCommand.needs_segments">
                            <div class="lg:col-span-2">
                                <label for="automation-segments" class="mb-1.5 block text-xs font-semibold text-slate-600">IP segments</label>
                                <textarea
                                    id="automation-segments"
                                    x-model="form.segments"
                                    rows="3"
                                    class="w-full rounded-lg border-slate-300 font-mono text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500"
                                    placeholder="10.62.38, 10.62.39, 10.62.36"
                                ></textarea>
                            </div>
                            <div>
                                <label for="automation-start-host" class="mb-1.5 block text-xs font-semibold text-slate-600">Start host</label>
                                <input id="automation-start-host" type="number" min="1" max="254" x-model.number="form.start_host" class="h-10 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label for="automation-end-host" class="mb-1.5 block text-xs font-semibold text-slate-600">End host</label>
                                <input id="automation-end-host" type="number" min="1" max="254" x-model.number="form.end_host" class="h-10 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                        </div>

                        <div x-show="selectedCommand.needs_targets">
                            <label for="automation-targets" class="mb-1.5 block text-xs font-semibold text-slate-600">Hostname / IP targets</label>
                            <textarea
                                id="automation-targets"
                                x-model="form.targets"
                                rows="5"
                                class="w-full rounded-lg border-slate-300 font-mono text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500"
                                placeholder="PC-001&#10;LAPTOP-002&#10;10.62.38.25"
                            ></textarea>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-3" x-show="selectedCommand.uses_asset_sync">
                            <div>
                                <label for="automation-factory" class="mb-1.5 block text-xs font-semibold text-slate-600">Factory</label>
                                <input id="automation-factory" type="text" x-model="form.factory" class="h-10 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label for="automation-department" class="mb-1.5 block text-xs font-semibold text-slate-600">Department</label>
                                <input id="automation-department" type="text" x-model="form.department" class="h-10 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label for="automation-max-parallel" class="mb-1.5 block text-xs font-semibold text-slate-600">Max parallel</label>
                                <input id="automation-max-parallel" type="number" min="1" max="50" x-model.number="form.max_parallel" class="h-10 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div class="lg:col-span-3">
                                <label for="automation-server-url" class="mb-1.5 block text-xs font-semibold text-slate-600">Asset sync API</label>
                                <input id="automation-server-url" type="url" x-model="form.server_url" class="h-10 w-full rounded-lg border-slate-300 font-mono text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                        </div>

                        <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)]" x-show="selectedCommand.requires_token">
                            <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700">
                                <input type="checkbox" x-model="form.use_config_token" :disabled="!environment.has_config_token" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span x-text="environment.has_config_token ? 'Use server token' : 'Server token not configured'"></span>
                            </label>
                            <input
                                type="password"
                                x-model="form.token"
                                :disabled="form.use_config_token"
                                class="h-10 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 disabled:bg-slate-100"
                                placeholder="Manual asset sync token"
                            >
                        </div>

                        <div class="flex flex-wrap gap-2" x-show="optionEntries.length > 0">
                            <template x-for="[key, option] in optionEntries" :key="key">
                                <label class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm">
                                    <input type="checkbox" x-model="form[key]" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span x-text="option.label"></span>
                                </label>
                            </template>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs text-slate-500">
                                <span class="font-mono" x-text="selectedCommand.script"></span>
                            </div>
                            <button
                                type="submit"
                                :disabled="running || !environment.can_execute"
                                class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                            >
                                <svg x-show="!running" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="m8 5 11 7-11 7V5Z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <svg x-show="running" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span x-text="running ? 'Running...' : 'Run command'"></span>
                            </button>
                        </div>
                    </form>
                </section>

                <section class="overflow-hidden rounded-lg border border-slate-900 bg-slate-950 shadow-sm">
                    <div class="flex items-center justify-between border-b border-white/10 bg-slate-900 px-4 py-2">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-300"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                            <span class="ml-2 text-xs font-semibold text-slate-300">Terminal Output</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="copyTerminal()" class="rounded-md border border-white/10 px-2 py-1 text-xs font-semibold text-slate-300 hover:bg-white/10">Copy</button>
                            <button type="button" @click="clearTerminal()" class="rounded-md border border-white/10 px-2 py-1 text-xs font-semibold text-slate-300 hover:bg-white/10">Clear</button>
                        </div>
                    </div>
                    <pre class="min-h-[360px] max-h-[620px] overflow-auto whitespace-pre-wrap p-4 font-mono text-xs leading-6 text-emerald-100" x-text="terminalText"></pre>
                </section>
            </main>
        </section>
    </div>

    <script>
        function assetAutomationConsole({ runUrl, csrfToken, commands, environment }) {
            const commandList = Object.values(commands);
            const defaultCommand = commandList[0] || {};

            return {
                runUrl,
                csrfToken,
                commands,
                commandList,
                environment,
                selectedKey: defaultCommand.key,
                running: false,
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
                selectCommand(key) {
                    this.selectedKey = key;
                    this.form.segments = this.selectedCommand.default_segments || this.form.segments || '';
                    this.form.max_parallel = this.selectedCommand.key === 'sync_local_printers' ? 8 : 20;
                    Object.entries(this.selectedCommand.options || {}).forEach(([optionKey, option]) => {
                        this.form[optionKey] = !!option.default;
                    });
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
                        const data = await response.json();

                        if (!response.ok) {
                            const messages = data.errors
                                ? Object.values(data.errors).flat().join('\n')
                                : (data.message || 'Command failed before execution.');
                            throw new Error(messages);
                        }

                        this.result = data;
                        this.terminalText = [
                            `zinus> ${data.command}`,
                            `status: ${data.successful ? 'success' : 'failed'} | exit: ${data.exit_code} | duration: ${data.duration_ms}ms`,
                            data.log_file ? `log: storage/logs/asset-automation/${data.log_file}` : null,
                            '',
                            '[stdout]',
                            data.stdout || '(empty)',
                            '',
                            '[stderr]',
                            data.stderr || '(empty)',
                            '',
                            '[outputs]',
                            ...(data.output_files || []).map((file) => `${file.exists ? 'ok' : '--'} ${file.name}${file.modified_at ? ' | ' + file.modified_at : ''}`),
                        ].filter((line) => line !== null).join('\n');
                        this.environment.recent_outputs = data.output_files || this.environment.recent_outputs;
                    } catch (error) {
                        this.terminalText += `\nerror: ${error.message}`;
                    } finally {
                        this.running = false;
                    }
                },
                refreshOutputs() {
                    if (this.result && this.result.output_files) {
                        this.environment.recent_outputs = this.result.output_files;
                    }
                },
                clearTerminal() {
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
