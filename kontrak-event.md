# Kontrak Event - Auctora Collection

## PembayaranDikonfirmasi

Diterbitkan setelah admin berhasil mengonfirmasi pembayaran
(`POST /pembayaran/{id}/konfirmasi` mengembalikan 200).

| Butir | Nilai |
|---|---|
| Nama event | `PembayaranDikonfirmasi` (bentuk lampau) |
| Penerbit | Layanan Pembayaran |
| Penerima | Layanan Stok, Layanan Pengiriman |
| Broker | RabbitMQ |
| Exchange | `auctora.events` (tipe `topic`, durable) |
| Routing key | `pembayaran.dikonfirmasi` |
| Antrean Stok | `stok.pembayaran_dikonfirmasi` (durable, di-bind ke routing key di atas) |
| Antrean Pengiriman | `pengiriman.pembayaran_dikonfirmasi` (durable, di-bind ke routing key di atas) |
| Format pesan | JSON, `content_type: application/json`, pesan persistent |

### Badan pesan

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `event_id` | string (uuid) | ya | Pengenal unik pesan, dipakai penerima untuk membuang pesan ganda |
| `nama_event` | string | ya | Selalu `PembayaranDikonfirmasi` |
| `versi` | integer | ya | Versi skema, saat ini `1` |
| `waktu_terjadi` | string (date-time, UTC) | ya | Waktu konfirmasi berhasil |
| `data.pembayaran_id` | string (uuid) | ya | Id pembayaran |
| `data.pesanan_id` | string (uuid) | ya | Id pesanan di Layanan Pesanan |
| `data.nominal` | integer | ya | Nominal dalam rupiah |
| `data.item` | array | ya | Daftar barang pesanan, minimal 1 elemen |
| `data.item[].barang_id` | string (uuid) | ya | Id barang di Layanan Stok |
| `data.item[].jumlah` | integer (>= 1) | ya | Jumlah potong |

### Contoh

```json
{
  "event_id": "c4d2f1a8-6b7e-4d3a-9f10-2e8a5b7c9d33",
  "nama_event": "PembayaranDikonfirmasi",
  "versi": 1,
  "waktu_terjadi": "2026-11-01T10:42:00Z",
  "data": {
    "pembayaran_id": "0b6d1e7c-4f2a-4a3e-8f55-1d9c7a2b3e44",
    "pesanan_id": "5f1c2a6e-8a47-4d52-9b1e-3c7f0d2a9e11",
    "nominal": 185000,
    "item": [
      { "barang_id": "a91e3c55-0d4b-4c1a-8e72-6f3b9d1a2c07", "jumlah": 2 }
    ]
  }
}
```

### Perilaku penerima

- **Layanan Stok**: mengurangi stok sesuai `data.item`. Jika `event_id` sudah pernah diproses, pesan diabaikan (idempotensi).
- **Layanan Pengiriman**: membuat catatan pengiriman berstatus menunggu resi untuk `data.pesanan_id`. Pesan ganda diabaikan dengan cara yang sama.
- Pesan yang gagal diproses tidak di-ack, sehingga tetap berada di antrean untuk dicoba lagi.

### Catatan rancangan

`data.item` diambil Layanan Pembayaran dari `GET /pesanan/{id}` saat konfirmasi.
Dengan begitu Layanan Stok tidak perlu memanggil Layanan Pesanan, dan kedua
penerima tidak bergantung pada layanan lain saat memproses event.
