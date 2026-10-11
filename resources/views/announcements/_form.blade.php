<div class="mb-4">
    <label for="title" class="block text-sm font-medium text-foreground mb-1">Title</label>
    <input type="text" name="title" id="title" class="input-field w-full" value="{{ old('title', $announcement->title ?? '') }}" required>
    @error('title') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
</div>

<div class="mb-4">
    <label for="body" class="block text-sm font-medium text-foreground mb-1">Body</label>
    <input id="body" type="hidden" name="body" value="{{ old('body', $announcement->body ?? '') }}">
    <trix-editor input="body" class="trix-content bg-white border border-border rounded-xl min-h-[200px]"></trix-editor>
    @error('body') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
</div>

<div class="mb-4 bg-muted p-4 rounded-xl border border-border">
    <label for="attachment" class="block text-sm font-medium text-foreground mb-2">Attachment (Optional, PDF/JPG/PNG max 5MB)</label>
    
    @if(isset($announcement) && $announcement->attachment_path)
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
    @if(isset($announcement) && $announcement->attachment_path)
        <p class="text-xs text-foreground/60 mt-1">Uploading a new file will replace the current one.</p>
    @endif
    @error('attachment') <span class="text-destructive text-sm">{{ $message }}</span> @enderror
</div>

<div class="mb-6 p-4 border border-border rounded-xl">
    <label class="block text-sm font-medium text-foreground mb-3">Publishing Status</label>
    
    <div class="space-y-3" x-data="{ status: '{{ old('status', isset($announcement) && $announcement->published_at !== null ? 'keep' : 'draft') }}' }">
        @if(isset($announcement) && $announcement->published_at !== null)
        <div>
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio" name="status" value="keep" x-model="status" class="text-primary border-border focus:ring-primary/20">
                <span class="ml-2 text-sm text-foreground">Keep current status</span>
            </label>
        </div>
        @endif
        
        <div>
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio" name="status" value="publish_now" x-model="status" class="text-primary border-border focus:ring-primary/20">
                <span class="ml-2 text-sm text-foreground">Publish Now</span>
            </label>
        </div>

        <div>
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio" name="status" value="schedule" x-model="status" class="text-primary border-border focus:ring-primary/20">
                <span class="ml-2 text-sm text-foreground">Schedule for later</span>
            </label>
            <div x-show="status === 'schedule'" class="mt-2 ml-6">
                <input type="datetime-local" name="scheduled_at" class="input-field text-sm" value="{{ old('scheduled_at', isset($announcement) && $announcement->published_at && $announcement->published_at > now() ? $announcement->published_at->format('Y-m-d\TH:i') : '') }}">
                @error('scheduled_at') <p class="text-destructive text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio" name="status" value="draft" x-model="status" class="text-primary border-border focus:ring-primary/20">
                <span class="ml-2 text-sm text-foreground">Save as Draft (Unpublished)</span>
            </label>
        </div>
    </div>
    @error('status') <p class="text-destructive text-sm mt-1">{{ $message }}</p> @enderror
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
<script type="text/javascript" src="https://unpkg.com/trix@2.0.8/dist/trix.umd.min.js" defer></script>
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener("trix-file-accept", function(event) {
        event.preventDefault(); // Disable file uploads inside Trix
    });
</script>
@endpush
