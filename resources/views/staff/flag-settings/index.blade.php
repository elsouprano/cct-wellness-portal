<x-staff-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
            {{ __('Flag Engine Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            


            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border">
                <div class="p-lg bg-white">
                    <p class="text-sm text-foreground/80 mb-8">
                        Adjust the sensitivity thresholds for the automated flagging engine. These changes take effect immediately for all future submissions.
                    </p>

                    <form method="POST" action="{{ route('flag-settings.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="space-y-8">
                            @foreach($settings->groupBy('flag_type') as $type => $groupSettings)
                                <div class="bg-muted/30 p-6 rounded-2xl border border-border">
                                    <h3 class="text-lg font-heading font-bold text-foreground uppercase mb-6 border-b border-border pb-2">
                                        {{ str_replace('_', ' ', $type) }} Flags
                                    </h3>
                                    
                                    <div class="space-y-5">
                                        @foreach($groupSettings as $setting)
                                            <div>
                                                <label for="setting_{{ $setting->id }}" class="block text-sm font-medium text-foreground/80">
                                                    {{ ucwords(str_replace('_', ' ', $setting->setting_key)) }}
                                                </label>
                                                <div class="mt-2 flex rounded-xl shadow-sm overflow-hidden border border-border focus-within:border-primary focus-within:ring focus-within:ring-primary focus-within:ring-opacity-50 transition-all">
                                                    <input type="number" step="0.01" name="settings[{{ $setting->id }}]" id="setting_{{ $setting->id }}" value="{{ $setting->setting_value }}" class="flex-1 block w-full sm:text-sm border-0 focus:ring-0 bg-transparent">
                                                    @if(str_contains($setting->setting_key, 'percentage'))
                                                        <span class="inline-flex items-center px-4 bg-muted text-foreground/60 sm:text-sm border-l border-border font-semibold">
                                                            %
                                                        </span>
                                                    @endif
                                                </div>
                                                <x-input-error :messages="$errors->get('settings.'.$setting->id)" class="mt-2" />
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="btn-primary">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-staff-layout>
