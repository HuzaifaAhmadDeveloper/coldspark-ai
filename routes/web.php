<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

// Stripe webhook (no auth middleware - must be outside)
Route::post('/stripe/webhook', [App\Http\Controllers\WebhookController::class, 'handleWebhook']);

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/history', function () {
        return view('history');
    })->name('history');

    Route::get('/bulk', function () {
        return view('bulk');
    })->name('bulk');

    Route::get('/team', function () {
        return view('team');
    })->name('team');

    Route::get('/warmup', function () {
        return view('warmup');
    })->name('warmup');

    Route::get('/crm', function () {
        return view('crm');
    })->name('crm');

    // Sample CSV download
    Route::get('/bulk/sample', function () {
        $csv  = "name,company,role,industry,pain_point,note\n";
        $csv .= "Sarah Johnson,TechFlow Inc,Head of Marketing,SaaS,Struggling to scale outbound sales,Saw their recent funding\n";
        $csv .= "James Miller,GrowthLab,CEO,Digital Marketing,Low reply rates on cold outreach,Posted about growth challenges\n";
        $csv .= "Ayesha Khan,NovaMed,Operations Manager,Healthcare,Manual patient onboarding,Recently hired 50 staff\n";
        return Response::make($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sample_prospects.csv"',
        ]);
    })->name('bulk.sample');

    // Export CSV
    Route::get('/bulk/export/{jobId}', function ($jobId) {
        $sequences = \App\Models\Sequence::with('prospect')
            ->where('user_id', auth()->id())
            ->latest()->take(50)->get();

        $csv = "name,company,role,subject1,subject2,email1,email2,email3\n";
        foreach ($sequences as $s) {
            $csv .= implode(',', array_map(
                fn($v) => '"' . str_replace('"', '""', $v) . '"',
                [
                    $s->prospect->name    ?? '',
                    $s->prospect->company ?? '',
                    $s->prospect->role    ?? '',
                    $s->subject1, $s->subject2,
                    $s->email1, $s->email2, $s->email3,
                ]
            )) . "\n";
        }

        return Response::make($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="coldspark_export.csv"',
        ]);
    })->name('bulk.export');

    // Billing routes
    Route::get('/billing', [App\Http\Controllers\BillingController::class, 'plans'])->name('billing.plans');
    Route::post('/billing/checkout', [App\Http\Controllers\BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/success', [App\Http\Controllers\BillingController::class, 'success'])->name('billing.success');
    Route::post('/billing/cancel', [App\Http\Controllers\BillingController::class, 'cancel'])->name('billing.cancel');
    Route::get('/billing/portal', [App\Http\Controllers\BillingController::class, 'portal'])->name('billing.portal');
});

require __DIR__.'/auth.php';