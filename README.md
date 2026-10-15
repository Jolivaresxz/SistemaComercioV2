# SistemaComercio: Gateway y balanceo de lecturas

Esta práctica usa **una entrada pública** (API Gateway) y varias copias completas de SistemaComercio. Cada PC backend ejecuta Apache, PHP y su **propia copia idéntica** de MySQL. Solo se permiten consultas `GET`; crear, modificar o eliminar datos está deshabilitado durante la demostración.

```text
Navegador / Postman
        |
        v
PC Gateway: /api/... --> Registry (SQLite: servidores y actividad)
        |                         |
        +-- Discovery y algoritmo-+
        |
        +----> PC A: Apache + PHP + MySQL local (copia completa)
        +----> PC B: Apache + PHP + MySQL local (copia completa)
        +----> PC C: Apache + PHP + MySQL local (copia completa)
        +----> PC D: Apache + PHP + MySQL local (copia completa)
```

El Registry guarda `instance_id`, URL/IP del servidor, estado, huella de los datos y peticiones activas. **No guarda contraseñas de MySQL.** El Gateway envía una petición HTTP al backend elegido; `db_connect.php` de ese backend usa sus propias variables `DB_*` para conectarse a su MySQL local. No hay que conectar el Gateway directamente a la base de datos de otro PC.

## 1. Preparación común en todos los computadores

