<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateFinancialFormMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only validate on POST requests for create/update operations
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            
            // Check if this is a pendapatan form
            if ($request->route()->getName() === 'filament.admin.resources.pendapatans.create' || 
                str_contains($request->url(), 'pendapatans/create')) {
                
                $this->validatePendapatanForm($request);
            }
            
            // Check if this is a pengeluaran form
            if ($request->route()->getName() === 'filament.admin.resources.pengeluarans.create' || 
                str_contains($request->url(), 'pengeluarans/create')) {
                
                $this->validatePengeluaranForm($request);
            }
        }
        
        return $next($request);
    }
    
    private function validatePendapatanForm(Request $request): void
    {
        // Additional server-side validation for pendapatan
        if ($request->has('nama_pendapatan')) {
            $existingPendapatan = \App\Models\Pendapatan::where('nama_pendapatan', $request->input('nama_pendapatan'))->first();
            if ($existingPendapatan) {
                abort(422, 'Nama pendapatan sudah digunakan.');
            }
        }
        
        // Validate code format
        if ($request->has('kode_pendapatan')) {
            $code = $request->input('kode_pendapatan');
            if (!preg_match('/^PND-\d{4}$/', $code)) {
                abort(422, 'Format kode pendapatan tidak valid. Harus PND-0001.');
            }
        }
    }
    
    private function validatePengeluaranForm(Request $request): void
    {
        // Additional server-side validation for pengeluaran
        if ($request->has('nama_pengeluaran')) {
            $existingPengeluaran = \App\Models\Pengeluaran::where('nama_pengeluaran', $request->input('nama_pengeluaran'))->first();
            if ($existingPengeluaran) {
                abort(422, 'Nama pengeluaran sudah digunakan.');
            }
        }
        
        // Validate code format
        if ($request->has('kode_pengeluaran')) {
            $code = $request->input('kode_pengeluaran');
            if (!preg_match('/^PNG-\d{4}$/', $code)) {
                abort(422, 'Format kode pengeluaran tidak valid. Harus PNG-0001.');
            }
        }
    }
}