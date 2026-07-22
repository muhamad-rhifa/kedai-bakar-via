<?php
// auth/google_service.php

// Masukkan Client ID dari Google Cloud Console di sini
define('GOOGLE_CLIENT_ID', 'GA673042325033-m3acsknqp59c7np9q8jcp2sf6o8eqq73.apps.googleusercontent.com');

// Masukkan Client Secret dari Google Cloud Console di sini
define('GOOGLE_CLIENT_SECRET', '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');

// Untuk menghindari error redirect_uri_mismatch, kita hardcode (tetapkan) URL-nya secara langsung.
// URL ini harus SAMA PERSIS dengan yang didaftarkan di Google Cloud Console.

define('GOOGLE_REDIRECT_URL', 'https://kedaibakarvia.shop/auth/google_callback.php');
