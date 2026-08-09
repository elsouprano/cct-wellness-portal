<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="card max-w-md mx-auto text-center border border-border">
                <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-primary">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                    </svg>
                </div>
                
                <h1 class="font-heading text-3xl font-bold text-primary mb-2">Welcome, {{ auth()->user()->first_name }}</h1>
                <p class="text-foreground/70 mb-8">You have successfully logged into the Wellness Portal.</p>
                
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-primary w-full mt-4">Logout</button>
                </form>
                <div class="p-6 text-foreground mt-4 text-center font-medium">
                    {{ __("You're logged in!") }}
                </div>
            </div>

            @if(auth()->user()->role === 'student')
                <!-- Inventory Widget (Student Only) -->
                @php
                    $academicYear = '2025-2026';
                    $submission = \App\Models\InventorySubmission::where('user_id', auth()->user()->id)
                        ->where('academic_year', $academicYear)
                        ->first();
                    
                    $isSubmitted = $submission && $submission->submitted_at;
                @endphp
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-3xl p-lg border border-border">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-heading font-semibold text-foreground">3rd Year Individual Inventory</h3>
                    </div>
                    <p class="text-foreground/70 mb-6">Complete your required psychological assessment for the current academic year.</p>
                    
                    @if($isSubmitted)
                        <a href="{{ route('inventory.index') }}" class="btn-secondary inline-block">View my submission status</a>
                    @else
                        <a href="{{ route('inventory.index') }}" class="btn-primary inline-block bg-accent border-accent hover:bg-accent/90">Take the 3rd Year Individual Inventory</a>
                    @endif
                </div>
            @endif

            <!-- Announcements Widget -->
            <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-3xl p-lg border border-border">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-heading font-semibold text-foreground">
                        {{ auth()->user()->isCounselor() || auth()->user()->isAdmin() ? 'Manage Campus Announcements' : 'Campus Announcements' }}
                    </h3>
                    <a href="{{ route('announcements.index') }}" class="text-primary font-medium hover:underline text-sm">View all &rarr;</a>
                </div>
                <p class="text-foreground/70 mb-6">
                    {{ auth()->user()->isCounselor() || auth()->user()->isAdmin() ? 'Create and manage announcements for students.' : 'Stay up to date with the latest news and updates from the guidance office.' }}
                </p>
                <a href="{{ route('announcements.index') }}" class="btn-primary inline-block">
                    {{ auth()->user()->isCounselor() || auth()->user()->isAdmin() ? 'Go to Announcements Dashboard' : 'Go to Announcements Feed' }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
