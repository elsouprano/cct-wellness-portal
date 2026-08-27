<div class="bg-card/40 p-5 rounded-2xl border border-border shadow-sm hover:border-primary/40 hover:shadow-md transition-all relative group">
    
    <!-- Hidden field for actual database ID -->
    <input type="hidden" x-model="item.id" :name="'items['+index+'][id]'">

    <!-- Delete Button -->
    <button type="button" @click="removeItem(index)" x-show="!isLocked" class="absolute text-foreground/30 hover:text-error hover:bg-error/10 p-1.5 rounded-lg transition-colors opacity-0 group-hover:opacity-100" style="top: 1rem; right: 1rem;" title="Remove question">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
    </button>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-5 pr-10">
        <!-- Item # -->
        <div class="md:col-span-3 lg:col-span-2">
            <label :for="'items['+index+'][item_number]'" class="block text-xs font-semibold text-foreground/50 uppercase tracking-wider mb-2">Item #</label>
            <input type="number" x-model="item.item_number" :name="'items['+index+'][item_number]'" placeholder="#" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm font-medium" :readonly="isLocked" required>
        </div>
        
        <!-- Prompt -->
        <div class="md:col-span-9 lg:col-span-10">
            <label :for="'items['+index+'][prompt]'" class="block text-xs font-semibold text-foreground/50 uppercase tracking-wider mb-2">Question / Prompt</label>
            <input type="text" x-model="item.prompt" :name="'items['+index+'][prompt]'" placeholder="e.g. I found it hard to wind down" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm font-medium" :readonly="isLocked" required>
        </div>

        <!-- Bottom Section -->
        <div class="md:col-span-12 grid grid-cols-1 md:grid-cols-2 gap-5 pt-1" x-show="scaleType === 'multiple_choice_unscored' || subcategories.length > 0">
            
            <!-- Sub-Category -->
            <div x-show="subcategories.length > 0">
                <label :for="'items['+index+'][question_subcategory_id]'" class="block text-xs font-semibold text-foreground/50 uppercase tracking-wider mb-2">Sub-Category</label>
                <select x-show="subcategories.length > 0" x-model="item.question_subcategory_id" :name="'items['+index+'][question_subcategory_id]'" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm font-medium" :disabled="isLocked">
                    <option value="">(None)</option>
                    <template x-for="sub in subcategories" :key="sub.id || sub.temp_id">
                        <option :value="sub.id || sub.temp_id" x-text="sub.name" :selected="item.question_subcategory_id == (sub.id || sub.temp_id)"></option>
                    </template>
                </select>
            </div>

            <!-- Custom Options -->
            <div x-show="scaleType === 'multiple_choice_unscored'">
                <label class="flex items-center gap-2 cursor-pointer mb-2 h-[18px]">
                    <input type="checkbox" x-model="item.useCustomOptions" @change="if(!item.useCustomOptions) item.options = ''" class="rounded border-border text-primary focus:ring-primary shadow-sm w-4 h-4" :disabled="isLocked">
                    <span class="text-xs font-semibold text-foreground/50 uppercase tracking-wider">Custom options</span>
                </label>
                <div x-show="item.useCustomOptions" x-collapse>
                    <textarea x-model="item.options" :name="'items['+index+'][options]'" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3" class="block w-full rounded-xl border-border shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm disabled:bg-gray-100 mt-1" :readonly="isLocked"></textarea>
                </div>
            </div>
        </div>
    </div>
</div>
