<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\FichajesDiariosService;
use Illuminate\Http\Request;

class FichajesDiariosController extends Controller
{
    public function __construct(private readonly FichajesDiariosService $service) {}

    public function index(Request $request)
    {
        $data = $this->service->handle($request);

        $groups = Group::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('fichajes.diarios', [
            'date'        => $data['date'],
            'groupId'     => $data['groupId'],
            'rows'        => $data['rows'],
            'stats'       => $data['stats'],
            'groups'      => $groups,
        ]);
    }
}
