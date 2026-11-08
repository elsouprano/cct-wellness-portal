@section('title', 'Account Management')
<x-staff-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-heading font-bold text-2xl text-foreground">
                    {{ __('Account Management') }}
                </h2>
                <p class="text-xs text-foreground/60 mt-0.5">
                    Manage guidance counselor accounts, credentials, and assigned schools.
                </p>
            </div>
            <button type="button" x-data x-on:click="$dispatch('open-modal', 'create-account')" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary text-white rounded-xl shadow-xs hover:bg-primary/90 transition-all font-semibold text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                + Add Counselor
            </button>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Error messages -->
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 p-4 rounded-xl shadow-xs">
                    <div class="flex items-center gap-2 text-red-800 font-semibold text-sm mb-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Please fix the following issues:
                    </div>
                    <ul class="list-disc list-inside text-xs text-red-700 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Accounts Card -->
            <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-heading font-bold text-lg text-foreground">Guidance Counselors</h3>
                            <p class="text-xs text-foreground/50 mt-0.5">Authorized staff with student counseling access</p>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-muted text-foreground/70 border border-border">
                            {{ $counselors->count() }} {{ $counselors->count() === 1 ? 'Counselor' : 'Counselors' }}
                        </span>
                    </div>

                    @if($counselors->isNotEmpty())
                        <!-- Desktop & Tablet Table (md and up: >=768px) -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-border/70 text-xs font-semibold text-foreground/50 uppercase tracking-wider">
                                        <th class="py-3 px-4">Counselor</th>
                                        <th class="py-3 px-4">School</th>
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4">Created</th>
                                        <th class="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50 text-sm">
                                    @foreach($counselors as $counselor)
                                        @php
                                            $assignedDepts = $counselor->assignedDepartments->isNotEmpty() 
                                                ? $counselor->assignedDepartments 
                                                : ($counselor->assignedDepartment ? collect([$counselor->assignedDepartment]) : collect());
                                            $initials = strtoupper(substr($counselor->first_name, 0, 1) . substr($counselor->last_name, 0, 1));
                                        @endphp
                                        <tr class="hover:bg-muted/20 transition-colors">
                                            <!-- Counselor -->
                                            <td class="py-4 px-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-full bg-primary/10 text-primary font-heading font-bold text-sm flex items-center justify-center shrink-0 border border-primary/20">
                                                        {{ $initials }}
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="font-semibold text-foreground text-base leading-tight">
                                                            {{ $counselor->first_name }} {{ $counselor->last_name }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- School -->
                                            <td class="py-4 px-4">
                                                @if($assignedDepts->isNotEmpty())
                                                    <div class="flex flex-wrap items-center gap-1.5 max-w-xs">
                                                        @foreach($assignedDepts as $dept)
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                                                                {{ $dept->name }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-muted text-foreground/50 border border-border">
                                                        Unassigned
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- Status -->
                                            <td class="py-4 px-4 whitespace-nowrap">
                                                @if($counselor->is_active)
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                        Active
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-muted text-foreground/60 border border-border" title="Deactivated by {{ $counselor->deactivatedBy?->first_name ?? 'Admin' }} at {{ $counselor->deactivated_at?->format('Y-m-d') }}">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-foreground/40"></span>
                                                        Inactive
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- Created -->
                                            <td class="py-4 px-4 whitespace-nowrap text-sm text-foreground/60">
                                                {{ $counselor->created_at->format('M d, Y') }}
                                            </td>

                                            <!-- Actions -->
                                            <td class="py-4 px-4 whitespace-nowrap text-right">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <!-- Edit Button -->
                                                    <button 
                                                        type="button"
                                                        x-data="" 
                                                        x-on:click="$dispatch('open-modal', 'edit-account-{{ $counselor->id }}')" 
                                                        title="Edit Counselor"
                                                        aria-label="Edit Counselor {{ $counselor->first_name }} {{ $counselor->last_name }}"
                                                        class="p-2 text-foreground/60 hover:text-primary hover:bg-primary/10 rounded-lg transition-colors border border-border/70 hover:border-primary/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                        </svg>
                                                    </button>
                                                    
                                                    <!-- Password Reset Form -->
                                                    <form method="POST" action="{{ route('manage.accounts.password-reset', $counselor) }}" class="inline-block m-0"
                                                          data-confirm="save"
                                                          data-confirm-title="Send Password Reset"
                                                          data-confirm-message="Send a password reset link to {{ $counselor->email }}?"
                                                          data-confirm-button="Send Link">
                                                        @csrf
                                                        <button 
                                                            type="submit" 
                                                            title="Reset Password"
                                                            aria-label="Reset Password for {{ $counselor->first_name }} {{ $counselor->last_name }}"
                                                            class="p-2 text-foreground/60 hover:text-amber-700 hover:bg-amber-50 rounded-lg transition-colors border border-border/70 hover:border-amber-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                                                            </svg>
                                                        </button>
                                                    </form>

                                                    <!-- Toggle Status Form -->
                                                    <form method="POST" action="{{ route('manage.accounts.toggle-status', $counselor) }}" class="inline-block m-0"
                                                          data-confirm="{{ $counselor->is_active ? 'delete' : 'save' }}"
                                                          data-confirm-title="{{ $counselor->is_active ? 'Deactivate Account' : 'Reactivate Account' }}"
                                                          data-confirm-message="Are you sure you want to {{ $counselor->is_active ? 'deactivate' : 'reactivate' }} {{ $counselor->first_name }} {{ $counselor->last_name }}?{{ $counselor->is_active ? ' This counselor will immediately lose access to the portal.' : '' }}"
                                                          data-confirm-button="{{ $counselor->is_active ? 'Deactivate' : 'Reactivate' }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button 
                                                            type="submit"
                                                            title="{{ $counselor->is_active ? 'Deactivate Account' : 'Reactivate Account' }}"
                                                            aria-label="{{ $counselor->is_active ? 'Deactivate' : 'Reactivate' }} account for {{ $counselor->first_name }} {{ $counselor->last_name }}"
                                                            class="{{ $counselor->is_active ? 'text-red-600 border-border/70 hover:border-red-200 hover:bg-red-50' : 'text-emerald-600 border-border/70 hover:border-emerald-200 hover:bg-emerald-50' }} p-2 rounded-lg transition-colors border focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                                            @if($counselor->is_active)
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                                                                </svg>
                                                            @else
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                                                                </svg>
                                                            @endif
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Stacked Cards (below md: 768px) -->
                        <div class="md:hidden space-y-4">
                            @foreach($counselors as $counselor)
                                @php
                                    $assignedDepts = $counselor->assignedDepartments->isNotEmpty() 
                                        ? $counselor->assignedDepartments 
                                        : ($counselor->assignedDepartment ? collect([$counselor->assignedDepartment]) : collect());
                                    $initials = strtoupper(substr($counselor->first_name, 0, 1) . substr($counselor->last_name, 0, 1));
                                @endphp
                                <div class="p-4 rounded-xl border border-border/80 bg-muted/10 space-y-3">
                                    <div class="flex items-start justify-between gap-2.5">
                                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                            <div class="w-10 h-10 rounded-full bg-primary/10 text-primary font-heading font-bold text-sm flex items-center justify-center shrink-0 border border-primary/20">
                                                {{ $initials }}
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="font-semibold text-foreground text-sm sm:text-base leading-tight truncate">
                                                    {{ $counselor->first_name }} {{ $counselor->last_name }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="shrink-0 pt-0.5">
                                            @if($counselor->is_active)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-muted text-foreground/60 border border-border">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-foreground/40"></span>
                                                    Inactive
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div>
                                        <span class="text-xs font-semibold text-foreground/50 uppercase tracking-wider block mb-1">School:</span>
                                        @if($assignedDepts->isNotEmpty())
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                @foreach($assignedDepts as $dept)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                                                        {{ $dept->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-muted text-foreground/50 border border-border">
                                                Unassigned
                                            </span>
                                        @endif
                                    </div>

                                    <div class="pt-3 border-t border-border/50 flex flex-wrap items-center justify-between gap-2 text-xs text-foreground/60">
                                        <span>Added {{ $counselor->created_at->format('M d, Y') }}</span>
                                        <div class="flex items-center gap-1.5">
                                            <!-- Edit -->
                                            <button 
                                                type="button"
                                                x-data="" 
                                                x-on:click="$dispatch('open-modal', 'edit-account-{{ $counselor->id }}')" 
                                                class="px-2.5 py-1 text-xs font-semibold text-foreground/70 hover:text-primary bg-white rounded-lg border border-border hover:border-primary/30">
                                                Edit
                                            </button>
                                            
                                            <!-- Reset Password -->
                                            <form method="POST" action="{{ route('manage.accounts.password-reset', $counselor) }}" class="inline-block m-0"
                                                  data-confirm="save"
                                                  data-confirm-title="Send Password Reset"
                                                  data-confirm-message="Send a password reset link to {{ $counselor->email }}?"
                                                  data-confirm-button="Send Link">
                                                @csrf
                                                <button 
                                                    type="submit" 
                                                    class="px-2.5 py-1 text-xs font-semibold text-foreground/70 hover:text-amber-700 bg-white rounded-lg border border-border hover:border-amber-200">
                                                    Reset
                                                </button>
                                            </form>

                                            <!-- Toggle Status -->
                                            <form method="POST" action="{{ route('manage.accounts.toggle-status', $counselor) }}" class="inline-block m-0"
                                                  data-confirm="{{ $counselor->is_active ? 'delete' : 'save' }}"
                                                  data-confirm-title="{{ $counselor->is_active ? 'Deactivate Account' : 'Reactivate Account' }}"
                                                  data-confirm-message="Are you sure you want to {{ $counselor->is_active ? 'deactivate' : 'reactivate' }} {{ $counselor->first_name }} {{ $counselor->last_name }}?{{ $counselor->is_active ? ' This counselor will immediately lose access to the portal.' : '' }}"
                                                  data-confirm-button="{{ $counselor->is_active ? 'Deactivate' : 'Reactivate' }}">
                                                @csrf
                                                @method('PATCH')
                                                <button 
                                                    type="submit" 
                                                    class="px-2.5 py-1 text-xs font-semibold rounded-lg border {{ $counselor->is_active ? 'text-red-600 bg-red-50/50 border-red-200' : 'text-emerald-600 bg-emerald-50/50 border-emerald-200' }}">
                                                    {{ $counselor->is_active ? 'Deactivate' : 'Reactivate' }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <!-- Proper Empty State -->
                        <div class="py-12 px-4 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-primary/10 text-primary flex items-center justify-center border border-primary/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                            </div>
                            <h4 class="font-heading font-bold text-lg text-foreground mb-1">No guidance counselors found</h4>
                            <p class="text-sm text-foreground/60 max-w-sm mx-auto mb-6">
                                Add your first guidance counselor account to enable student counseling and schedule management.
                            </p>
                            <button type="button" x-data x-on:click="$dispatch('open-modal', 'create-account')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-white rounded-xl shadow-xs hover:bg-primary/90 transition-colors font-semibold text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Add Counselor
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Edit Account Modals -->
            @foreach($counselors as $counselor)
                <x-modal name="edit-account-{{ $counselor->id }}" focusable maxWidth="lg">
                    <form method="POST" action="{{ route('manage.accounts.update', $counselor) }}" class="p-6 sm:p-8"
                          data-confirm="edit"
                          data-confirm-title="Edit Counselor Account">
                        @csrf
                        @method('PUT')
                        <div class="mb-6">
                            <h2 class="font-heading text-xl font-bold text-foreground">
                                Edit Counselor Account
                            </h2>
                            <p class="text-xs text-foreground/60 mt-1">Update profile information and assigned schools.</p>
                            <p class="text-xs text-foreground/50 mt-1 mb-2">
                                <span class="text-destructive font-bold" aria-hidden="true">*</span> Required field
                            </p>
                        </div>

                        <div class="space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name_{{ $counselor->id }}" class="block text-xs font-semibold text-foreground/70 mb-1 label-required">First Name</label>
                                    <input type="text" id="first_name_{{ $counselor->id }}" name="first_name" value="{{ old('first_name', $counselor->first_name) }}" class="block w-full px-4 py-2.5 rounded-xl border-border shadow-xs focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm transition-all" required>
                                </div>

                                <div>
                                    <label for="last_name_{{ $counselor->id }}" class="block text-xs font-semibold text-foreground/70 mb-1 label-required">Last Name</label>
                                    <input type="text" id="last_name_{{ $counselor->id }}" name="last_name" value="{{ old('last_name', $counselor->last_name) }}" class="block w-full px-4 py-2.5 rounded-xl border-border shadow-xs focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm transition-all" required>
                                </div>
                            </div>

                            <div>
                                <label for="email_{{ $counselor->id }}" class="block text-xs font-semibold text-foreground/70 mb-1 label-required">Email Address (Institutional)</label>
                                <input type="email" id="email_{{ $counselor->id }}" name="email" value="{{ old('email', $counselor->email) }}" class="block w-full px-4 py-2.5 rounded-xl border-border shadow-xs focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm transition-all" required>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-foreground/70 mb-2 label-required">Assigned Schools (Select one or more)</label>
                                @php
                                    $currentDeptIds = $counselor->assignedDepartments->pluck('id')->all();
                                    if ($counselor->department_id && !in_array($counselor->department_id, $currentDeptIds)) {
                                        $currentDeptIds[] = $counselor->department_id;
                                    }
                                @endphp
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-3 bg-muted/20 border border-border rounded-xl max-h-48 overflow-y-auto">
                                    @foreach($departments as $dept)
                                        <label class="flex items-center gap-2.5 text-sm text-foreground p-1.5 hover:bg-white rounded-lg cursor-pointer transition-colors">
                                            <input type="checkbox" name="department_ids[]" value="{{ $dept->id }}" 
                                                {{ in_array($dept->id, old('department_ids', $currentDeptIds)) ? 'checked' : '' }}
                                                class="rounded border-border text-primary focus:ring-primary/20">
                                            <span class="text-xs font-medium">{{ $dept->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex items-center justify-end gap-3 pt-4 border-t border-border/60">
                            <button type="button" x-on:click="$dispatch('close')" class="px-5 py-2.5 bg-white text-foreground/70 rounded-xl border border-border shadow-xs hover:bg-muted/50 transition-colors font-medium text-sm">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-xl shadow-xs hover:bg-primary/90 transition-colors font-semibold text-sm">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </x-modal>
            @endforeach

            <!-- Create Account Modal -->
            <x-modal name="create-account" :show="$errors->hasAny(['first_name', 'last_name', 'email', 'department_id'])" focusable maxWidth="lg">
                <form method="POST" action="{{ route('manage.accounts.store') }}" class="p-6 sm:p-8"
                      data-confirm="save"
                      data-confirm-title="Add Guidance Counselor">
                    @csrf
                    <div class="mb-6">
                        <h2 class="font-heading text-xl font-bold text-foreground">
                            Add Guidance Counselor
                        </h2>
                        <p class="text-xs text-foreground/60 mt-1">Create a staff account and assign school responsibilities.</p>
                        <p class="text-xs text-foreground/50 mt-1 mb-2">
                            <span class="text-destructive font-bold" aria-hidden="true">*</span> Required field
                        </p>
                    </div>

                    <div class="space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="first_name" class="block text-xs font-semibold text-foreground/70 mb-1 label-required">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" class="block w-full px-4 py-2.5 rounded-xl border-border shadow-xs focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm transition-all" required>
                            </div>

                            <div>
                                <label for="last_name" class="block text-xs font-semibold text-foreground/70 mb-1 label-required">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" class="block w-full px-4 py-2.5 rounded-xl border-border shadow-xs focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm transition-all" required>
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-semibold text-foreground/70 mb-1 label-required">Email Address (Institutional)</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" class="block w-full px-4 py-2.5 rounded-xl border-border shadow-xs focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm transition-all" required>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-foreground/70 mb-2 label-required">Assigned Schools (Select one or more)</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-3 bg-muted/20 border border-border rounded-xl max-h-48 overflow-y-auto">
                                @foreach($departments as $dept)
                                    <label class="flex items-center gap-2.5 text-sm text-foreground p-1.5 hover:bg-white rounded-lg cursor-pointer transition-colors">
                                        <input type="checkbox" name="department_ids[]" value="{{ $dept->id }}" 
                                            {{ in_array($dept->id, old('department_ids', [])) ? 'checked' : '' }}
                                            class="rounded border-border text-primary focus:ring-primary/20">
                                        <span class="text-xs font-medium">{{ $dept->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 p-3.5 rounded-xl bg-primary/5 border border-primary/10">
                        <p class="text-xs text-foreground/70 leading-relaxed">
                            A secure random password will be generated automatically. The counselor will receive an institutional email containing a secure link to set their password.
                        </p>
                    </div>

                    <div class="mt-8 flex items-center justify-end gap-3 pt-4 border-t border-border/60">
                        <button type="button" x-on:click="$dispatch('close')" class="px-5 py-2.5 bg-white text-foreground/70 rounded-xl border border-border shadow-xs hover:bg-muted/50 transition-colors font-medium text-sm">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-xl shadow-xs hover:bg-primary/90 transition-colors font-semibold text-sm">
                            Add Counselor
                        </button>
                    </div>
                </form>
            </x-modal>
        </div>
    </div>
</x-staff-layout>
