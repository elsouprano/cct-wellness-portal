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

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Success Message -->
            @if(session('success'))
                <div class="bg-primary/10 border-l-4 border-primary p-4 rounded-xl shadow-sm">
                    <p class="text-primary font-medium">{{ session('success') }}</p>
                </div>
            @endif
            
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
            <div class="bg-surface shadow-sm sm:rounded-2xl border border-border/50 overflow-hidden">
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
                                                    class="text-primary hover:text-primary/80 transition-colors">
                                                    Edit
                                                </button>
                                                
                                                <!-- Password Reset Form -->
                                                <form method="POST" action="{{ route('manage.accounts.password-reset', $counselor) }}" class="m-0">
                                                    @csrf
                                                    <button type="submit" class="text-primary hover:text-primary/80 transition-colors" onclick="return confirm('Send a password reset link to this counselor?')">
                                                        Reset Password
                                                    </button>
                                                </form>

                                                <!-- Toggle Status Form -->
                                                <form method="POST" action="{{ route('manage.accounts.toggle-status', $counselor) }}" class="m-0">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="{{ $counselor->is_active ? 'text-error hover:text-error/80' : 'text-green-600 hover:text-green-800' }} transition-colors" onclick="return confirm('Are you sure you want to {{ $counselor->is_active ? 'deactivate' : 'reactivate' }} this account?')">
                                                        {{ $counselor->is_active ? 'Deactivate' : 'Reactivate' }}
                                                    </button>
                                                </form>
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
    @endforeach

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
