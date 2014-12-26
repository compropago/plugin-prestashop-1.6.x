plugin-prestashop ComproPago / 1.5.x - 1.6.x
=================

## Instalación:

1. Subir por FTP o en .zip por el manejador de modulos de PrestaShop:
<ol>
<li> Subir por FTP el directorio "compropago" a el directorio de los módulos "modules" que se encuentra en el directorio raiz de su PrestaShop.
</li>
<li> Comprimir el directorio "compropago" en formato .zip y subirlo por el manejador de modulos de PrestaShop<br />
</li>
</ol>
	
3. En la administración de Prestashop (backoffice) ir a: **Módulos > Módulos** y buscar el nombre del módulo: **compropago** y dar click en "Instalar". Una vez instlado recibirá el siguiente mensaje: " Módulo(s) instalado correctamente."<br />

4. Agregar las llaves, ir al panel de administración de ComproPago (https://compropago.com/panel/configuracion), copiar y pegar las llaves, dentro de las configuraciones del modulo en el panel de administración de PrestaShop (backoffice)

5. Una vez ingresadas las llaves se puede proceder a la configuración del Webhook, quien será responsable de actualizar el estado de las ordenes de compra en automatico. 


## Configuración Webhook

1. Ir al area de Webhooks dentro del panel de administración de ComproPago (https://compropago.com/panel/webhooks)

2. Agregar una dirección Webhook e ingresar: <b> [direcciondetienda.com]</b>/modules/compropago/includes/webhook.php cambiando "[direcciondetienda.com]" por el nombre de dominio de su tienda

3. Una vez agregada la dirección, dar click en el botón "Probar", recibira un mensaje similar a "Pruebas: El webhook esta correctamente instalado."

