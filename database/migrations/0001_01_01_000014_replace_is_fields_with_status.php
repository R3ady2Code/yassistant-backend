<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tenants: is_active → status
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('status')->default('active')->after('slug');
        });
        DB::table('tenants')->where('is_active', true)->update(['status' => 'active']);
        DB::table('tenants')->where('is_active', false)->update(['status' => 'inactive']);
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        // Channels: is_active → status
        Schema::table('channels', function (Blueprint $table) {
            $table->string('status')->default('inactive')->after('webhook_secret');
        });
        DB::table('channels')->where('is_active', true)->update(['status' => 'active']);
        DB::table('channels')->where('is_active', false)->update(['status' => 'inactive']);
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        // Conversations: is_closed → status
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('status')->default('open')->after('scenario_state');
        });
        DB::table('conversations')->where('is_closed', true)->update(['status' => 'closed']);
        DB::table('conversations')->where('is_closed', false)->update(['status' => 'open']);
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('is_closed');
        });

        // Scenarios: is_active → status
        Schema::table('scenarios', function (Blueprint $table) {
            $table->string('status')->default('active')->after('schema');
        });
        DB::table('scenarios')->where('is_active', true)->update(['status' => 'active']);
        DB::table('scenarios')->where('is_active', false)->update(['status' => 'inactive']);
        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        // Tenant Branches: is_active → status
        Schema::table('tenant_branches', function (Blueprint $table) {
            $table->string('status')->default('active')->after('phone');
        });
        DB::table('tenant_branches')->where('is_active', true)->update(['status' => 'active']);
        DB::table('tenant_branches')->where('is_active', false)->update(['status' => 'inactive']);
        Schema::table('tenant_branches', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        // Tenants: status → is_active
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('slug');
        });
        DB::table('tenants')->where('status', 'active')->update(['is_active' => true]);
        DB::table('tenants')->where('status', 'inactive')->update(['is_active' => false]);
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // Channels: status → is_active
        Schema::table('channels', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('webhook_secret');
        });
        DB::table('channels')->where('status', 'active')->update(['is_active' => true]);
        DB::table('channels')->where('status', 'inactive')->update(['is_active' => false]);
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // Conversations: status → is_closed
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('is_closed')->default(false)->after('scenario_state');
        });
        DB::table('conversations')->where('status', 'closed')->update(['is_closed' => true]);
        DB::table('conversations')->where('status', 'open')->update(['is_closed' => false]);
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // Scenarios: status → is_active
        Schema::table('scenarios', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('schema');
        });
        DB::table('scenarios')->where('status', 'active')->update(['is_active' => true]);
        DB::table('scenarios')->where('status', 'inactive')->update(['is_active' => false]);
        Schema::table('scenarios', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // Tenant Branches: status → is_active
        Schema::table('tenant_branches', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('phone');
        });
        DB::table('tenant_branches')->where('status', 'active')->update(['is_active' => true]);
        DB::table('tenant_branches')->where('status', 'inactive')->update(['is_active' => false]);
        Schema::table('tenant_branches', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
