<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\CheckStatus;
use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\Monitor;
use App\Models\StatusPage;
use App\Services\MonitorMetrics;
use Illuminate\Http\JsonResponse;

class PublicStatusController extends Controller
{
    private const UPTIME_DAYS = 90;

    /**
     * Public, unauthenticated status page. Exposes only presentation data —
     * never tokens, targets, user ids or other internals.
     */
    public function show(string $slug, MonitorMetrics $metrics): JsonResponse
    {
        $page = StatusPage::query()
            ->where('slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        $page->load([
            'monitors' => fn ($query) => $query->orderByPivot('sort_order')
                ->with(['latestCheck', 'openIncidents']),
        ]);

        $components = $page->monitors->map(fn (Monitor $monitor): array => [
            'name' => $monitor->pivot->display_name ?? $monitor->name,
            'status' => $this->componentStatus($monitor),
            'uptime' => $metrics->dailyUptime($monitor, self::UPTIME_DAYS),
        ])->values();

        $incidents = Incident::query()
            ->whereIn('monitor_id', $page->monitors->pluck('id'))
            ->with('updates')
            ->latest('opened_at')
            ->limit(20)
            ->get()
            ->map(fn (Incident $incident): array => [
                'cause' => $incident->cause,
                'opened_at' => $incident->opened_at->toIso8601String(),
                'closed_at' => $incident->closed_at?->toIso8601String(),
                'updates' => $incident->updates->map(fn (IncidentUpdate $update): array => [
                    'status' => $update->status->value,
                    'message' => $update->message,
                    'created_at' => $update->created_at?->toIso8601String(),
                ])->all(),
            ])->values();

        return response()->json([
            'title' => $page->title,
            'overall_status' => $this->overallStatus($components->pluck('status')->all()),
            'components' => $components,
            'incidents' => $incidents,
        ]);
    }

    /**
     * @return 'operational'|'down'|'unknown'
     */
    private function componentStatus(Monitor $monitor): string
    {
        if ($monitor->openIncidents->isNotEmpty()) {
            return 'down';
        }

        return match ($monitor->latestCheck?->status) {
            CheckStatus::Ok => 'operational',
            CheckStatus::Failed => 'down',
            default => 'unknown',
        };
    }

    /**
     * @param  list<string>  $statuses
     * @return 'operational'|'degraded'|'down'|'unknown'
     */
    private function overallStatus(array $statuses): string
    {
        if ($statuses === []) {
            return 'unknown';
        }

        if (in_array('down', $statuses, true)) {
            return count(array_filter($statuses, fn (string $s): bool => $s === 'down')) === count($statuses)
                ? 'down'
                : 'degraded';
        }

        return in_array('unknown', $statuses, true) ? 'unknown' : 'operational';
    }
}
