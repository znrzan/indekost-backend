<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsAppService
{
    protected string $baseUrl;
    protected string $session;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.waha.base_url', 'http://localhost:3000');
        $this->session = config('services.waha.session', 'default');
        $this->apiKey = config('services.waha.api_key');
    }

    /**
     * Get WAHA session status.
     */
    public function getSessionStatus(): ?array
    {
        try {
            $url = "{$this->baseUrl}/api/sessions/{$this->session}";
            
            $response = Http::timeout(5)->get($url);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        } catch (Exception $e) {
            Log::error('Failed to get WAHA session status', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if WAHA session is ready.
     */
    public function isSessionReady(): bool
    {
        $status = $this->getSessionStatus();
        return $status && ($status['status'] ?? '') === 'WORKING';
    }

    /**
     * Send WhatsApp text message via WAHA.
     *
     * @param string $to WhatsApp number in format 628xxx
     * @param string $message Message content
     * @return bool Success status
     */
    public function sendNotification(string $to, string $message): bool
    {
        try {
            // Format chatId for WAHA (628xxx@c.us)
            $chatId = $this->formatChatId($to);

            Log::info('Sending WhatsApp notification via WAHA', [
                'to' => $chatId,
                'message' => $message,
            ]);

            $url = "{$this->baseUrl}/api/sendText";
            
            $headers = ['Content-Type' => 'application/json'];
            if ($this->apiKey) {
                $headers['X-Api-Key'] = $this->apiKey;
            }

            $response = Http::withHeaders($headers)->post($url, [
                'session' => $this->session,
                'chatId' => $chatId,
                'text' => $message,
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp notification sent successfully', [
                    'to' => $chatId,
                    'response' => $response->json(),
                ]);
                return true;
            } else {
                Log::error('WhatsApp notification failed', [
                    'to' => $chatId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return false;
            }
        } catch (Exception $e) {
            Log::error('WhatsApp notification error', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Format phone number to WAHA chatId format (628xxx@c.us).
     *
     * @param string $number Phone number
     * @return string Formatted chatId
     */
    protected function formatChatId(string $number): string
    {
        // Remove any spaces, dashes, or special characters
        $number = preg_replace('/[^0-9]/', '', $number);

        // If number starts with 0, replace with 62
        if (str_starts_with($number, '0')) {
            $number = '62' . substr($number, 1);
        }

        // If number doesn't start with 62, add it
        if (!str_starts_with($number, '62')) {
            $number = '62' . $number;
        }

        // Add @c.us suffix for WAHA
        return $number . '@c.us';
    }

    /**
     * Send monthly billing notification with payment details.
     *
     * @param string $to WhatsApp number
     * @param array $data Payment data (tenant_name, room_number, amount, period, upload_link)
     * @return bool Success status
     */
    public function sendBillingNotification(string $to, array $data): bool
    {
        $message = "🏠 *Tagihan Kost Bulan {$data['period']}*\n\n";
        $message .= "Kepada Yth: {$data['tenant_name']}\n";
        $message .= "Kamar: {$data['room_number']}\n";
        $message .= "Tagihan: Rp " . number_format($data['amount'], 0, ',', '.') . "\n\n";
        $message .= "Silakan lakukan pembayaran dan upload bukti transfer melalui link berikut:\n";
        $message .= $data['upload_link'] . "\n\n";
        $message .= "Terima kasih! 🙏";

        return $this->sendNotification($to, $message);
    }

    /**
     * Send low meter balance alert notification.
     *
     * @param string $to WhatsApp number
     * @param array $data Meter data (room_number, type, current, threshold, unit)
     * @return bool Success status
     */
    public function sendLowMeterAlert(string $to, array $data): bool
    {
        $icon = $data['type'] === 'listrik' ? '⚡' : '💧';
        $typeLabel = ucfirst($data['type']);
        
        $message = "{$icon} *Peringatan Saldo Meter*\n\n";
        $message .= "Kamar: {$data['room_number']}\n";
        $message .= "Jenis: {$typeLabel}\n";
        $message .= "Saldo Saat Ini: {$data['current']} {$data['unit']}\n";
        $message .= "Batas Minimal: {$data['threshold']} {$data['unit']}\n\n";
        $message .= "⚠️ Saldo meter Anda sudah rendah! Segera lakukan pengisian ulang untuk menghindari terputusnya layanan.\n\n";
        $message .= "Terima kasih! 🙏";

        return $this->sendNotification($to, $message);
    }

    /**
     * Send new ticket notification to owner.
     *
     * @param string $to Owner's WhatsApp number
     * @param array $data Ticket data (room_number, tenant_name, title, priority, ticket_url)
     * @return bool Success status
     */
    public function sendNewTicketNotification(string $to, array $data): bool
    {
        $priorityIcon = [
            'low' => 'ℹ️',
            'medium' => '⚠️',
            'high' => '🚨',
        ];

        $icon = $priorityIcon[$data['priority']] ?? '🔧';
        
        $message = "{$icon} *Laporan Kerusakan Baru*\n\n";
        $message .= "Kamar: {$data['room_number']}\n";
        $message .= "Tenant: {$data['tenant_name']}\n";
        $message .= "Judul: {$data['title']}\n";
        $message .= "Prioritas: " . ucfirst($data['priority']) . "\n\n";
        $message .= "Lihat detail dan foto:\n";
        $message .= $data['ticket_url'] . "\n\n";
        $message .= "Mohon segera ditindaklanjuti. 🔧";

        return $this->sendNotification($to, $message);
    }

    /**
     * Send ticket resolved notification to tenant.
     *
     * @param string $to Tenant's WhatsApp number
     * @param array $data Ticket data (room_number, title)
     * @return bool Success status
     */
    public function sendTicketResolvedNotification(string $to, array $data): bool
    {
        $message = "✅ *Laporan Kerusakan Telah Diselesaikan*\n\n";
        $message .= "Kamar: {$data['room_number']}\n";
        $message .= "Judul: {$data['title']}\n\n";
        $message .= "Masalah telah diperbaiki. Mohon dicek kembali.\n\n";
        $message .= "Terima kasih atas laporannya! 🙏";

        return $this->sendNotification($to, $message);
    }
}
