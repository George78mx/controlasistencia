<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->text('valor')->nullable();
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('departamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('puestos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->unsignedSmallInteger('tolerancia_minutos')->default(10);
            // Minutos de retardo a partir de los cuales el día cuenta como falta
            $table->unsignedSmallInteger('minutos_para_falta')->default(30);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('horario_dias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horario_id')->constrained('horarios')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana'); // 1 = lunes ... 7 = domingo (ISO)
            $table->boolean('laborable')->default(true);
            $table->time('entrada')->nullable();
            $table->time('salida')->nullable();
            $table->unique(['horario_id', 'dia_semana']);
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('ubicacion')->nullable();
            $table->string('ip');
            $table->unsignedInteger('puerto')->default(80);
            $table->boolean('usar_https')->default(false);
            $table->string('usuario');
            $table->text('password'); // cifrado
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('intervalo_minutos')->nullable(); // null = usar valor general
            $table->string('token_push', 64)->unique();
            $table->string('modelo')->nullable();
            $table->string('numero_serie')->nullable();
            $table->string('firmware')->nullable();
            $table->string('mac')->nullable();
            $table->boolean('en_linea')->default(false);
            $table->timestamp('ultima_conexion_at')->nullable();
            $table->timestamp('ultima_sincronizacion_at')->nullable();
            $table->timestamp('descargado_hasta')->nullable();
            $table->text('ultimo_error')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('device_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('tipo')->default('programada'); // programada, manual, push, prueba
            $table->string('estado'); // ok, error
            $table->timestamp('rango_desde')->nullable();
            $table->timestamp('rango_hasta')->nullable();
            $table->unsignedInteger('eventos_recibidos')->default(0);
            $table->unsignedInteger('eventos_nuevos')->default(0);
            $table->unsignedInteger('duracion_ms')->default(0);
            $table->text('mensaje')->nullable();
            $table->timestamps();
            $table->index(['device_id', 'created_at']);
        });

        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string('numero_empleado', 32)->unique(); // = employeeNo en la terminal
            $table->string('nombre');
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();
            $table->string('rfc', 13)->nullable();
            $table->string('curp', 18)->nullable();
            $table->string('nss', 11)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('genero', 1)->nullable();
            $table->string('email')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('direccion')->nullable();
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->nullOnDelete();
            $table->foreignId('puesto_id')->nullable()->constrained('puestos')->nullOnDelete();
            $table->foreignId('horario_id')->nullable()->constrained('horarios')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete(); // equipo principal
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_baja')->nullable();
            $table->boolean('activo')->default(true);
            $table->string('contacto_emergencia')->nullable();
            $table->string('telefono_emergencia', 20)->nullable();
            $table->string('foto')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('checadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->foreignId('empleado_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->string('numero_empleado', 32);
            $table->dateTime('fecha_hora');
            $table->unsignedBigInteger('serial_no')->nullable();
            $table->string('tipo')->nullable();   // attendanceStatus: checkIn, checkOut, breakOut...
            $table->string('metodo')->nullable(); // currentVerifyMode
            $table->unsignedSmallInteger('major')->nullable();
            $table->unsignedSmallInteger('minor')->nullable();
            $table->string('origen', 12)->default('consulta'); // consulta | push
            $table->json('raw')->nullable();
            $table->timestamps();
            $table->unique(['device_id', 'numero_empleado', 'fecha_hora'], 'checadas_unicas');
            $table->index(['empleado_id', 'fecha_hora']);
            $table->index(['numero_empleado', 'fecha_hora']);
            $table->index('serial_no');
        });

        Schema::create('dias_festivos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->string('descripcion');
            $table->timestamps();
        });

        Schema::create('tipos_incidencia', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 10)->unique();
            $table->string('nombre');
            $table->string('color', 7)->default('#6366f1');
            $table->boolean('con_goce')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('incidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->foreignId('tipo_incidencia_id')->constrained('tipos_incidencia');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('folio')->nullable();
            $table->text('motivo')->nullable();
            $table->string('documento')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['empleado_id', 'fecha_inicio', 'fecha_fin']);
        });

        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->date('fecha');
            $table->foreignId('horario_id')->nullable()->constrained('horarios')->nullOnDelete();
            $table->dateTime('entrada_programada')->nullable();
            $table->dateTime('salida_programada')->nullable();
            $table->dateTime('entrada')->nullable();
            $table->dateTime('salida')->nullable();
            $table->unsignedSmallInteger('num_checadas')->default(0);
            $table->unsignedInteger('minutos_retardo')->default(0);
            $table->unsignedInteger('minutos_salida_anticipada')->default(0);
            $table->unsignedInteger('minutos_trabajados')->default(0);
            // asistencia, retardo, falta, justificada, descanso, festivo, pendiente, sin_horario
            $table->string('estatus', 20);
            $table->boolean('falta_por_retardo')->default(false);
            $table->foreignId('incidencia_id')->nullable()->constrained('incidencias')->nullOnDelete();
            $table->string('observaciones')->nullable();
            $table->timestamps();
            $table->unique(['empleado_id', 'fecha']);
            $table->index(['fecha', 'estatus']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('password');
            $table->timestamp('ultimo_acceso_at')->nullable()->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['activo', 'ultimo_acceso_at']));
        foreach (['asistencias', 'incidencias', 'tipos_incidencia', 'dias_festivos', 'checadas', 'empleados',
            'device_sync_logs', 'devices', 'horario_dias', 'horarios', 'puestos', 'departamentos', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