1. Conecten todos los PC a la misma red privada. Elijan uno como Gateway (puede ser también un backend). Anoten su IPv4 de LAN con `ipconfig`; no usen `127.0.0.1` ni `localhost` para comunicarse entre PC.
2. En cada PC, clonen el mismo commit de [SistemaComercioV2](https://github.com/Jolivaresxz/SistemaComercioV2) en `C:\xampp\htdocs\sistemaComercio\sistema_comercio` (o hagan `git pull` si ya está clonado). Inicien Apache y MySQL desde XAMPP. En `C:\xampp\apache\conf\httpd.conf`, verifiquen que `LoadModule rewrite_module modules/mod_rewrite.so` no esté comentada y habiliten `AllowOverride All` para la carpeta del proyecto, como en los ejemplos de abajo. Reinicien Apache.
3. Preparen la misma base `sistemacomercio` con las tablas `cliente`, `producto`, `pedido` y `pago` en **cada** MySQL. Desde `http://localhost/phpmyadmin`, una persona selecciona la base y usa **Exportar**; las demás crean la base con el mismo nombre y usan **Importar** con exactamente ese archivo. Comprueben que las cuatro tablas tengan la misma cantidad de filas. No hagan escrituras después de importar: la aplicación no sincroniza bases distintas.
4. Recomiendo un usuario MySQL local con permisos **solo `SELECT`** sobre `sistemacomercio`, en vez de usar `root`. En la pestaña SQL de phpMyAdmin de cada PC pueden ejecutar el siguiente ejemplo, cambiando la clave. Pongan ese usuario y clave en las variables de Apache `DB_USER` y `DB_PASSWORD`. El host puede quedarse en `localhost`; MySQL no necesita exponerse a la LAN.

```sql
CREATE USER 'usuario_lectura'@'localhost' IDENTIFIED BY 'UNA_CLAVE_LOCAL_SEGURA';
GRANT SELECT ON sistemacomercio.* TO 'usuario_lectura'@'localhost';
```
5. Permitan Apache en el firewall de Windows **solo en la red privada** y comprueben desde otro PC `http://IP_DEL_PC/sistemaComercio/sistema_comercio/health.php`. Debe responder `status: ok`, `instanceId` y `datasetHash`. Si responde 503, revisen Apache, MySQL, las tablas y `DB_*`.

El archivo de exportación SQL y las contraseñas **no se suben al repositorio**. Si se agrega, modifica o borra un dato en una sola copia, las huellas dejan de coincidir y el Registry la excluye. Para recuperar la práctica, restauren el mismo export en todos los PC y reinicien el registro con una base del Registry nueva; o definan explícitamente `EXPECTED_DATASET_HASH` con la huella correcta.

## 2. Variables de Apache en cada PC

Pueden definirlas en la configuración de Apache de XAMPP, dentro de un bloque `Directory` para la carpeta del proyecto, y reiniciar Apache. Usen valores propios; **no guarden los tokens ni las claves en Git**.

Ejemplo en el PC Gateway (`192.168.1.10`), que también actúa como backend:

```apache
<Directory "C:/xampp/htdocs/sistemaComercio/sistema_comercio">
    AllowOverride All
    Require all granted
    SetEnv GATEWAY_ENABLED "1"
    SetEnv REGISTRY_DB_PATH "C:/xampp/gateway-data/registry.sqlite"
    SetEnv REGISTRY_TOKEN "TOKEN_REGISTRY_COMPARTIDO"
    SetEnv BACKEND_GATEWAY_TOKEN "TOKEN_BACKEND_COMPARTIDO"
    SetEnv LOAD_BALANCER "random"
    SetEnv INSTANCE_ID "pc-gateway"
    SetEnv DB_HOST "localhost"
    SetEnv DB_NAME "sistemacomercio"
    SetEnv DB_USER "usuario_lectura"
    SetEnv DB_PASSWORD "CLAVE_MYSQL_LOCAL"
</Directory>
```

Antes de iniciar Apache, creen `C:\xampp\gateway-data` como carpeta **fuera de `htdocs`** y aseguren que la cuenta con que corre Apache pueda escribir allí. SQLite crea `registry.sqlite`, `registry.sqlite-wal` y `registry.sqlite-shm`. No compartan este archivo entre computadores.

Ejemplo para un PC backend (cambien ID y clave local en cada uno):

```apache
<Directory "C:/xampp/htdocs/sistemaComercio/sistema_comercio">
    AllowOverride All
    Require all granted
    SetEnv GATEWAY_ENABLED "0"
    SetEnv INSTANCE_ID "pc-producto"
    SetEnv BACKEND_GATEWAY_TOKEN "TOKEN_BACKEND_COMPARTIDO"
    SetEnv DB_HOST "localhost"
    SetEnv DB_NAME "sistemacomercio"
    SetEnv DB_USER "usuario_lectura"
    SetEnv DB_PASSWORD "CLAVE_MYSQL_LOCAL"
</Directory>
```

`TOKEN_REGISTRY_COMPARTIDO` se usa para registrar instancias y consultar actividad; `TOKEN_BACKEND_COMPARTIDO` impide que una persona consulte directamente las rutas antiguas de un backend. Usen dos valores largos y distintos. En esta LAN de clase se usa HTTP sin cifrado: **no envíen credenciales reales ni expongan el sistema a Internet**.

## 3. Registrar y descubrir instancias

En **cada** PC backend, abran PowerShell en la carpeta del proyecto y ejecuten (cambiando IP e ID):

```powershell
$env:REGISTRY_URL = 'http://192.168.1.10/sistemaComercio/sistema_comercio/registry'
$env:INSTANCE_BASE_URL = 'http://192.168.1.11/sistemaComercio/sistema_comercio'
$env:INSTANCE_ID = 'pc-producto'
$env:REGISTRY_TOKEN = 'TOKEN_REGISTRY_COMPARTIDO'
& 'C:\xampp\php\php.exe' bin\instance.php watch
```

Dejen abierta esa consola. `watch` registra el PC y envía un latido cada 15 segundos. Si se cierra, el Registry deja de ofrecer esa instancia después de 45 segundos. En el PC Gateway, hagan lo mismo si desean usarlo también como backend, con su propia IP e `INSTANCE_ID`.

Comprueben el Service Discovery desde un navegador:

```text
http://192.168.1.10/sistemaComercio/sistema_comercio/registry/discover
http://192.168.1.10/sistemaComercio/sistema_comercio/registry/instances
```

`discover` devuelve solo instancias `UP`, recientes y con la misma huella de datos. `instances` permite ver también `DOWN`, `STALE` y `MISMATCH`. Si un PC no aparece, revisen su consola `watch`, su `/health.php`, la red y que todos tengan **los mismos datos**.

## 4. Consultar por el Gateway

Usen **solo la IP del Gateway** en el navegador o Postman. No llamen a las rutas antiguas de los backend desde el cliente.

```text
GET http://192.168.1.10/sistemaComercio/sistema_comercio/api/clientes?algorithm=random
GET http://192.168.1.10/sistemaComercio/sistema_comercio/api/clientes/1?algorithm=ip_hash
GET http://192.168.1.10/sistemaComercio/sistema_comercio/api/productos?algorithm=least_connections
GET http://192.168.1.10/sistemaComercio/sistema_comercio/api/pedidos/cliente/1?algorithm=consistent_hashing
```

Rutas disponibles: `/api/clientes`, `/api/clientes/{id}`, `/api/clientes/rut/{rut}`, `/api/productos`, `/api/productos/{id}`, `/api/pedidos`, `/api/pedidos/{id}`, `/api/pedidos/cliente/{clienteId}`, `/api/pagos`, `/api/pagos/{id}` y `/api/pagos/pedido/{pedidoId}`.

Algoritmos aceptados en `?algorithm=` (si se omite, se usa `LOAD_BALANCER`, o `random`):

| Algoritmo | Qué hace | Qué se verá en clase |
| --- | --- | --- |
| `random` | Elige una instancia al azar. | Varias consultas pueden ir a PC distintos. |
| `ip_hash` | Para la misma IP cliente, intenta elegir siempre el mismo PC mientras el conjunto no cambia. | Cada estudiante puede repetir la consulta desde su propio PC. Si todos prueban desde el mismo navegador, todos tienen la misma IP. |
| `least_connections` | Elige el PC con menos peticiones **activas**; en empate, el primer ID. | Con consultas muy rápidas, el contador vuelve a cero inmediatamente: prueben peticiones simultáneas para ver la diferencia. |
| `consistent_hashing` | Usa un anillo con 64 nodos virtuales por PC. Las consultas por ID/RUT usan esa clave; los listados usan IP cliente. | La misma clave suele volver al mismo PC. Si uno cae, se reasigna a otro sin cambiar todos los mapeos. |

La respuesta incluye `X-Selected-Instance`, `X-Algorithm` y `X-Request-Id` en sus encabezados. La tabla `requests` de SQLite registra IP cliente, ruta, algoritmo, instancia, duración y estado `ACTIVE`, `COMPLETED`, `FAILED` o `TIMEOUT`; `instances.active_requests` muestra la carga actual. Pueden consultar los últimos 50 intentos con el token:

```powershell
$headers = @{ 'X-Registry-Token' = 'TOKEN_REGISTRY_COMPARTIDO' }
Invoke-RestMethod 'http://192.168.1.10/sistemaComercio/sistema_comercio/registry/activity' -Headers $headers
```

El contador mide **peticiones HTTP en curso**, no usuarios conectados ni sesiones permanentes. Guardar una actividad completada no hace que ese servidor permanezca ocupado.

## 5. Demostración de caída y comprobaciones

1. Comprueben que todos aparecen `UP` en `/registry/instances` y con `datasetHash` idéntico.
2. Consulten varias veces `/api/clientes` con cada algoritmo y miren los encabezados de respuesta y `/registry/activity`.
3. Detengan Apache de un backend (no del Gateway) o cierren su proceso. Consulten de nuevo por el Gateway. Si se había elegido ese PC, el intento queda `FAILED`, se marca `DOWN` y la misma lectura se reintenta en otro PC `UP`.
4. Reinicien Apache y `watch` para que vuelva a registrarse. Si su base cambió, aparecerá `MISMATCH` y no recibirá lecturas.
5. Prueben `POST` a una ruta antigua: debe responder `405`. Una consulta directa a un backend sin `X-Gateway-Token` debe responder `403` cuando `BACKEND_GATEWAY_TOKEN` está configurado.

Pruebas locales sin levantar toda la LAN, desde la carpeta del proyecto:

```powershell
& 'C:\xampp\php\php.exe' tests\registry_test.php
& 'C:\xampp\php\php.exe' tests\balancer_test.php
& 'C:\xampp\php\php.exe' tests\gateway_routes_test.php
```

Estas pruebas verifican lógica y persistencia. Antes de presentarlo, hagan **una prueba real de extremo a extremo en los PC del grupo**: el entorno de desarrollo no contiene sus bases MySQL ni sus IP de LAN.
