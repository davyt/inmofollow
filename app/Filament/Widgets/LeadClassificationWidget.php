<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class LeadClassificationWidget extends Widget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.lead-classification-widget';

    public function getViewData(): array
    {
        $user = auth()->user();

        $query = Lead::query();

        if ($user?->isAgent()) {
            $query->where('user_id', $user->id);
        }

        $rows = (clone $query)
            ->whereNotNull('ai_classification')
            ->select('ai_classification', DB::raw('COUNT(*) as count'))
            ->groupBy('ai_classification')
            ->orderByRaw("CASE WHEN ai_classification = 'sin_respuesta' THEN 1 ELSE 0 END DESC")
            ->orderByDesc('count')
            ->get();

        $total   = $rows->sum('count');
        $pending = (clone $query)->whereNull('ai_classification')->count();

        return compact('rows', 'total', 'pending');
    }
}
