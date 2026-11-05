# Monitor de Servidor con Laravel

Trabajo Práctico para la materia **Programación IV** (Cohorte 2026) - Universidad Tecnológica Nacional (UTN FRRe).  
Docente: Fernando Aguirre.

---

## Entorno y Sistema Operativo

- **Sistema Operativo utilizado:** Linux Mint (Ubuntu 24.04 LTS base / Kernel Linux 7.0 generic x86_64)
- **PHP:** 8.3.6 (cli)
- **Laravel:** 13.x

### Adaptación para Linux (Desafío Opcional)
El proyecto implementa soporte multiplataforma completo (`PHP_OS_FAMILY`):
- **En Windows:** Ejecuta el script PowerShell codificado en Base64 utilizando WMI (`Get-CimInstance`).
- **En Linux (Linux Mint):** Lee de manera nativa los pseudo-archivos del kernel de Linux:
  - Memoria: `/proc/meminfo` (`MemTotal` y `MemAvailable`).
  - Tiempo de encendido: `/proc/uptime`.
  - Temperatura CPU: `/sys/class/thermal/thermal_zone0/temp` (en miligrados Celsius, convertido a °C).
  - Uso de CPU (%): `/proc/stat` calculando el diferencial de actividad del procesador.

---

## Respuestas a las Preguntas de Evaluación

### 1. ¿Por qué la temperatura suele aparecer como "No disponible" en Windows? Prueben abrir la terminal como administrador y comparen.
En Windows, la consulta WMI para la temperatura utiliza el espacio de nombres `root/wmi` y la clase `MSAcpi_ThermalZoneTemperature`. Esta clase requiere **privilegios de Administrador** para acceder a sus propiedades debido a las directivas de seguridad de WMI; al correr una terminal estándar o un servidor de desarrollo como usuario común, la consulta falla silenciosamente con un error de acceso denegado (`Access Denied`). Además, en muchas computadoras de escritorio y motherboards modernas, el fabricante no implementa o expone la interfaz estándar de ACPI en WMI genérico, requiriendo drivers o software propietario de monitoreo.

### 2. ¿Por qué el script de PowerShell nunca debe incluir datos que vengan del usuario?
Porque generaría una vulnerabilidad crítica de **Inyección de Comandos (Command Injection / Arbitrary Code Execution)**. Si parámetros enviados por el usuario (mediante query strings, formularios o cabeceras) fueran concatenados dentro del comando o script de shell sin una desinfección estricta, un atacante podría inyectar operadores de shell (como `;`, `|`, `&`, `&&`) y ejecutar comandos arbitrarios con los permisos del proceso web del servidor, comprometiendo por completo el sistema operativo.

### 3. ¿Qué riesgo tiene dejar `/api/metrics` sin autenticación en un servidor real?
Existen dos riesgos principales:
1. **Fuga de información de infraestructura (Information Disclosure / Reconnaissance):** Un atacante externo puede conocer con precisión la versión de PHP (facilitando la búsqueda de exploits específicos), el sistema operativo, la arquitectura del servidor, los nombres de host internos y la disponibilidad de recursos.
2. **Denegación de Servicio (DoS):** Si el endpoint ejecuta comandos de sistema o lecturas pesadas, un atacante o botnet puede disparar miles de peticiones simultáneas consumiendo el CPU y la memoria del servidor, degradando o derribando el servicio para usuarios legítimos.

### 4. Con polling cada 5 segundos, ¿cuántos pedidos por minuto recibe el servidor si hay 50 personas mirando?
- Cada usuario realiza 1 petición cada 5 segundos = **12 peticiones por minuto** ($60 \text{ seg} / 5 \text{ seg} = 12$).
- Con 50 personas concurrentes:  
  $$50 \times 12 = \mathbf{600 \text{ peticiones por minuto}}$$ (es decir, una tasa sostenida de **10 peticiones por segundo**).

Esto demuestra la ineficiencia del polling HTTP tradicional ante un número creciente de clientes, justificando el uso de WebSockets (como Laravel Reverb) para notificaciones en tiempo real dirigidas por eventos.

### 5. Si una app solo necesita la temperatura, ¿qué datos recibe de más al llamar a `/api/metrics`?
Al consultar `/api/metrics`, el cliente recibe el payload completo:
- `hostname`
- `os`
- `php`
- `disk` (`total_gb`, `free_gb`, `used_pct`)
- `memory` (`total_mb`, `available_mb`, `used_pct`)
- `cpu_pct`
- `uptime_s`
- `at`

Todos estos campos son redundantes si solo se requiere `cpu_temp_c`. En diseño de APIs, este exceso de transferencia de datos innecesarios se denomina **over-fetching**, problema que tecnologías como **GraphQL** resuelven al permitir al cliente especificar de forma declarativa exactamente los campos requeridos.

---

## Rutas Disponibles

- `GET /servidor`: Vista web con tarjetas responsivas y actualización automática vía polling cada 5s.
- `GET /api/metrics`: Endpoint API REST JSON (limitado a 60 peticiones/minuto mediante `throttle:60,1`).
