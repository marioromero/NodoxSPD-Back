<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessActivity extends Model
{
    // 1. Indicamos la tabla (opcional, pero buena práctica)
    protected $table = 'business_activities';

    // 2. Definimos nuestra clave primaria personalizada
    protected $primaryKey = 'codigo';

    // 3. Eloquent asume que las PK son enteros autoincrementales. Lo desactivamos.
    public $incrementing = false;

    // 4. Le decimos que nuestra PK es un string
    protected $keyType = 'string';

    // 5. Como es un catálogo estático del SII, desactivamos los timestamps
    // (a menos que los hayas agregado a tu migración, en cuyo caso borra esta línea)
    public $timestamps = false;

    // 6. Los campos que se pueden asignar masivamente
    protected $fillable = [
        'codigo',
        'rubro',
        'descripcion',
        'afecto_iva',
        'categoria_tributaria',
        'disponible_internet',
    ];
}
