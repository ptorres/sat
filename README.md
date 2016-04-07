# sat
Programas para creacion y validación de XML, Facturas CFDI, Retenciones, Contabilidad electronica (SAT/SHCP/Mexico)

ce : Valildacion de contabilidad electronica (balanza y catalogo de cuentas)

xsd : Esquemas para validacion de forma y sintaxis

xslt : Reglas de transformacion para generar cadena original usando xsltproc

raiz :  Certificados raiz del SAT para validar que el sello sea con un certificado emitido por el SAT

cfdcvali.php : Programa en PHP para validacion de XML de CFD/CFDI y retenciones

satxmlvs32.php : Programa en PHP para generacion de XML de CFDI y sellarlo.

La validacion de semantica para el Complemento de Comercio Exterior valida
contra varios catalogos, esos los tengo yo en una tabla de mi base de datos.

En mi caso uso postgresql y creo la tabla con el comando

  $ psql -f pac_catalogos.sql

Para cargar los datos tengo un archivo de texto delimitado por TAB

  $ psql
  > \copy pac_catalogos from '/path/absoluto/a/pac_catalogos.cpy'


