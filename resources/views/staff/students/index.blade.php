<x-staff-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-text-primary leading-tight">
                {{ __('Student Profiles') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Search and Filter Bar -->
            <div class="bg-white p-4 shadow-sm sm:rounded-2xl border border-border/50">
                <form method="GET" action="{{ route('staff.students.index') }}" class="flex gap-4">
                    <div class="flex-1">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, student ID, or email..." class="w-full border-gray-300 focus:border-primary focus:ring-primary rounded-xl shadow-sm">
                    </div>
                    <button type="submit" class="px-6 py-2 bg-primary text-white font-semibold rounded-xl shadow-sm hover:bg-primary/90 transition-colors">
                        Search
                    </button>
                    @if(request('search'))
                        <a href="{{ route('staff.students.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 font-semibold rounded-xl shadow-sm hover:bg-gray-200 transition-colors">
                            Clear
                        </a>
                    @endif
                </form>
            </div>

            <!-- Students Table -->
            <div class="bg-white shadow-sm sm:rounded-2xl border border-border/50 overflow-hidden">
                <div class="p-6 text-text-primary">
                    <h3 class="text-lg font-semibold mb-4">Student Directory</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border/50 text-left">
                            <thead>
                                <tr class="text-text-muted text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 font-medium">Name</th>
                                    <th class="px-4 py-3 font-medium">Student ID</th>
                                    <th class="px-4 py-3 font-medium">Program & Section</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/50">
                                @forelse($students as $student)
                                    <tr class="hover:bg-background/50 transition-colors">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full overflow-hidden shrink-0 border border-border bg-white">
                                                    <img src="{{ $student->avatar_url }}" alt="Avatar" class="w-full h-full object-cover" />
                                                </div>
                                                <div>
                                                    <div class="font-medium text-text-primary">{{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_initial }}</div>
                                                    <div class="text-xs text-text-muted">{{ $student->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-text-primary font-medium">{{ $student->student_id ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-text-primary">{{ $student->program ?? 'N/A' }} - {{ $student->section ?? 'N/A' }}</div>
                                            <div class="text-xs text-text-muted">Year {{ $student->year_level ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if($student->is_active)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('staff.students.show', $student) }}" class="inline-flex items-center gap-1 text-primary hover:text-primary-dark transition-colors bg-primary/10 hover:bg-primary/20 px-3 py-1.5 rounded-lg">
                                                View Profile &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-text-muted">
                                            No students found matching your criteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $students->links() }}
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</x-staff-layout>
