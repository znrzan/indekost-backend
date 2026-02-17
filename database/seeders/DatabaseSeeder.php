<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Admin (Superadmin)
        // System currently doesn't differentiate roles in Users table
        $admin = User::firstOrCreate(
            ['email' => 'admin@indekost.my.id'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $this->command->info('Admin created: ' . $admin->email);

        // 2. Create Owner
        $owner = User::firstOrCreate(
            ['email' => 'owner@indekost.my.id'],
            [
                'name' => 'Chandra Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $this->command->info('Owner created: ' . $owner->email);

        // 3. Create Rooms for Owner
        $room1 = Room::firstOrCreate(
            ['room_number' => '101', 'owner_id' => $owner->id],
            [
                'price' => 1500000,
                'status' => 'occupied',
            ]
        );

        Room::firstOrCreate(
            ['room_number' => '102', 'owner_id' => $owner->id],
            [
                'price' => 1750000,
                'status' => 'available',
            ]
        );
        $this->command->info('Rooms created for Owner: 101, 102');

        // 4. Create Tenant
        $tenant = Tenant::firstOrCreate(
            ['whatsapp_number' => '081234567890'],
            [
                'name' => 'Budi Tenant',
                'room_id' => $room1->id,
                'entry_date' => Carbon::parse('2024-01-01'),
                'status' => 'active',
            ]
        );
        $this->command->info('Tenant created: Budi Tenant');
        $this->command->info("  -> Tenant ID: {$tenant->id}");
        $this->command->info("  -> WhatsApp: {$tenant->whatsapp_number}");
        $this->command->info("  -> Login via Backend: tenant_id={$tenant->id}, whatsapp_number={$tenant->whatsapp_number}");
    }
}
