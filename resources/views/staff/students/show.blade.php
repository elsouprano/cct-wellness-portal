@section('title')Profile - {{ \Illuminate\Support\Str::limit($student->first_name . ' ' . $student->last_name, 25) }}@endsection
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

            @if (session('success'))
                <div class="bg-primary/10 border-l-4 border-primary p-4 rounded-r-lg text-sm text-primary font-medium">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-destructive/10 border-l-4 border-destructive p-4 rounded-r-lg text-sm text-destructive font-medium">
                    {{ session('error') }}
                </div>
            @endif
            
            <!-- Action Buttons -->
            <div class="flex items-center gap-3 flex-wrap">
                @if($latestSubmission)
                    <a href="{{ route('staff.inventory.export', $latestSubmission) }}" target="_blank" class="px-4 py-2 bg-primary text-white text-sm font-semibold rounded shadow-sm hover:bg-primary/90 transition-colors">
                        Export as PDF
                    </a>
                @endif
                @if(auth()->user()->isAdmin())
                    @if($student->is_active)
                        <form action="{{ route('staff.students.archive', $student) }}" method="POST" onsubmit="return confirm('Are you sure you want to archive this student? They will be removed from the active student directory.');" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-4 py-2 bg-destructive text-white text-sm font-semibold rounded shadow-sm hover:bg-destructive/90 transition-colors">
                                Archive Student
                            </button>
                        </form>
                    @else
                        <form action="{{ route('staff.students.unarchive', $student) }}" method="POST" onsubmit="return confirm('Are you sure you want to unarchive this student and restore them to the active student directory?');" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded shadow-sm hover:bg-emerald-700 transition-colors">
                                Unarchive Student
                            </button>
                        </form>
                    @endif
                @endif
            </div>

            <!-- Student Profile Card -->
            <div class="bg-white rounded shadow-sm border border-border/50 p-6">
                <div class="flex items-center gap-4 mb-8">
                    <img src="{{ $student->avatar_url }}" alt="Avatar" width="64" height="64" loading="lazy" decoding="async" class="w-16 h-16 rounded-full object-cover shrink-0 shadow-sm border border-border" />
                    <div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <h3 class="text-2xl font-bold text-primary">{{ $student->first_name }} {{ $student->middle_initial }} {{ $student->last_name }}</h3>
                            @if($student->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Archived
                                </span>
                            @endif

                            @if($student->year_level_confirmed)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                                    Confirmed
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-secondary/10 text-secondary border border-secondary/20">
                                    Pending
                                </span>
                            @endif
                        </div>
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
                        <p class="text-xs text-gray-500 mb-1">Year Level</p>
                        <p class="text-sm font-bold text-gray-900">{{ $student->year_level ? ($student->year_level . (is_numeric($student->year_level) ? ' Year' : '')) : 'N/A' }}</p>
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
                            @if($latestSubmission->submitted_at)
                                <p class="text-sm font-bold text-green-600">Submitted on {{ $latestSubmission->submitted_at->format('M d, Y h:i A') }}</p>
                            @else
                                <p class="text-sm font-bold text-yellow-600">Incomplete Submission</p>
                            @endif
                        @else
                            <p class="text-sm font-bold text-gray-500">No submission</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tabs and Content wrapped in Alpine -->
            <div x-data="{ activeTab: 'overview' }">
                <!-- Tabs -->
                <div class="mt-8 border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none transition-colors">
                            Overview
                        </button>
                        <button @click="activeTab = 'responses'" :class="activeTab === 'responses' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none transition-colors">
                            Questionnaire Responses
                        </button>
                        <button @click="activeTab = 'history'" :class="activeTab === 'history' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none transition-colors">
                            Assessment History
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div x-show="activeTab === 'overview'" x-transition.opacity class="mt-4">
                    @if($latestSubmission)
                        <div class="bg-white shadow-sm rounded border border-border/50 p-6">
                            <h4 class="text-lg font-bold text-primary mb-6">Latest Scoring Results ({{ $latestSubmission->academic_year }})</h4>

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
                                                    <li>{{ $flag->description ?? ($flag->flag_type . ' flag on ' . $flag->category) }}</li>
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
                                        <a href="{{ route('staff.inventory.show', $latestSubmission->id) }}" class="px-4 py-2 bg-primary text-white text-sm font-semibold rounded shadow-sm hover:bg-primary/90 transition-colors">
                                            Review Submission
                                        </a>
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
                                        @if($latestSubmission->scores && $latestSubmission->scores->count() > 0)
                                            @foreach($latestSubmission->scores as $score)
                                                <tr>
                                                    <td class="px-4 py-4 font-bold text-gray-900">{{ $score->questionCategory->name ?? $score->category ?? 'Unknown Category' }}</td>
                                                    <td class="px-4 py-4 text-gray-600">{{ $score->raw_score ?? $score->score }}</td>
                                                    <td class="px-4 py-4">
                                                        @php
                                                            $isSevere = str_contains(strtolower($score->severity_label ?? $score->interpretation_label), 'severe') || str_contains(strtolower($score->severity_label ?? $score->interpretation_label), 'high');
                                                        @endphp
                                                        <span class="inline-flex px-2 py-1 {{ $isSevere ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' }} text-xs font-semibold rounded">
                                                            {{ $score->severity_label ?? $score->interpretation_label ?? 'Normal' }}
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
                            <div class="mt-4 text-right">
                                <a href="{{ route('staff.inventory.show', $latestSubmission->id) }}" class="text-primary hover:underline text-sm font-semibold">View Full Results &rarr;</a>
                            </div>
                        </div>
                    @else
                        <div class="bg-white shadow-sm rounded border border-border/50 p-6 mt-4 text-center">
                            <p class="text-gray-500 py-8">This student has not submitted any assessments yet.</p>
                        </div>
                    @endif
                </div>

                <!-- Tab: Responses -->
                <div x-show="activeTab === 'responses'" style="display: none;" x-transition.opacity class="mt-4">
                    @if($latestSubmission && isset($responsesByCategory) && $responsesByCategory->isNotEmpty())
                        <div class="space-y-6">
                            <!-- Quality Classification Banner -->
                            @php
                                $qa = $latestSubmission->qualityAssessment;
                                $classification = $latestSubmission->quality_classification ?? ($qa?->classification ?? ($latestSubmission->flags->isNotEmpty() ? 'FOR REVIEW' : 'ACCEPTABLE'));
                                $isForReview = $classification === 'FOR REVIEW';
                            @endphp
                            <div class="p-5 rounded-2xl border {{ $isForReview ? 'bg-amber-50/70 border-amber-200 text-amber-950' : 'bg-emerald-50/70 border-emerald-200 text-emerald-950' }}">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="flex items-start sm:items-center gap-3">
                                        @if($isForReview)
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 shadow-xs">
                                                <svg class="w-4 h-4 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                RESPONSE QUALITY: FOR REVIEW
                                            </span>
                                            <div class="text-xs font-medium text-amber-900">
                                                @if($qa && !empty($qa->flag_reasons))
                                                    {{ implode(' • ', $qa->flag_reasons) }}
                                                @else
                                                    Quality review indicators were detected for this submission.
                                                @endif
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 shadow-xs">
                                                <svg class="w-4 h-4 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                RESPONSE QUALITY: ACCEPTABLE
                                            </span>
                                            <div class="text-xs font-medium text-emerald-900">
                                                All responses verified with normal pacing, variety, and question consistency.
                                            </div>
                                        @endif
                                    </div>
                                    <a href="{{ route('staff.inventory.show', $latestSubmission->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold {{ $isForReview ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'bg-primary text-white hover:bg-primary/90' }} transition-colors shrink-0">
                                        Open Verification View &rarr;
                                    </a>
                                </div>
                            </div>

                            <!-- Category Tabs and Tables -->
                            <div class="bg-white rounded-2xl shadow-sm border border-border/50 overflow-hidden" x-data="{ activeCategory: '{{ $responsesByCategory->keys()->first() }}' }">
                                <div class="px-6 py-4 border-b border-border bg-muted/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <h4 class="text-base font-bold text-primary">Student Questionnaire Responses</h4>
                                        <p class="text-xs text-foreground/60 mt-0.5">Academic Year: {{ $latestSubmission->academic_year }} &bull; Submitted: {{ $latestSubmission->submitted_at ? $latestSubmission->submitted_at->format('M d, Y h:i A') : 'Incomplete' }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
                                        @foreach($responsesByCategory->keys() as $cat)
                                            <button 
                                                type="button"
                                                @click="activeCategory = '{{ $cat }}'"
                                                :class="activeCategory === '{{ $cat }}' ? 'bg-primary text-white font-semibold' : 'bg-white text-gray-700 border border-border hover:bg-muted'"
                                                class="px-3 py-1.5 rounded-lg text-xs transition-colors shrink-0 uppercase tracking-wide">
                                                {{ str_replace('_', ' ', $cat) }} ({{ $responsesByCategory[$cat]->count() }})
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="p-6">
                                    @foreach($responsesByCategory as $category => $responses)
                                        @php
                                            $catConfig = isset($inventoryConfig) ? $inventoryConfig->firstWhere('name', $category) : null;
                                        @endphp
                                        <div x-show="activeCategory === '{{ $category }}'" style="display: none;" class="space-y-4">
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-border text-sm">
                                                    <thead class="bg-muted/40">
                                                        <tr>
                                                            <th class="px-4 py-3 text-left text-xs font-semibold text-foreground/60 uppercase w-16">Item #</th>
                                                            <th class="px-4 py-3 text-left text-xs font-semibold text-foreground/60 uppercase">Question Prompt</th>
                                                            <th class="px-4 py-3 text-right text-xs font-semibold text-foreground/60 uppercase w-48">Student's Answer</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-border bg-white">
                                                        @foreach($responses->sortBy('item_number') as $resp)
                                                            @php
                                                                $prompt = $resp->questionItem?->prompt ?? ($catConfig?->questionItems->firstWhere('item_number', $resp->item_number)?->prompt ?? 'Question prompt unavailable');
                                                            @endphp
                                                            <tr class="hover:bg-muted/20 transition-colors">
                                                                <td class="px-4 py-3 font-semibold text-foreground/70">
                                                                    Q{{ $resp->item_number }}
                                                                </td>
                                                                <td class="px-4 py-3 text-foreground/90 leading-snug">
                                                                    {{ $prompt }}
                                                                </td>
                                                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-3 py-1 rounded-full bg-primary/10 text-primary font-bold border border-primary/20 text-xs">
                                                                        {{ $resp->response_value }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="bg-white shadow-sm rounded border border-border/50 p-12 text-center">
                            <p class="text-gray-500">No questionnaire responses recorded for this student.</p>
                        </div>
                    @endif
                </div>

                <div x-show="activeTab === 'history'" style="display: none;" x-transition.opacity class="mt-4">
                    <div class="bg-white shadow-sm rounded border border-border/50">
                        <div class="px-6 py-4 border-b border-border bg-muted/20">
                            <h4 class="text-lg font-bold text-primary">Assessment History</h4>
                        </div>
                        @if($student->inventorySubmissions->isEmpty())
                            <div class="p-12 text-center">
                                <p class="text-gray-500">No past assessments found for this student.</p>
                            </div>
                        @else
                            <ul class="divide-y divide-border">
                                @foreach($student->inventorySubmissions as $submission)
                                    <li class="hover:bg-muted/30 transition-colors">
                                        <a href="{{ route('staff.inventory.show', $submission->id) }}" class="flex items-center justify-between p-6 group">
                                            <div>
                                                <h5 class="text-base font-bold text-gray-900 group-hover:text-primary transition-colors">Academic Year: {{ $submission->academic_year }}</h5>
                                                <p class="text-sm text-gray-500 mt-1">Submitted on {{ $submission->submitted_at ? $submission->submitted_at->format('F j, Y \a\t g:i A') : 'Incomplete' }}</p>
                                                
                                                @if($submission->flags->count() > 0)
                                                    <div class="mt-2 inline-flex items-center gap-1 text-xs font-medium bg-red-100 text-red-800 px-2 py-0.5 rounded">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                        {{ $submission->flags->count() }} Flags
                                                    </div>
                                                @else
                                                    <div class="mt-2 inline-flex items-center gap-1 text-xs font-medium bg-green-100 text-green-800 px-2 py-0.5 rounded">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                        Normal
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="text-primary group-hover:translate-x-1 transition-transform">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                                </svg>
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-staff-layout>
