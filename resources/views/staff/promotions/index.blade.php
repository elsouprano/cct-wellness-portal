@section('title', 'Promote Students via CSV')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                    {{ __('Promote Students via CSV') }}
                </h2>
                <p class="text-xs text-foreground/60 mt-0.5">Bulk advance student year levels with spreadsheet upload</p>
            </div>
            <div class="space-x-2">
                <a href="{{ route('year-levels.index') }}" class="btn-secondary text-sm px-4 py-2">
                    &larr; Back to Year Levels
                </a>
                <a href="{{ route('admin.promotions.sample-csv') }}" class="btn-secondary text-sm px-4 py-2 inline-flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-primary">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Download Sample CSV
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-secondary/10 border-l-4 border-secondary p-4 rounded-r-xl">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <p class="text-sm text-secondary font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if (session('info'))
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-xl">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm text-blue-800 font-medium">{{ session('info') }}</p>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-destructive/10 border-l-4 border-destructive p-4 rounded-r-xl">
                    <div class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-destructive shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <p class="text-sm text-destructive font-bold">Please correct the following errors:</p>
                            <ul class="list-disc list-inside text-xs text-destructive mt-1 space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Upload Section Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl p-6 sm:p-8 border border-border">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left: Instructions -->
                    <div class="lg:col-span-1 space-y-4 border-b lg:border-b-0 lg:border-r border-border pb-6 lg:pb-0 lg:pr-8">
                        <div class="flex items-center gap-2 text-primary font-heading font-bold text-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            Instructions & Requirements
                        </div>
                        
                        <p class="text-sm text-foreground/80 leading-relaxed">
                            Upload a standard CSV file (e.g. exported from Microsoft Excel or Google Sheets) to promote students to their new year levels.
                        </p>

                        <div class="bg-muted/40 rounded-2xl p-4 border border-border/80 text-xs space-y-2">
                            <div class="font-bold text-foreground uppercase tracking-wider">Required Header Row:</div>
                            <div class="bg-white px-3 py-1.5 rounded-lg border border-border font-mono text-primary font-semibold">
                                student_number,year_level
                            </div>
                            <div class="font-bold text-foreground uppercase tracking-wider mt-2">Accepted Values:</div>
                            <ul class="list-disc list-inside space-y-1 text-foreground/70">
                                <li><strong>student_number:</strong> Existing student ID (e.g. 2023-0001)</li>
                                <li><strong>year_level:</strong> 1st, 2nd, 3rd, or 4th</li>
                                <li>No blank cells or duplicate student numbers</li>
                                <li>UTF-8 encoded, max size 2MB</li>
                            </ul>
                        </div>

                        <div class="pt-2">
                            <a href="{{ route('admin.promotions.sample-csv') }}" class="text-xs font-bold text-primary hover:underline inline-flex items-center gap-1">
                                &darr; Download sample CSV template (.csv)
                            </a>
                        </div>
                    </div>

                    <!-- Right: Upload Form -->
                    <div class="lg:col-span-2 flex flex-col justify-center space-y-6">
                        <div>
                            <h3 class="text-lg font-heading font-semibold text-foreground mb-1">Upload CSV File</h3>
                            <p class="text-xs text-foreground/60">Select your file to preview validated changes before applying.</p>
                        </div>

                        <form action="{{ route('admin.promotions.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <div class="border-2 border-dashed border-border rounded-2xl p-6 text-center hover:border-primary/50 transition-colors bg-muted/10">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-primary/40 mx-auto mb-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                                </svg>
                                <label for="csv_file" class="cursor-pointer block">
                                    <span class="btn-secondary text-xs px-4 py-2 inline-block mb-2">Choose CSV File</span>
                                    <input id="csv_file" name="csv_file" type="file" accept=".csv" class="hidden" onchange="document.getElementById('selected-filename').textContent = this.files[0] ? this.files[0].name : 'No file chosen';" required>
                                </label>
                                <p id="selected-filename" class="text-xs font-mono text-foreground/60">No file chosen (Max 2MB)</p>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button type="submit" class="btn-primary text-sm px-6 py-2.5 inline-flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    Upload & Preview
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Preview Section (if preview exists in session) -->
            @if ($preview)
                @php
                    $validCount = count($preview['valid_rows']);
                    $invalidCount = count($preview['invalid_rows']);
                    $firstTwenty = array_slice($preview['valid_rows'], 0, 20);
                @endphp

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border space-y-6">
                    <!-- Preview Header Bar -->
                    <div class="p-6 border-b border-border bg-muted/20 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <span class="text-xs uppercase tracking-wider font-bold text-accent">File Review</span>
                            <h3 class="text-xl font-heading font-bold text-foreground">
                                Preview: <span class="font-mono text-primary">{{ $preview['file_name'] }}</span>
                            </h3>
                        </div>
                        
                        <!-- Status counts & Action buttons -->
                        <div class="flex items-center flex-wrap gap-3">
                            <span class="px-3 py-1 text-xs font-bold rounded-full bg-secondary/10 text-secondary border border-secondary/20">
                                {{ $validCount }} Valid {{ Str::plural('Row', $validCount) }}
                            </span>
                            @if ($invalidCount > 0)
                                <span class="px-3 py-1 text-xs font-bold rounded-full bg-destructive/10 text-destructive border border-destructive/20">
                                    {{ $invalidCount }} Invalid {{ Str::plural('Row', $invalidCount) }}
                                </span>
                                <a href="{{ route('admin.promotions.invalid-csv') }}" class="btn-secondary text-xs px-3 py-1.5 inline-flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                    Download Invalid Rows CSV
                                </a>
                            @endif

                            <form action="{{ route('admin.promotions.clear') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-foreground/60 hover:text-foreground font-semibold px-2 py-1">
                                    Cancel
                                </button>
                            </form>

                            @if ($validCount > 0)
                                <form action="{{ route('admin.promotions.confirm') }}" method="POST" onsubmit="return confirm('Are you sure you want to promote {{ $validCount }} student(s)? They will be prompted for section confirmation on their next login.');" class="inline">
                                    @csrf
                                    <button type="submit" class="btn-primary text-xs px-4 py-2 font-bold shadow-sm inline-flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        Confirm Promotion ({{ $validCount }})
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <!-- Valid Rows Preview Table (First 20) -->
                    <div class="px-6 space-y-3">
                        <div class="flex justify-between items-center">
                            <h4 class="font-heading font-bold text-base text-foreground">
                                Valid Rows Preview <span class="text-xs font-normal text-foreground/60">(Showing first {{ min(20, $validCount) }} of {{ $validCount }})</span>
                            </h4>
                        </div>

                        @if ($validCount > 0)
                            <div class="overflow-x-auto border border-border rounded-2xl">
                                <table class="min-w-full divide-y divide-border text-sm">
                                    <thead class="bg-muted/40">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left text-xs font-bold text-foreground/70 uppercase">Row</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-bold text-foreground/70 uppercase">Student Number</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-bold text-foreground/70 uppercase">Student Name</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-bold text-foreground/70 uppercase">Program</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-bold text-foreground/70 uppercase">Current Year &rarr; New Year</th>
                                            <th class="px-4 py-2.5 text-center text-xs font-bold text-foreground/70 uppercase">Section Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border bg-white">
                                        @foreach ($firstTwenty as $row)
                                            <tr class="hover:bg-muted/20">
                                                <td class="px-4 py-2 text-xs font-mono text-foreground/50">{{ $row['row_number'] }}</td>
                                                <td class="px-4 py-2 font-mono font-semibold text-primary">{{ $row['student_number'] }}</td>
                                                <td class="px-4 py-2 font-medium text-foreground">{{ $row['student_name'] }}</td>
                                                <td class="px-4 py-2 text-foreground/70">{{ $row['program'] }}</td>
                                                <td class="px-4 py-2 whitespace-nowrap">
                                                    <span class="text-foreground/70">{{ $row['current_year'] }}</span>
                                                    <span class="text-primary font-bold mx-1">&rarr;</span>
                                                    <span class="font-bold text-secondary">{{ $row['new_year'] }}</span>
                                                </td>
                                                <td class="px-4 py-2 text-center">
                                                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-accent/10 text-accent font-semibold">
                                                        Cleared (Student will confirm)
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-foreground/50 py-4">No valid rows found in this file.</p>
                        @endif
                    </div>

                    <!-- Invalid Rows Table (If Any) -->
                    @if ($invalidCount > 0)
                        <div class="px-6 pb-6 space-y-3">
                            <div class="flex justify-between items-center">
                                <h4 class="font-heading font-bold text-base text-destructive flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Invalid Rows ({{ $invalidCount }}) — Will be skipped
                                </h4>
                            </div>

                            <div class="overflow-x-auto border border-destructive/20 rounded-2xl bg-destructive/5 max-h-80 overflow-y-auto">
                                <table class="min-w-full divide-y divide-destructive/20 text-sm">
                                    <thead class="bg-destructive/10">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-bold text-destructive uppercase">Row</th>
                                            <th class="px-4 py-2 text-left text-xs font-bold text-destructive uppercase">Student Number</th>
                                            <th class="px-4 py-2 text-left text-xs font-bold text-destructive uppercase">Year Level</th>
                                            <th class="px-4 py-2 text-left text-xs font-bold text-destructive uppercase">Reason for Rejection</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-destructive/10 bg-white">
                                        @foreach ($preview['invalid_rows'] as $invRow)
                                            <tr class="hover:bg-destructive/5">
                                                <td class="px-4 py-2 text-xs font-mono text-foreground/50">{{ $invRow['row_number'] }}</td>
                                                <td class="px-4 py-2 font-mono text-destructive font-semibold">{{ $invRow['student_number'] }}</td>
                                                <td class="px-4 py-2 font-mono text-foreground/70">{{ $invRow['year_level'] }}</td>
                                                <td class="px-4 py-2 text-xs text-destructive font-medium">{{ $invRow['reason'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Batch Audit Log History -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-border">
                <div class="p-6 border-b border-border bg-muted/20">
                    <h3 class="text-base font-heading font-semibold text-foreground">Recent Promotion Batches</h3>
                    <p class="text-xs text-foreground/60">Historical audit trail of CSV uploads</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead class="bg-muted/40">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-foreground/70 uppercase">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-foreground/70 uppercase">Admin</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-foreground/70 uppercase">File Name</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-foreground/70 uppercase">Rows Applied</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-foreground/70 uppercase">Rows Skipped</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-white">
                            @forelse ($batches as $batch)
                                <tr class="hover:bg-muted/20">
                                    <td class="px-6 py-3 text-xs text-foreground/70 font-mono">
                                        {{ $batch->created_at->format('M d, Y h:i A') }}
                                    </td>
                                    <td class="px-6 py-3 font-semibold text-foreground">
                                        {{ $batch->admin ? $batch->admin->first_name . ' ' . $batch->admin->last_name : 'System' }}
                                    </td>
                                    <td class="px-6 py-3 font-mono text-xs text-primary">
                                        {{ $batch->file_name }}
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-secondary/10 text-secondary">
                                            {{ $batch->rows_applied }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        @if ($batch->rows_skipped > 0)
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-destructive/10 text-destructive">
                                                {{ $batch->rows_skipped }}
                                            </span>
                                        @else
                                            <span class="text-xs text-foreground/40">0</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-foreground/40 text-sm">
                                        No promotion batches logged yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-staff-layout>
