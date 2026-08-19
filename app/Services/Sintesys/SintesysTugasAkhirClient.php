<?php

namespace App\Services\Sintesys;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class SintesysTugasAkhirClient
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $tanggalMulai, string $tanggalSelesai, ?int $jenisUjianId = null): array
    {
        $token = (string) config('services.sintesys.token');

        if ($token === '') {
            throw new SintesysException('SINTESYS_TOKEN belum diatur di .env.');
        }

        $url = (string) config('services.sintesys.url');

        if ($url === '') {
            throw new SintesysException('SINTESYS_URL belum diatur.');
        }

        $body = [
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'kode_prodi' => (string) config('services.sintesys.kode_prodi', '2151'),
        ];

        if ($jenisUjianId) {
            $body['jenis_ujian_id'] = $jenisUjianId;
        }

        try {
            $response = Http::timeout((int) config('services.sintesys.timeout', 120))
                ->acceptJson()
                ->withToken($token)
                ->asJson()
                ->post($url, $body)
                ->throw();
        } catch (RequestException $e) {
            $status = $e->response?->status() ?? 0;

            throw new SintesysException(
                'Gagal menghubungi Sintesys'.($status ? " (HTTP {$status})" : '').'.',
                $status,
                $e,
            );
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new SintesysException('Respons Sintesys tidak valid.');
        }

        $rows = $json['data'] ?? $json;

        if (! is_array($rows)) {
            throw new SintesysException('Respons Sintesys tidak berisi data ujian.');
        }

        return array_values($rows);
    }
}
