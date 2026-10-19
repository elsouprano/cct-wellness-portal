@section('title', 'Edit Announcement')
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
                    
                    @include('announcements._form')

                    <div class="flex justify-end gap-4">
                        <a href="{{ route('announcements.index') }}" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Update Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-staff-layout>
