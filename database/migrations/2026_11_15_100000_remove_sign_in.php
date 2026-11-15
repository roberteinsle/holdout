<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Installations from before the move to the home network still have the
     * sign-in tables and columns (passkeys, email links, invitations, TOTP,
     * roles). Remove whatever exists; fresh installations have none of it.
     */
    public function up(): void
    {
        Schema::dropIfExists('passkeys');
        Schema::dropIfExists('login_tokens');
        Schema::dropIfExists('invitations');

        $columns = array_values(array_filter(
            ['email', 'email_verified_at', 'role', 'totp_secret', 'totp_confirmed_at', 'totp_recovery_codes', 'totp_last_used_step'],
            fn (string $column): bool => Schema::hasColumn('users', $column),
        ));

        if ($columns !== []) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }

    public function down(): void
    {
        // Sign-in was removed on purpose; there is nothing to restore.
    }
};
