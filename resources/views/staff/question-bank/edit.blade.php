<x-staff-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                {{ __('Edit Question Category: ') }} {{ $category->name }}
            </h2>
            <a href="{{ route('question-bank.index') }}" class="btn-secondary text-sm px-4 py-2">&larr; Back to Question Bank</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border"
                 x-data="questionCategoryForm()">
                <div class="p-lg bg-white">
                    <form method="POST" action="{{ route('question-bank.update', $category) }}">
                        @csrf
                        @method('PUT')
                        
                        <!-- Category Settings -->
                        <div class="mb-8 pb-8 border-b border-border">
                            <h3 class="text-lg font-heading font-medium text-foreground mb-4">Category Details</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-foreground/80">Category Name</label>
                                    <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}" placeholder="e.g. Depression, Anxiety, and Stress" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>
                                
                                <div>
                                    <label for="year_level" class="block text-sm font-medium text-foreground/80">Year Level</label>
                                    <select id="year_level" name="year_level" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                        @foreach(['1st', '2nd', '3rd', '4th'] as $level)
                                            <option value="{{ $level }}" {{ old('year_level', $category->year_level) == $level ? 'selected' : '' }}>{{ $level }} Year</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('year_level')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                @php
                                    $currentScale = old('scale_type', $category->scale_type);
                                @endphp
                                <div>
                                    <label for="scale_type" class="block text-sm font-medium text-foreground/80">Scale Type</label>
                                    <select id="scale_type" name="scale_type" x-model="scaleType" :disabled="isLocked" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors disabled:bg-gray-100 disabled:cursor-not-allowed" required>
                                        <option value="numeric_scale" {{ $currentScale === 'numeric_scale' ? 'selected' : '' }}>Numeric Scale</option>
                                        <option value="multiple_choice_unscored" {{ $currentScale === 'multiple_choice_unscored' ? 'selected' : '' }}>Multiple Choice (Unscored)</option>
                                    </select>
                                    <span class="text-xs text-foreground/50 mt-1 block">Determines the options shown to students during the inventory</span>
                                    <x-input-error :messages="$errors->get('scale_type')" class="mt-2" />
                                </div>
                                
                                <template x-if="scaleType === 'numeric_scale'">
                                    <div class="col-span-1 md:col-span-2 grid grid-cols-2 gap-6">
                                        <div>
                                            <label for="scale_min" class="block text-sm font-medium text-foreground/80">Scale Minimum</label>
                                            <input type="number" id="scale_min" name="scale_min" value="{{ old('scale_min', $category->scale_min) }}" :disabled="isLocked" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors disabled:bg-gray-100 disabled:cursor-not-allowed" required>
                                            <x-input-error :messages="$errors->get('scale_min')" class="mt-2" />
                                        </div>
                                        <div>
                                            <label for="scale_max" class="block text-sm font-medium text-foreground/80">Scale Maximum</label>
                                            <input type="number" id="scale_max" name="scale_max" value="{{ old('scale_max', $category->scale_max) }}" :disabled="isLocked" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors disabled:bg-gray-100 disabled:cursor-not-allowed" required>
                                            <x-input-error :messages="$errors->get('scale_max')" class="mt-2" />
                                        </div>
                                    </div>
                                </template>
                                
                                <div>
                                    <label for="display_order" class="block text-sm font-medium text-foreground/80">Display Order</label>
                                    <input type="number" id="display_order" name="display_order" value="{{ old('display_order', $category->display_order) }}" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors" required>
                                    <x-input-error :messages="$errors->get('display_order')" class="mt-2" />
                                </div>
                            </div>

                            <div>
                                <label for="instructions" class="block text-sm font-medium text-foreground/80">Instructions for Students</label>
                                <textarea id="instructions" name="instructions" rows="3" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 transition-colors">{{ old('instructions', $category->instructions) }}</textarea>
                                <x-input-error :messages="$errors->get('instructions')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex space-x-4 mb-6 border-b border-border overflow-x-auto pb-1">
                            <button type="button" @click="activeTab = 'items'" :class="activeTab === 'items' ? 'border-primary text-primary' : 'border-transparent text-foreground/60 hover:text-foreground hover:border-border'" class="py-2 px-4 border-b-2 font-medium text-sm transition-colors whitespace-nowrap">
                                Questions
                            </button>
                            <button type="button" @click="activeTab = 'subcats'" :class="activeTab === 'subcats' ? 'border-primary text-primary' : 'border-transparent text-foreground/60 hover:text-foreground hover:border-border'" class="py-2 px-4 border-b-2 font-medium text-sm transition-colors whitespace-nowrap">
                                Sub-Categories
                            </button>
                            <button type="button" @click="activeTab = 'pairs'" :class="activeTab === 'pairs' ? 'border-primary text-primary' : 'border-transparent text-foreground/60 hover:text-foreground hover:border-border'" class="py-2 px-4 border-b-2 font-medium text-sm transition-colors">
                                Correlated Pairs
                            </button>
                            <button type="button" @click="activeTab = 'ranges'" :class="activeTab === 'ranges' ? 'border-primary text-primary' : 'border-transparent text-foreground/60 hover:text-foreground hover:border-border'" class="py-2 px-4 border-b-2 font-medium text-sm transition-colors">
                                Interpretation Ranges
                            </button>
                        </div>

                        <!-- Questions / Items Tab -->
                        <div x-show="activeTab === 'items'">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-heading font-medium text-foreground">Question Items</h3>
                                <button type="button" @click="addItem()" class="btn-secondary text-sm px-3 py-1.5 flex items-center gap-1" x-show="!isLocked">
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
                                    <div class="w-48 shrink-0 text-sm font-medium text-foreground/80" x-show="subcategories.length > 0">Sub-Category</div>
                                    <div class="w-9 shrink-0" x-show="!isLocked"></div>
                                </div>

                                <template x-for="(item, index) in items" :key="item.uid">
                                    <div class="flex flex-col md:flex-row gap-3 items-start bg-muted/10 p-3 rounded-xl border border-border/50 hover:border-primary/30 transition-colors relative group">
                                        
                                        <!-- Hidden field for actual database ID -->
                                        <input type="hidden" x-model="item.id" :name="'items['+index+'][id]'">

                                        <div class="w-full md:w-20 shrink-0">
                                            <label :for="'items['+index+'][item_number]'" class="md:hidden block text-sm font-medium text-foreground/80 mb-1">Item #</label>
                                            <input type="number" x-model="item.item_number" :name="'items['+index+'][item_number]'" placeholder="#" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" :readonly="isLocked" required>
                                        </div>
                                        
                                        <div class="w-full md:flex-1">
                                            <label :for="'items['+index+'][prompt]'" class="md:hidden block text-sm font-medium text-foreground/80 mb-1">Question / Prompt</label>
                                            <input type="text" x-model="item.prompt" :name="'items['+index+'][prompt]'" placeholder="e.g. I found it hard to wind down" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" :readonly="isLocked" required>
                                        </div>

                                        <div class="w-full md:w-48 shrink-0">
                                            <label :for="'items['+index+'][options]'" class="md:hidden block text-sm font-medium text-foreground/80 mb-1">Options</label>
                                            <input type="text" x-model="item.options" :name="'items['+index+'][options]'" placeholder="e.g. Never, Sometimes..." class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" :readonly="isLocked">
                                        </div>
                                        
                                        <div class="w-full md:w-48 shrink-0" x-show="subcategories.length > 0">
                                            <label :for="'items['+index+'][question_subcategory_id]'" class="md:hidden block text-sm font-medium text-foreground/80 mb-1">Sub-Category</label>
                                            <select x-model="item.question_subcategory_id" :name="'items['+index+'][question_subcategory_id]'" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" :disabled="isLocked">
                                                <option value="">(None)</option>
                                                <template x-for="sub in subcategories" :key="sub.id || sub.temp_id">
                                                    <option :value="sub.id || sub.temp_id" x-text="sub.name" :selected="item.question_subcategory_id == (sub.id || sub.temp_id)"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div class="shrink-0 flex items-center justify-end w-full md:w-9 mt-2 md:mt-0 md:pt-1" x-show="!isLocked">
                                            <button type="button" @click="removeItem(index)" class="text-error/70 hover:text-error hover:bg-error/10 p-2 rounded-xl transition-colors md:opacity-50 group-hover:opacity-100 flex items-center gap-1 w-full md:w-auto justify-center" title="Remove question">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                <span class="md:hidden text-sm">Remove</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                
                                <div x-show="items.length === 0" class="text-center py-8 bg-muted/20 border border-dashed border-border rounded-2xl text-foreground/60">
                                    No questions in this category. Click "Add Question" to start.
                                </div>
                            </div>
                        </div>

                        <!-- Sub-Categories Tab -->
                        <div x-show="activeTab === 'subcats'" style="display: none;">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-heading font-medium text-foreground">Sub-Categories</h3>
                                <button type="button" @click="addSubcategory()" class="btn-primary text-sm px-4 py-2" x-show="!isLocked">
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
                                    <button type="button" @click="addSubcategory()" class="text-primary hover:text-primary-hover font-medium text-sm transition-colors" x-show="!isLocked">
                                        Add your first Sub-Category
                                    </button>
                                </div>
                            </template>
                            
                            <template x-if="subcategories.length > 0">
                                <div class="space-y-4">
                                    <template x-for="(sub, index) in subcategories" :key="index">
                                        <div class="flex items-start gap-4 p-4 bg-background border border-border rounded-xl shadow-sm relative group">
                                            <input type="hidden" :name="'subcategories['+index+'][id]'" :value="sub.id">
                                            <input type="hidden" :name="'subcategories['+index+'][temp_id]'" :value="sub.temp_id">
                                            <div class="flex-grow">
                                                <label class="block text-sm font-medium text-foreground/80 mb-1">Name</label>
                                                <input type="text" x-model="sub.name" :name="'subcategories['+index+'][name]'" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" :readonly="isLocked" required>
                                            </div>
                                            <div class="w-24">
                                                <label class="block text-sm font-medium text-foreground/80 mb-1">Order</label>
                                                <input type="number" x-model="sub.display_order" :name="'subcategories['+index+'][display_order]'" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" :readonly="isLocked" required>
                                            </div>
                                            <button type="button" @click="removeSubcategory(index)" class="mt-7 text-red-500 hover:text-red-700 transition-colors p-2" x-show="!isLocked" title="Remove">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- Correlated Pairs Tab -->
                        <div x-show="activeTab === 'pairs'" style="display: none;">
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h3 class="text-lg font-heading font-medium text-foreground">Correlated Question Pairs</h3>
                                    <p class="text-sm text-foreground/70">Define logical relationships between questions to detect contradictions.</p>
                                </div>
                                <button type="button" @click="addPair()" class="btn-secondary text-sm px-3 py-1.5 flex items-center gap-1" x-show="!isLocked && availableItems.length >= 2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Add Pair
                                </button>
                            </div>
                            
                            <x-input-error :messages="$errors->get('pairs')" class="mb-4" />

                            <div x-show="availableItems.length < 2" class="mb-4 p-4 bg-warning/10 border-l-4 border-warning text-warning-dark text-sm rounded-r-lg">
                                You must save at least two questions before you can define pairs.
                            </div>

                            <div class="space-y-4">
                                <template x-for="(pair, index) in pairs" :key="pair.uid">
                                    <div class="bg-muted/20 p-5 rounded-2xl border border-border">
                                        
                                        <!-- Hidden field for actual database ID -->
                                        <input type="hidden" x-model="pair.id" :name="'pairs['+index+'][id]'">

                                        <div class="flex justify-end mb-2" x-show="!isLocked">
                                            <button type="button" @click="removePair(index)" class="text-error/70 hover:text-error hover:bg-error/10 p-1.5 rounded-lg transition-colors flex items-center gap-1 text-sm" title="Remove pair">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                Remove Pair
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <label class="block text-sm font-medium text-foreground/80">Question A</label>
                                                <select x-model="pair.question_item_id_a" :name="'pairs['+index+'][question_item_id_a]'" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" :disabled="isLocked" required>
                                                    <option value="" disabled>Select Question A</option>
                                                    <template x-for="avail in availableItems" :key="avail.id">
                                                        <option :value="avail.actualId" x-text="avail.label" :disabled="pair.question_item_id_b == avail.actualId"></option>
                                                    </template>
                                                </select>
                                                <input type="hidden" :name="'pairs['+index+'][question_item_id_a]'" :value="pair.question_item_id_a" x-if="isLocked">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-foreground/80">Question B</label>
                                                <select x-model="pair.question_item_id_b" :name="'pairs['+index+'][question_item_id_b]'" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" :disabled="isLocked" required>
                                                    <option value="" disabled>Select Question B</option>
                                                    <template x-for="avail in availableItems" :key="avail.id">
                                                        <option :value="avail.actualId" x-text="avail.label" :disabled="pair.question_item_id_a == avail.actualId"></option>
                                                    </template>
                                                </select>
                                                <input type="hidden" :name="'pairs['+index+'][question_item_id_b]'" :value="pair.question_item_id_b" x-if="isLocked">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <label class="block text-sm font-medium text-foreground/80">Relationship Type</label>
                                                <select x-model="pair.relationship_type" :name="'pairs['+index+'][relationship_type]'" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" :disabled="isLocked" required>
                                                    <option value="similar">Similar (Answers should be close)</option>
                                                    <option value="inverse">Inverse (Answers should be opposite)</option>
                                                </select>
                                                <input type="hidden" :name="'pairs['+index+'][relationship_type]'" :value="pair.relationship_type" x-if="isLocked">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-foreground/80">Contradiction Threshold (%)</label>
                                                <div class="flex items-center mt-1">
                                                    <input type="number" step="0.01" min="0" max="100" x-model="pair.contradiction_threshold" :name="'pairs['+index+'][contradiction_threshold]'" class="block w-full rounded-l-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" :readonly="isLocked" required>
                                                    <span class="inline-flex items-center px-3 rounded-r-xl border border-l-0 border-border bg-muted text-foreground/60 text-sm">%</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-foreground/80">Notes / Reason for Pairing</label>
                                            <textarea x-model="pair.notes" :name="'pairs['+index+'][notes]'" rows="2" class="mt-1 block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" placeholder="e.g. These two questions measure identical sentiments." :readonly="isLocked"></textarea>
                                        </div>
                                    </div>
                                </template>
                                
                                <div x-show="pairs.length === 0" class="text-center py-8 bg-muted/20 border border-dashed border-border rounded-2xl text-foreground/60">
                                    No pairs defined yet. Click "Add Pair" to start.
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8 pt-6 border-t border-border" x-show="activeTab !== 'ranges'">
                            <button type="submit" class="btn-primary px-8">
                                {{ __('Update Category & Questions') }}
                            </button>
                        </div>
                    </form>

                        <!-- Interpretation Ranges Tab -->
                        <div x-show="activeTab === 'ranges'" class="mt-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-heading font-medium text-foreground">Interpretation Ranges</h3>
                                <p class="text-sm text-foreground/70">Define custom score bands and labels. These will replace the raw scores in views.</p>
                            </div>

                            <div class="mb-4 text-sm text-text-muted">
                                @if($category->scale_type === 'numeric_scale' && $category->scale_max !== null)
                                    <p class="font-medium text-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="inline-block w-4 h-4 mr-1">
                                          <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                        </svg>
                                        Hint: Theoretical Total Score Range for this category is 
                                        <strong>{{ $category->questionItems->count() * $category->scale_min }}</strong> to 
                                        <strong>{{ $category->questionItems->count() * $category->scale_max }}</strong>
                                        (based on {{ $category->questionItems->count() }} items). 
                                        @if(strtolower($category->name) === 'dass21')
                                            <em>(Note: DASS21 applies a ×2 multiplier to the raw score automatically during scoring).</em>
                                        @endif
                                    </p>
                                @endif
                                <p>Interpretation ranges map numeric scores to meaningful labels (e.g., "Normal", "Severe"). 
                                If your scale uses Subscale Tags, you must define ranges for EACH subscale tag individually.</p>
                            </div>

                            @if(session('success'))
                                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                                    <span class="block sm:inline">{{ session('success') }}</span>
                                </div>
                            @endif
                            
                            @if($errors->any())
                                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                    <ul class="list-disc pl-5">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="space-y-6">
                                @foreach($category->interpretationRanges as $range)
                                    <form method="POST" action="{{ route('interpretation-ranges.update', ['question_category' => $category, 'range' => $range]) }}" class="bg-muted p-4 rounded-xl border border-border" x-data="{ isEditing: false, showWarning: false }">
                                        @csrf
                                        @method('PUT')
                                        <div class="flex flex-wrap md:flex-nowrap gap-4 items-end">
                                            <div class="w-full md:w-32">
                                                <label class="block text-xs font-medium text-foreground/80">Subscale</label>
                                                <input type="text" name="subscale_tag" value="{{ old('subscale_tag', $range->subscale_tag) }}" :disabled="!isEditing" class="mt-1 block w-full text-sm rounded-lg border-border disabled:opacity-50">
                                            </div>
                                            <div class="w-full md:w-20">
                                                <label class="block text-xs font-medium text-foreground/80">Min</label>
                                                <input type="number" name="min_score" value="{{ old('min_score', $range->min_score) }}" :disabled="!isEditing" class="mt-1 block w-full text-sm rounded-lg border-border disabled:opacity-50">
                                            </div>
                                            <div class="w-full md:w-20">
                                                <label class="block text-xs font-medium text-foreground/80">Max</label>
                                                <input type="number" name="max_score" value="{{ old('max_score', $range->max_score) }}" :disabled="!isEditing" class="mt-1 block w-full text-sm rounded-lg border-border disabled:opacity-50">
                                            </div>
                                            <div class="w-full md:w-40">
                                                <label class="block text-xs font-medium text-foreground/80">Label</label>
                                                <input type="text" name="label" value="{{ old('label', $range->label) }}" :disabled="!isEditing" class="mt-1 block w-full text-sm rounded-lg border-border disabled:opacity-50">
                                            </div>
                                            <div class="w-full md:w-32">
                                                <label class="block text-xs font-medium text-foreground/80">Color</label>
                                                <select name="color_tag" :disabled="!isEditing" class="mt-1 block w-full text-sm rounded-lg border-border disabled:opacity-50">
                                                    @foreach(['green', 'yellow', 'orange', 'red', 'purple', 'gray', 'blue'] as $col)
                                                        <option value="{{ $col }}" {{ $range->color_tag === $col ? 'selected' : '' }}>{{ ucfirst($col) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="w-full md:w-20">
                                                <label class="block text-xs font-medium text-foreground/80">Order</label>
                                                <input type="number" name="display_order" value="{{ old('display_order', $range->display_order) }}" :disabled="!isEditing" class="mt-1 block w-full text-sm rounded-lg border-border disabled:opacity-50">
                                            </div>
                                            <div class="flex-shrink-0 flex gap-2 pb-1">
                                                <template x-if="!isEditing">
                                                    <button type="button" @click="isEditing = true" class="text-primary hover:text-accent font-medium text-sm px-2">Edit</button>
                                                </template>
                                                <template x-if="isEditing">
                                                    <div class="flex gap-2">
                                                        <button type="button" @click="{{ $range->is_official_default ? 'showWarning = true' : '$el.closest(\'form\').submit()' }}" class="text-green-600 hover:text-green-800 font-medium text-sm px-2">Save</button>
                                                        <button type="button" @click="isEditing = false" class="text-gray-500 hover:text-gray-700 font-medium text-sm px-2">Cancel</button>
                                                    </div>
                                                </template>
                                                <button type="button" @click="$refs.deleteForm.submit()" class="text-destructive hover:opacity-80 font-medium text-sm px-2" :disabled="isEditing">Delete</button>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <label class="block text-xs font-medium text-foreground/80">Description</label>
                                            <input type="text" name="description" value="{{ old('description', $range->description) }}" :disabled="!isEditing" class="mt-1 block w-full text-sm rounded-lg border-border disabled:opacity-50">
                                        </div>
                                        @if($range->is_official_default)
                                            <div class="mt-2 text-xs text-orange-600 flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                Official Default Band
                                            </div>
                                        @endif

                                        <!-- Warning Modal for Editing Official Default -->
                                        <div x-show="showWarning" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                                <div x-show="showWarning" @click="showWarning = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                                                <div x-show="showWarning" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                        <div class="sm:flex sm:items-start">
                                                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                                                <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                </svg>
                                                            </div>
                                                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Edit Official Standard?</h3>
                                                                <div class="mt-2">
                                                                    <p class="text-sm text-gray-500">
                                                                        This is an officially published clinical cutoff (DASS-21). Editing this means your interpretation will no longer match the validated published standard. Are you sure you want to proceed?
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                                            Yes, Update Range
                                                        </button>
                                                        <button type="button" @click="showWarning = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                    
                                    <form method="POST" action="{{ route('interpretation-ranges.destroy', ['question_category' => $category, 'range' => $range]) }}" x-ref="deleteForm" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endforeach

                                <!-- Add New Range -->
                                <div class="mt-8 border-t border-border pt-6">
                                    <h4 class="text-md font-heading font-medium text-foreground mb-4">Add New Range</h4>
                                    <form method="POST" action="{{ route('interpretation-ranges.store', $category) }}" class="bg-white p-4 rounded-xl border border-dashed border-primary/50">
                                        @csrf
                                        <div class="flex flex-wrap md:flex-nowrap gap-4 items-end">
                                            <div class="w-full md:w-32">
                                                <label class="block text-xs font-medium text-foreground/80">Subscale</label>
                                                <input type="text" name="subscale_tag" value="{{ old('subscale_tag') }}" class="mt-1 block w-full text-sm rounded-lg border-border">
                                            </div>
                                            <div class="w-full md:w-20">
                                                <label class="block text-xs font-medium text-foreground/80">Min</label>
                                                <input type="number" name="min_score" value="{{ old('min_score') }}" class="mt-1 block w-full text-sm rounded-lg border-border" required>
                                            </div>
                                            <div class="w-full md:w-20">
                                                <label class="block text-xs font-medium text-foreground/80">Max</label>
                                                <input type="number" name="max_score" value="{{ old('max_score') }}" class="mt-1 block w-full text-sm rounded-lg border-border" required>
                                            </div>
                                            <div class="w-full md:w-40">
                                                <label class="block text-xs font-medium text-foreground/80">Label</label>
                                                <input type="text" name="label" value="{{ old('label') }}" class="mt-1 block w-full text-sm rounded-lg border-border" required>
                                            </div>
                                            <div class="w-full md:w-32">
                                                <label class="block text-xs font-medium text-foreground/80">Color</label>
                                                <select name="color_tag" class="mt-1 block w-full text-sm rounded-lg border-border" required>
                                                    @foreach(['green', 'yellow', 'orange', 'red', 'purple', 'gray', 'blue'] as $col)
                                                        <option value="{{ $col }}" {{ old('color_tag') === $col ? 'selected' : '' }}>{{ ucfirst($col) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="w-full md:w-20">
                                                <label class="block text-xs font-medium text-foreground/80">Order</label>
                                                <input type="number" name="display_order" value="{{ old('display_order', 0) }}" class="mt-1 block w-full text-sm rounded-lg border-border" required>
                                            </div>
                                            <div class="flex-shrink-0 flex gap-2 pb-1">
                                                <button type="submit" class="btn-primary text-sm px-4 py-2">Add</button>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <label class="block text-xs font-medium text-foreground/80">Description</label>
                                            <input type="text" name="description" value="{{ old('description') }}" class="mt-1 block w-full text-sm rounded-lg border-border">
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                </div>
            </div>
        </div>
    </div>
    
    <script nonce="{{ $cspNonce }}">
        function questionCategoryForm() {
            // Load old input if exists, otherwise load from DB model
            let oldItems = @json(old('items'));
            if (!oldItems) {
                // Map the DB items
                let dbItems = @json($category->questionItems);
                oldItems = dbItems.map(item => ({
                    id: item.id,
                    item_number: item.item_number,
                    prompt: item.prompt,
                    options: (item.options && Array.isArray(item.options)) ? item.options.join(', ') : '',
                    question_subcategory_id: item.question_subcategory_id || ''
                }));
            } else {
                // Ensure IDs exist for alpine key binding
                oldItems = oldItems.map((item, index) => ({
                    ...item,
                    id: item.id || Date.now() + index
                }));
            }

            // Ensure unique UIDs for alpine key binding
            oldItems = oldItems.map((item, index) => ({
                ...item,
                uid: 'existing_' + (item.id || Date.now() + index)
            }));

            let oldSubcategories = @json(old('subcategories'));
            if (!oldSubcategories) {
                let dbSubs = @json($category->subcategories);
                oldSubcategories = dbSubs.map(sub => ({
                    id: sub.id,
                    temp_id: sub.id,
                    name: sub.name,
                    display_order: sub.display_order
                }));
            } else {
                oldSubcategories = oldSubcategories.map((sub, index) => ({
                    ...sub,
                    temp_id: sub.id || sub.temp_id || 'temp_' + Date.now() + index
                }));
            }

            // Handle Correlated Pairs
            let oldPairs = @json(old('pairs'));
            if (!oldPairs) {
                let dbPairs = @json($category->correlatedPairs);
                oldPairs = dbPairs.map(pair => ({
                    id: pair.id,
                    question_item_id_a: pair.question_item_id_a,
                    question_item_id_b: pair.question_item_id_b,
                    relationship_type: pair.relationship_type,
                    contradiction_threshold: pair.contradiction_threshold,
                    notes: pair.notes || ''
                }));
            }

            oldPairs = oldPairs.map((pair, index) => ({
                ...pair,
                uid: 'pair_existing_' + (pair.id || Date.now() + index)
            }));

            return {
                activeTab: '{{ session('activeTab', 'items') }}',
                scaleType: '{{ old("scale_type", $category->scale_type) }}',
                items: oldItems,
                subcategories: oldSubcategories,
                pairs: oldPairs,
                isLocked: {{ $category->isDynamicallyLocked() ? 'true' : 'false' }},
                addItem() {
                    if (this.isLocked) return;
                    const nextNum = this.items.length > 0 
                        ? Math.max(...this.items.map(i => parseInt(i.item_number) || 0)) + 1 
                        : 1;
                        
                    this.items.push({
                        uid: 'new_' + Date.now(),
                        id: null,
                        item_number: nextNum,
                        prompt: '',
                        options: '',
                        question_subcategory_id: ''
                    });
                },
                removeItem(index) {
                    if (this.isLocked) return;
                    this.items.splice(index, 1);
                },
                addSubcategory() {
                    if (this.isLocked) return;
                    const nextOrder = this.subcategories.length > 0 
                        ? Math.max(...this.subcategories.map(s => parseInt(s.display_order) || 0)) + 1 
                        : 1;
                        
                    this.subcategories.push({
                        id: null,
                        temp_id: 'temp_' + Date.now(),
                        name: '',
                        display_order: nextOrder
                    });
                },
                removeSubcategory(index) {
                    if (this.isLocked) return;
                    this.subcategories.splice(index, 1);
                },
                addPair() {
                    if (this.isLocked) return;
                    this.pairs.push({
                        uid: 'new_pair_' + Date.now(),
                        id: null,
                        question_item_id_a: '',
                        question_item_id_b: '',
                        relationship_type: 'similar',
                        contradiction_threshold: 75.00,
                        notes: ''
                    });
                },
                removePair(index) {
                    if (this.isLocked) return;
                    this.pairs.splice(index, 1);
                },
                get availableItems() {
                    return this.items.filter(i => i.id || i.prompt).map(i => ({
                        id: i.id || i.uid, // Fallback to uid if not saved yet (though relation needs real IDs)
                        label: `Item ${i.item_number}: ${i.prompt.substring(0, 30)}...`,
                        actualId: i.id
                    })).filter(i => i.actualId !== null);
                }
            }
        }
    </script>
</x-staff-layout>
