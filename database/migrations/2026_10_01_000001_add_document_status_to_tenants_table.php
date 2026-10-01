<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'nomor_halal')) {
                $table->string('nomor_halal')->nullable()->after('name');
            }
            if (!Schema::hasColumn('tenants', 'owner_name')) {
                $table->string('owner_name')->nullable()->after('nomor_halal');
            }
            if (!Schema::hasColumn('tenants', 'nik')) {
                $table->string('nik')->nullable()->after('owner_name');
            }
            if (!Schema::hasColumn('tenants', 'no_kk')) {
                $table->string('no_kk')->nullable()->after('nik');
            }
            if (!Schema::hasColumn('tenants', 'phone')) {
                $table->string('phone')->nullable()->after('no_kk');
            }
            if (!Schema::hasColumn('tenants', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('tenants', 'birth_place_date')) {
                $table->string('birth_place_date')->nullable()->after('address');
            }
            if (!Schema::hasColumn('tenants', 'file_ktp')) {
                $table->string('file_ktp')->nullable()->after('birth_place_date');
            }
            if (!Schema::hasColumn('tenants', 'file_kk')) {
                $table->string('file_kk')->nullable()->after('file_ktp');
            }
            if (!Schema::hasColumn('tenants', 'file_npwp')) {
                $table->string('file_npwp')->nullable()->after('file_kk');
            }
            if (!Schema::hasColumn('tenants', 'file_sertifikat_higenitas')) {
                $table->string('file_sertifikat_higenitas')->nullable()->after('file_npwp');
            }
            if (!Schema::hasColumn('tenants', 'document_status')) {
                $table->enum('document_status', ['belum_lengkap', 'lengkap'])->default('belum_lengkap')->after('file_sertifikat_higenitas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $columns = [
                'nomor_halal',
                'owner_name',
                'nik',
                'no_kk',
                'phone',
                'address',
                'birth_place_date',
                'file_ktp',
                'file_kk',
                'file_npwp',
                'file_sertifikat_higenitas',
                'document_status',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
