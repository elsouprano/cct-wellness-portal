@section('title', 'Flag Settings')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                    {{ __('Flag Settings') }}
                </h2>
                <p class="text-xs text-foreground/60 mt-0.5">
                    Configure response quality detection baselines and review parameters.
                </p>
            </div>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200 shadow-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Automatic Engine Active
            </div>
        </div>
    </x-slot>

    <div class="py-3 sm:py-5" x-data="{ showDrawer: false }" @keydown.escape.window="showDrawer = false">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">

            @if(session('success'))
                <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-medium flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            <!-- Compact One-Line Informational Banner -->
            <div class="bg-white rounded-2xl border border-border p-3.5 sm:px-5 flex items-center justify-between gap-4 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-xs text-foreground/75 leading-relaxed">
                        Submissions are automatically evaluated using deterministic rules upon completion. The engine operates using established psychological assessment baselines.
                    </p>
                </div>
                <button type="button" 
                        @click="showDrawer = true" 
                        class="btn-primary text-xs py-2 px-3.5 shrink-0 inline-flex items-center gap-1.5 shadow-xs">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Customize Thresholds
                </button>
            </div>

            <!-- Active Detection Methods (3 Cards in One Row on xl) -->
            <div>
                <div class="flex items-center justify-between mb-3 px-1">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-foreground/60">Active Detection Methods</h3>
                    <span class="text-xs text-foreground/50">Deterministic psychological evaluation rules</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-stretch">
                    @foreach($methodDefinitions as $typeKey => $method)
                        @php
                            $settingModel = $settings->firstWhere('flag_type', $typeKey);
                            $currentValue = $settingModel?->setting_value;
                        @endphp
                        <div class="bg-white rounded-2xl border border-border shadow-xs p-6 flex flex-col justify-between hover:border-primary/30 transition-colors h-full">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="font-heading font-bold text-foreground text-lg">
                                        {{ $method['title'] }}
                                    </h4>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-wide shrink-0">
                                        Enabled
                                    </span>
                                </div>
                                <p class="text-[15px] text-foreground/75 leading-relaxed min-h-[4.5rem]">
                                    {{ $method['description'] }}
                                </p>
                            </div>

                            <div class="mt-6 pt-4 border-t border-border/60 flex items-center justify-between bg-muted/20 -mx-6 -mb-6 px-6 py-4 rounded-b-2xl">
                                <div>
                                    <span class="block text-xs font-semibold text-foreground/50 uppercase tracking-wider">Baseline Value</span>
                                    <span class="block text-2xl font-semibold text-primary font-mono whitespace-nowrap">
                                        {{ $currentValue }} <span class="text-base font-medium font-sans text-primary/80">{{ $method['unit'] }}</span>
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span class="block text-xs font-semibold text-foreground/50 uppercase tracking-wider">Reference</span>
                                    <span class="block text-sm font-medium text-foreground/70 whitespace-nowrap">
                                        Default: {{ $method['default'] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- RIGHT-SIDE SLIDE-OVER DRAWER FOR ADVANCED THRESHOLDS -->
        <div x-show="showDrawer" 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-hidden" 
             aria-labelledby="slide-over-title" 
             role="dialog" 
             aria-modal="true">
            <div class="absolute inset-0 overflow-hidden">
                <!-- Backdrop with blur -->
                <div x-show="showDrawer" 
                     x-transition:enter="ease-in-out duration-300" 
                     x-transition:enter-start="opacity-0" 
                     x-transition:enter-end="opacity-100" 
                     x-transition:leave="ease-in-out duration-300" 
                     x-transition:leave-start="opacity-100" 
                     x-transition:leave-end="opacity-0" 
                     class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" 
                     @click="showDrawer = false"></div>

                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div x-show="showDrawer" 
                         x-transition:enter="transform transition ease-in-out duration-300 sm:duration-400" 
                         x-transition:enter-start="translate-x-full" 
                         x-transition:enter-end="translate-x-0" 
                         x-transition:leave="transform transition ease-in-out duration-300 sm:duration-400" 
                         x-transition:leave-start="translate-x-0" 
                         x-transition:leave-end="translate-x-full" 
                         class="pointer-events-auto w-screen max-w-md">
                        
                        <div class="flex h-full flex-col overflow-y-auto bg-white shadow-2xl border-l border-border">
                            <!-- Drawer Header -->
                            <div class="p-6 border-b border-border bg-muted/20 flex items-center justify-between">
                                <div>
                                    <h3 id="slide-over-title" class="font-heading font-bold text-base text-foreground">
                                        Customize Thresholds
                                    </h3>
                                    <p class="text-xs text-foreground/60 mt-0.5">
                                        Standard defaults are already optimal. Customize only if institutional policy requires adjustments.
                                    </p>
                                </div>
                                <button type="button" 
                                        @click="showDrawer = false" 
                                        class="rounded-xl p-1.5 text-foreground/40 hover:text-foreground hover:bg-muted transition-colors focus:outline-none">
                                    <span class="sr-only">Close panel</span>
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Drawer Body: Form Fields -->
                            <div class="relative flex-1 p-6 space-y-5">
                                <form id="flag-settings-form" method="POST" action="{{ route('flag-settings.update') }}" data-confirm="edit" data-confirm-title="Confirm Threshold Changes" class="space-y-4">
                                    @csrf
                                    @method('PUT')

                                    <p class="text-xs text-foreground/50 mb-3">
                                        <span class="text-destructive font-bold" aria-hidden="true">*</span> Required field
                                    </p>

                                    @foreach($settings as $setting)
                                        @php
                                            $meta = $methodDefinitions[$setting->flag_type] ?? null;
                                        @endphp
                                        <div class="bg-muted/20 p-4 rounded-2xl border border-border">
                                            <label for="setting_{{ $setting->id }}" class="block text-xs font-bold text-foreground label-required">
                                                {{ $meta['title'] ?? ucwords(str_replace('_', ' ', $setting->flag_type)) }}
                                            </label>
                                            <p class="text-[11px] text-foreground/60 mt-0.5 mb-2.5">
                                                {{ ucwords(str_replace('_', ' ', $setting->setting_key)) }} &bull; Default: {{ $meta['default'] ?? 'N/A' }}
                                            </p>

                                            <div class="flex rounded-xl shadow-xs border border-border bg-white focus-within:border-primary focus-within:ring-1 focus-within:ring-primary overflow-hidden">
                                                <input type="number" 
                                                       step="{{ $meta['step'] ?? '0.01' }}" 
                                                       min="{{ $meta['min'] ?? '0' }}" 
                                                       max="{{ $meta['max'] ?? '100' }}"
                                                       name="settings[{{ $setting->id }}]" 
                                                       id="setting_{{ $setting->id }}" 
                                                       value="{{ old('settings.'.$setting->id, $setting->setting_value) }}" 
                                                       class="block w-full text-xs border-0 focus:ring-0 px-3 py-2 text-foreground font-semibold">
                                                <span class="inline-flex items-center px-3 bg-muted text-foreground/60 text-xs font-semibold border-l border-border">
                                                    {{ $meta['unit'] ?? '' }}
                                                </span>
                                            </div>
                                            <x-input-error :messages="$errors->get('settings.'.$setting->id)" class="mt-1 text-xs" />
                                        </div>
                                    @endforeach
                                </form>

                                <form id="reset-defaults-form" method="POST" action="{{ route('flag-settings.reset') }}" data-confirm="delete" data-confirm-title="Reset Flag Thresholds" data-confirm-message="Reset all thresholds back to the established system defaults? This cannot be undone." class="hidden">
                                    @csrf
                                </form>
                            </div>

                            <!-- Drawer Footer: Action Buttons -->
                            <div class="p-5 border-t border-border bg-muted/20 flex items-center justify-between gap-3">
                                <button type="submit" 
                                        form="reset-defaults-form" 
                                        class="px-3.5 py-2 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl transition-colors">
                                    Reset to Defaults
                                </button>

                                <div class="flex items-center gap-2">
                                    <button type="button" 
                                            @click="showDrawer = false" 
                                            class="px-3.5 py-2 text-xs font-semibold text-foreground/70 bg-white hover:bg-muted border border-border rounded-xl transition-colors">
                                        Cancel
                                    </button>
                                    <button type="submit" 
                                            form="flag-settings-form" 
                                            class="btn-primary text-xs py-2 px-4 shadow-xs font-bold">
                                        Save Thresholds
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</x-staff-layout>
