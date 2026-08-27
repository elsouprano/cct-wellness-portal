<x-staff-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-heading font-bold text-2xl text-primary leading-tight">
                Student Profile
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Action Buttons -->
            <div class="flex items-center gap-3">
                @if($latestSubmission)
                    <a href="{{ route('staff.inventory.export', $latestSubmission) }}" target="_blank" class="px-4 py-2 bg-primary text-white text-sm font-semibold rounded shadow-sm hover:bg-primary/90 transition-colors">
                        Export as PDF
                    </a>
                @endif
                <button class="px-4 py-2 bg-accent text-foreground text-sm font-semibold rounded shadow-sm hover:bg-accent/90 transition-colors">
                    Mark as Pending
                </button>
                <button class="px-4 py-2 bg-destructive text-white text-sm font-semibold rounded shadow-sm hover:bg-destructive/90 transition-colors">
                    Archive Student
                </button>
            </div>

            <!-- Student Profile Card -->
            <div class="bg-white rounded shadow-sm border border-border/50 p-6">
                <div class="flex items-center gap-4 mb-8">
                    <img src="{{ $student->avatar_url }}" alt="Avatar" class="w-16 h-16 rounded-full object-cover shrink-0 shadow-sm border border-border" />
                    <div>
                        <h3 class="text-2xl font-bold text-primary">{{ $student->first_name }} {{ $student->middle_initial }} {{ $student->last_name }}</h3>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-5 gap-y-6 gap-x-4">
                    <!-- Row 1 -->
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Student ID</p>
                        <p class="text-sm font-bold text-gray-900">{{ $student->student_id ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Department</p>
                        <p class="text-sm font-bold text-gray-900">{{ $student->structuredProgram->department->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Program</p>
                        <p class="text-sm font-bold text-gray-900">{{ $student->structuredProgram->code ?? $student->program ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Major</p>
                        <p class="text-sm font-bold text-gray-900">&mdash;</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Section</p>
                        <p class="text-sm font-bold text-gray-900">{{ $student->section ?? 'N/A' }}</p>
                    </div>

                    <!-- Row 2 -->
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Email</p>
                        <p class="text-sm font-bold text-gray-900 truncate" title="{{ $student->email }}">{{ $student->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Contact Number</p>
                        <p class="text-sm font-bold text-gray-900">{{ $student->contact_number ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Is Paying Student</p>
                        <p class="text-sm font-bold text-gray-900">{{ $student->is_paying_student ? 'Yes' : 'No' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Address</p>
                        <p class="text-sm font-bold text-gray-900 truncate" title="{{ $student->address_line1 }}">{{ $student->address_line1 ?? 'N/A' }}</p>
                    </div>
                    
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Submission Status</p>
                        @if($latestSubmission)
                            <p class="text-sm font-bold text-green-600">Submitted on {{ $latestSubmission->submitted_at->format('M d, Y h:i A') }}</p>
                        @else
                            <p class="text-sm font-bold text-gray-500">No submission</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mt-8 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <a href="#" class="border-primary text-primary whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Scoring Results
                    </a>
                    <a href="#" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        DASS-21
                    </a>
                </nav>
            </div>

            <!-- Tab Content -->
            @if($latestSubmission)
                <div class="bg-white shadow-sm rounded border border-border/50 p-6 mt-4">
                    <h4 class="text-lg font-bold text-primary mb-6">Scoring Results</h4>

                    <div class="bg-muted/30 rounded-lg p-6 mb-8 border border-border/50">
                        <h5 class="text-primary font-bold text-sm mb-4">Mental Health Risk Assessment</h5>
                        
                        <div class="flex items-start gap-8">
                            <div>
                                <p class="text-sm font-bold text-gray-900 mb-2">Overall Risk Level:</p>
                                @php
                                    $overallRisk = 'NORMAL';
                                    $riskColor = 'bg-secondary text-white';
                                    
                                    if ($latestSubmission->flags->count() > 0) {
                                        $overallRisk = 'URGENT';
                                        $riskColor = 'bg-destructive text-white';
                                    }
                                @endphp
                                <div class="inline-flex px-4 py-2 {{ $riskColor }} font-bold rounded shadow-sm">
                                    {{ $overallRisk }}
                                </div>
                            </div>
                            
                            <div>
                                <p class="text-sm font-bold text-gray-900 mb-2">Risk Flags Detected:</p>
                                @if($latestSubmission->flags->count() > 0)
                                    <ul class="list-disc list-inside text-sm text-destructive space-y-1">
                                        @foreach($latestSubmission->flags as $flag)
                                            <li>{{ $flag->description }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-sm text-gray-600">No specific risk flags detected in the latest assessment.</p>
                                @endif
                            </div>
                        </div>

                        @if($latestSubmission->flags->count() > 0)
                            <div class="mt-8 pt-4 border-t border-border/50 flex justify-between items-center">
                                <div class="flex items-center gap-2 text-accent font-bold text-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 drop-shadow-sm">
                                      <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
                                    </svg>
                                    Requires Review
                                </div>
                                <button class="px-4 py-2 bg-primary text-white text-sm font-semibold rounded shadow-sm hover:bg-primary/90 transition-colors">
                                    Mark as Reviewed
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Scoring Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 font-bold text-gray-900">Category Name</th>
                                    <th class="px-4 py-3 font-bold text-gray-900">Raw Score</th>
                                    <th class="px-4 py-3 font-bold text-gray-900">Interpretation Label</th>
                                    <th class="px-4 py-3 font-bold text-gray-900">Needs Counseling</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @if($latestSubmission->scores)
                                    @foreach($latestSubmission->scores as $score)
                                        <tr>
                                            <td class="px-4 py-4 font-bold text-gray-900">{{ $score->questionCategory->name ?? 'Unknown Category' }}</td>
                                            <td class="px-4 py-4 text-gray-600">{{ $score->score }}</td>
                                            <td class="px-4 py-4">
                                                @php
                                                    $isSevere = str_contains(strtolower($score->interpretation_label), 'severe') || str_contains(strtolower($score->interpretation_label), 'high');
                                                @endphp
                                                <span class="inline-flex px-2 py-1 {{ $isSevere ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' }} text-xs font-semibold rounded">
                                                    {{ $score->interpretation_label ?? 'Normal' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                @if($isSevere)
                                                    <span class="inline-flex px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded">Yes</span>
                                                @else
                                                    <span class="inline-flex px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded">No</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4" class="px-4 py-4 text-center text-gray-500">No category scores available for this submission.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-white shadow-sm rounded border border-border/50 p-6 mt-4 text-center">
                    <p class="text-gray-500 py-8">This student has not submitted any assessments yet.</p>
                </div>
            @endif
        </div>
    </div>
</x-staff-layout>
