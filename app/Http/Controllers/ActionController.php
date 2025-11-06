<?php

namespace App\Http\Controllers;

use App\Models\Action;
use Illuminate\Http\Request;

class ActionController extends Controller
{
    public function index()
    {
        $actions = Action::with([
                'pincode.license.organization', 
                'pincode.license.product',
                'user' => function($query) {
                    $query->withTrashed();
                }
            ])
            ->whereHas('pincode', function($query) {
                $query->whereNull('deleted_at');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        
        return view('actions.index', compact('actions'));
    }
}