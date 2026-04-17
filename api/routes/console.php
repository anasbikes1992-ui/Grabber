<?php

use App\Services\CashCommissionInvoiceService;
use App\Services\TaxiDriverScoringService;
use App\Services\TaxiQuestService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ── Taxi Sprint 3 Scheduled Jobs ─────────────────────────────────────────────

// Score all drivers weekly (Monday 02:00 UTC)
Schedule::call(fn() => app(TaxiDriverScoringService::class)->scoreAllDrivers())
    ->weeklyOn(1, '02:00')
    ->name('taxi:score-drivers')
    ->withoutOverlapping();

// Reset daily quests at midnight
Schedule::call(fn() => app(TaxiQuestService::class)->resetDailyQuests())
    ->daily()
    ->at('00:00')
    ->name('taxi:reset-daily-quests')
    ->withoutOverlapping();

// Reset weekly quests Monday 00:30 UTC
Schedule::call(fn() => app(TaxiQuestService::class)->resetWeeklyQuests())
    ->weeklyOn(1, '00:30')
    ->name('taxi:reset-weekly-quests')
    ->withoutOverlapping();

// Generate cash commission statements Monday 08:00
Schedule::call(fn() => app(CashCommissionInvoiceService::class)->generateWeeklyStatements())
    ->weeklyOn(1, '08:00')
    ->name('taxi:generate-commission-statements')
    ->withoutOverlapping();

// Suspend overdue drivers daily 09:00
Schedule::call(fn() => app(CashCommissionInvoiceService::class)->suspendOverdue())
    ->daily()
    ->at('09:00')
    ->name('taxi:suspend-overdue-drivers')
    ->withoutOverlapping();

