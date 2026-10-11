@props(['title' => null, 'wide' => false])

<x-auth-layout :title="$title" :wide="$wide" {{ $attributes }}>
    {{ $slot }}
    <x-logout-modal />
</x-auth-layout>
