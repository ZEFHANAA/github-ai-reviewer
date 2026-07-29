<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitRepositoryRequest;
use Illuminate\View\View;

class RepositorySubmissionController extends Controller
{
    public function store(SubmitRepositoryRequest $request): View
    {
        return view('repositories.validated', [
            'repository' => $request->repository(),
        ]);
    }
}
