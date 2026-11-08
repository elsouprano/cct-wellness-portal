@php
    $isStaff = auth()->user()->isCounselor() || auth()->user()->isAdmin();
@endphp
@section('title'){{ \Illuminate\Support\Str::limit($announcement->title, 35) }}@endsection
<x-dynamic-component :component="$isStaff ? 'staff-layout' : 'app-layout'">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                <a href="{{ route('announcements.index') }}" class="text-foreground/60 hover:text-primary transition-colors">&larr; Back to Announcements</a>
            </h2>
            @if($isStaff)
                <a href="{{ route('announcements.edit', $announcement) }}" class="btn-secondary flex items-center gap-2 text-sm">
                    Edit Announcement
                </a>
            @endif
        </div>
    </x-slot>

    <div class="{{ $isStaff ? 'py-12' : '' }}">
        <div class="{{ $isStaff ? 'max-w-7xl mx-auto sm:px-6 lg:px-8' : 'max-w-4xl mx-auto' }}">
            <div class="{{ $isStaff ? 'bg-white overflow-hidden shadow-sm sm:rounded-3xl p-lg border border-border' : 'bg-white overflow-hidden shadow-xs rounded-2xl p-6 sm:p-8 border border-border' }}">
                
                <div class="mb-6 border-b border-border pb-6">
                    <h1 class="text-2xl sm:text-3xl font-heading font-bold text-foreground mb-4">{{ $announcement->title }}</h1>
                    
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-sm font-semibold px-3 py-1 rounded-full {{ $announcement->published_at && $announcement->published_at <= now() ? 'bg-secondary/10 text-secondary' : 'bg-muted text-foreground/70' }}">
                            {{ $announcement->published_at && $announcement->published_at <= now() ? 'Published' : 'Draft / Scheduled' }}
                        </span>
                        <span class="text-sm text-foreground/60">
                            {{ $announcement->published_at ? $announcement->published_at->format('F d, Y \a\t h:i A') : 'No date set' }}
                            @if($announcement->published_at && $announcement->updated_at > $announcement->published_at->copy()->addMinutes(1))
                                <span class="italic text-foreground/40 text-sm ml-1">(Edited {{ $announcement->updated_at->format('M d, Y') }})</span>
                            @endif
                        </span>
                        <span class="text-sm text-foreground/40">&bull;</span>
                        <span class="text-sm font-medium text-primary">By {{ $announcement->user->first_name }} {{ $announcement->user->last_name }}</span>
                    </div>
                </div>

                <div class="prose prose-base sm:prose-lg max-w-none prose-a:text-accent hover:prose-a:text-accent/80 prose-img:rounded-xl">
                    {!! $announcement->body !!}
                </div>

                @if($announcement->attachment_path)
                    <div class="mt-10 pt-6 border-t border-border">
                        <h3 class="text-sm font-semibold text-foreground uppercase tracking-wider mb-4">Attachments</h3>
                        <a href="{{ Storage::url($announcement->attachment_path) }}" target="_blank" class="inline-flex items-center gap-3 px-4 py-3 bg-muted border border-border rounded-xl hover:bg-muted/80 hover:border-primary/30 transition-all group">
                            <div class="bg-white p-2 rounded-lg shadow-xs group-hover:shadow text-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM200,216H56V40h88V88a8,8,0,0,0,8,8h48V216ZM144,136v40a8,8,0,0,1-16,0V136a8,8,0,0,1,16,0Z"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-foreground group-hover:text-primary transition-colors">{{ $announcement->attachment_original_name }}</p>
                                <p class="text-sm text-foreground/60">Click to view / download</p>
                            </div>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-dynamic-component>
