@section('title', 'Edit Announcement')
<x-staff-layout>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-foreground leading-tight">
            {{ __('Edit Announcement') }}
        </h2>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl sm:rounded-3xl p-4 sm:p-8 border border-border">
                <form action="{{ route('announcements.update', $announcement) }}" method="POST" enctype="multipart/form-data" data-confirm="edit" data-confirm-title="Confirm Announcement">
                    @csrf
                    @method('PUT')
                    
                    @include('announcements._form')

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                        <a href="{{ route('announcements.index') }}" class="btn-secondary w-full sm:w-auto text-center">Cancel</a>
                        <button type="submit" class="btn-primary w-full sm:w-auto">Update Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-staff-layout>
