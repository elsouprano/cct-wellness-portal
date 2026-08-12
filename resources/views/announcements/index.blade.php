<x-dynamic-component :component="auth()->user()->isCounselor() || auth()->user()->isAdmin() ? 'staff-layout' : 'app-layout'">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
                {{ __('Announcements') }}
            </h2>
            @if(auth()->user()->isAdmin() || auth()->user()->isCounselor())
                <a href="{{ route('announcements.create') }}" class="btn-primary flex items-center gap-2 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M224,128a8,8,0,0,1-8,8H136v80a8,8,0,0,1-16,0V136H40a8,8,0,0,1,0-16h80V40a8,8,0,0,1,16,0v80h80A8,8,0,0,1,224,128Z"></path></svg>
                    New Announcement
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if(session('status'))
                <div class="bg-primary/10 border-l-4 border-primary text-primary p-4 rounded-r-xl" role="alert">
                    <p class="font-medium">{{ session('status') }}</p>
                </div>
            @endif

            @forelse($announcements as $announcement)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl p-lg border border-border flex flex-col md:flex-row gap-6 items-start">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-xs font-semibold px-3 py-1 rounded-full {{ $announcement->published_at && $announcement->published_at <= now() ? 'bg-secondary/10 text-secondary' : 'bg-muted text-foreground/70' }}">
                                {{ $announcement->published_at && $announcement->published_at <= now() ? 'Published' : 'Draft / Scheduled' }}
                            </span>
                            <span class="text-sm text-foreground/60">
                                {{ $announcement->published_at ? $announcement->published_at->format('M d, Y h:i A') : 'No date set' }}
                            </span>
                            <span class="text-sm text-foreground/40">&bull;</span>
                            <span class="text-sm text-foreground/60">By {{ $announcement->user->first_name }} {{ $announcement->user->last_name }}</span>
                        </div>
                        
                        <h3 class="text-2xl font-heading font-semibold text-foreground mb-4">
                            <a href="{{ route('announcements.show', $announcement) }}" class="hover:text-primary transition-colors">
                                {{ $announcement->title }}
                            </a>
                        </h3>
                        
                        <!-- Snippet of content -->
                        <div class="text-foreground/80 line-clamp-3 mb-4 prose prose-sm max-w-none">
                            {{ Str::limit(strip_tags($announcement->body), 200) }}
                        </div>

                        <div class="flex items-center gap-4">
                            <a href="{{ route('announcements.show', $announcement) }}" class="text-primary font-medium hover:underline text-sm">Read full announcement &rarr;</a>
                            
                            @if($announcement->attachment_path)
                                <span class="text-foreground/40 text-sm flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM200,216H56V40h88V88a8,8,0,0,0,8,8h48V216ZM144,136v40a8,8,0,0,1-16,0V136a8,8,0,0,1,16,0Z"></path></svg>
                                    Has attachment
                                </span>
                            @endif
                        </div>
                    </div>

                    @if(auth()->user()->isAdmin() || auth()->user()->isCounselor())
                        <div class="flex flex-row md:flex-col gap-2 shrink-0 w-full md:w-auto mt-4 md:mt-0">
                            <a href="{{ route('announcements.edit', $announcement) }}" class="btn-secondary text-sm px-4 py-2 text-center flex-1">Edit</a>
                            <form action="{{ route('announcements.destroy', $announcement) }}" method="POST" class="flex-1" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full bg-destructive/10 text-destructive border-2 border-destructive/20 hover:bg-destructive hover:text-white px-4 py-2 rounded-2xl font-semibold transition-all duration-200 cursor-pointer text-sm">Delete</button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl p-lg border border-border text-center py-16">
                    <svg class="mx-auto h-12 w-12 text-muted-foreground mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="text-lg font-medium text-foreground">No announcements yet</h3>
                    <p class="mt-1 text-sm text-foreground/60">Check back later for updates from the guidance office.</p>
                </div>
            @endforelse

            <div class="mt-6">
                {{ $announcements->links() }}
            </div>
        </div>
    </div>
</x-dynamic-component>
