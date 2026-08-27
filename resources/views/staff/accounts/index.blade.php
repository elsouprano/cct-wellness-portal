<x-staff-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-text-primary leading-tight">
                {{ __('Account Management') }}
            </h2>
            <button x-data="" x-on:click="$dispatch('open-modal', 'create-account')" class="px-4 py-2 bg-primary text-white rounded-xl shadow-sm hover:bg-primary/90 transition-colors">
                + Create Counselor
            </button>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Success Message -->

            
            <!-- Error messages -->
            @if($errors->any())
                <div class="bg-error/10 border-l-4 border-error p-4 rounded-xl shadow-sm mb-4">
                    <ul class="list-disc list-inside text-error font-medium">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Accounts Table -->
            <div class="bg-white shadow-sm sm:rounded-2xl border border-border/50 overflow-hidden">
                <div class="p-6 text-text-primary">
                    <h3 class="text-lg font-semibold mb-4">Guidance Counselors</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-border/50 text-left">
                            <thead>
                                <tr class="text-text-muted text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 font-medium">Name</th>
                                    <th class="px-4 py-3 font-medium">Email</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium">Created On</th>
                                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/50">
                                @forelse($counselors as $counselor)
                                    <tr class="hover:bg-background/50 transition-colors">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="font-medium text-text-primary">{{ $counselor->first_name }} {{ $counselor->last_name }}</div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-text-muted">{{ $counselor->email }}</div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if($counselor->is_active)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800" title="Deactivated by {{ $counselor->deactivatedBy?->first_name }} at {{ $counselor->deactivated_at?->format('Y-m-d') }}">
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-text-muted">
                                            {{ $counselor->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center justify-end gap-3">
                                                <!-- Edit Button -->
                                                <button 
                                                    x-data="" 
                                                    x-on:click="$dispatch('open-modal', 'edit-account-{{ $counselor->id }}')" 
                                                    class="px-3 py-1.5 border border-primary/20 rounded-lg text-primary hover:bg-primary/5 transition-colors">
                                                    Edit
                                                </button>
                                                
                                                <!-- Password Reset Form -->
                                                <form method="POST" action="{{ route('manage.accounts.password-reset', $counselor) }}" class="m-0">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1.5 border border-primary/20 rounded-lg text-primary hover:bg-primary/5 transition-colors" onclick="return confirm('Send a password reset link to this counselor?')">
                                                        Reset Password
                                                    </button>
                                                </form>

                                                <!-- Toggle Status Button -->
                                                <button 
                                                    x-data="" 
                                                    x-on:click="$dispatch('open-modal', 'toggle-status-{{ $counselor->id }}')" 
                                                    class="{{ $counselor->is_active ? 'text-red-600 border-red-200 hover:bg-red-50' : 'text-green-600 border-green-200 hover:bg-green-50' }} px-3 py-1.5 border rounded-lg transition-colors">
                                                    {{ $counselor->is_active ? 'Deactivate' : 'Reactivate' }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-text-muted">
                                            No guidance counselors found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Account Modals -->
    @foreach($counselors as $counselor)
        <x-modal name="edit-account-{{ $counselor->id }}" focusable maxWidth="lg">
            <form method="POST" action="{{ route('manage.accounts.update', $counselor) }}" class="p-8">
                @csrf
                @method('PUT')
                <h2 class="text-xl font-bold text-foreground mb-6">
                    Edit Counselor Account
                </h2>

                <div class="space-y-6">
                    <div>
                        <label for="first_name_{{ $counselor->id }}" class="block text-sm font-medium text-text-muted">First Name</label>
                        <input type="text" id="first_name_{{ $counselor->id }}" name="first_name" value="{{ old('first_name', $counselor->first_name) }}" class="mt-2 block w-full px-4 py-2.5 rounded-xl border-border shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" required>
                    </div>

                    <div>
                        <label for="last_name_{{ $counselor->id }}" class="block text-sm font-medium text-text-muted">Last Name</label>
                        <input type="text" id="last_name_{{ $counselor->id }}" name="last_name" value="{{ old('last_name', $counselor->last_name) }}" class="mt-2 block w-full px-4 py-2.5 rounded-xl border-border shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" required>
                    </div>

                    <div>
                        <label for="email_{{ $counselor->id }}" class="block text-sm font-medium text-text-muted">Email Address (Institutional)</label>
                        <input type="email" id="email_{{ $counselor->id }}" name="email" value="{{ old('email', $counselor->email) }}" class="mt-2 block w-full px-4 py-2.5 rounded-xl border-border shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" required>
                    </div>
                </div>

                                            <div class="mt-8 flex items-center justify-end gap-4">
                                                <button type="button" x-on:click="$dispatch('close')" class="px-5 py-2.5 bg-surface text-text-muted rounded-xl border border-border shadow-sm hover:bg-background transition-colors font-medium">
                                                    Cancel
                                                </button>
                                                <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-xl shadow-sm hover:bg-primary/90 transition-colors font-medium">
                                                    Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </x-modal>

                                    <!-- Toggle Status Modal -->
                                    <x-modal name="toggle-status-{{ $counselor->id }}" focusable maxWidth="sm">
                                        <form method="POST" action="{{ route('manage.accounts.toggle-status', $counselor) }}" class="p-8">
                                            @csrf
                                            @method('PATCH')
                                            
                                            <div class="flex flex-col items-center text-center space-y-4">
                                                <div class="w-16 h-16 rounded-full {{ $counselor->is_active ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600' }} flex items-center justify-center">
                                                    @if($counselor->is_active)
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                    @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    @endif
                                                </div>
                                                
                                                <h2 class="text-xl font-bold text-foreground">
                                                    {{ $counselor->is_active ? 'Deactivate Account' : 'Reactivate Account' }}
                                                </h2>
                                                
                                                <p class="text-text-muted">
                                                    Are you sure you want to {{ $counselor->is_active ? 'deactivate' : 'reactivate' }} the account for <strong class="text-foreground">{{ $counselor->first_name }} {{ $counselor->last_name }}</strong>?
                                                    @if($counselor->is_active)
                                                        <br><span class="text-sm mt-2 inline-block">They will no longer be able to log in to the portal.</span>
                                                    @endif
                                                </p>
                                            </div>

                                            <div class="mt-8 flex items-center justify-center gap-4 w-full">
                                                <button type="button" x-on:click="$dispatch('close')" class="flex-1 px-5 py-2.5 bg-white text-text-muted rounded-xl border border-border shadow-sm hover:bg-background transition-colors font-medium">
                                                    Cancel
                                                </button>
                                                <button type="submit" class="flex-1 px-5 py-2.5 {{ $counselor->is_active ? 'bg-destructive hover:bg-destructive/90' : 'bg-primary hover:bg-primary/90' }} text-white rounded-xl shadow-sm transition-colors font-medium">
                                                    Yes, {{ $counselor->is_active ? 'Deactivate' : 'Reactivate' }}
                                                </button>
                                            </div>
                                        </form>
                                    </x-modal>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-text-muted">
                                            No guidance counselors found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Account Modal -->
    <x-modal name="create-account" focusable maxWidth="lg">
        <form method="POST" action="{{ route('manage.accounts.store') }}" class="p-8">
            @csrf
            <h2 class="text-xl font-bold text-foreground mb-6">
                Create Guidance Counselor
            </h2>

            <div class="space-y-6">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-text-muted">First Name</label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" class="mt-2 block w-full px-4 py-2.5 rounded-xl border-border shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" required>
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-medium text-text-muted">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" class="mt-2 block w-full px-4 py-2.5 rounded-xl border-border shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" required>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-text-muted">Email Address (Institutional)</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="mt-2 block w-full px-4 py-2.5 rounded-xl border-border shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" required>
                </div>
            </div>

            <div class="mt-6">
                <p class="text-sm text-text-muted italic">
                    A secure random password will be generated automatically. The counselor will receive an email containing a link to set their password.
                </p>
            </div>

            <div class="mt-8 flex items-center justify-end gap-4">
                <button type="button" x-on:click="$dispatch('close')" class="px-5 py-2.5 bg-surface text-text-muted rounded-xl border border-border shadow-sm hover:bg-background transition-colors font-medium">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-xl shadow-sm hover:bg-primary/90 transition-colors font-medium">
                    Create Counselor
                </button>
            </div>
        </form>
    </x-modal>
</x-staff-layout>
