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
                                <template x-for="(item, index) in items" :key="item.id">
                                    <div class="bg-white p-5 rounded-2xl border border-primary/20 mb-4 shadow-sm relative group transition-all hover:shadow-md hover:border-primary/40">
                                        
                                        <!-- Single Row Grid for Item, Question, and Sub-Category -->
                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 md:gap-6 items-start">
                                            
                                            <!-- ITEM # (2 columns) -->
                                            <div class="md:col-span-2">
                                                <div class="flex items-center gap-2 mb-1.5">
                                                    <button type="button" @click="removeItem(index)" class="text-foreground/50 hover:text-error transition-colors cursor-pointer" title="Remove question">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                    <label :for="'items['+index+'][item_number]'" class="block text-xs font-semibold text-foreground/50 tracking-wider uppercase">Item #</label>
                                                </div>
                                                <input type="number" x-model="item.item_number" :name="'items['+index+'][item_number]'" class="block w-full rounded-xl border-primary/20 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 transition-all text-sm py-2.5 px-3" required>
                                            </div>
                                            
                                            <!-- QUESTION / PROMPT (7 columns) -->
                                            <div class="md:col-span-7">
                                                <label :for="'items['+index+'][prompt]'" class="block text-xs font-semibold text-foreground/50 tracking-wider uppercase mb-1.5">Question / Prompt</label>
                                                <input type="text" x-model="item.prompt" :name="'items['+index+'][prompt]'" class="block w-full rounded-xl border-primary/20 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 transition-all text-sm py-2.5 px-3" required>
                                            </div>

                                            <!-- SUB-CATEGORY (3 columns) -->
                                            <div class="md:col-span-3">
                                                <label :for="'items['+index+'][subscale_tag]'" class="block text-xs font-semibold text-foreground/50 tracking-wider uppercase mb-1.5">Sub-Category</label>
                                                <select x-model="item.subscale_tag" :name="'items['+index+'][subscale_tag]'" class="block w-full rounded-xl border-primary/20 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 transition-all text-sm py-2.5 px-3 bg-white">
                                                    <option value="">Select...</option>
                                                    <option value="Emotional">Emotional</option>
                                                    <option value="Psychological">Psychological</option>
                                                    <option value="Social">Social</option>
                                                    <option value="Physical">Physical</option>
                                                    <option value="Academic">Academic</option>
                                                    <option value="Financial">Financial</option>
                                                </select>
                                            </div>

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
    
    <script nonce="{{ $cspNonce }}">
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
