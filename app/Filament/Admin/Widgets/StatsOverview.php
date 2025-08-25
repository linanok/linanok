<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Link;
use App\Models\User;
use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return auth()->user()->can('view link');
    }

    protected function getStats(): array
    {
        $totalLinks = Link::count();
        $totalUsers = User::count();
        $activeUsers = User::active()->count();

        // Calculate active links (not expired)
        $availableLinks = Link::available()->count();

        // Get unique visitors (by IP) in the last 30 days
        $uniqueVisitors = Visit::select('ip')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->distinct('ip')
            ->count();

        // Get unique visitors from the previous 30 days
        $previousMonthUniqueVisitors = Visit::select('ip')
            ->where('created_at', '>=', Carbon::now()->subDays(60))
            ->where('created_at', '<', Carbon::now()->subDays(30))
            ->distinct('ip')
            ->count();

        // Calculate trend percentage for unique visitors
        $uniqueVisitorsTrend = $previousMonthUniqueVisitors > 0
            ? (($uniqueVisitors - $previousMonthUniqueVisitors) / $previousMonthUniqueVisitors) * 100
            : 100;

        // Calculate trend percentages
        $previousMonthVisits = Visit::where('created_at', '>=', Carbon::now()->subDays(60))
            ->where('created_at', '<', Carbon::now()->subDays(30))
            ->count();

        $currentMonthVisits = Visit::where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();

        $visitsTrend = $previousMonthVisits > 0
            ? (($currentMonthVisits - $previousMonthVisits) / $previousMonthVisits) * 100
            : 100;

        // Get top performing link
        $topLink = Link::orderBy('visit_count', 'desc')->first();
        $topVisits = $topLink ? $topLink->visit_count : 0;
        $topLinkUrl = $topLink ? get_short_url($topLink) : '#';

        return [
            Stat::make('Total Links', number_format($totalLinks))
                ->description('Total shortened URLs created')
                ->descriptionIcon('heroicon-m-document')
                ->chart([
                    $totalLinks * 0.6, $totalLinks * 0.7, $totalLinks * 0.8, $totalLinks * 0.9, $totalLinks,
                ])
                ->color('primary')
                ->icon('heroicon-o-link'),

            Stat::make('Visits This Month', number_format($currentMonthVisits))
                ->description(sprintf('%+.1f%% from last month', $visitsTrend))
                ->descriptionIcon($visitsTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart([
                    $currentMonthVisits * 0.5, $currentMonthVisits * 0.6, $currentMonthVisits * 0.7, $currentMonthVisits * 0.8, $currentMonthVisits,
                ])
                ->color($visitsTrend >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-cursor-arrow-rays'),

            Stat::make('Available Links', number_format($availableLinks))
                ->description(sprintf('%d%% of total links', ($totalLinks > 0 ? round(($availableLinks / $totalLinks) * 100) : 0)))
                ->descriptionIcon('heroicon-m-check-badge')
                ->chart([
                    $availableLinks * 0.7, $availableLinks * 0.8, $availableLinks * 0.9, $availableLinks * 0.95, $availableLinks,
                ])
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Unique Visitors', number_format($uniqueVisitors))
                ->description(sprintf('%+.1f%% from last month', $uniqueVisitorsTrend))
                ->descriptionIcon($uniqueVisitorsTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart([
                    $uniqueVisitors * 0.6, $uniqueVisitors * 0.7, $uniqueVisitors * 0.8, $uniqueVisitors * 0.9, $uniqueVisitors,
                ])
                ->color($uniqueVisitorsTrend >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-users'),

            Stat::make('Top Performing Link', number_format($topVisits))
                ->description($topLink ? substr($topLink->original_url, 0, 30).'...' : 'No links yet')
                ->descriptionIcon('heroicon-m-star')
                ->url($topLinkUrl, shouldOpenInNewTab: true)
                ->color('warning')
                ->icon('heroicon-o-trophy'),

            Stat::make('Active Users', number_format($totalUsers))
                ->description(sprintf('%d%% of total users', round(($activeUsers / $totalUsers) * 100)))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->icon('heroicon-o-user-circle'),
        ];
    }
}
