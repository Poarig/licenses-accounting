<?php

namespace App\Http\Controllers;

use App\Models\Action;
use Illuminate\Http\Request;

class ActionController extends Controller
{
    public function index()
    {
        $actions = Action::with([
                'pincode.license', 
                'user'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('actions.index', compact('actions'));
    }
}