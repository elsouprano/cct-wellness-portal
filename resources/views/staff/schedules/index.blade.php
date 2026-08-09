<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                {{ __('Assessment Schedules') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-primary/10 border-l-4 border-primary p-4 rounded-r-lg mb-6">
                    <p class="text-sm text-primary font-medium">{{ session('success') }}</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border">
                <div class="p-lg text-foreground border-b border-border flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-heading font-medium text-foreground">Manage Schedules</h3>
                        <p class="text-sm text-foreground/70 mt-1">Configure when the assessment is open for students.</p>
                    </div>
                    <a href="{{ route('schedules.create') }}" class="btn-primary">Add Schedule</a>
                </div>
                
                @if($schedules->isEmpty())
                    <div class="p-12 text-center text-foreground/50">
                        <svg class="mx-auto h-12 w-12 text-foreground/30 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        No schedules found. Students will not be able to access the assessment.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase tracking-wider">Acad Year</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase tracking-wider">Level & Program</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase tracking-wider">Opens At</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-foreground/70 uppercase tracking-wider">Closes At</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold text-foreground/70 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-border">
                                @foreach($schedules as $schedule)
                                    <tr class="hover:bg-muted/30 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-foreground">{{ $schedule->academicYear->label }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground/80">
                                            {{ $schedule->year_level }} 
                                            @if($schedule->program)
                                                ({{ $schedule->program }})
                                            @else
                                                <span class="text-foreground/50 italic">(All Programs)</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground/70">
                                            {{ \Carbon\Carbon::parse($schedule->open_date)->format('M d, Y') }} at {{ $schedule->open_time }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground/70">
                                            {{ \Carbon\Carbon::parse($schedule->close_date)->format('M d, Y') }} at {{ $schedule->close_time }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('schedules.edit', $schedule) }}" class="text-primary hover:text-primary/80 mr-4 font-semibold transition-colors">Edit</a>
                                            <form action="{{ route('schedules.destroy', $schedule) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this schedule?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-destructive hover:text-destructive/80 font-semibold transition-colors">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
