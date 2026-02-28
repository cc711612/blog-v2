<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('articles', 'slug')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->string('slug', 180)->nullable()->after('id');
                $table->index('slug');
            });
        }

        DB::table('articles')
            ->select('id', 'title', 'slug')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    if (!Schema::hasColumn('articles', 'slug')) {
                        return;
                    }

                    $existing = trim((string) ($row->slug ?? ''));
                    $slug = $existing !== '' ? Str::slug($existing) : Str::slug((string) $row->title);
                    if ($slug === '') {
                        $slug = 'article-'.$row->id;
                    }

                    DB::table('articles')
                        ->where('id', $row->id)
                        ->update(['slug' => $slug]);
                }
            }, 'id');
    }

    public function down(): void
    {
        if (Schema::hasColumn('articles', 'slug')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropIndex(['slug']);
                $table->dropColumn('slug');
            });
        }
    }
};
