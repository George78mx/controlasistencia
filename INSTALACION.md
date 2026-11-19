# Control de Asistencia – Hikvision MinMoe (DS-K1T344MBFWX-E1)

Laravel 12 · PHP 8.2 · MySQL/MariaDB · Spatie Permission · DomPDF · Laravel Excel.

## 1. Instalación (XAMPP en Windows)

```powershell
cd C:\xampp\htdocs\controlasistencia
composer install
copy .env.example .env      # solo si no existe .env
php artisan key:generate
```

En `.env` revisa:

```
APP_URL=http://localhost/controlasistencia
APP_TIMEZONE=America/Mexico_City
DB_CONNECTION=mysql
DB_DATABASE=controlasistencia
DB_USERNAME=root
DB_PASSWORD=
```

Crea la base de datos `controlasistencia` (utf8mb4) en phpMyAdmin y ejecuta:

```powershell
php artisan migrate --seed
php artisan storage:link
```

Usuario inicial: **admin@empresa.com / Admin123*** (cámbialo en *Mi perfil*).

### Publicarlo en Apache (XAMPP)

El proyecto vive en `C:\xampp\htdocs\controlasistencia` y abre en
**http://localhost/controlasistencia** (o `http://IP-del-servidor/controlasistencia`).
El `.htaccess` de la raíz manda todo a `public/` y bloquea `.env`, `vendor`, `storage`, etc.
Pon en `.env`: `APP_URL=http://localhost/controlasistencia`.

Opcional, para una URL propia (`http://asistencia.local`), agrega un VirtualHost en
`C:\xampp\apache\conf\extra\httpd-vhosts.conf` con `DocumentRoot "C:/xampp/htdocs/controlasistencia/public"`
y `AllowOverride All`, y cambia `APP_URL`.

## 2. Descarga automática (obligatorio)

Laravel revisa cada minuto qué equipo ya cumplió su intervalo y solo consulta ese.
Registra la tarea de Windows (en una consola como Administrador):

```powershell
schtasks /create /sc minute /mo 1 /tn "ControlAsistencia" /tr "C:\xampp\htdocs\controlasistencia\programador.bat" /ru SYSTEM /f
```

Alternativa mientras pruebas: `php artisan schedule:work` (deja la consola abierta).

Tareas programadas (`routes/console.php`):

| Tarea | Frecuencia |
|---|---|
| `hikvision:sincronizar` | revisa cada minuto; cada equipo según sus minutos configurados |
| `asistencia:calcular` | cada 15 min (pasa *pendientes* a *falta* y cierra el día) |
| `asistencia:calcular --desde=hace 7 días` | diario 02:30 |

Comandos manuales útiles:

```powershell
php artisan hikvision:sincronizar --forzar
php artisan hikvision:sincronizar --equipo=1 --desde="2026-11-01" --hasta="2026-11-19 23:59"
php artisan asistencia:calcular --desde=2026-11-01 --hasta=2026-11-19
```

## 3. Configuración de cada terminal (web del equipo)

1. **IP fija** dentro de la VPN y que el servidor la alcance (abre `http://IP` desde el servidor).
2. **Fecha y hora**: NTP activo, zona **UTC-06:00 Ciudad de México**, sin horario de verano.
3. **ISAPI habilitado**: *Configuración → Red → Servicio de red / Protocolo de integración*.
4. **Usuario operador** exclusivo para la plataforma (autenticación **Digest**).
5. **Asistencia** (*Time & Attendance*): modo manual/automático y horarios de entrada/salida para que cada checada llegue marcada.
6. **Empleados**: el *Employee No.* de la terminal debe ser igual al **No. de empleado** del catálogo.

Después, en la plataforma: *Equipos → Nuevo equipo* → IP, puerto, usuario y contraseña → **Probar conexión**.
Con **Consultar usuarios** puedes importar al catálogo los empleados ya registrados en la terminal.

### Tiempo real (opcional)
En el detalle del equipo se muestra una URL tipo `http://servidor/hikvision/eventos/{token}`.
Captúrala en la terminal en *Red → Servicio avanzado → HTTP Listening*. La descarga periódica
sigue activa para rellenar huecos si el servidor estuvo caído.

## 4. Cómo funciona

- **Solo lectura**: la plataforma únicamente usa `GET deviceInfo`, `POST AcsEvent` (búsqueda) y
  `POST UserInfo/Search` (búsqueda). No hay ninguna llamada que borre o modifique datos del equipo.
- **Sin duplicados**: índice único (equipo + No. empleado + fecha/hora). Cada consulta repite el
  *traslape* configurado hacia atrás para no perder checadas.
- **Cálculo diario**: primera checada = entrada, última = salida. Estatus posibles: asistencia,
  retardo, falta (o falta por retardo), justificada (incidencia), descanso, festivo, pendiente, sin horario.
- **Retardos**: tolerancia y minutos para falta por horario; *N retardos = 1 falta* en Configuración.
- **Turnos nocturnos**: si la salida es menor que la entrada (22:00–06:00), el turno termina al día siguiente.
- **Roles**: Administrador, Recursos Humanos, Supervisor y Consulta; permisos editables en *Roles y permisos*.

## 5. Subir a GitHub y desplegar en el servidor por SSH

En Git **no** se suben `.env`, `vendor/`, `public/storage` ni los logs (ver `.gitignore`).

En esta PC (primera vez):

```powershell
cd C:\xampp\htdocs\controlasistencia
git add .
git commit -m "Control de asistencia"
git remote add origin git@github.com:USUARIO/controlasistencia.git
git push -u origin main
```

En el servidor (primera vez), dentro de la carpeta web:

```bash
git clone git@github.com:USUARIO/controlasistencia.git
cd controlasistencia
composer install --no-dev --optimize-autoloader
cp .env.example .env        # y edita BD, APP_URL, APP_ENV=production, APP_DEBUG=false, APP_TIMEZONE
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
```

- Linux: da permisos de escritura al servidor web en `storage/` y `bootstrap/cache/`
  (`chown -R www-data:www-data storage bootstrap/cache`) y agrega al cron:
  `* * * * * cd /ruta/controlasistencia && php artisan schedule:run >> /dev/null 2>&1`
- Windows: registra `programador.bat` con `schtasks` (sección 2).

Actualizaciones posteriores en el servidor:

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
```

> Importante: `APP_KEY` cifra las contraseñas de los equipos guardadas en la base. Si cambias la
> llave, vuelve a capturar la contraseña de cada equipo.