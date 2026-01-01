<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Ajoute les colonnes manquantes NULLABLE d'abord
            if (!Schema::hasColumn('invoices', 'issue_date')) {
                $table->date('issue_date')->nullable()->after('invoice_number');
            }
            if (!Schema::hasColumn('invoices', 'due_date')) {
                $table->date('due_date')->nullable()->after('issue_date');
            }
            if (!Schema::hasColumn('invoices', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('invoices', 'amount')) {
                $table->decimal('amount', 10, 2)->default(0)->after('paid_at');
            }
            if (!Schema::hasColumn('invoices', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('invoices', 'currency')) {
                $table->string('currency', 3)->default('CHF')->after('tax_amount');
            }
            if (!Schema::hasColumn('invoices', 'status')) {
                $table->enum('status', ['pending', 'paid', 'overdue', 'canceled'])->default('pending')->after('currency');
            }
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['issue_date', 'due_date', 'paid_at', 'amount', 'tax_amount', 'currency', 'status']);
        });
    }
};