<?php
// Endereço fixo do QR Code impresso: https://seudominio.com.br/qr
// Redireciona (302, nunca guardado em cache) para 'qr_destino' do config.php, ou para o site.
require __DIR__ . '/api/lib.php';
$d = trim((string)cfg('qr_destino'));
if (!preg_match('#^https://#i', $d)) $d = rtrim((string)cfg('site_url'), '/') . '/';
header('Cache-Control: no-store');
header('Location: ' . $d, true, 302);
