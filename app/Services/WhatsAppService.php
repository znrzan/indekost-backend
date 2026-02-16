<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsAppService
{
    protected string $gatewayUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->gatewayUrl = config('services.whatsapp.gateway_url');
        $this->apiKey = config('services.whatsapp.api_key');
    }

    /**
     * Send WhatsApp notification to a phone number.
     *
     * @param string $to WhatsApp number (format: 628xxx)
     * @param string $message Message content
     * @return bool Success status
     */
    public function sendNotification(string $to, string $message): bool
    {
        try {
            // Ensure number is in correct format (628xxx)
            $to = $this->formatPhoneNumber($to);

            Log::info('Sending WhatsApp notification', [
                'to' => $to,
                'message' => $message,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->gatewayUrl, [
                'to' => $to,
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp notification sent successfully', [
                    'to' => $to,
                    'response' => $response->json(),
                ]);
                return true;
            } else {
                Log::error('WhatsApp notification failed', [
                    'to' => $to,
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
     * Format phone number to Indonesia WhatsApp format (628xxx).
     *
     * @param string $number Phone number
     * @return string Formatted number
     */
    protected function formatPhoneNumber(string $number): string
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

        return $number;
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
