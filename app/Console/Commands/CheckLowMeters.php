<?php

namespace App\Console\Commands;

use App\Models\Meter;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckLowMeters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meters:check-low
                            {--dry-run : Run command without sending actual notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for meters with low balance and send WhatsApp notifications';

    protected WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        parent::__construct();
        $this->whatsappService = $whatsappService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('🔍 Checking for low meter balances...');

        // Get all meters with low balance
        $lowMeters = Meter::with(['room.tenant', 'owner'])
            ->lowBalance()
            ->get();

        if ($lowMeters->isEmpty()) {
            $this->info('✅ No low balance meters found.');
            return self::SUCCESS;
        }

        $this->info("📊 Found {$lowMeters->count()} meter(s) with low balance.");
        $this->newLine();

        $sentCount = 0;
        $failedCount = 0;

        foreach ($lowMeters as $meter) {
            $tenant = $meter->room->tenant;

            if (!$tenant) {
                $this->warn("⚠️  Skipping meter ID {$meter->id} - Room {$meter->room->room_number} has no tenant");
                continue;
            }

            $this->line("Processing: Room {$meter->room->room_number} - {$meter->type}");
            $this->line("  Tenant: {$tenant->name}");
            $this->line("  Balance: {$meter->last_value} {$meter->unit} (Threshold: {$meter->threshold})");

            if ($isDryRun) {
                $this->info('  [DRY RUN] Notification would be sent to: ' . $tenant->whatsapp_number);
                $sentCount++;
            } else {
                $success = $this->whatsappService->sendLowMeterAlert(
                    $tenant->whatsapp_number,
                    [
                        'room_number' => $meter->room->room_number,
                        'type' => $meter->type,
                        'current' => number_format($meter->last_value, 2),
                        'threshold' => number_format($meter->threshold, 2),
                        'unit' => $meter->unit ?? 'unit',
                    ]
                );

                if ($success) {
                    $this->info('  ✅ Notification sent successfully');
                    $sentCount++;
                } else {
                    $this->error('  ❌ Failed to send notification');
                    $failedCount++;
                }
            }

            $this->newLine();
        }

        // Summary
        $this->info('📊 Summary:');
        $this->info("  Total Low Meters: {$lowMeters->count()}");
        $this->info("  Notifications Sent: {$sentCount}");
        
        if ($failedCount > 0) {
            $this->error("  Failed: {$failedCount}");
        }

        if ($isDryRun) {
            $this->warn('⚠️  This was a dry run. No actual notifications were sent.');
        }

        Log::info('CheckLowMeters command completed', [
            'total_low_meters' => $lowMeters->count(),
            'sent' => $sentCount,
            'failed' => $failedCount,
            'dry_run' => $isDryRun,
        ]);

        return self::SUCCESS;
    }
}
