<x-layout title="Larafony Framework Demo">
    <h1>Larafony Framework Demo</h1>

    <p>
        <x-status-badge status="success" active="true">Framework Active</x-status-badge>
        <x-status-badge status="info" active="true">PSR-7/17</x-status-badge>
        <x-status-badge status="warning" active="false">Debug Mode</x-status-badge>
    </p>

    <x-info-card>
        <h2>PSR-7/17 Implementation Active</h2>
        <p><strong>Request Method:</strong> {{ $method }}</p>
        <p><strong>Request URI:</strong> {{ $uri }}</p>
        <p><strong>Protocol:</strong> HTTP/{{ $protocol }}</p>
        <p><strong>Current Time:</strong> {{ $currentTime }}</p>

        @slot('footer')
            <em>✨ This footer is rendered via named slot!</em>
        @endslot
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
