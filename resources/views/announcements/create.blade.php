<x-staff-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
            {{ __('Create Announcement') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl p-lg border border-border">
                <form action="{{ route('announcements.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="title" class="block text-sm font-medium text-foreground mb-1">Title</label>
                        <input type="text" name="title" id="title" class="input-field w-full" value="{{ old('title') }}" required>
                        @error('title') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="body" class="block text-sm font-medium text-foreground mb-1">Body</label>
                        <input id="body" type="hidden" name="body" value="{{ old('body') }}">
                        <trix-editor input="body" class="trix-content bg-white border border-border rounded-xl min-h-[200px]"></trix-editor>
                        @error('body') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="attachment" class="block text-sm font-medium text-foreground mb-1">Attachment (PDF, JPG, PNG - Max 5MB)</label>
                        <input type="file" name="attachment" id="attachment" class="input-field w-full" accept=".pdf,.jpg,.jpeg,.png">
                        @error('attachment') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-6">
                        <label for="published_at" class="block text-sm font-medium text-foreground mb-1">Publish Date (Leave blank to save as Draft)</label>
                        <input type="datetime-local" name="published_at" id="published_at" class="input-field w-full" value="{{ old('published_at') }}">
                        @error('published_at') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-4">
                        <a href="{{ route('announcements.index') }}" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Save Announcement</button>
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
