<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Section Confirmation | {{ config('app.name') }}</title>
        <meta name="description" content="Web-Based Student Individual Inventory Management System for the Guidance and Counseling Services of City College of Tagaytay.">
        <meta property="og:title" content="Section Confirmation | {{ config('app.name') }}">
        <meta property="og:description" content="Web-Based Student Individual Inventory Management System for the Guidance and Counseling Services of City College of Tagaytay.">
        <meta property="og:type" content="website">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-foreground bg-[#f4f7f4] antialiased min-h-screen flex flex-col justify-center items-center p-4 sm:p-6 selection:bg-primary/20">

        <div class="w-full max-w-xl mx-auto space-y-6">

            <!-- Logo & Brand Header -->
            <div class="text-center space-y-1.5">
                <img src="{{ asset('images/guidance-logo.png') }}" alt="CCT Guidance Logo" width="56" height="56" class="mx-auto h-14 w-auto mix-blend-multiply" />
                <h2 class="text-sm font-bold uppercase tracking-widest text-primary">City College of Tagaytay</h2>
                <p class="text-sm text-foreground/60">Guidance Counseling &amp; Wellness Portal</p>
            </div>

            <!-- Main Prompt Card -->
            <div class="bg-white rounded-2xl p-6 sm:p-8 border border-border shadow-xs space-y-6">

                <!-- Notification Badge -->
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-primary/10 text-primary font-semibold text-sm rounded-full border border-primary/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                    Academic Promotion
                </div>

                <!-- Greeting & Heading -->
                <div class="space-y-2">
                    <h1 class="font-heading text-2xl font-bold text-foreground">
                        You've been promoted to <span class="text-primary">{{ $user->year_level }} Year</span>!
                    </h1>
                    <p class="text-sm sm:text-base text-foreground/75 leading-relaxed">
                        Please select your official section for this academic year to update your student record and continue using the portal.
                    </p>
                </div>

                <!-- Student Info Pill -->
                <div class="bg-muted/30 rounded-xl p-4 border border-border flex flex-col sm:flex-row sm:items-center justify-between text-sm gap-3">
                    <div>
                        <span class="text-foreground/60 uppercase tracking-wider block font-semibold text-sm">Student Name</span>
                        <span class="font-bold text-foreground">{{ $user->first_name }} {{ $user->last_name }}</span>
                    </div>
                    <div>
                        <span class="text-foreground/60 uppercase tracking-wider block font-semibold text-sm">Student ID</span>
                        <span class="font-mono font-bold text-foreground">{{ $user->student_id }}</span>
                    </div>
                    <div>
                        <span class="text-foreground/60 uppercase tracking-wider block font-semibold text-sm">Program</span>
                        <span class="font-bold text-primary">{{ $user->program }}</span>
                    </div>
                </div>

                <!-- Errors -->
                @if ($errors->any())
                    <div class="bg-destructive/10 border-l-4 border-destructive p-3.5 rounded-r-xl">
                        <p class="text-sm text-destructive font-bold">{{ $errors->first() }}</p>
                    </div>
                @endif

                <!-- Section Selection Form -->
                <form action="{{ route('student.section-confirmation.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label for="section" class="block text-sm font-bold text-foreground mb-2">
                            Select Your Section <span class="text-destructive">*</span>
                        </label>
                        <select id="section" name="section" class="input-field w-full text-sm sm:text-base font-semibold py-3 cursor-pointer @error('section') border-destructive ring-1 ring-destructive @enderror" required autofocus>
                            <option value="">-- Choose your official section --</option>
                            @foreach ($validSections as $sectionOption)
                                <option value="{{ $sectionOption }}" {{ old('section') === $sectionOption ? 'selected' : '' }}>
                                    {{ $user->program }} {{ $sectionOption }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-sm text-foreground/60 mt-1.5">
                            Showing verified sections for <strong>{{ $user->program }}</strong> ({{ $user->year_level }} Year).
                        </p>
                    </div>

                    <div class="pt-1">
                        <button type="submit" class="btn-primary w-full py-3 text-sm font-bold flex items-center justify-center gap-2">
                            <span>Save Section &amp; Continue</span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Helpful Note -->
                <p class="text-sm text-center text-foreground/60">
                    Need help or cannot find your section? Please contact the Guidance Counseling Office.
                </p>
            </div>

            <!-- Logout option for the student -->
            <div class="text-center">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-foreground/60 hover:text-primary transition-colors underline font-medium cursor-pointer">
                        Log out of this account
                    </button>
                </form>
            </div>

        </div>

    </body>
</html>
