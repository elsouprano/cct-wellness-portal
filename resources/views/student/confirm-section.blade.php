<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CCT Wellness Portal') }} - Section Confirmation</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-foreground bg-background antialiased min-h-screen flex flex-col justify-center items-center p-4 sm:p-6 relative selection:bg-primary/20">

        <!-- Decorative organic background shapes -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none -z-10">
            <svg class="absolute -top-[10%] -left-[10%] w-[50%] h-[50%] text-primary opacity-5" fill="currentColor" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
              <path d="M44.7,-76.4C58.8,-69.2,71.8,-59.1,81.6,-46.3C91.4,-33.5,98,-18,97.4,-2.9C96.8,12.2,89,26.8,78.7,39.6C68.4,52.4,55.6,63.4,41.4,71.4C27.2,79.4,11.6,84.4,-3.6,89.5C-18.8,94.6,-33.6,99.8,-45.5,94.3C-57.4,88.8,-66.4,72.6,-73.4,57.2C-80.4,41.8,-85.4,27.2,-87.3,12C-89.2,-3.2,-88,-19,-81.1,-32C-74.2,-45,-61.6,-55.2,-48,-62.7C-34.4,-70.2,-19.8,-75,-4.4,-67.4C11,-59.8,22,-39.8,30.6,-47.1C39.2,-54.4,45.4,-74.6,44.7,-76.4Z" transform="translate(100 100)" />
            </svg>
            <svg class="absolute top-[40%] -right-[15%] w-[60%] h-[60%] text-accent opacity-5" fill="currentColor" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
              <path d="M38.1,-63.9C49.9,-54.4,60.5,-44,69.5,-31.6C78.5,-19.2,85.9,-4.8,85,9C84.1,22.8,74.9,36,63.6,46.5C52.3,57,38.9,64.8,24.8,69.7C10.7,74.6,-4.1,76.6,-18.2,74.1C-32.3,71.6,-45.7,64.6,-57.1,54.7C-68.5,44.8,-77.9,32,-82.1,17.7C-86.3,3.4,-85.3,-12.4,-79.1,-25.9C-72.9,-39.4,-61.5,-50.6,-48.6,-59.6C-35.7,-68.6,-21.3,-75.4,-7.1,-65.7C7.1,-56,26.3,-73.4,38.1,-63.9Z" transform="translate(100 100)" />
            </svg>
        </div>

        <div class="w-full max-w-xl mx-auto space-y-6">

            <!-- Logo & Brand Header -->
            <div class="text-center space-y-2">
                <img src="{{ asset('images/guidance-logo.png') }}" alt="CCT Guidance Logo" width="56" height="56" class="mx-auto h-14 w-auto mix-blend-multiply drop-shadow-sm" />
                <h2 class="text-sm font-bold uppercase tracking-widest text-primary">City College of Tagaytay</h2>
                <p class="text-xs text-foreground/60">Guidance Counseling & Wellness Portal</p>
            </div>

            <!-- Main Prompt Card -->
            <div class="bg-white rounded-3xl p-8 sm:p-10 border border-border shadow-xl space-y-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-primary via-accent to-secondary"></div>

                <!-- Celebration / Notification Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-primary/10 text-primary font-bold text-xs rounded-full uppercase tracking-wider">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                    Academic Promotion
                </div>

                <!-- Greeting & Heading -->
                <div class="space-y-2">
                    <h1 class="font-heading text-2xl sm:text-3xl font-bold text-foreground">
                        You've been promoted to <span class="text-primary">{{ $user->year_level }} Year</span>!
                    </h1>
                    <p class="text-sm sm:text-base text-foreground/75 leading-relaxed">
                        Please select your official section for this academic year to update your student record and continue using the portal.
                    </p>
                </div>

                <!-- Student Info Pill -->
                <div class="bg-muted/30 rounded-2xl p-4 border border-border/80 flex flex-col sm:flex-row sm:items-center justify-between text-xs gap-2">
                    <div>
                        <span class="text-foreground/50 uppercase tracking-wider block font-semibold">Student Name</span>
                        <span class="font-bold text-foreground">{{ $user->first_name }} {{ $user->last_name }}</span>
                    </div>
                    <div>
                        <span class="text-foreground/50 uppercase tracking-wider block font-semibold">Student ID</span>
                        <span class="font-mono font-bold text-foreground">{{ $user->student_id }}</span>
                    </div>
                    <div>
                        <span class="text-foreground/50 uppercase tracking-wider block font-semibold">Program</span>
                        <span class="font-bold text-primary">{{ $user->program }}</span>
                    </div>
                </div>

                <!-- Errors -->
                @if ($errors->any())
                    <div class="bg-destructive/10 border-l-4 border-destructive p-3.5 rounded-r-xl">
                        <p class="text-xs text-destructive font-bold">{{ $errors->first() }}</p>
                    </div>
                @endif

                <!-- Section Selection Form -->
                <form action="{{ route('student.section-confirmation.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label for="section" class="block text-sm font-bold text-foreground mb-2">
                            Select Your Section <span class="text-destructive">*</span>
                        </label>
                        <select id="section" name="section" class="input-field w-full text-base font-semibold py-3 cursor-pointer @error('section') border-destructive ring-1 ring-destructive @enderror" required autofocus>
                            <option value="">-- Choose your official section --</option>
                            @foreach ($validSections as $sectionOption)
                                <option value="{{ $sectionOption }}" {{ old('section') === $sectionOption ? 'selected' : '' }}>
                                    {{ $user->program }} {{ $sectionOption }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-foreground/60 mt-1.5">
                            Showing verified sections for <strong>{{ $user->program }}</strong> ({{ $user->year_level }} Year).
                        </p>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn-primary w-full py-3.5 text-base font-bold shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2">
                            <span>Save Section & Continue</span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Helpful Note -->
                <p class="text-[11px] text-center text-foreground/50">
                    Need help or cannot find your section? Please contact the Guidance Counseling Office.
                </p>
            </div>

            <!-- Logout option for the student -->
            <div class="text-center">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs text-foreground/60 hover:text-primary transition-colors underline font-medium">
                        Log out of this account
                    </button>
                </form>
            </div>

        </div>

    </body>
</html>
