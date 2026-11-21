<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion')->nullable(); // dirección u observaciones
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->foreignId('sucursal_id')->nullable()->after('id')->constrained('sucursales')->nullOnDelete();
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->foreignId('sucursal_id')->nullable()->after('departamento_id')->constrained('sucursales')->nullOnDelete();
        });

        // Datos existentes: una sucursal por equipo (se pueden renombrar después)
        // y cada empleado toma la sucursal de su equipo principal.
        $ahora = now();
        foreach (DB::table('devices')->orderBy('id')->get() as $d) {
            $nombre = $d->nombre;
            $n = 2;
            while (DB::table('sucursales')->where('nombre', $nombre)->exists()) {
                $nombre = "{$d->nombre} ({$n})";
                $n++;
            }
            $id = DB::table('sucursales')->insertGetId([
                'nombre' => $nombre, 'descripcion' => $d->ubicacion, 'activo' => true,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
            DB::table('devices')->where('id', $d->id)->update(['sucursal_id' => $id]);
            DB::table('empleados')->where('device_id', $d->id)->update(['sucursal_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('empleados', fn (Blueprint $t) => $t->dropConstrainedForeignId('sucursal_id'));
        Schema::table('devices', fn (Blueprint $t) => $t->dropConstrainedForeignId('sucursal_id'));
        Schema::dropIfExists('sucursales');
    }
};
