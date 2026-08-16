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
                                    <select id="scale_type" name="scale_type" x-model="scaleType" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                        <option value="" disabled>Select a scale type...</option>
                                        <option value="numeric_scale">Numeric Scale</option>
                                        <option value="multiple_choice_unscored">Multiple Choice (Unscored)</option>
                                    </select>
                                    <span class="text-xs text-foreground/50 mt-1 block">Determines the options shown to students during the inventory</span>
                                    <x-input-error :messages="$errors->get('scale_type')" class="mt-2" />
                                </div>
                                
                                <template x-if="scaleType === 'numeric_scale'">
                                    <div class="col-span-1 md:col-span-2 grid grid-cols-2 gap-6">
                                        <div>
                                            <label for="scale_min" class="block text-sm font-medium text-foreground/80">Scale Minimum</label>
                                            <input type="number" id="scale_min" name="scale_min" value="{{ old('scale_min') }}" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                            <x-input-error :messages="$errors->get('scale_min')" class="mt-2" />
                                        </div>
                                        <div>
                                            <label for="scale_max" class="block text-sm font-medium text-foreground/80">Scale Maximum</label>
                                            <input type="number" id="scale_max" name="scale_max" value="{{ old('scale_max') }}" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                            <x-input-error :messages="$errors->get('scale_max')" class="mt-2" />
                                        </div>
                                    </div>
                                </template>
                                
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

                        <!-- Tabs Navigation -->
                        <div class="flex space-x-4 mb-6 border-b border-border overflow-x-auto pb-1">
                            <button type="button" @click="activeTab = 'items'" :class="activeTab === 'items' ? 'border-primary text-primary' : 'border-transparent text-foreground/60 hover:text-foreground hover:border-border'" class="py-2 px-4 border-b-2 font-medium text-sm transition-colors whitespace-nowrap">
                                Questions
                            </button>
                            <button type="button" @click="activeTab = 'subcats'" :class="activeTab === 'subcats' ? 'border-primary text-primary' : 'border-transparent text-foreground/60 hover:text-foreground hover:border-border'" class="py-2 px-4 border-b-2 font-medium text-sm transition-colors whitespace-nowrap">
                                Sub-Categories
                            </button>
                        </div>

                        <!-- Questions / Items -->
                        <div x-show="activeTab === 'items'">
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
                                    <div class="flex-1 text-sm font-medium text-foreground/80">Question / Prompt</div>
                                    <div class="w-48 shrink-0 text-sm font-medium text-foreground/80">Options <span class="text-xs font-normal text-foreground/50">(optional)</span></div>
                                    <div class="w-48 shrink-0 text-sm font-medium text-foreground/80">Sub-Category</div>
                                    <div class="w-9 shrink-0"></div>
                                </div>

                                <template x-for="(item, index) in items" :key="item.uid">
                                    <div class="flex flex-col md:flex-row gap-3 items-start bg-muted/10 p-3 rounded-xl border border-border/50 hover:border-primary/30 transition-colors relative group">
                                        
                                        <div class="w-full md:w-20 shrink-0">
                                            <label :for="'items['+index+'][item_number]'" class="md:hidden block text-sm font-medium text-foreground/80 mb-1">Item #</label>
                                            <input type="number" x-model="item.item_number" :name="'items['+index+'][item_number]'" placeholder="#" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" required>
                                        </div>
                                        
                                        <div class="w-full md:flex-1">
                                            <label :for="'items['+index+'][prompt]'" class="md:hidden block text-sm font-medium text-foreground/80 mb-1">Question / Prompt</label>
                                            <input type="text" x-model="item.prompt" :name="'items['+index+'][prompt]'" placeholder="e.g. I found it hard to wind down" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" required>
                                        </div>

                                        <div class="w-full md:w-48 shrink-0">
                                            <label :for="'items['+index+'][options]'" class="md:hidden block text-sm font-medium text-foreground/80 mb-1">Options</label>
                                            <input type="text" x-model="item.options" :name="'items['+index+'][options]'" placeholder="e.g. Never, Sometimes..." class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                                        </div>
                                        
                                        <div class="w-full md:w-48 shrink-0">
                                            <label :for="'items['+index+'][question_subcategory_id]'" class="md:hidden block text-sm font-medium text-foreground/80 mb-1">Sub-Category</label>
                                            
                                            <!-- When subcategories exist -->
                                            <select x-show="subcategories.length > 0" x-model="item.question_subcategory_id" :name="'items['+index+'][question_subcategory_id]'" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                                                <option value="">(None)</option>
                                                <template x-for="sub in subcategories" :key="sub.temp_id">
                                                    <option :value="sub.temp_id" x-text="sub.name" :selected="item.question_subcategory_id == sub.temp_id"></option>
                                                </template>
                                            </select>

                                            <!-- When no subcategories exist -->
                                            <div x-show="subcategories.length === 0" class="flex items-center h-11 px-3 bg-muted/20 border border-dashed border-border rounded-xl text-sm text-foreground/50">
                                                Define in tab first
                                            </div>
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

                        <!-- Sub-Categories Tab -->
                        <div x-show="activeTab === 'subcats'" style="display: none;">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-heading font-medium text-foreground">Sub-Categories</h3>
                                <button type="button" @click="addSubcategory()" class="btn-primary text-sm px-4 py-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 inline-block mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                    Add Sub-Category
                                </button>
                            </div>
                            <div class="mb-4 text-sm text-text-muted">
                                <p>Use sub-categories to group related questions together (e.g. "Depression", "Anxiety") and generate multiple scores within this category. If you don't define any sub-categories, all items contribute to a single total score.</p>
                            </div>

                            <template x-if="subcategories.length === 0">
                                <div class="p-8 text-center bg-muted/20 border border-dashed border-border rounded-xl">
                                    <p class="text-foreground/70 mb-2">This category has no sub-categories — all items contribute to a single total score.</p>
                                    <button type="button" @click="addSubcategory()" class="text-primary hover:text-primary-hover font-medium text-sm transition-colors">
                                        Add your first Sub-Category
                                    </button>
                                </div>
                            </template>
                            
                            <template x-if="subcategories.length > 0">
                                <div class="space-y-4">
                                    <template x-for="(sub, index) in subcategories" :key="index">
                                        <div class="flex items-start gap-4 p-4 bg-background border border-border rounded-xl shadow-sm relative group">
                                            <input type="hidden" :name="'subcategories['+index+'][temp_id]'" :value="sub.temp_id">
                                            <div class="flex-grow">
                                                <label class="block text-sm font-medium text-foreground/80 mb-1">Name</label>
                                                <input type="text" x-model="sub.name" :name="'subcategories['+index+'][name]'" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" required>
                                            </div>
                                            <div class="w-24">
                                                <label class="block text-sm font-medium text-foreground/80 mb-1">Order</label>
                                                <input type="number" x-model="sub.display_order" :name="'subcategories['+index+'][display_order]'" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" required>
                                            </div>
                                            <button type="button" @click="removeSubcategory(index)" class="mt-7 text-red-500 hover:text-red-700 transition-colors p-2" title="Remove">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </template>
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
                oldItems = [{
                    uid: 'existing_' + Date.now(),
                    item_number: 1,
                    prompt: '',
                    options: '',
                    question_subcategory_id: ''
                }];
            } else {
                // Ensure UIDs exist for alpine key binding
                oldItems = oldItems.map((item, index) => ({
                    ...item,
                    uid: 'existing_' + (item.id || Date.now() + index)
                }));
            }

            let oldSubcategories = @json(old('subcategories', []));
            oldSubcategories = oldSubcategories.map((sub, index) => ({
                ...sub,
                temp_id: sub.temp_id || 'temp_' + Date.now() + index
            }));

            return {
                activeTab: '{{ session('activeTab', 'items') }}',
                scaleType: '{{ old("scale_type", "") }}',
                items: oldItems,
                subcategories: oldSubcategories,
                addItem() {
                    const nextNum = this.items.length > 0 
                        ? Math.max(...this.items.map(i => parseInt(i.item_number) || 0)) + 1 
                        : 1;
                        
                    this.items.push({
                        uid: 'new_' + Date.now(),
                        item_number: nextNum,
                        prompt: '',
                        options: '',
                        question_subcategory_id: ''
                    });
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                },
                addSubcategory() {
                    const nextOrder = this.subcategories.length > 0 
                        ? Math.max(...this.subcategories.map(s => parseInt(s.display_order) || 0)) + 1 
                        : 1;
                        
                    this.subcategories.push({
                        temp_id: 'temp_' + Date.now(),
                        name: '',
                        display_order: nextOrder
                    });
                },
                removeSubcategory(index) {
                    this.subcategories.splice(index, 1);
                }
            }
        }
    </script>
</x-staff-layout>
