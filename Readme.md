# BETA - Base para adaptar a sus necesidades
Funcionamiento parcial en 1.6,1.7 y listo para adaptar.

Nota: este modulo se deja inconcluso por irregularidades en las respuestas de TCC y queda liberado el código para que en un futuro cualquiera pueda aportar a concluirlo


# Prestashop modulo TCC
Permite integrar los servicios del Transportista TCC Colombia en su tienda.

## Cómo funciona?
En el formulario de direcciones del cliente comprueba si se selecciona en el selector de paises "Colombia" y agrega un selector de ciudades ocultando el input de ingreso de ciudad y el postcode con el fin de que en el proceso de pago el usuario cuente con el código DANE que sera el campo postcode y junto con los otros datos del pedido se hace un request al webservice de TCC por medio de un SoapClient para poder retornar las tarifas de TCC


## Nota
- Revisar/Actualizar el listado de estados DANE concuerde con el de el archivo `/carriertcc/views/js/dane.json`
- En el administrador verificar que en paises/Colombia este activado el código postal
- Es indispensable que los campos del formulario de envío tengan el id (#city,#id_country,#id_state,.postcode) de lo contrario revisar el archivo `/carriertcc/views/js/front.js` para hacer que estos sean encontrados.
- Al instalar el modulo encontrara la configuracion de este los campos para ingresar el usuario, la clave y mas datos proporcionados por TCC
