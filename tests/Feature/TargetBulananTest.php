<?php

namespace Tests\Feature;

use App\Models\Kantin;
use App\Models\SalesDetail;
use App\Models\SalesImport;
use App\Models\Tenant;
use App\Models\TenantTarget;
use App\Models\User;
use App\Filament\Resources\TargetBulanan\TargetBulananResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TargetBulananTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_view_and_is_scoped_to_their_target(): void
    {
        $kantin = Kantin::create(['name' => 'Kantin Gedung A']);
        $tenant1 = Tenant::create([
            'kantin_id' => $kantin->id,
            'name' => 'Ayam Geprek Bu Sri',
            'target_omzet' => 50000000,
        ]);
        $tenant2 = Tenant::create([
            'kantin_id' => $kantin->id,
            'name' => 'Kopi Kenangan',
            'target_omzet' => 60000000,
        ]);

        $target1 = TenantTarget::create([
            'tenant_id' => $tenant1->id,
            'year' => 2026,
            'month' => 10,
            'target_amount' => 50000000,
        ]);

        $target2 = TenantTarget::create([
            'tenant_id' => $tenant2->id,
            'year' => 2026,
            'month' => 10,
            'target_amount' => 60000000,
        ]);

        $user = User::factory()->create([
            'role' => 'tenant',
            'tenant_id' => $tenant1->id,
        ]);

        $this->actingAs($user);

        $query = TargetBulananResource::getEloquentQuery();
        $records = $query->get();

        $this->assertCount(1, $records);
        $this->assertEquals($target1->id, $records->first()->id);
    }

    public function test_calculates_month_revenue_and_achievement_correctly(): void
    {
        $kantin = Kantin::create(['name' => 'Kantin Gedung B']);
        $tenant = Tenant::create([
            'kantin_id' => $kantin->id,
            'name' => 'Bakso Pak Kumis',
            'target_omzet' => 10000000,
        ]);

        $target = TenantTarget::create([
            'tenant_id' => $tenant->id,
            'year' => (int) now()->year,
            'month' => (int) now()->month,
            'target_amount' => 10000000,
        ]);

        $import = SalesImport::create([
            'kantin_id' => $kantin->id,
            'file_name' => 'test.csv',
            'period_raw' => '01/10/2026 - 31/10/2026',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'total_rows' => 1,
            'total_amount' => 8500000,
        ]);

        SalesDetail::create([
            'sales_import_id' => $import->id,
            'tenant_id' => $tenant->id,
            'item_name' => 'Bakso Spesial',
            'qty' => 100,
            'gross_sales' => 8500000,
            'discount' => 0,
            'grand_total' => 8500000,
        ]);

        $actual = TargetBulananResource::calculateActualSales($target);
        $this->assertEquals(8500000, $actual);

        $achievement = ($actual / $target->target_amount) * 100;
        $this->assertEquals(85.0, $achievement);
    }
}
