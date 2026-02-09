@props(['asset'])

@php
    $rootFolder = \App\Services\S3Service::getRootFolder();
    $allSegments = array_filter(explode('/', $asset->folder));

    // Build full paths for each segment
    $allPaths = [];
    $currentPath = '';
    foreach ($allSegments as $segment) {
        $currentPath = $currentPath ? $currentPath . '/' . $segment : $segment;
        $allPaths[] = $currentPath;
    }

    // If root folder is set, remove it from display
    $breadcrumbSegments = array_values($allSegments);
    $breadcrumbPaths = $allPaths;
    if ($rootFolder !== '' && count($breadcrumbSegments) > 0 && $breadcrumbSegments[0] === $rootFolder) {
        array_shift($breadcrumbSegments);
        array_shift($breadcrumbPaths);
    }
@endphp

@if(count($breadcrumbSegments) > 0)
<nav class="text-sm text-gray-500 flex items-center">
    <!-- Full breadcrumb (hidden on small screens) -->
    <span class="hidden sm:flex items-center">
        @foreach($breadcrumbSegments as $index => $segment)
            <span class="mx-1 text-gray-400">/</span>
            <a href="{{ route('assets.index', ['folder' => $breadcrumbPaths[$index]]) }}"
               class="hover:text-orca-black transition-colors {{ $loop->last ? 'font-medium text-gray-700' : '' }}">
                {{ $segment }}
            </a>
        @endforeach
    </span>

    <!-- Collapsed breadcrumb (shown only on small screens) -->
    <span class="flex items-center sm:hidden">
        @if(count($breadcrumbSegments) > 1)
            <span class="mx-1 text-gray-400">/</span>
            <span class="text-gray-400">...</span>
        @endif
        <span class="mx-1 text-gray-400">/</span>
        <a href="{{ route('assets.index', ['folder' => end($breadcrumbPaths)]) }}"
           class="font-medium text-gray-700 hover:text-orca-black transition-colors">
            {{ end($breadcrumbSegments) }}
        </a>
    </span>
</nav>
@endif
