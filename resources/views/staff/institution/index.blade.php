@section('title', 'Institution Data')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-heading text-xl font-bold text-foreground leading-tight">
                    {{ __('Institution Data') }}
                </h2>
                <p class="text-xs text-foreground/60 mt-0.5">
                    Manage the schools, programs, and academic years used for registration and scheduling.
                </p>
            </div>
            <div>
                <a href="{{ route('institution.index', ['show_archived' => $showArchived ? 0 : 1]) }}" 
                   class="btn-secondary text-xs py-2 px-3.5 inline-flex items-center gap-1.5 shadow-2xs font-semibold">
                    <svg class="w-3.5 h-3.5 text-foreground/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                    <span>{{ $showArchived ? 'Hide Archived' : 'Show Archived' }}</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            @if(session('status'))
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center gap-2 shadow-2xs">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if(session('info'))
                <div class="p-4 rounded-2xl bg-blue-50 border border-blue-200 text-blue-800 text-sm font-medium flex items-center gap-2 shadow-2xs">
                    <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('info') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium shadow-2xs">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Academic Years -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border">
                <div class="p-lg border-b border-border bg-muted/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex-1 min-w-0 pr-4">
                        <h3 class="text-lg font-heading font-semibold text-foreground">Academic Years</h3>
                        <p class="text-sm text-foreground/70 mt-1 leading-relaxed">Manage the academic years used for assessments and analytics. Setting an academic year as active automatically promotes students to +1 year level and prompts them to select their new section.</p>
                    </div>
                    <button x-data x-on:click="$dispatch('open-modal', 'create-academic-year')" class="btn-primary whitespace-nowrap shrink-0 self-center">
                        Add Academic Year
                    </button>
                </div>

                <div class="p-lg">
                    @if($academicYears->isEmpty())
                        <div class="text-center py-12">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-foreground/20 mx-auto mb-4 shrink-0" style="width: 48px; height: 48px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                            <p class="text-foreground/50">No active academic years have been added yet.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($academicYears as $year)
                                <div class="flex justify-between items-center p-4 border {{ $year->is_current ? 'border-primary bg-primary/5' : 'border-border/50 bg-white' }} rounded-xl transition-colors">
                                    <div class="flex items-center gap-3">
                                        <h4 class="font-medium text-foreground text-lg">{{ $year->label }}</h4>
                                        @if($year->is_current)
                                            <span class="bg-primary text-white text-xs font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">Active</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-3">
                                        @if(!$year->is_current)
                                            @if($year->canBeSetActive())
                                                <form method="post" action="{{ route('institution.academic-years.set-current', $year->id) }}"
                                                      data-confirm="save"
                                                      data-confirm-title="Set Active Academic Year"
                                                      data-confirm-message="Setting {{ $year->label }} as the active academic year will automatically promote all eligible students to their next year level (+1) and prompt them to select their new section. Are you sure you want to proceed?"
                                                      data-confirm-button="Set Active">
                                                    @csrf
                                                    @method('patch')
                                                    <button type="submit" 
                                                            class="text-sm font-medium text-accent hover:underline">
                                                        Set Active
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs font-medium text-foreground/40 italic">Past year</span>
                                            @endif
                                        @endif

                                        <button x-data x-on:click="$dispatch('open-modal', 'edit-academic-year-{{ $year->id }}')" class="text-sm font-medium text-primary hover:underline px-2">Edit</button>

                                        @if(!$year->is_current)
                                            <form method="post" action="{{ route('institution.academic-years.destroy', $year->id) }}" class="inline"
                                                  data-confirm="delete"
                                                  data-confirm-title="Archive Academic Year"
                                                  data-confirm-message="Archive {{ $year->label }}? It will be hidden from lists. Existing records are kept."
                                                  data-confirm-button="Archive">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="text-sm font-medium text-rose-600 hover:underline px-2">Archive</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                <!-- Edit Academic Year Modal -->
                                <x-modal name="edit-academic-year-{{ $year->id }}" focusable>
                                    <form method="post" action="{{ route('institution.academic-years.update', $year->id) }}" class="p-6"
                                          data-confirm="edit"
                                          data-confirm-title="Edit Academic Year">
                                        @csrf
                                        @method('put')
                                        <h2 class="text-lg font-bold text-foreground">Edit Academic Year</h2>
                                        <div class="mt-4">
                                            <x-input-label for="label-{{ $year->id }}" value="Label (e.g. 2026-2027)" />
                                            <x-text-input id="label-{{ $year->id }}" name="label" type="text" class="mt-1 block w-full" value="{{ $year->label }}" required />
                                        </div>
                                        <div class="mt-6 flex justify-end">
                                            <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                            <x-primary-button class="ms-3">Save Changes</x-primary-button>
                                        </div>
                                    </form>
                                </x-modal>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Create Academic Year Modal -->
            <x-modal name="create-academic-year" focusable>
                <form method="post" action="{{ route('institution.academic-years.store') }}" class="p-6"
                      data-confirm="save"
                      data-confirm-title="Add New Academic Year">
                    @csrf
                    <h2 class="text-lg font-bold text-foreground">Add New Academic Year</h2>
                    <p class="mt-1 text-sm text-foreground/70">Create a new academic year to group assessment schedules and analytics.</p>
                    
                    <div class="mt-4">
                        <x-input-label for="label" value="Label (e.g. 2026-2027)" />
                        <x-text-input id="label" name="label" type="text" class="mt-1 block w-full" placeholder="2026-2027" required />
                    </div>

                    <div class="mt-4 flex items-center">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="set_as_current" value="1" class="rounded border-gray-300 text-primary shadow-sm focus:ring-primary">
                            <span class="ms-2 text-sm text-foreground/80 font-medium">Set as active academic year immediately (promotes students to +1 year level)</span>
                        </label>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                        <x-primary-button class="ms-3">Create Academic Year</x-primary-button>
                    </div>
                </form>
            </x-modal>

            <!-- School and Programs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border" 
                 x-data="{ 
                     activeDepartment: null,
                     search: '',
                     matchesSearch(dept) {
                         if (!this.search.trim()) return true;
                         const q = this.search.toLowerCase().trim();
                         if (dept.name.toLowerCase().includes(q)) return true;
                         return dept.programs.some(p => (p.name && p.name.toLowerCase().includes(q)) || (p.code && p.code.toLowerCase().includes(q)));
                     }
                 }">
                <div class="p-lg border-b border-border bg-muted/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex-1 min-w-0 pr-4">
                        <h3 class="text-lg font-heading font-semibold text-foreground">School & Programs</h3>
                        <p class="text-sm text-foreground/70 mt-1 leading-relaxed">Manage the schools and programs used for registration and scheduling.</p>
                    </div>
                    <button x-data x-on:click="$dispatch('open-modal', 'create-department')" class="btn-primary whitespace-nowrap shrink-0 self-center">
                        Add School
                    </button>
                </div>

                <div class="p-lg space-y-4">
                    <!-- Search Input -->
                    @if($departments->isNotEmpty())
                        <div class="relative max-w-md">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-foreground/40">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input type="text" 
                                   x-model="search" 
                                   placeholder="Search school or program name/code..." 
                                   class="w-full text-xs rounded-xl border-border pl-9 pr-3.5 py-2.5 bg-muted/20 focus:bg-white focus:ring-primary focus:border-primary transition-colors">
                        </div>
                    @endif

                    @if($departments->isEmpty())
                        <div class="text-center py-12">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-foreground/20 mx-auto mb-4 shrink-0" style="width: 48px; height: 48px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.315 48.315 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
                            </svg>
                            <p class="text-foreground/50">No schools have been added yet.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($departments as $dept)
                                <div class="border border-border rounded-2xl overflow-hidden bg-white shadow-xs"
                                     x-show="matchesSearch({{ json_encode(['name' => $dept->name, 'programs' => $dept->programs->map(fn($p) => ['name' => $p->name, 'code' => $p->code])]) }})"
                                     x-transition.opacity>
                                     <!-- School Header -->
                                    <div class="px-6 py-4 bg-muted/5 flex justify-between items-center cursor-pointer hover:bg-muted/10 transition-colors select-none" 
                                         @click="activeDepartment = activeDepartment === {{ $dept->id }} ? null : {{ $dept->id }}">
                                        <div class="flex items-center gap-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" 
                                                 class="w-4 h-4 text-primary transition-transform duration-200" 
                                                 :class="activeDepartment === {{ $dept->id }} ? 'rotate-90' : ''">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                            </svg>
                                            <h4 class="font-heading font-bold text-foreground text-base sm:text-lg">{{ $dept->name }}</h4>
                                            <span class="bg-primary/10 text-primary text-xs font-semibold px-2.5 py-0.5 rounded-full border border-primary/20">
                                                {{ $dept->programs->count() }} {{ Str::plural('Program', $dept->programs->count()) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2" @click.stop>
                                            <button type="button" x-data x-on:click="$dispatch('open-modal', 'edit-department-{{ $dept->id }}')" class="text-xs font-semibold text-primary hover:underline px-2 py-1 rounded hover:bg-primary/5">Edit</button>
                                            <form method="post" action="{{ route('institution.departments.destroy', $dept->id) }}" class="inline"
                                                  data-confirm="delete"
                                                  data-confirm-title="Archive School"
                                                  data-confirm-message="Archive {{ $dept->name }}? It will be hidden from lists. Existing records are kept."
                                                  data-confirm-button="Archive">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline px-2 py-1 rounded hover:bg-rose-50">Archive</button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Programs List Accordion -->
                                    <div x-show="activeDepartment === {{ $dept->id }}" x-collapse x-cloak>
                                        <div class="border-t border-border p-6 bg-white space-y-4">
                                            <div class="flex items-center justify-between">
                                                <h5 class="font-semibold text-foreground/80 text-xs uppercase tracking-wider">Programs in this School</h5>
                                                <button type="button" x-data x-on:click="$dispatch('open-modal', 'create-program-{{ $dept->id }}')" class="text-xs btn-secondary py-1 px-3 inline-flex items-center gap-1 shadow-xs font-medium">
                                                    + Add Program
                                                </button>
                                            </div>

                                            @if($dept->programs->isEmpty())
                                                <div class="text-xs text-foreground/50 py-6 text-center italic bg-muted/10 rounded-xl border border-dashed border-border">
                                                    No programs added to this school yet. Click "+ Add Program" to add one.
                                                </div>
                                            @else
                                                <div class="overflow-x-auto rounded-xl border border-border">
                                                    <table class="min-w-full divide-y divide-border text-left text-xs">
                                                        <thead class="bg-muted/40 font-semibold text-foreground/70 uppercase">
                                                            <tr>
                                                                <th class="px-4 py-2.5 w-24">Code</th>
                                                                <th class="px-4 py-2.5">Program Name</th>
                                                                <th class="px-4 py-2.5 w-32">Students</th>
                                                                <th class="px-4 py-2.5 text-right w-28">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-border bg-white">
                                                            @foreach($dept->programs as $prog)
                                                                <tr class="hover:bg-muted/20 transition-colors">
                                                                    <td class="px-4 py-3 font-mono font-bold text-primary">
                                                                        {{ $prog->code ?: '—' }}
                                                                    </td>
                                                                    <td class="px-4 py-3 font-semibold text-foreground">
                                                                        {{ $prog->name }}
                                                                    </td>
                                                                    <td class="px-4 py-3 text-foreground/70">
                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-muted text-[11px] font-medium text-foreground/75 border border-border">
                                                                            {{ $prog->users_count }} {{ Str::plural('student', $prog->users_count) }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                                                        <button type="button" x-data x-on:click="$dispatch('open-modal', 'edit-program-{{ $prog->id }}')" class="text-primary hover:underline font-semibold mr-2">Edit</button>
                                                                        <form method="post" action="{{ route('institution.programs.destroy', $prog->id) }}" class="inline"
                                                                              data-confirm="delete"
                                                                              data-confirm-title="Archive Program"
                                                                              data-confirm-message="Archive {{ $prog->name }}? It will be hidden from lists. Existing records are kept."
                                                                              data-confirm-button="Archive">
                                                                            @csrf
                                                                            @method('delete')
                                                                            <button type="submit" class="text-rose-600 hover:underline font-semibold">Archive</button>
                                                                        </form>
                                                                    </td>
                                                                </tr>

                                                                <!-- Edit Program Modal -->
                                                                <x-modal name="edit-program-{{ $prog->id }}" focusable>
                                                                    <form method="post" action="{{ route('institution.programs.update', $prog->id) }}" class="p-6"
                                                                          data-confirm="edit"
                                                                          data-confirm-title="Edit Program">
                                                                        @csrf
                                                                        @method('put')
                                                                        <h2 class="text-lg font-medium text-foreground font-heading">Edit Program</h2>
                                                                        <input type="hidden" name="department_id" value="{{ $dept->id }}">
                                                                        <div class="mt-4">
                                                                            <x-input-label for="name_{{ $prog->id }}" value="Program Name" />
                                                                            <x-text-input id="name_{{ $prog->id }}" name="name" type="text" class="mt-1 block w-full text-xs" :value="$prog->name" required />
                                                                        </div>
                                                                        <div class="mt-4">
                                                                            <x-input-label for="code_{{ $prog->id }}" value="Program Code (Optional)" />
                                                                            <x-text-input id="code_{{ $prog->id }}" name="code" type="text" class="mt-1 block w-full text-xs" :value="$prog->code" placeholder="e.g. BSCS" />
                                                                        </div>
                                                                        <div class="mt-6 flex justify-end gap-2">
                                                                            <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                                                            <x-primary-button>Save Changes</x-primary-button>
                                                                        </div>
                                                                    </form>
                                                                </x-modal>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Edit School Modal -->
                                    <x-modal name="edit-department-{{ $dept->id }}" focusable>
                                        <form method="post" action="{{ route('institution.departments.update', $dept->id) }}" class="p-6"
                                              data-confirm="edit"
                                              data-confirm-title="Edit School">
                                            @csrf
                                            @method('put')
                                            <h2 class="text-lg font-bold text-foreground font-heading">Edit School</h2>
                                            <div class="mt-4">
                                                <x-input-label for="name_{{ $dept->id }}" value="School Name" />
                                                <x-text-input id="name_{{ $dept->id }}" name="name" type="text" class="mt-1 block w-full" :value="$dept->name" required />
                                            </div>
                                            <div class="mt-6 flex justify-end gap-2">
                                                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                                <x-primary-button>Save Changes</x-primary-button>
                                            </div>
                                        </form>
                                    </x-modal>

                                    <!-- Create Program Modal -->
                                    <x-modal name="create-program-{{ $dept->id }}" focusable>
                                        <form method="post" action="{{ route('institution.programs.store') }}" class="p-6"
                                              data-confirm="save"
                                              data-confirm-title="Add Program">
                                            @csrf
                                            <h2 class="text-lg font-bold text-foreground font-heading">Add Program to {{ $dept->name }}</h2>
                                            <input type="hidden" name="department_id" value="{{ $dept->id }}">
                                            <div class="mt-4">
                                                <x-input-label for="name_new_{{ $dept->id }}" value="Program Name" />
                                                <x-text-input id="name_new_{{ $dept->id }}" name="name" type="text" class="mt-1 block w-full text-xs" required />
                                            </div>
                                            <div class="mt-4">
                                                <x-input-label for="code_new_{{ $dept->id }}" value="Program Code (Optional)" />
                                                <x-text-input id="code_new_{{ $dept->id }}" name="code" type="text" class="mt-1 block w-full text-xs" placeholder="e.g. BSCS" />
                                            </div>
                                            <div class="mt-6 flex justify-end gap-2">
                                                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                                <x-primary-button>Create Program</x-primary-button>
                                            </div>
                                        </form>
                                    </x-modal>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Create School Modal (with batch programs) -->
            <x-modal name="create-department" focusable>
                <form method="post" action="{{ route('institution.departments.store') }}" 
                      class="p-6"
                      data-confirm="save"
                      data-confirm-title="Add School"
                      x-data="{ 
                          programs: [], 
                          addProgram() { 
                              this.programs.push({ name: '', code: '' }); 
                          }, 
                          removeProgram(index) { 
                              this.programs.splice(index, 1); 
                          } 
                      }">
                    @csrf
                    <h2 class="text-lg font-bold text-foreground font-heading">Add School</h2>
                    <p class="text-xs text-foreground/60 mt-0.5">Define a new academic school and optionally include its initial programs.</p>

                    <div class="mt-4">
                        <x-input-label for="dept_name" value="School Name" />
                        <x-text-input id="dept_name" name="name" type="text" class="mt-1 block w-full" placeholder="e.g. School of Education" required />
                    </div>

                    <!-- Optional Programs Rows -->
                    <div class="mt-6 border-t border-border pt-4">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="text-xs font-bold text-foreground uppercase tracking-wider">Initial Programs (Optional)</h3>
                                <p class="text-[11px] text-foreground/50">You can add programs now or create them later.</p>
                            </div>
                            <button type="button" 
                                    @click="addProgram()" 
                                    class="text-xs btn-secondary py-1 px-2.5 inline-flex items-center gap-1 shadow-2xs font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>+ Add program</span>
                            </button>
                        </div>

                        <div class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                            <template x-for="(prog, index) in programs" :key="index">
                                <div class="flex items-center gap-2 p-2 rounded-xl bg-muted/20 border border-border">
                                    <div class="flex-1">
                                        <input type="text" 
                                               :name="'programs[' + index + '][name]'" 
                                               x-model="prog.name" 
                                               placeholder="Program name (e.g. BS Information Technology)" 
                                               required 
                                               class="w-full text-xs rounded-lg border-border py-1.5 px-2.5 focus:border-primary focus:ring-primary shadow-2xs">
                                    </div>
                                    <div class="w-28">
                                        <input type="text" 
                                               :name="'programs[' + index + '][code]'" 
                                               x-model="prog.code" 
                                               placeholder="Code (BSIT)" 
                                               required 
                                               class="w-full text-xs font-mono rounded-lg border-border py-1.5 px-2.5 focus:border-primary focus:ring-primary shadow-2xs">
                                    </div>
                                    <button type="button" 
                                            @click="removeProgram(index)" 
                                            class="text-foreground/40 hover:text-rose-600 p-1 rounded-lg transition-colors"
                                            title="Remove row">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                            <div x-show="programs.length === 0" class="text-xs text-foreground/40 text-center py-3 italic">
                                No programs added to this school yet.
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2 border-t border-border pt-4">
                        <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                        <x-primary-button>Create School</x-primary-button>
                    </div>
                </form>
            </x-modal>

            @if($showArchived)
                <!-- ARCHIVED RECORDS SECTION -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-dashed border-border/80">
                    <div class="p-lg border-b border-border bg-amber-50/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-heading font-bold text-foreground flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                                <span>Archived Records</span>
                            </h3>
                            <p class="text-xs text-foreground/60 mt-0.5">Archived items are hidden from new student registration and scheduling. Existing historical records are kept.</p>
                        </div>
                    </div>

                    <div class="p-lg space-y-6">
                        <!-- Archived Academic Years -->
                        <div>
                            <h4 class="text-xs font-bold text-foreground/60 uppercase tracking-wider mb-2">Archived Academic Years ({{ $archivedAcademicYears->count() }})</h4>
                            @if($archivedAcademicYears->isEmpty())
                                <p class="text-xs text-foreground/40 italic py-2">No archived academic years.</p>
                            @else
                                <div class="space-y-2">
                                    @foreach($archivedAcademicYears as $ay)
                                        <div class="flex items-center justify-between p-3 rounded-xl bg-muted/20 border border-border">
                                            <div>
                                                <span class="font-medium text-foreground text-sm">{{ $ay->label }}</span>
                                                <span class="text-xs text-foreground/50 ml-2">Archived on {{ $ay->archived_at?->format('M d, Y') }}</span>
                                            </div>
                                            <form method="POST" action="{{ route('institution.academic-years.restore', $ay->id) }}"
                                                  data-confirm="save"
                                                  data-confirm-title="Restore Academic Year"
                                                  data-confirm-message="Restore this academic year?"
                                                  data-confirm-button="Restore">
                                                @csrf
                                                @method('patch')
                                                <button type="submit" class="text-xs font-semibold text-primary hover:underline px-2 py-1 rounded hover:bg-primary/5">
                                                    Restore
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Archived Schools -->
                        <div class="pt-4 border-t border-border">
                            <h4 class="text-xs font-bold text-foreground/60 uppercase tracking-wider mb-2">Archived Schools ({{ $archivedDepartments->count() }})</h4>
                            @if($archivedDepartments->isEmpty())
                                <p class="text-xs text-foreground/40 italic py-2">No archived schools.</p>
                            @else
                                <div class="space-y-2">
                                    @foreach($archivedDepartments as $d)
                                        <div class="flex items-center justify-between p-3 rounded-xl bg-muted/20 border border-border">
                                            <div>
                                                <span class="font-medium text-foreground text-sm">{{ $d->name }}</span>
                                                <span class="text-xs text-foreground/50 ml-2">Archived on {{ $d->archived_at?->format('M d, Y') }} ({{ $d->programs_count }} programs)</span>
                                            </div>
                                            <form method="POST" action="{{ route('institution.departments.restore', $d->id) }}"
                                                  data-confirm="save"
                                                  data-confirm-title="Restore School"
                                                  data-confirm-message="Restore this school and make it active again?"
                                                  data-confirm-button="Restore">
                                                @csrf
                                                @method('patch')
                                                <button type="submit" class="text-xs font-semibold text-primary hover:underline px-2 py-1 rounded hover:bg-primary/5">
                                                    Restore
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Archived Programs -->
                        <div class="pt-4 border-t border-border">
                            <h4 class="text-xs font-bold text-foreground/60 uppercase tracking-wider mb-2">Archived Programs ({{ $archivedPrograms->count() }})</h4>
                            @if($archivedPrograms->isEmpty())
                                <p class="text-xs text-foreground/40 italic py-2">No archived programs.</p>
                            @else
                                <div class="space-y-2">
                                    @foreach($archivedPrograms as $p)
                                        <div class="flex items-center justify-between p-3 rounded-xl bg-muted/20 border border-border">
                                            <div>
                                                <span class="font-bold text-primary font-mono text-xs mr-2">{{ $p->code }}</span>
                                                <span class="font-medium text-foreground text-sm">{{ $p->name }}</span>
                                                <span class="text-xs text-foreground/50 ml-2">School: {{ $p->department?->name }} &bull; Archived on {{ $p->archived_at?->format('M d, Y') }}</span>
                                            </div>
                                            <form method="POST" action="{{ route('institution.programs.restore', $p->id) }}"
                                                  data-confirm="save"
                                                  data-confirm-title="Restore Program"
                                                  data-confirm-message="Restore this program and make it active again?"
                                                  data-confirm-button="Restore">
                                                @csrf
                                                @method('patch')
                                                <button type="submit" class="text-xs font-semibold text-primary hover:underline px-2 py-1 rounded hover:bg-primary/5">
                                                    Restore
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-staff-layout>
