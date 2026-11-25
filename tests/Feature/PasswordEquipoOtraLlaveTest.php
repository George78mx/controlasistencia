<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use App\Services\HikvisionClient;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasswordEquipoOtraLlaveTest extends TestCase
{
    use RefreshDatabase;

    /** Simula una base copiada de otra instalación: la contraseña se cifró con otra APP_KEY. */
    private function equipoConOtraLlave(): Device
    {
        $equipo = Device::create(['nombre' => 'Bodyshop', 'ip' => '10.3.30.201', 'puerto' => 80, 'usuario' => 'admin', 'password' => 'x']);
        $otra = new Encrypter(random_bytes(32), 'aes-256-cbc');
        DB::table('devices')->where('id', $equipo->id)->update(['password' => $otra->encryptString('ClaveVieja1')]);
        return $equipo->fresh();
    }

    public function test_editar_el_equipo_reemplaza_la_contrasena_ilegible(): void
    {
        $equipo = $this->equipoConOtraLlave();
        $admin = User::create(['name' => 'Admin', 'email' => 'a@a.com', 'password' => 'x', 'activo' => true]);
        $admin->assignRole(Role::create(['name' => 'Administrador', 'guard_name' => 'web']));

        $this->actingAs($admin)->put(route('equipos.update', $equipo), [
            'nombre' => 'Bodyshop', 'ip' => '10.3.30.201', 'puerto' => 80, 'usuario' => 'admin',
            'password' => 'ClaveNueva2', 'activo' => '1',
        ])->assertRedirect(route('equipos.show', $equipo));

        $this->assertSame('ClaveNueva2', Crypt::decryptString(DB::table('devices')->value('password')));
        $this->assertSame('ClaveNueva2', $equipo->fresh()->passwordDescifrada());
    }

    public function test_editar_otros_datos_sin_contrasena_no_falla(): void
    {
        $equipo = $this->equipoConOtraLlave();
        $admin = User::create(['name' => 'Admin', 'email' => 'a@a.com', 'password' => 'x', 'activo' => true]);
        $admin->assignRole(Role::create(['name' => 'Administrador', 'guard_name' => 'web']));

        $this->actingAs($admin)->put(route('equipos.update', $equipo), [
            'nombre' => 'Bodyshop 2', 'ip' => '10.3.30.201', 'puerto' => 80, 'usuario' => 'admin', 'password' => '', 'activo' => '1',
        ])->assertRedirect();
        $this->assertSame('Bodyshop 2', $equipo->fresh()->nombre);
    }

    public function test_probar_conexion_explica_como_corregir(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('vuelve a capturar su contraseña');
        (new HikvisionClient($this->equipoConOtraLlave()))->deviceInfo();
    }
}
