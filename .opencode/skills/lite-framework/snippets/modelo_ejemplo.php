<?php
class Producto extends Modelo {
    protected static $tabla = 'productos';
    protected static $idColumna = 'id';
    protected static $rellenable = ['nombre', 'descripcion', 'precio', 'stock', 'categoria_id', 'activo'];
    protected static $tipos = [
        'id' => 'int',
        'precio' => 'float',
        'stock' => 'int',
        'categoria_id' => 'int',
        'activo' => 'bool',
    ];

    public function categoria() {
        return $this->perteneceA(Categoria::class, 'categoria_id', 'id');
    }

    public function ventas() {
        return $this->tieneMuchos(Venta::class, 'producto_id', 'id');
    }

    public function estaActivo() {
        return (bool) $this->activo;
    }

    public function activar() {
        $this->activo = true;
        return $this->guardar();
    }

    public function desactivar() {
        $this->activo = false;
        return $this->guardar();
    }
}
