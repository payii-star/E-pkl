<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MemberPageController extends Controller
{
    public function showCard($member)
    {
        return view('app');
    }
}
