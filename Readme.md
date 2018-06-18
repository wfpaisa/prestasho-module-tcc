# BETA
Testeado en Prestashop 1.7.

# Prestashop modulo TCC
Permite integrar los servicios del Transportista TCC Colombia en su tienda.

## Cómo funciona?
En el formulario de direcciones del cliente comprueba si se selecciona en el selector de paises "Colombia" y agrega un selector de ciudades 
ocultando el input de ingreso de ciudad y el postcode con el fin de que en el proceso de pago el usuario cuente con el código DANE que sera 
el campo postcode y junto con los otros datos del pedido se hace un request al webservice de TCC por medio de un SoapClient para poder 
retornar las tarifas de TCC.

Al valor retornado por TCC se le agrega los valores ingresados en las distintas zonas del transportista y costos adicionales del envío ubicado en los productos.

Dependiendo del Peso y Dimensiones se consulta al webservice:
- `>= 5 kilos`: Se consulta por paquetería
- `>= 20 x 20 x 20 Centímetros`: se consulta por paquetería `./carriertcc.php` linea ~ 340

La sumatoria de Pesos y Total del producto se redondea al mayor utilizando `ceils()` ver línea ~ 301:
- `0.4`kg -> `1`kg
- `$12000.34` -> `$12001`

## Nota
- En `Prestashop/Internacional/Ubicaciones geografias/Departamentos` buscar `Distrito Capital` y remplazarlo por `Bogotá, d.c.`.
- Es importante revisar que el nombre de los departamentos de Prestashop concuerde con los estados de `/carriertcc/views/js/dane.json`.
- En el administrador verificar que en paises/Colombia este activado el código postal.
- Es indispensable que los campos del formulario de envío tengan el id (#city,#id_country,#id_state,.postcode) de lo contrario revisar el archivo `/carriertcc/views/js/front.js` para hacer que estos sean encontrados.
- Al instalar el modulo encontrara la configuracion de este los campos para ingresar el usuario, la clave y mas datos proporcionados por TCC.
- En `Prestashop/Transporte/Transportadoras` Verificar que el transportista TCC este activado para las zonas de los departamentos de Colombia.
- Pedir a TCC acceder a la URL`http://clientes.tcc.com.co/servicios/liquidacionacuerdos.asmx?wsdl` y al método `consultarliquidacion`.