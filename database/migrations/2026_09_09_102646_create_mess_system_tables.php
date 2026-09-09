<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Users Table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('system_role', ['super_admin', 'user'])->default('user');
            $table->string('avatar')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // 2. Messes (Groups) Table
        Schema::create('messes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // Unique join code for mess
            $table->text('address')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Mess User (Pivot Table for Multi-mess & Mess Roles)
        Schema::create('mess_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained('messes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['manager', 'member'])->default('member');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['mess_id', 'user_id']); // One user can join a specific mess only once
        });

        // 4. Mess Invitations / Join Requests Table
        Schema::create('mess_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained('messes')->onDelete('cascade');
            $table->foreignId('invited_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();
        });

        // 5. Month/Cycle Management (প্রতি মাসের আলাদা হিসাবের জন্য)
        Schema::create('mess_months', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained('messes')->onDelete('cascade');
            $table->string('month_year'); // Format: YYYY-MM (e.g. 2026-09)
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->timestamps();
        });

        // 6. Deposits (টাকা জমা) Table
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained('messes')->onDelete('cascade');
            $table->foreignId('mess_month_id')->constrained('mess_months')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->date('deposit_date');
            $table->string('note')->nullable();
            $table->foreignId('approved_by')->constrained('users')->onDelete('cascade'); // Manager who approved
            $table->timestamps();
        });

        // 7. Bazars (বাজার খরচের তালিকা) Table
        Schema::create('bazars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained('messes')->onDelete('cascade');
            $table->foreignId('mess_month_id')->constrained('mess_months')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Market done by
            $table->date('bazar_date');
            $table->decimal('total_cost', 10, 2);
            $table->text('description')->nullable(); // Items list summary
            $table->timestamps();
        });

        // 8. Bazar Items Breakdown (বাজারের ক্যাটাগরি ও বিবরণ)
        Schema::create('bazar_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bazar_id')->constrained('bazars')->onDelete('cascade');
            $table->string('item_name');
            $table->decimal('quantity', 8, 2)->nullable();
            $table->string('unit')->nullable(); // kg, liter, pcs etc.
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });

        // 9. Daily Meals (দৈনিক মিলের হিসাব) Table
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained('messes')->onDelete('cascade');
            $table->foreignId('mess_month_id')->constrained('mess_months')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('meal_date');
            $table->decimal('breakfast', 4, 1)->default(0); // 0, 0.5, 1
            $table->decimal('lunch', 4, 1)->default(0);
            $table->decimal('dinner', 4, 1)->default(0);
            $table->decimal('total_meal', 5, 1)->default(0); // Auto summary breakfast+lunch+dinner
            $table->timestamps();

            $table->unique(['mess_id', 'user_id', 'meal_date']);
        });

        // 10. Fixed / Other Expenses (ঘর ভাড়া, ওয়াইফাই, বুয়া বিল ইত্যাদি)
        Schema::create('fixed_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mess_id')->constrained('messes')->onDelete('cascade');
            $table->foreignId('mess_month_id')->constrained('mess_months')->onDelete('cascade');
            $table->string('title'); // Rent, Wifi, Gas, Maid, Electric bill
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_expenses');
        Schema::dropIfExists('meals');
        Schema::dropIfExists('bazar_items');
        Schema::dropIfExists('bazars');
        Schema::dropIfExists('deposits');
        Schema::dropIfExists('mess_months');
        Schema::dropIfExists('mess_invitations');
        Schema::dropIfExists('mess_user');
        Schema::dropIfExists('messes');
        Schema::dropIfExists('users');
    }
};
