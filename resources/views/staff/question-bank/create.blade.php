<x-staff-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                {{ __('Add Question Category') }}
            </h2>
            <a href="{{ route('question-bank.index') }}" class="btn-secondary text-sm px-4 py-2">&larr; Back to Question Bank</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border"
                 x-data="questionCategoryForm()">
                <div class="p-lg bg-white">
                    <form method="POST" action="{{ route('question-bank.store') }}">
                        @csrf
                        
                        <!-- Category Settings -->
                        <div class="mb-8 pb-8 border-b border-border">
                            <h3 class="text-lg font-heading font-medium text-foreground mb-4">Category Details</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-foreground/80">Category Name</label>
                                    <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Depression, Anxiety, and Stress" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>
                                
                                <div>
                                    <label for="year_level" class="block text-sm font-medium text-foreground/80">Year Level</label>
                                    <select id="year_level" name="year_level" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                        @foreach(['1st', '2nd', '3rd', '4th'] as $level)
                                            <option value="{{ $level }}" {{ old('year_level', $defaultYearLevel) == $level ? 'selected' : '' }}>{{ $level }} Year</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('year_level')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label for="scale_type" class="block text-sm font-medium text-foreground/80">Scale Type</label>
                                    <select id="scale_type" name="scale_type" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                        <option value="" disabled {{ old('scale_type') ? '' : 'selected' }}>Select a scale type...</option>
                                        <option value="likert_1_7" {{ old('scale_type') === 'likert_1_7' ? 'selected' : '' }}>Likert Scale (1-7)</option>
                                        <option value="likert_0_3" {{ old('scale_type') === 'likert_0_3' ? 'selected' : '' }}>Likert Scale (0-3)</option>
                                        <option value="likert_1_5" {{ old('scale_type') === 'likert_1_5' ? 'selected' : '' }}>Likert Scale (1-5)</option>
                                        <option value="single_choice_no_score" {{ old('scale_type') === 'single_choice_no_score' ? 'selected' : '' }}>Multiple Choice (Unscored)</option>
                                    </select>
                                    <span class="text-xs text-foreground/50 mt-1 block">Determines the options shown to students during the inventory</span>
                                    <x-input-error :messages="$errors->get('scale_type')" class="mt-2" />
                                </div>
                                
                                <div>
                                    <label for="display_order" class="block text-sm font-medium text-foreground/80">Display Order</label>
                                    <input type="number" id="display_order" name="display_order" value="{{ old('display_order', 1) }}" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                    <x-input-error :messages="$errors->get('display_order')" class="mt-2" />
                                </div>
                            </div>

                            <div>
                                <label for="instructions" class="block text-sm font-medium text-foreground/80">Instructions for Students</label>
                                <textarea id="instructions" name="instructions" rows="3" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors">{{ old('instructions') }}</textarea>
                                <x-input-error :messages="$errors->get('instructions')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Questions / Items -->
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-heading font-medium text-foreground">Question Items</h3>
                                <button type="button" @click="addItem()" class="btn-secondary text-sm px-3 py-1.5 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Add Question
                                </button>
                            </div>
                            
                            <x-input-error :messages="$errors->get('items')" class="mb-4" />

                            <div class="space-y-4">
                                <div class="hidden md:flex gap-3 mb-2 px-3">
                                    <div class="w-20 shrink-0 text-sm font-medium text-foreground/80">Item #</div>
                                    <div class="w-[35%] text-sm font-medium text-foreground/80">Question / Prompt</div>
                                    <div class="w-[25%] text-sm font-medium text-foreground/80">Options <span class="text-xs font-normal text-foreground/50">(leave blank for default)</span></div>
                                    <div class="flex-1 text-sm font-medium text-foreground/80">Subscale Tag</div>
                                    <div class="w-9 shrink-0"></div>
                                </div>

                                <template x-for="(item, index) in items" :key="item.id">
                                    <div class="flex flex-col md:flex-row gap-3 items-start bg-muted/10 p-3 rounded-xl border border-border/50 hover:border-primary/30 transition-colors relative group">
                                        
                                        <div class="w-full md:w-20 shrink-0">
                                            <label :for="'items['+index+'][item_number]'" class="md:hidden block text-sm font-medium text-foreground/80 mb-1">Item #</label>
                                            <input type="number" x-model="item.item_number" :name="'items['+index+'][item_number]'" placeholder="#" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" required>
                                        </div>
                                        
                                        <div class="w-full md:w-[35%]">
                                            <label :for="'items['+index+'][prompt]'" class="md:hidden block text-sm font-medium text-foreground/80 mb-1">Question / Prompt</label>
                                            <input type="text" x-model="item.prompt" :name="'items['+index+'][prompt]'" placeholder="e.g. I found it hard to wind down" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" required>
                                        </div>

                                        <div class="w-full md:w-[25%]">
                                            <label :for="'items['+index+'][options]'" class="md:hidden block text-sm font-medium text-foreground/80 mb-1">Options (Comma separated)</label>
                                            <input type="text" x-model="item.options" :name="'items['+index+'][options]'" placeholder="e.g. Never, Sometimes..." class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                                        </div>
                                        
                                        <div class="w-full md:flex-1">
                                            <label :for="'items['+index+'][subscale_tag]'" class="md:hidden block text-sm font-medium text-foreground/80 mb-1">Subscale Tag</label>
                                            <input type="text" x-model="item.subscale_tag" :name="'items['+index+'][subscale_tag]'" placeholder="e.g. stress, anxiety" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                                        </div>

                                        <div class="shrink-0 flex items-center justify-end w-full md:w-9 mt-2 md:mt-0 md:pt-1">
                                            <button type="button" @click="removeItem(index)" class="text-error/70 hover:text-error hover:bg-error/10 p-2 rounded-xl transition-colors md:opacity-50 group-hover:opacity-100 flex items-center gap-1 w-full md:w-auto justify-center" title="Remove question">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                <span class="md:hidden text-sm">Remove</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                
                                <div x-show="items.length === 0" class="text-center py-8 bg-muted/20 border border-dashed border-border rounded-2xl text-foreground/60">
                                    No questions added yet. Click "Add Question" to start.
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8 pt-6 border-t border-border">
                            <button type="submit" class="btn-primary px-8">
                                {{ __('Create Category & Questions') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function questionCategoryForm() {
            let oldItems = @json(old('items', []));
            if (oldItems.length === 0) {
                // Initialize with one empty item by default
                oldItems = [{
                    id: Date.now(),
                    item_number: 1,
                    prompt: '',
                    options: '',
                    subscale_tag: ''
                }];
            } else {
                // Ensure IDs exist for alpine key binding
                oldItems = oldItems.map((item, index) => ({
                    ...item,
                    id: item.id || Date.now() + index
                }));
            }

            return {
                items: oldItems,
                addItem() {
                    const nextNum = this.items.length > 0 
                        ? Math.max(...this.items.map(i => parseInt(i.item_number) || 0)) + 1 
                        : 1;
                        
                    this.items.push({
                        id: Date.now(),
                        item_number: nextNum,
                        prompt: '',
                        options: '',
                        subscale_tag: ''
                    });
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                }
            }
        }
    </script>
</x-staff-layout>
