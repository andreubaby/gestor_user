<?php

namespace App\Http\Controllers;

use App\Mail\OnboardingMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OnboardingController extends Controller
{
    public function onboardingCreate()
    {
        $workers = \App\Models\TrabajadorPolifonia::query()
            ->select(['id','nombre','email','activo'])
            ->orderBydesc('id')
            ->get();

        $packs = config('onboarding.packs'); // o lo que uses

        return view('usuarios.onboarding_send', [
            'workers' => $workers,
            'packs' => $packs,
        ]);
    }

    public function onboardingSend(Request $request)
    {
        $request->validate([
            'trabajador_id' => ['required','integer'],
            'pack'          => ['required','string'],
        ]);

        $worker = \App\Models\TrabajadorPolifonia::query()
            ->select(['id','nombre','email'])
            ->findOrFail((int)$request->trabajador_id);

        if (!$worker->email) {
            return back()->withErrors(['email' => 'Este trabajador no tiene email.'])->withInput();
        }

        $packs = config('onboarding.packs', []);
        $packKey = (string)$request->pack;

        if (!isset($packs[$packKey])) {
            return back()->withErrors(['pack' => 'Pack no válido.'])->withInput();
        }

        Mail::to($worker->email)->send(new OnboardingMail($worker->nombre, $packs[$packKey]));

        return back()->with('success', '✅ Email enviado a '.$worker->email)->withInput();
    }
}
