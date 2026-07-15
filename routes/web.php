<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

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
                    $s->prospect->name   ?? '',
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
});

require __DIR__.'/auth.php';