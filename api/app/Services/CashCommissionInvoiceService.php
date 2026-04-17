<?php

namespace App\Services;

use App\Models\TaxiCashCommissionInvoice;
use App\Models\TaxiTrip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashCommissionInvoiceService
{
    private const COMMISSION_RATE = 0.15; // 15%

    /**
     * Generate weekly cash commission statements for all drivers.
     * Called every Monday at 08:00 via scheduler.
     */
    public function generateWeeklyStatements(): void
    {
        $periodEnd   = now()->startOfWeek()->subDay()->endOfDay();
        $periodStart = $periodEnd->copy()->startOfWeek();

        // Get all drivers who had cash rides this week
        $drivers = TaxiTrip::where('payment_method', 'cash')
            ->where('status', 'completed')
            ->where('cash_paid', true)
            ->whereBetween('completed_at', [$periodStart, $periodEnd])
            ->select('driver_id')
            ->distinct()
            ->pluck('driver_id');

        foreach ($drivers as $driverId) {
            try {
                $this->generateStatement($driverId, $periodStart, $periodEnd);
            } catch (\Throwable $e) {
                Log::error("Cash commission statement failed for driver {$driverId}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Generate statement for one driver for one period.
     */
    public function generateStatement(string $driverId, $periodStart, $periodEnd): TaxiCashCommissionInvoice
    {
        $trips = TaxiTrip::where('driver_id', $driverId)
            ->where('payment_method', 'cash')
            ->where('status', 'completed')
            ->where('cash_paid', true)
            ->whereBetween('completed_at', [$periodStart, $periodEnd])
            ->get();

        $totalFares      = $trips->sum('final_fare');
        $commissionAmount = $totalFares * self::COMMISSION_RATE;
        $dueAt           = now()->addDays(7); // 7 days to pay

        $invoice = TaxiCashCommissionInvoice::updateOrCreate(
            [
                'driver_id'    => $driverId,
                'period_start' => $periodStart->toDateString(),
            ],
            [
                'period_end'        => $periodEnd->toDateString(),
                'total_cash_rides'  => $trips->count(),
                'total_cash_fares'  => round($totalFares, 2),
                'commission_rate'   => self::COMMISSION_RATE,
                'commission_amount' => round($commissionAmount, 2),
                'status'            => 'unpaid',
                'due_at'            => $dueAt,
            ]
        );

        // Update commission_amount on the individual trips
        TaxiTrip::whereIn('id', $trips->pluck('id'))->update([
            'commission_amount'   => DB::raw("final_fare * " . self::COMMISSION_RATE),
            'commission_invoiced' => true,
        ]);

        Log::info("Commission invoice generated: driver={$driverId} amount=LKR{$commissionAmount}");

        // TODO: dispatch email + push notification to driver

        return $invoice;
    }

    /**
     * Suspend drivers with overdue invoices > 7 days.
     * Called every day at 09:00 via scheduler.
     */
    public function suspendOverdue(): void
    {
        $overdueDriverIds = TaxiCashCommissionInvoice::where('status', 'unpaid')
            ->where('due_at', '<', now()->subDays(7))
            ->where('suspension_triggered', false)
            ->pluck('driver_id');

        foreach ($overdueDriverIds as $driverId) {
            DB::transaction(function () use ($driverId) {
                // Mark driver inactive
                User::where('id', $driverId)->update(['is_active' => false]);

                // Mark invoices as suspension triggered
                TaxiCashCommissionInvoice::where('driver_id', $driverId)
                    ->where('status', 'unpaid')
                    ->update(['suspension_triggered' => true, 'status' => 'overdue']);
            });

            Log::warning("Driver {$driverId} suspended due to overdue cash commission invoice.");
        }
    }

    /**
     * Mark an invoice as paid (called by admin when driver pays).
     */
    public function markPaid(string $invoiceId, string $paymentRef): TaxiCashCommissionInvoice
    {
        $invoice = TaxiCashCommissionInvoice::findOrFail($invoiceId);

        $invoice->update([
            'status'      => 'paid',
            'payment_ref' => $paymentRef,
            'paid_at'     => now(),
        ]);

        // Re-activate driver if they were suspended and have no other overdue invoices
        $otherOverdue = TaxiCashCommissionInvoice::where('driver_id', $invoice->driver_id)
            ->where('status', 'overdue')
            ->where('id', '!=', $invoiceId)
            ->exists();

        if (!$otherOverdue) {
            User::where('id', $invoice->driver_id)->update(['is_active' => true]);
        }

        return $invoice;
    }
}
