# SistemaComercio: guía fácil para el grupo

**No tienen que escribir las IP en una tabla ni editar Apache.** Cada PC se configura una vez con `1-configurar-servidor.bat` y se registra abriendo `2-registrar-servidor.bat`.

```text
Cliente → PC Gateway → Registry (¿qué PC está disponible?) → PC elegido → su MySQL local
```

Todos los PC tienen el **sistema completo** y una **copia idéntica de la base de datos**. Esta práctica es solo de lectura: `POST`, `PUT`, `PATCH` y `DELETE` están bloqueados. El Gateway **no se conecta a MySQL de los compañeros**; envía consultas HTTP al PC elegido. El Registry solo guarda direcciones, estado y actividad.

## Paso 1. Lo que preparan todos

1. Conecten todos los PC a la misma red privada y anoten su IPv4 con `ipconfig` (por ejemplo `192.168.1.11`). Elijan quién será el Gateway.
2. Pongan este repositorio en `C:\xampp\htdocs\sistemaComercio\sistema_comercio` en cada PC y enciendan **Apache y MySQL** en XAMPP.
3. En phpMyAdmin, una persona exporta la base `sistemacomercio`; cada compañero importa **ese mismo archivo** en su MySQL local. Deben existir las tablas `cliente`, `producto`, `pedido` y `pago` con los mismos datos. No cambien datos durante la práctica: no hay sincronización de escrituras.
4. Comprueben que desde otro PC se puede abrir `http://IP_DEL_PC/sistemaComercio/sistema_comercio/health.php`. Si el firewall pregunta, permitan Apache **solo en la red privada**. MySQL debe seguir local, sin abrir su puerto a la red.

Si `health.php` responde `503`, comprueben la base, las cuatro tablas y el usuario/clave de MySQL. Si alguna ruta `/api/...` responde `404` de Apache, activen `mod_rewrite` y `AllowOverride All` para la carpeta del proyecto en XAMPP.

## Paso 2. Primero configura el Gateway

En el PC elegido como Gateway, hagan doble clic en **`1-configurar-servidor.bat`**. Elijan opción `1` y respondan las preguntas: nombre del PC, su IPv4 y acceso a su MySQL local. La ruta web propuesta sirve si todos usaron la carpeta del paso 1.

Al terminar aparecen **dos tokens**. El dueño del Gateway los comparte con los compañeros por un canal privado. La configuración se guarda en `config.local.php`, que Git ignora; **no suban ese archivo ni los tokens a GitHub**.

El Gateway también puede participar como backend. Para eso, después de configurarlo, abre **`2-registrar-servidor.bat`** y deja esa ventana abierta.

## Paso 3. Cada compañero se registra

En **cada otro PC**, hagan doble clic en **`1-configurar-servidor.bat`**. Elijan opción `2` y escriban:

- Un nombre único, por ejemplo `pc-pedro`.
- La IPv4 **de ese PC**.
- La IPv4 **del Gateway**.
- El usuario y clave **de su MySQL local**.
- Los dos tokens entregados por el Gateway.

Después abran **`2-registrar-servidor.bat` y dejen esa ventana abierta**. Este es el registro: el programa avisa al Registry del Gateway cuál es la dirección del PC y luego manda un latido cada 15 segundos. Si se cierra la ventana, ese PC deja de estar disponible después de 45 segundos.

Por ejemplo, si el Gateway es `192.168.1.10`, abran en el navegador:

```text
http://192.168.1.10/sistemaComercio/sistema_comercio/registry/instances
```

Ahí deben aparecer todos. `UP` significa disponible; `DOWN` o `STALE`, desconectado; `MISMATCH`, datos distintos a los demás. **Las IP se registran automáticamente** en la base SQLite del Gateway; no se escriben a mano.

## Paso 4. Probar el balanceador

El cliente usa **solo la IP del Gateway**. Cambien la IP del ejemplo por la suya:

```text
http://192.168.1.10/sistemaComercio/sistema_comercio/api/clientes?algorithm=random
http://192.168.1.10/sistemaComercio/sistema_comercio/api/clientes?algorithm=ip_hash
http://192.168.1.10/sistemaComercio/sistema_comercio/api/clientes?algorithm=least_connections
http://192.168.1.10/sistemaComercio/sistema_comercio/api/clientes/1?algorithm=consistent_hashing
```

`random` elige al azar. `ip_hash` tiende a enviar la misma IP cliente al mismo PC. `least_connections` elige el PC con menos consultas **en curso**; las consultas breves terminan rápido, así que el contador suele volver a cero. `consistent_hashing` tiende a enviar la misma clave de consulta al mismo PC. En Postman o en la pestaña Red del navegador, el encabezado `X-Selected-Instance` muestra qué PC respondió.

También hay rutas `/api/productos`, `/api/pedidos`, `/api/pagos` y búsquedas por ID (por ejemplo `/api/productos/1`). El Registry guarda cada intento, su algoritmo, PC elegido y resultado en `requests`; las peticiones activas se cuentan en `instances.active_requests`. Si un PC elegido falla, el Gateway intenta otro disponible.

## Si algo no funciona

- **No aparece un PC:** miren su ventana `2-registrar-servidor.bat`, prueben su `/health.php`, revisen IP, firewall y tokens.
- **Sale `MISMATCH`:** importen de nuevo **el mismo** archivo de base de datos en todos. La primera copia registrada fija la huella esperada del Registry; si cambian el conjunto de datos, habrá que iniciar un Registry nuevo.
- **El asistente no encuentra PHP:** el lanzador espera XAMPP en `C:\xampp\php\php.exe`.
- **Ya habían configurado variables `SetEnv` en Apache:** esas variables tienen prioridad sobre `config.local.php`. Retiren el bloque viejo si quieren usar el asistente.
- **Seguridad:** usen datos de práctica y una red privada; la demostración usa HTTP, no HTTPS. Idealmente, el usuario MySQL local debe tener permiso solo `SELECT`.

Pruebas de la lógica sin los PC reales:

```powershell
& 'C:\xampp\php\php.exe' tests\local_config_test.php
& 'C:\xampp\php\php.exe' tests\registry_test.php
& 'C:\xampp\php\php.exe' tests\balancer_test.php
& 'C:\xampp\php\php.exe' tests\gateway_routes_test.php
```

Antes de presentarlo, hagan una prueba real con las IP, Apache y MySQL de todo el grupo. Las pruebas automáticas no sustituyen esa verificación.
