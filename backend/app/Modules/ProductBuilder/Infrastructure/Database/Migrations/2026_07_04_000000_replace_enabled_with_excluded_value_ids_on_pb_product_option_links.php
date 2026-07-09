<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Switches the product↔option-template pivot from an inclusion list
 * (`enabled_value_ids`, null = all) to an exclusion list (`excluded_value_ids`,
 * null/[] = none excluded). With exclusion semantics, values added to a
 * template AFTER it was linked to a product are inherited automatically —
 * the product only stores what it explicitly hides.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('pb_product_option_links', function (Blueprint $table): void {
            $table->jsonb('excluded_value_ids')->nullable()->after('legend'); // null/[] = no exclusions
        });

        // Convert existing inclusion lists: excluded = template values - enabled.
        DB::table('pb_product_option_links')
            ->whereNotNull('enabled_value_ids')
            ->orderBy('id')
            ->each(function (object $link): void {
                $enabled = json_decode((string) $link->enabled_value_ids, true) ?: [];
                $all = DB::table('pb_option_template_values')
                    ->where('template_id', $link->template_id)
                    ->pluck('id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();

                $excluded = array_values(array_diff($all, $enabled));

                DB::table('pb_product_option_links')
                    ->where('id', $link->id)
                    ->update(['excluded_value_ids' => $excluded === [] ? null : json_encode($excluded)]);
            });

        Schema::table('pb_product_option_links', function (Blueprint $table): void {
            $table->dropColumn('enabled_value_ids');
        });
    }

    public function down(): void
    {
        Schema::table('pb_product_option_links', function (Blueprint $table): void {
            $table->jsonb('enabled_value_ids')->nullable()->after('legend');
        });

        DB::table('pb_product_option_links')
            ->whereNotNull('excluded_value_ids')
            ->orderBy('id')
            ->each(function (object $link): void {
                $excluded = json_decode((string) $link->excluded_value_ids, true) ?: [];
                $all = DB::table('pb_option_template_values')
                    ->where('template_id', $link->template_id)
                    ->pluck('id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();

                $enabled = array_values(array_diff($all, $excluded));

                DB::table('pb_product_option_links')
                    ->where('id', $link->id)
                    ->update(['enabled_value_ids' => json_encode($enabled)]);
            });

        Schema::table('pb_product_option_links', function (Blueprint $table): void {
            $table->dropColumn('excluded_value_ids');
        });
    }
};
