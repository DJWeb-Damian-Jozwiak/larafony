<x-layout title="404 - Not Found">
    <h1>404 - Page Not Found</h1>

    <p>
        <x-status-badge status="success" active="true">Framework Active</x-status-badge>
        <x-status-badge status="info" active="true">PSR-7/17</x-status-badge>
        <x-status-badge status="error" active="true">404 Error</x-status-badge>
    </p>

    <x-info-card>
        <h2>Page Not Found</h2>
        <p><strong>Requested Path:</strong> {{ $path }}</p>
        <p>The page you are looking for does not exist.</p>

        @slot('footer')
            <em>✨ This footer is rendered via named slot!</em>
        @endslot
    </x-info-card>

    <x-alert>
        <p>Error: The requested resource could not be found.</p>
    </x-alert>

    <ul>
        <li><a href="/">🏠 Go back home</a></li>
    </ul>
</x-layout>
