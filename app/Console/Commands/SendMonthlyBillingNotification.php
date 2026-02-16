<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendMonthlyBillingNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:send-monthly-notification 
                            {--dry-run : Run without actually sending messages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send monthly billing notifications to active tenants via WhatsApp';

    protected WhatsAppService $whatsappService;

    /**
     * Create a new command instance.
     */
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
        $this->info('Starting monthly billing notification process...');

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No messages will be sent');
        }

        // Check WAHA session status first
        if (!$isDryRun) {
            $this->info('Checking WAHA session status...');
            $sessionStatus = $this->whatsappService->getSessionStatus();
            
            if (!$sessionStatus) {
                $this->error('Cannot connect to WAHA API. Please check WAHA_BASE_URL configuration.');
                return Command::FAILURE;
            }

            $this->info("WAHA Session: {$sessionStatus['name']} - Status: {$sessionStatus['status']}");

            if (!$this->whatsappService->isSessionReady()) {
                $this->error('WAHA session is not ready. Current status: ' . ($sessionStatus['status'] ?? 'unknown'));
                $this->error('Please ensure WhatsApp is connected in WAHA before running this command.');
                return Command::FAILURE;
            }

            $this->info('✓ WAHA session is ready!');
        }

        // Get current billing period (current month)
        $currentPeriod = Carbon::now()->format('Y-m');
        $this->info("Billing period: {$currentPeriod}");

        // Get all active tenants with their room and owner information
        $tenants = Tenant::with('room.owner')
            ->active()
            ->get();

        $this->info("Found {$tenants->count()} active tenants");

        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        $successCount = 0;
        $failCount = 0;

        foreach ($tenants as $tenant) {
            // Generate upload link using tenant domain from environment
            $tenantUrl = config('app.tenant_url', config('app.url'));
            $uploadLink = $tenantUrl . '/api/payments/upload-proof?tenant_id=' . $tenant->id . '&period=' . $currentPeriod;

            if ($isDryRun) {
                $this->newLine();
                $this->line("Would send to: {$tenant->name} ({$tenant->formatted_whatsapp})");
                $this->line("Room: {$tenant->room->room_number}");
                $this->line("Amount: Rp " . number_format($tenant->room->price, 0, ',', '.'));
                $successCount++;
            } else {
                // Send WhatsApp notification via WAHA
                $result = $this->whatsappService->sendBillingNotification(
                    $tenant->formatted_whatsapp,
                    [
                        'tenant_name' => $tenant->name,
                        'room_number' => $tenant->room->room_number,
                        'amount' => $tenant->room->price,
                        'period' => $currentPeriod,
                        'upload_link' => $uploadLink,
                    ]
                );

                if ($result) {
                    $successCount++;
                } else {
                    $failCount++;
                    $this->newLine();
                    $this->error("Failed to send to: {$tenant->name} ({$tenant->formatted_whatsapp})");
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info("=== Summary ===");
        $this->info("Total tenants: {$tenants->count()}");
        $this->info("Successfully sent: {$successCount}");
        
        if ($failCount > 0) {
            $this->error("Failed: {$failCount}");
        }

        if ($isDryRun) {
            $this->warn("This was a DRY RUN. No actual messages were sent.");
        }

        $this->info('Billing notification process completed!');

        return Command::SUCCESS;
    }
}
