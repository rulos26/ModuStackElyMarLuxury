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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insertar configuraciones por defecto
        DB::table('app_settings')->insert([
            [
                'key' => 'app_name',
                'value' => 'AdminLTE 3',
                'type' => 'string',
                'description' => 'Nombre de la aplicación',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'app_logo',
                'value' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzMiIGhlaWdodD0iMzMiIHZpZXdCb3g9IjAgMCAzMyAzMyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjMzIiBoZWlnaHQ9IjMzIiByeD0iNCIgZmlsbD0iIzAwN2JmZiIvPgo8dGV4dCB4PSIxNi41IiB5PSIyMCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjEwIiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+TDwvdGV4dD4KPC9zdmc+',
                'type' => 'string',
                'description' => 'Logo de la aplicación (base64 o URL)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'app_icon',
                'value' => 'fas fa-fw fa-tachometer-alt',
                'type' => 'string',
                'description' => 'Icono de la aplicación (FontAwesome)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'app_title_prefix',
                'value' => '',
                'type' => 'string',
                'description' => 'Prefijo del título de la aplicación',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'app_title_postfix',
                'value' => '',
                'type' => 'string',
                'description' => 'Postfijo del título de la aplicación',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
