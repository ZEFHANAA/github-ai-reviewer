<?php

namespace App\Http\Controllers;

use App\Analysis\FinalScoreCalculator;
use App\Http\Requests\SubmitRepositoryRequest;
use App\Services\AI\SafeAIReviewService;
use App\Services\Analysis\DeterministicRepositoryAnalysisService;
use App\Services\GitHub\Exceptions\GitHubRepositoryException;
use App\Services\GitHub\GitHubRepositoryService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RepositorySubmissionController extends Controller
{
    public function store(SubmitRepositoryRequest $request, GitHubRepositoryService $github, DeterministicRepositoryAnalysisService $analysis, FinalScoreCalculator $calculator, SafeAIReviewService $ai): View|Response
    {
        try {
            $repository = $github->fetchMetadata($request->repository());
            $findings = $analysis->analyze($github->collectSnapshot($request->repository(), $repository));
            $report = $calculator->report($findings);

            return view('repositories.show', [
                'repository' => $repository,
                'findings' => $findings,
                'report' => $report,
                // Deterministic results above are already final; AI is optional enrichment.
                'aiReview' => $ai->review($repository, $report),
            ]);
        } catch (GitHubRepositoryException $exception) {
            return response()->view('repositories.error', [
                'message' => $exception->getMessage(),
            ], $exception->status());
        }
    }
}
