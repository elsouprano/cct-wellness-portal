@section('title', 'Response Quality Settings')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                {{ __('Response Quality Settings') }}
            </h2>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Automatic Engine Active
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="{ showAdvanced: false }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-xl text-emerald-800 text-sm font-medium shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Informational Banner -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border p-6 sm:p-8">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-heading font-bold text-foreground">
                            Automatic Rule-Based Assessment
                        </h3>
                        <p class="text-sm text-foreground/70 mt-1 leading-relaxed">
                            Submissions are automatically evaluated using deterministic, reproducible rules upon completion. Guidance personnel do not need to configure mathematical formulas or thresholds. The engine operates using established psychological assessment baselines.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Detection Methods Overview -->
            <div class="space-y-4">
                <div class="px-2">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-foreground/60">Active Detection Methods</h3>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    @foreach($methodDefinitions as $typeKey => $method)
                        @php
                            $settingModel = $settings->firstWhere('flag_type', $typeKey);
                            $currentValue = $settingModel?->setting_value;
                        @endphp
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-border flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="space-y-1 max-w-2xl">
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-primary/10 text-primary uppercase tracking-wide">
                                        Enabled
                                    </span>
                                    <h4 class="font-heading font-bold text-foreground text-base">
                                        {{ $method['title'] }}
                                    </h4>
                                </div>
                                <p class="text-sm text-foreground/70 leading-relaxed">
                                    {{ $method['description'] }}
                                </p>
                            </div>

                            <div class="shrink-0 bg-muted/40 p-3.5 rounded-xl border border-border min-w-[190px] text-right">
                                <span class="block text-xs font-medium text-foreground/50 uppercase">Current Baseline</span>
                                <span class="block text-base font-bold text-primary mt-0.5">
                                    {{ $currentValue }} {{ $method['unit'] }}
                                </span>
                                <span class="block text-[11px] text-foreground/40 mt-0.5">
                                    Default: {{ $method['default'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Optional Advanced Administrator Configuration -->
            <div class="bg-white rounded-3xl shadow-sm border border-border overflow-hidden">
                <div class="p-6 border-b border-border flex items-center justify-between cursor-pointer select-none hover:bg-muted/20 transition-colors" @click="showAdvanced = !showAdvanced">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-muted flex items-center justify-center text-foreground/60">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-heading font-semibold text-foreground text-sm">
                                Advanced Administrator Thresholds (Optional)
                            </h4>
                            <p class="text-xs text-foreground/50">
                                Standard defaults are already optimal. Customize only if institutional policy requires specific threshold adjustments.
                            </p>
                        </div>
                    </div>
                    <button type="button" class="text-foreground/40 hover:text-foreground">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transition-transform duration-200" :class="showAdvanced ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <div x-show="showAdvanced" x-collapse style="display: none;" class="p-6 bg-muted/10 space-y-6">
                    <form method="POST" action="{{ route('flag-settings.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="space-y-5">
                            @foreach($settings as $setting)
                                @php
                                    $meta = $methodDefinitions[$setting->flag_type] ?? null;
                                @endphp
                                <div class="bg-white p-5 rounded-2xl border border-border shadow-sm">
                                    <label for="setting_{{ $setting->id }}" class="block text-sm font-semibold text-foreground">
                                        {{ $meta['title'] ?? ucwords(str_replace('_', ' ', $setting->flag_type)) }} Threshold
                                    </label>
                                    <p class="text-xs text-foreground/60 mt-0.5 mb-3">
                                        {{ ucwords(str_replace('_', ' ', $setting->setting_key)) }} &bull; Default: {{ $meta['default'] ?? 'N/A' }}
                                    </p>

                                    <div class="flex rounded-xl shadow-sm border border-border focus-within:border-primary focus-within:ring-1 focus-within:ring-primary overflow-hidden max-w-xs">
                                        <input type="number" 
                                               step="{{ $meta['step'] ?? '0.01' }}" 
                                               min="{{ $meta['min'] ?? '0' }}" 
                                               max="{{ $meta['max'] ?? '100' }}"
                                               name="settings[{{ $setting->id }}]" 
                                               id="setting_{{ $setting->id }}" 
                                               value="{{ old('settings.'.$setting->id, $setting->setting_value) }}" 
                                               class="block w-full sm:text-sm border-0 focus:ring-0 px-3 py-2 text-foreground font-semibold">
                                        <span class="inline-flex items-center px-3 bg-muted text-foreground/60 text-xs font-semibold border-l border-border">
                                            {{ $meta['unit'] ?? '' }}
                                        </span>
                                    </div>
                                    <x-input-error :messages="$errors->get('settings.'.$setting->id)" class="mt-2" />
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-border">
                            <button type="submit" form="reset-defaults-form" class="btn-secondary text-xs px-4 py-2 text-destructive border-destructive/20 hover:bg-destructive/10">
                                Reset to Established Defaults
                            </button>

                            <button type="submit" class="btn-primary text-xs px-5 py-2">
                                Save Custom Thresholds
                            </button>
                        </div>
                    </form>

                    <form id="reset-defaults-form" method="POST" action="{{ route('flag-settings.reset') }}" onsubmit="return confirm('Reset all thresholds back to the established system defaults?');" class="hidden">
                        @csrf
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-staff-layout>
