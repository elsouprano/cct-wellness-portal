<x-staff-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-heading text-xl text-primary leading-tight">
                {{ __('Institution Settings') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            @if(session('status'))
                <div class="bg-accent/10 border-l-4 border-accent p-4 rounded-md">
                    <p class="text-accent font-medium">{{ session('status') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
                    <ul class="list-disc list-inside text-red-600">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Academic Years -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border">
                <div class="p-lg border-b border-border bg-muted/10 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-heading font-semibold text-foreground">Academic Years</h3>
                        <p class="text-sm text-foreground/70 mt-1">Manage the academic years used for assessments and analytics.</p>
                    </div>
                    <button x-data x-on:click="$dispatch('open-modal', 'create-academic-year')" class="btn-primary">
                        Add Academic Year
                    </button>
                </div>

                <div class="p-lg">
                    @if($academicYears->isEmpty())
                        <div class="text-center py-12">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-foreground/20 mx-auto mb-4 shrink-0" style="width: 48px; height: 48px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                            <p class="text-foreground/50">No academic years have been added yet.</p>
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
                                            <form method="post" action="{{ route('institution.academic-years.set-current', $year->id) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit" class="text-sm font-medium text-accent hover:underline">Set Active</button>
                                            </form>
                                        @endif
                                        <button x-data x-on:click="$dispatch('open-modal', 'edit-academic-year-{{ $year->id }}')" class="text-sm font-medium text-primary hover:underline px-2">Edit</button>
                                        <button x-data x-on:click="$dispatch('open-modal', 'delete-academic-year-{{ $year->id }}')" class="text-sm font-medium text-red-600 hover:underline px-2">Delete</button>
                                    </div>
                                </div>

                                <!-- Edit Academic Year Modal -->
                                <x-modal name="edit-academic-year-{{ $year->id }}" focusable>
                                    <form method="post" action="{{ route('institution.academic-years.update', $year->id) }}" class="p-6">
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

                                <!-- Delete Academic Year Modal -->
                                <x-modal name="delete-academic-year-{{ $year->id }}" focusable>
                                    <form method="post" action="{{ route('institution.academic-years.destroy', $year->id) }}" class="p-6">
                                        @csrf
                                        @method('delete')
                                        <h2 class="text-lg font-bold text-foreground">Delete Academic Year</h2>
                                        <p class="mt-2 text-sm text-foreground/70">Are you sure you want to delete the academic year "{{ $year->label }}"?</p>
                                        <div class="mt-6 flex justify-end">
                                            <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                            <x-danger-button class="ms-3">Delete</x-danger-button>
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
                <form method="post" action="{{ route('institution.academic-years.store') }}" class="p-6">
                    @csrf
                    <h2 class="text-lg font-bold text-foreground">Add New Academic Year</h2>
                    <p class="mt-1 text-sm text-foreground/70">Create a new academic year to group assessment schedules and analytics.</p>
                    
                    <div class="mt-4">
                        <x-input-label for="label" value="Label (e.g. 2026-2027)" />
                        <x-text-input id="label" name="label" type="text" class="mt-1 block w-full" placeholder="2026-2027" required />
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                        <x-primary-button class="ms-3">Create Academic Year</x-primary-button>
                    </div>
                </form>
            </x-modal>

            <!-- Departments and Programs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border" x-data="{ activeDepartment: null }">
                <div class="p-lg border-b border-border bg-muted/10 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-heading font-semibold text-foreground">Departments & Programs</h3>
                        <p class="text-sm text-foreground/70 mt-1">Manage the institutional structure used for registrations and scheduling.</p>
                    </div>
                    <button x-data x-on:click="$dispatch('open-modal', 'create-department')" class="btn-primary">
                        Add Department
                    </button>
                </div>

                <div class="p-lg">
                    @if($departments->isEmpty())
                        <div class="text-center py-12">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-foreground/20 mx-auto mb-4 shrink-0" style="width: 48px; height: 48px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.315 48.315 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
                            </svg>
                            <p class="text-foreground/50">No departments have been added yet.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($departments as $dept)
                                <div class="border border-border rounded-xl overflow-hidden bg-white">
                                    <!-- Department Header -->
                                    <div class="px-6 py-4 bg-muted/5 flex justify-between items-center cursor-pointer hover:bg-muted/10 transition-colors" @click="activeDepartment = activeDepartment === {{ $dept->id }} ? null : {{ $dept->id }}">
                                        <div class="flex items-center gap-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary transition-transform" :class="activeDepartment === {{ $dept->id }} ? 'rotate-90' : ''">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                            </svg>
                                            <h4 class="font-heading font-bold text-foreground text-lg">{{ $dept->name }}</h4>
                                            <span class="bg-primary/10 text-primary text-xs font-medium px-2.5 py-0.5 rounded-full">{{ $dept->programs->count() }} Programs</span>
                                        </div>
                                        <div class="flex items-center gap-2" @click.stop>
                                            <button x-data x-on:click="$dispatch('open-modal', 'edit-department-{{ $dept->id }}')" class="text-sm font-medium text-primary hover:underline px-2">Edit</button>
                                            <button x-data x-on:click="$dispatch('open-modal', 'delete-department-{{ $dept->id }}')" class="text-sm font-medium text-red-600 hover:underline px-2">Delete</button>
                                        </div>
                                    </div>

                                    <!-- Programs List -->
                                    <div x-show="activeDepartment === {{ $dept->id }}" x-collapse x-cloak>
                                        <div class="border-t border-border p-6 bg-white">
                                            <div class="flex justify-between items-center mb-4">
                                                <h5 class="font-semibold text-foreground/80 text-sm uppercase tracking-wider">Programs in this Department</h5>
                                                <button x-data x-on:click="$dispatch('open-modal', 'create-program-{{ $dept->id }}')" class="text-sm btn-secondary py-1 px-3">
                                                    + Add Program
                                                </button>
                                            </div>

                                            @if($dept->programs->isEmpty())
                                                <p class="text-sm text-foreground/50 py-4 italic">No programs added to this department.</p>
                                            @else
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    @foreach($dept->programs as $prog)
                                                        <div class="flex justify-between items-center p-4 border border-border/50 rounded-lg hover:border-primary/30 transition-colors">
                                                            <div>
                                                                <p class="font-medium text-foreground">{{ $prog->name }}</p>
                                                                @if($prog->code)
                                                                    <p class="text-xs text-foreground/60 mt-0.5">{{ $prog->code }}</p>
                                                                @endif
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <button x-data x-on:click="$dispatch('open-modal', 'edit-program-{{ $prog->id }}')" class="text-xs text-primary hover:underline">Edit</button>
                                                                <button x-data x-on:click="$dispatch('open-modal', 'delete-program-{{ $prog->id }}')" class="text-xs text-red-600 hover:underline">Delete</button>
                                                            </div>
                                                        </div>

                                                        <!-- Edit Program Modal -->
                                                        <x-modal name="edit-program-{{ $prog->id }}" focusable>
                                                            <form method="post" action="{{ route('institution.programs.update', $prog->id) }}" class="p-6">
                                                                @csrf
                                                                @method('put')
                                                                <h2 class="text-lg font-medium text-foreground font-heading">Edit Program</h2>
                                                                <input type="hidden" name="department_id" value="{{ $dept->id }}">
                                                                <div class="mt-4">
                                                                    <x-input-label for="name_{{ $prog->id }}" value="Program Name" />
                                                                    <x-text-input id="name_{{ $prog->id }}" name="name" type="text" class="mt-1 block w-full" :value="$prog->name" required />
                                                                </div>
                                                                <div class="mt-4">
                                                                    <x-input-label for="code_{{ $prog->id }}" value="Program Code (Optional)" />
                                                                    <x-text-input id="code_{{ $prog->id }}" name="code" type="text" class="mt-1 block w-full" :value="$prog->code" placeholder="e.g. BSCS" />
                                                                </div>
                                                                <div class="mt-6 flex justify-end">
                                                                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                                                    <x-primary-button class="ml-3">Save Changes</x-primary-button>
                                                                </div>
                                                            </form>
                                                        </x-modal>

                                                        <!-- Delete Program Modal -->
                                                        <x-modal name="delete-program-{{ $prog->id }}" focusable>
                                                            <form method="post" action="{{ route('institution.programs.destroy', $prog->id) }}" class="p-6">
                                                                @csrf
                                                                @method('delete')
                                                                <h2 class="text-lg font-medium text-foreground font-heading">Delete Program</h2>
                                                                <p class="mt-2 text-sm text-foreground/70">Are you sure you want to delete "{{ $prog->name }}"? This action cannot be undone, and will be blocked if any users or schedules are assigned to this program.</p>
                                                                <div class="mt-6 flex justify-end">
                                                                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                                                    <x-primary-button class="ml-3 bg-red-600 hover:bg-red-700 focus:bg-red-700 active:bg-red-800 border-red-600">Delete Program</x-primary-button>
                                                                </div>
                                                            </form>
                                                        </x-modal>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Edit Department Modal -->
                                    <x-modal name="edit-department-{{ $dept->id }}" focusable>
                                        <form method="post" action="{{ route('institution.departments.update', $dept->id) }}" class="p-6">
                                            @csrf
                                            @method('put')
                                            <h2 class="text-lg font-medium text-foreground font-heading">Edit Department</h2>
                                            <div class="mt-4">
                                                <x-input-label for="name_{{ $dept->id }}" value="Department Name" />
                                                <x-text-input id="name_{{ $dept->id }}" name="name" type="text" class="mt-1 block w-full" :value="$dept->name" required />
                                            </div>
                                            <div class="mt-6 flex justify-end">
                                                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                                <x-primary-button class="ml-3">Save Changes</x-primary-button>
                                            </div>
                                        </form>
                                    </x-modal>

                                    <!-- Delete Department Modal -->
                                    <x-modal name="delete-department-{{ $dept->id }}" focusable>
                                        <form method="post" action="{{ route('institution.departments.destroy', $dept->id) }}" class="p-6">
                                            @csrf
                                            @method('delete')
                                            <h2 class="text-lg font-medium text-foreground font-heading">Delete Department</h2>
                                            <p class="mt-2 text-sm text-foreground/70">Are you sure you want to delete "{{ $dept->name }}"? You must delete or reassign all programs under this department first.</p>
                                            <div class="mt-6 flex justify-end">
                                                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                                <x-primary-button class="ml-3 bg-red-600 hover:bg-red-700 focus:bg-red-700 active:bg-red-800 border-red-600">Delete Department</x-primary-button>
                                            </div>
                                        </form>
                                    </x-modal>

                                    <!-- Create Program Modal -->
                                    <x-modal name="create-program-{{ $dept->id }}" focusable>
                                        <form method="post" action="{{ route('institution.programs.store') }}" class="p-6">
                                            @csrf
                                            <h2 class="text-lg font-medium text-foreground font-heading">Add Program to {{ $dept->name }}</h2>
                                            <input type="hidden" name="department_id" value="{{ $dept->id }}">
                                            <div class="mt-4">
                                                <x-input-label for="name_new_{{ $dept->id }}" value="Program Name" />
                                                <x-text-input id="name_new_{{ $dept->id }}" name="name" type="text" class="mt-1 block w-full" required />
                                            </div>
                                            <div class="mt-4">
                                                <x-input-label for="code_new_{{ $dept->id }}" value="Program Code (Optional)" />
                                                <x-text-input id="code_new_{{ $dept->id }}" name="code" type="text" class="mt-1 block w-full" placeholder="e.g. BSCS" />
                                            </div>
                                            <div class="mt-6 flex justify-end">
                                                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                                <x-primary-button class="ml-3">Add Program</x-primary-button>
                                            </div>
                                        </form>
                                    </x-modal>

                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Create Department Modal -->
            <x-modal name="create-department" focusable>
                <form method="post" action="{{ route('institution.departments.store') }}" class="p-6">
                    @csrf
                    <h2 class="text-lg font-medium text-foreground font-heading">Add New Department</h2>
                    <div class="mt-4">
                        <x-input-label for="name_new_dept" value="Department Name" />
                        <x-text-input id="name_new_dept" name="name" type="text" class="mt-1 block w-full" required />
                    </div>
                    <div class="mt-6 flex justify-end">
                        <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                        <x-primary-button class="ml-3">Create Department</x-primary-button>
                    </div>
                </form>
            </x-modal>

        </div>
    </div>
</x-staff-layout>
