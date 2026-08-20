<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Controller ini TIDAK punya tabel/model sendiri. Semua data (Projects,
 * Statistics, Menu, Services, Testimonials, Teams, Footer, Landing Content,
 * Client Logos) beneran disimpan di database project Landing.
 *
 * Controller ini cuma "penyambung generic": nerima request apapun dari
 * dashboard E-pkl yang udah lolos auth JWT + permission E-pkl (lihat
 * routes/api.php), lalu meneruskannya APA ADANYA (termasuk file upload)
 * ke API internal Landing pakai API key rahasia (server-ke-server).
 *
 * Kenapa generic (bukan 1 controller per modul)? Karena front-end (Vue)-nya
 * di-copy langsung dari dashboard Landing dan manggil path yang PERSIS SAMA
 * (/master/projects, /master/statistics, dst) — jadi kita tinggal terusin
 * apapun yang masuk, tanpa perlu tau detail tiap modul satu-satu.
 */
class LandingProxyController extends Controller
{
    /**
     * Whitelist — cuma resource ini yang boleh diteruskan ke Landing.
     * Mencegah endpoint proxy ini disalahgunakan buat manggil path lain.
     */
    private const ALLOWED = [
        'projects', 'statistics', 'menu', 'services',
        'testimonials', 'teams', 'footer', 'landing-content', 'client-logos',
    ];

    public function proxy(Request $request, string $path)
    {
        $resource = explode('/', ltrim($path, '/'))[0] ?? '';

        if (!in_array($resource, self::ALLOWED, true)) {
            return response()->json(['message' => 'Resource tidak diizinkan'], 403);
        }

        $url = rtrim(config('services.landing_api.url'), '/') . '/internal/' . ltrim($path, '/');

        $http = Http::withHeaders([
            'X-Internal-Api-Key' => config('services.landing_api.key'),
            'Accept'             => 'application/json',
        ]);

        // Field biasa (exclude field internal axios E-pkl kayak "tahun" yang
        // nggak relevan buat Landing, dan exclude file — file ditangani terpisah).
        $fields = $request->except(['tahun', ...array_keys($request->allFiles())]);

        // ── Ada file upload? Harus dikirim multipart, method PUT-lewat-POST ──
        if ($request->allFiles() !== []) {
            foreach ($request->allFiles() as $key => $file) {
                $http = $http->attach($key, file_get_contents($file->getRealPath()), $file->getClientOriginalName());
            }

            // Laravel nggak bisa kirim file asli lewat method PUT/DELETE native,
            // makanya di-spoof pakai _method (sama kayak yang FE lakuin ke kita).
            if ($request->method() !== 'POST') {
                $fields['_method'] = $request->method();
            }

            $res = $http->post($url, $fields);
            return response()->json($res->json(), $res->status());
        }

        // ── Request biasa (tanpa file) ──
        $res = match ($request->method()) {
            'GET'    => $http->get($url, $fields),
            'POST'   => $http->post($url, $fields),
            'PUT'    => $http->put($url, $fields),
            'DELETE' => $http->delete($url, $fields),
            default  => null,
        };

        if (!$res) {
            return response()->json(['message' => 'Method tidak didukung'], 405);
        }

        return response()->json($res->json(), $res->status());
    }
}