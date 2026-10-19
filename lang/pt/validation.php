<?php

return [
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser texto.',
    'email' => 'O campo :attribute deve ser um endereço de email válido.',
    'confirmed' => 'A confirmação do campo :attribute não coincide.',
    'unique' => 'O valor do campo :attribute já está em utilização.',
    'min' => [
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'max' => [
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'alpha_dash' => 'O campo :attribute só pode conter letras, números, hífenes e underscores.',

    'attributes' => [
        'email' => 'email',
        'password' => 'palavra-passe',
        'nickname' => 'nickname',
        'display_name' => 'nome apresentado',
        'preferred_locale' => 'língua preferida',
    ],
];
