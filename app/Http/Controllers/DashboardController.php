<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_assets' => Asset::count(),
            'total_tags' => Tag::count(),
            'user_tags' => Tag::where('type', 'user')->count(),
            'ai_tags' => Tag::where('type', 'ai')->count(),
            'my_assets' => Asset::where('user_id', Auth::id())->count(),
            'total_users' => User::count(),
            'trashed_assets' => Asset::onlyTrashed()->count(),
        ];

        // Calculate total storage used
        $totalSize = Asset::sum('size');
        $stats['total_storage'] = $this->formatBytes($totalSize);

        // Get user role
        $user = Auth::user();
        $isAdmin = $user->isAdmin();

        // Editor-only stats
        if (! $isAdmin) {
            $myTags = Tag::whereHas('assets', fn ($q) => $q->where('user_id', $user->id))->get();
            $stats['my_tags'] = $myTags->count();
            $stats['my_user_tags'] = $myTags->where('type', 'user')->count();
            $stats['my_ai_tags'] = $myTags->where('type', 'ai')->count();

            $stats['items_per_page'] = $user->getItemsPerPage();
            $stats['items_per_page_is_default'] = $user->getPreference('items_per_page') === null;
        }

        return view('dashboard', compact('stats', 'isAdmin'));
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
