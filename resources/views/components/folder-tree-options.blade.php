@props(['folders', 'rootFolder'])

@php
    $rootPrefix = $rootFolder !== '' ? $rootFolder . '/' : '';

    // Build items with depth info
    $items = [];
    foreach ($folders as $folder) {
        $isRoot = ($folder === '' || ($rootFolder !== '' && $folder === $rootFolder));
        $relativePath = $isRoot ? '' : ($rootPrefix !== '' ? str_replace($rootPrefix, '', $folder) : $folder);
        $depth = $relativePath ? substr_count($relativePath, '/') + 1 : 0;

        $items[] = [
            'value' => $folder,
            'label' => $isRoot ? '/ (root)' : basename($folder),
            'depth' => $depth,
            'relativePath' => $relativePath,
            'isRoot' => $isRoot,
        ];
    }

    // Determine isLast: a folder is last if no later sibling exists at the same depth+parent
    foreach ($items as $i => &$item) {
        if ($item['isRoot']) {
            $item['isLast'] = false;
            continue;
        }

        $parentPath = dirname($item['relativePath']);
        $isLast = true;

        for ($j = $i + 1; $j < count($items); $j++) {
            $other = $items[$j];
            if ($other['isRoot']) continue;
            // Same depth and same parent = sibling
            if ($other['depth'] === $item['depth'] && dirname($other['relativePath']) === $parentPath) {
                $isLast = false;
                break;
            }
            // If we hit a shallower item, no more siblings possible
            if ($other['depth'] < $item['depth']) {
                break;
            }
        }

        $item['isLast'] = $isLast;
    }
    unset($item);

    // Render with a stack tracking isLast at each ancestor depth
    $stack = [];
@endphp

@foreach($items as $item)
    @if($item['isRoot'])
        <option value="{{ $item['value'] }}">{{ $item['label'] }}</option>
    @else
        @php
            // Trim stack to ancestor levels only, then push current
            $stack = array_slice($stack, 0, $item['depth'] - 1);
            $stack[] = $item['isLast'];

            // Build prefix from ancestor levels (use non-breaking spaces so browsers don't collapse them in <option>)
            $nbsp = "\u{00A0}";
            $prefix = '';
            for ($d = 0; $d < count($stack) - 1; $d++) {
                $prefix .= $stack[$d] ? $nbsp.$nbsp : '│'.$nbsp;
            }
            // Current level connector
            $prefix .= $item['isLast'] ? '└─' : '├─';
        @endphp
        <option value="{{ $item['value'] }}">{{ $prefix . $item['label'] }}</option>
    @endif
@endforeach
