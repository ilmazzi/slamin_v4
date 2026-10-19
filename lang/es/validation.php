<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser una cadena de texto.',
    'email' => 'El campo :attribute debe ser una dirección de email válida.',
    'confirmed' => 'La confirmación del campo :attribute no coincide.',
    'unique' => 'El valor del campo :attribute ya está en uso.',
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'max' => [
        'string' => 'El campo :attribute no puede superar los :max caracteres.',
    ],
    'alpha_dash' => 'El campo :attribute solo puede contener letras, números, guiones y guiones bajos.',

    'attributes' => [
        'email' => 'email',
        'password' => 'contraseña',
        'nickname' => 'apodo',
        'display_name' => 'nombre visible',
        'preferred_locale' => 'idioma preferido',
    ],
];
