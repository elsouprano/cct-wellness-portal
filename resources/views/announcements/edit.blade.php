<x-staff-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
            {{ __('Edit Announcement') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl p-lg border border-border">
                <form action="{{ route('announcements.update', $announcement) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-4">
                        <label for="title" class="block text-sm font-medium text-foreground mb-1">Title</label>
                        <input type="text" name="title" id="title" class="input-field w-full" value="{{ old('title', $announcement->title) }}" required>
                        @error('title') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="body" class="block text-sm font-medium text-foreground mb-1">Body</label>
                        <input id="body" type="hidden" name="body" value="{{ old('body', $announcement->body) }}">
                        <trix-editor input="body" class="trix-content bg-white border border-border rounded-xl min-h-[200px]"></trix-editor>
                        @error('body') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4 bg-muted p-4 rounded-xl border border-border">
                        <label for="attachment" class="block text-sm font-medium text-foreground mb-2">Attachment (Optional, PDF/JPG/PNG max 5MB)</label>
                        
                        @if($announcement->attachment_path)
                            <div class="mb-3 p-3 bg-white border border-border rounded-lg flex items-center justify-between">
                                <span class="text-sm font-medium flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM200,216H56V40h88V88a8,8,0,0,0,8,8h48V216ZM144,136v40a8,8,0,0,1-16,0V136a8,8,0,0,1,16,0Z"></path></svg>
                                    Current: {{ $announcement->attachment_original_name }}
                                </span>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="remove_attachment" value="1" class="rounded border-border text-destructive shadow-sm focus:ring-destructive/20" {{ old('remove_attachment') ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-destructive font-semibold">Remove</span>
                                </label>
                            </div>
                        @endif

                        <input type="file" name="attachment" id="attachment" class="input-field w-full bg-white" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-foreground/60 mt-1">Uploading a new file will replace the current one.</p>
                        @error('attachment') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-6">
                        <label for="published_at" class="block text-sm font-medium text-foreground mb-1">Publish Date (Leave blank to save as Draft)</label>
                        <input type="datetime-local" name="published_at" id="published_at" class="input-field w-full" value="{{ old('published_at', $announcement->published_at ? $announcement->published_at->format('Y-m-d\TH:i') : '') }}">
                        @error('published_at') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-4">
                        <a href="{{ route('announcements.index') }}" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Update Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/trix@2.0.8/dist/trix.css">
    <style>
        trix-toolbar [data-trix-button-group="file-tools"] {
            display: none;
        }
    </style>
    @endpush
    @push('scripts')
    <script type="text/javascript" src="https://unpkg.com/trix@2.0.8/dist/trix.umd.min.js"></script>
    <script nonce="{{ $cspNonce }}">
        document.addEventListener("trix-file-accept", function(event) {
            event.preventDefault(); // Disable file uploads inside Trix
        });
    </script>
    @endpush
</x-staff-layout>
