<?php

return [
    'required' => 'Il campo :attribute è obbligatorio.',
    'string' => 'Il campo :attribute deve essere una stringa.',
    'email' => 'Il campo :attribute deve essere un indirizzo email valido.',
    'confirmed' => 'La conferma del campo :attribute non corrisponde.',
    'unique' => 'Il valore del campo :attribute è già stato utilizzato.',
    'min' => [
        'string' => 'Il campo :attribute deve contenere almeno :min caratteri.',
    ],
    'max' => [
        'string' => 'Il campo :attribute non può superare :max caratteri.',
    ],
    'alpha_dash' => 'Il campo :attribute può contenere solo lettere, numeri, trattini e underscore.',

    'attributes' => [
        'email' => 'email',
        'password' => 'password',
        'nickname' => 'nickname',
        'display_name' => 'nome visualizzato',
        'preferred_locale' => 'lingua preferita',
    ],
];
