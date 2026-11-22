<?php

declare(strict_types=1);

return [
    'accepted' => 'Kolom :attribute harus diterima.',
    'required' => 'Kolom :attribute wajib diisi.',
    'required_if' => 'Kolom :attribute wajib diisi ketika :other bernilai :value.',
    'filled' => 'Kolom :attribute harus memiliki nilai.',
    'email' => 'Format :attribute tidak valid.',
    'string' => 'Kolom :attribute harus berupa teks.',
    'integer' => 'Kolom :attribute harus berupa bilangan bulat.',
    'numeric' => 'Kolom :attribute harus berupa angka.',
    'boolean' => 'Kolom :attribute harus bernilai benar atau salah.',
    'array' => 'Kolom :attribute harus berupa array.',
    'max' => [
        'numeric' => ':attribute tidak boleh lebih dari :max.',
        'file' => ':attribute tidak boleh lebih dari :max kilobita.',
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
        'array' => ':attribute tidak boleh lebih dari :max item.',
    ],
    'min' => [
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
        'array' => ':attribute minimal :min item.',
    ],
    'exists' => ':attribute yang dipilih tidak valid.',
    'unique' => ':attribute sudah digunakan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'date' => ':attribute bukan tanggal yang valid.',
    'after_or_equal' => ':attribute harus sama atau setelah :date.',
    'mimes' => ':attribute harus berkas berjenis: :values.',
    'file' => ':attribute harus berupa berkas.',
    'in' => ':attribute yang dipilih tidak valid.',
    'regex' => 'Format :attribute tidak valid.',
    'password' => [
        'min' => ':attribute minimal :min karakter.',
        'uncompromised' => ':attribute pernah bocor. Pilih kata sandi lain.',
    ],
    'upload_too_large' => 'Berkas tidak boleh lebih besar dari :max KB.',
    'key_format' => 'Kunci harus 2–10 huruf.',
    'attributes' => [],
];
