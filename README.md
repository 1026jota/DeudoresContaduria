## DEUDORES CONTADURIA

_Paquete composer para Laravel que resuelve si una persona aparece como deudora en la base de datos de la contaduria_

## Comenzando 🚀

_Estas instrucciones te permitirán obtener una copia del proyecto en funcionamiento._


### Instalación 🔧

_para instalar el paquete ejecute el siguiente comando en consola:_

```
composer require 1026jota/deudores-contaduria
npm install @nesk/puphpeteer
```
_después para publicar el archivo de configuración ejecuta siguiente comando:_

```
php artisan vendor:publish --provider='Jota\DeudoresContaduria\Providers\DeudoresContaduriaProviders'
```

_en el archivo config/contaduria.php se deben llenar los campos:_

```
    //en bash ejecutar whereis node y poner la ruta
    'node' => '',

    //para poder acceder debe crear un usuario en la contaduria
    'user' => '',
    'password' => ''
```

## USO

```
    use Jota\DeudoresContaduria\Classes\DeudoresContaduria;

    $cedula = 12345678
    $contaduria = new DeudoresContaduria();
    $contaduria->searchByCedula($cedula);
    return $contaduria->getResult();

```
## Ejemplo resultado

```
cuando la cédula no está reportada
[
  "is_registered" => false
  "response" => array:1 [
    "response" => "El documento de identificación número XXXXXXXXX NO está incluido en el BDME que publica la CONTADURIA GENERAL DE LA NACIÓN, de acuerdo con lo establecido en el artículo 2° de la Ley 901 de 2004."
  ]
]

cuando la cédula está arroja resultado
[
  "is_registered" => true
  "response" => array:1 [
    "response" => "El documento de identificación número XXXXXXX SI está incluido en el BDME que publica la CONTADURIA GENERAL DE LA NACIÓN, de acuerdo con lo establecido en el artículo 2° de la Ley 901 de 2004."
  ]
  "entidad_reportante" => " XXXXXXXX XXXXX"
  "info" => array:14 [
    0 => array:4 [
      "nombre_reportado" => "XXXXXX"
      "numero_obligacion" => "XXXX"
      "estado" => "Sin Leyenda"
      "fecha_corte" => "2021/05/31"
    ]
]

```

## Autores ✒️

* **Jofree Alexander Montaño Nieto** - *developer* - [1026jota](https://github.com/1026jota)

## Licencia 📄

Este proyecto está bajo la Licencia (MIT).

---