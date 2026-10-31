<?php
// STUB untuk demonstrasi Checkpoint 1. Bukan Layanan Pesanan yang sebenarnya.
header('Content-Type: application/json');

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && preg_match('#^/api/v1/pesanan/([0-9a-f-]{36})$#i', $path, $m)) {

    // id berisi nol semua dipakai untuk mendemonstrasikan galat 404
    if ($m[1] === '00000000-0000-0000-0000-000000000000') {
        http_response_code(404);
        echo json_encode(['status' => 404, 'title' => 'Pesanan tidak ditemukan']);
        return true;
    }

    echo json_encode([
        'id' => $m[1],
        'total' => 185000,
        'item' => [
            ['barang_id' => 'a91e3c55-0d4b-4c1a-8e72-6f3b9d1a2c07', 'jumlah' => 2],
        ],
    ]);
    return true;
}

http_response_code(404);
echo json_encode(['status' => 404, 'title' => 'Tidak ditemukan']);
return true;