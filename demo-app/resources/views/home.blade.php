<x-layout title="Larafony Framework Demo">
    <h1>Larafony Framework Demo</h1>
    
    <x-info-card>
        <h2>PSR-7/17 Implementation Active</h2>
        <p><strong>Request Method:</strong> {{ $method }}</p>
        <p><strong>Request URI:</strong> {{ $uri }}</p>
        <p><strong>Protocol:</strong> HTTP/{{ $protocol }}</p>
        <p><strong>Current Time:</strong> {{ $currentTime }}</p>
    </x-info-card>

    <x-alert>
        <p>Error Handler is active. Try these endpoints:</p>
    </x-alert>

    <ul>
        <li><a href="/info">📊 View Request Info (JSON)</a></li>
        <li><a href="/error">⚠️ Trigger E_WARNING</a></li>
        <li><a href="/exception">💥 Trigger Exception</a></li>
        <li><a href="/fatal">☠️ Trigger Fatal Error</a></li>
    </ul>
</x-layout>
