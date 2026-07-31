<?php

namespace App\Http\Controllers;

use App\Analysis\FinalScoreCalculator;
use App\Http\Requests\SubmitRepositoryRequest;
use App\Services\AI\SafeAIReviewService;
use App\Services\Analysis\DeterministicRepositoryAnalysisService;
use App\Services\GitHub\Exceptions\GitHubRepositoryException;
use App\Services\GitHub\GitHubRepositoryService;
use App\Services\Persistence\AnalysisPersistenceService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RepositorySubmissionController extends Controller
{
    public function store(SubmitRepositoryRequest $request, GitHubRepositoryService $github, DeterministicRepositoryAnalysisService $analysis, FinalScoreCalculator $calculator, SafeAIReviewService $ai, AnalysisPersistenceService $persistence): View|Response
    {
        try {
            $repository = $github->fetchMetadata($request->repository());
            $snapshot = $github->collectSnapshot($request->repository(), $repository);
            $findings = $analysis->analyze($snapshot);
            $report = $calculator->report($findings);
            $aiReview = $ai->review($repository, $report);
            $analysisRecord = $persistence->persist($snapshot, $report, $aiReview);

            return view('repositories.show', [
                'repository' => $repository,
                'findings' => $findings,
                'report' => $report,
                // Deterministic results above are already final; AI is optional enrichment.
                'aiReview' => $aiReview,
                'analysis' => $analysisRecord,
            ]);
        } catch (GitHubRepositoryException $exception) {
            return response()->view('repositories.error', [
                'message' => $exception->getMessage(),
            ], $exception->status());
        }
    }
}
