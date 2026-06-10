<?php
$datos = $_POST;

$validador = new Validador($datos, [
    'nombre' => 'requerido|minimo:3|maximo:100',
    'correo' => 'requerido|correo|maximo:150',
    'password' => 'minimo:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
    'password_confirmar' => 'confirmado',
    'telefono' => 'regex:/^\+?[0-9]{9,15}$/',
    'edad' => 'entero|minimo:18|maximo:120',
    'categoria_id' => 'numero',
]);

if ($validador->falla()) {
    $errores = $validador->obtenerErrores();
    foreach ($errores as $campo => $mensajes) {
        foreach ($mensajes as $mensaje) {
            echo "$campo: $mensaje\n";
        }
    }
    exit;
}

$datosValidados = $validador->errores();
