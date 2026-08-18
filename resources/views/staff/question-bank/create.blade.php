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

                                <template x-if="scaleType === 'multiple_choice_unscored'">
                                    <div>
                                        <label for="default_options" class="block text-sm font-medium text-foreground/80">Default Options <span class="text-xs font-normal text-foreground/50">(one per line, optional if every item has custom options)</span></label>
                                        <textarea id="default_options" name="default_options" rows="3" placeholder="e.g.&#10;Never&#10;Sometimes&#10;Always" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors">{{ old('default_options') }}</textarea>
                                        <x-input-error :messages="$errors->get('default_options')" class="mt-2" />
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
                                <textarea id="instructions" name="instructions" rows="8" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors text-base">{{ old('instructions') }}</textarea>
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
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                                <div>
                                    <h3 class="text-lg font-heading font-medium text-foreground">Question Items</h3>
                                    <template x-if="scaleType === 'multiple_choice_unscored'">
                                        <p class="text-xs font-medium text-foreground/60 mt-1">
                                            <span x-text="items.filter(i => i.useCustomOptions).length"></span> of <span x-text="items.length"></span> items use custom options
                                        </p>
                                    </template>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="showBulkAddModal = true" class="btn-outline text-sm px-3 py-1.5 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                        Bulk Add
                                    </button>
                                    <button type="button" @click="addItem()" class="btn-secondary text-sm px-3 py-1.5 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        Add Question
                                    </button>
                                </div>
                            </div>
                            
                            <div x-show="bulkAddSuccessMessage" x-transition x-cloak class="mb-4 p-3 bg-primary/10 text-primary-dark rounded-xl text-sm font-medium border border-primary/20 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span x-text="bulkAddSuccessMessage"></span>
                            </div>
                            
                            <x-input-error :messages="$errors->get('items')" class="mb-4" />

                            <div class="space-y-4">
                                <template x-for="(item, index) in items" :key="item.uid">
                                    <div class="bg-card/40 p-5 rounded-2xl border border-border shadow-sm hover:border-primary/40 hover:shadow-md transition-all relative group">
                                        
                                        <!-- Delete Button -->
                                        <button type="button" @click="removeItem(index)" class="absolute top-4 right-4 text-foreground/30 hover:text-error hover:bg-error/10 p-1.5 rounded-lg transition-colors opacity-0 group-hover:opacity-100" title="Remove question">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-5 pr-10">
                                            <!-- Item # -->
                                            <div class="md:col-span-3 lg:col-span-2">
                                                <label :for="'items['+index+'][item_number]'" class="block text-xs font-semibold text-foreground/50 uppercase tracking-wider mb-2">Item #</label>
                                                <input type="number" x-model="item.item_number" :name="'items['+index+'][item_number]'" placeholder="#" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm font-medium" required>
                                            </div>
                                            
                                            <!-- Prompt -->
                                            <div class="md:col-span-9 lg:col-span-10">
                                                <label :for="'items['+index+'][prompt]'" class="block text-xs font-semibold text-foreground/50 uppercase tracking-wider mb-2">Question / Prompt</label>
                                                <input type="text" x-model="item.prompt" :name="'items['+index+'][prompt]'" placeholder="e.g. I found it hard to wind down" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm font-medium" required>
                                            </div>

                                            <!-- Bottom Section -->
                                            <div class="md:col-span-12 grid grid-cols-1 md:grid-cols-2 gap-5 pt-1" x-show="scaleType === 'multiple_choice_unscored' || subcategories.length > 0">
                                                
                                                <!-- Sub-Category -->
                                                <div x-show="subcategories.length > 0">
                                                    <label :for="'items['+index+'][question_subcategory_id]'" class="block text-xs font-semibold text-foreground/50 uppercase tracking-wider mb-2">Sub-Category</label>
                                                    <select x-show="subcategories.length > 0" x-model="item.question_subcategory_id" :name="'items['+index+'][question_subcategory_id]'" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm font-medium">
                                                        <option value="">(None)</option>
                                                        <template x-for="sub in subcategories" :key="sub.temp_id">
                                                            <option :value="sub.temp_id" x-text="sub.name" :selected="item.question_subcategory_id == sub.temp_id"></option>
                                                        </template>
                                                    </select>
                                                </div>

                                                <!-- Custom Options -->
                                                <div x-show="scaleType === 'multiple_choice_unscored'">
                                                    <label class="flex items-center gap-2 cursor-pointer mb-2 h-[18px]">
                                                        <input type="checkbox" x-model="item.useCustomOptions" class="rounded border-border text-primary focus:ring-primary shadow-sm w-4 h-4">
                                                        <span class="text-xs font-semibold text-foreground/50 uppercase tracking-wider">Custom options</span>
                                                    </label>
                                                    <div x-show="item.useCustomOptions" x-collapse>
                                                        <textarea x-model="item.options" :name="'items['+index+'][options]'" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm mt-1"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                
                                <div x-show="items.length === 0" class="text-center py-12 bg-muted/10 border border-dashed border-border rounded-3xl text-foreground/60">
                                    <svg class="mx-auto h-12 w-12 text-foreground/20 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" /></svg>
                                    <p class="text-sm font-medium">No questions added yet. Click "Add Question" to start.</p>
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
                
                <!-- Bulk Add Modal -->
                <div x-show="showBulkAddModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                        <div x-show="showBulkAddModal" @click="showBulkAddModal = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity bg-background/80 backdrop-blur-sm" aria-hidden="true"></div>

                        <div x-show="showBulkAddModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-card rounded-2xl shadow-xl border border-border">
                            <div class="flex justify-between items-center mb-5">
                                <h3 class="text-lg font-heading font-medium text-foreground">Bulk Add Questions</h3>
                                <button type="button" @click="showBulkAddModal = false" class="text-foreground/50 hover:text-foreground transition-colors p-1">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                            
                            <div class="mt-2">
                                <p class="text-sm text-foreground/70 mb-4">Paste or type your questions below, <strong>one per line</strong>. Empty lines will be ignored.</p>
                                <textarea x-model="bulkAddText" rows="10" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm" placeholder="1. I found it hard to wind down&#10;2. I was aware of dryness of my mouth&#10;3. I couldn't seem to experience any positive feeling at all"></textarea>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" @click="showBulkAddModal = false" class="btn-outline px-4 py-2 text-sm">Cancel</button>
                                <button type="button" @click="processBulkAdd()" class="btn-primary px-4 py-2 text-sm">Add Questions</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script nonce="{{ $cspNonce }}">
        function questionCategoryForm() {
            let oldItems = @json(old('items', []));
            oldItems = oldItems.map(item => ({
                ...item,
                uid: item.uid || 'existing_' + Date.now() + Math.random(),
                useCustomOptions: (item.options && item.options.trim() !== '') ? true : false
            }));

            if (oldItems.length === 0) {
                oldItems = [{
                    uid: 'existing_' + Date.now(),
                    item_number: 1,
                    prompt: '',
                    options: '',
                    useCustomOptions: false,
                    question_subcategory_id: ''
                }];
            }

            let oldSubcategories = @json(old('subcategories', []));
            oldSubcategories = oldSubcategories.map((sub, index) => ({
                ...sub,
                temp_id: sub.temp_id || 'temp_' + Date.now() + index
            }));

            return {
                activeTab: '{{ session('activeTab', 'items') }}',
                scaleType: '{{ old("scale_type", 'numeric_scale') }}',
                items: oldItems,
                subcategories: oldSubcategories,
                showBulkAddModal: false,
                bulkAddText: '',
                bulkAddSuccessMessage: '',
                processBulkAdd() {
                    const lines = this.bulkAddText.split('\n').map(l => l.trim()).filter(l => l.length > 0);
                    if (lines.length === 0) {
                        this.showBulkAddModal = false;
                        return;
                    }
                    
                    let maxNum = 0;
                    if (this.items.length > 0) {
                        maxNum = Math.max(...this.items.map(i => parseInt(i.item_number) || 0));
                    }
                    
                    lines.forEach(line => {
                        maxNum++;
                        this.items.push({
                            uid: 'new_' + Date.now() + Math.random(),
                            item_number: maxNum,
                            prompt: line,
                            options: '',
                            useCustomOptions: false,
                            question_subcategory_id: ''
                        });
                    });
                    
                    this.bulkAddText = '';
                    this.showBulkAddModal = false;
                    
                    this.bulkAddSuccessMessage = `${lines.length} questions added successfully.`;
                    setTimeout(() => {
                        this.bulkAddSuccessMessage = '';
                    }, 4000);
                },
                addItem() {
                    const nextNum = this.items.length > 0 
                        ? Math.max(...this.items.map(i => parseInt(i.item_number) || 0)) + 1 
                        : 1;
                        
                    this.items.push({
                        uid: 'new_' + Date.now(),
                        item_number: nextNum,
                        prompt: '',
                        options: '',
                        useCustomOptions: false,
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
