<?php

namespace App\Http\Controllers;

use App\Jobs\RecordTrackingEventJob;
use App\Services\CampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CampaignTrackingController extends Controller
{
    // 1x1 transparent GIF, served regardless of whether the token is valid
    // so a broken/expired token never shows a visible broken-image icon.
    private const PIXEL_GIF = "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;";

    public function pixel(string $token, CampaignService $service): Response
    {
        if ($service->findByTrackingToken($token)) {
            RecordTrackingEventJob::dispatch($token, 'opened');
        }

        return response(self::PIXEL_GIF, 200, [
            'Content-Type'  => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function click(string $token, Request $request, CampaignService $service): RedirectResponse
    {
        $destination = $request->query('url', '');
        $safe = filter_var($destination, FILTER_VALIDATE_URL) && str_starts_with(strtolower($destination), 'http')
            ? $destination
            : config('app.url');

        if ($service->findByTrackingToken($token)) {
            RecordTrackingEventJob::dispatch($token, 'clicked');
        }

        return redirect()->away($safe);
    }
}
