<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitRepositoryRequest;
use App\Services\Analysis\DeterministicRepositoryAnalysisService;
use App\Services\GitHub\Exceptions\GitHubRepositoryException;
use App\Services\GitHub\GitHubRepositoryService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RepositorySubmissionController extends Controller
{
    public function store(SubmitRepositoryRequest $request, GitHubRepositoryService $github, DeterministicRepositoryAnalysisService $analysis, \App\Analysis\FinalScoreCalculator $calculator): View|Response
    {
        try {
            $repository = $github->fetchMetadata($request->repository());
            $findings = $analysis->analyze($github->collectSnapshot($request->repository(), $repository));
            $report = $calculator->report($findings);

            return view('repositories.show', [
                'repository' => $repository,
                'findings' => $findings,
                'report' => $report,
            ]);
        } catch (GitHubRepositoryException $exception) {
            return response()->view('repositories.error', [
                'message' => $exception->getMessage(),
            ], $exception->status());
        }
    }
}
