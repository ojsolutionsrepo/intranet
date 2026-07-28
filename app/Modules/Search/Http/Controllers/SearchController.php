<?php

namespace App\Modules\Search\Http\Controllers;

use App\Core\Modules\ModuleRegistry;
use App\Modules\Search\Services\SearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SearchController extends Controller
{
    public function index(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('search'), 404);

        return view('search::index');
    }

    public function suggest(ModuleRegistry $registry, Request $request, SearchService $search): JsonResponse
    {
        abort_unless($registry->isEnabled('search'), 404);

        $started = hrtime(true);
        $hits = $search->suggest($request->user(), (string) $request->query('q', ''));
        $took = (hrtime(true) - $started) / 1e6;

        return response()->json([
            'hits' => $hits->values(),
            'took_ms' => round($took, 2),
        ]);
    }
}
