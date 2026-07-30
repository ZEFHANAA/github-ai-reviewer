<?php

namespace App\Services\AI\Providers;

use App\AI\AIReviewRequest;
use App\AI\AIReviewResponse;
use App\Analysis\AnalysisFinding;
use App\Contracts\AIReviewProviderInterface;
use App\ValueObjects\GitHubRepositoryMetadata;

/**
 * Deterministic stand-in used until a real AI provider is wired in (Phase 7B+).
 * Produces stable output for unit tests; never calls a network.
 */
final class FakeAIReviewProvider implements AIReviewProviderInterface
{
    public function review(AIReviewRequest $request): AIReviewResponse
    {
        $metadata = $request->metadata;
        $report = $request->report;

        $summary = $metadata->fullName.' is a '.$metadata->primaryLanguage.' repository with '.($metadata->starsCount).' stars.';

        [$documentation, $concerns] = $this->documentationAndConcerns($report->findings);

        return new AIReviewResponse(
            repositorySummary: $summary,
            documentationObservations: $documentation,
            maintainabilityObservations: $this->maintainability($report->findings),
            potentialConcerns: $concerns,
            prioritizedRecommendations: $this->recommendations($report->findings, $metadata),
        );
    }

    /**
     * @param  array<int, AnalysisFinding>  $findings
     * @return array{0: list<string>, 1: list<string>}
     */
    private function documentationAndConcerns(array $findings): array
    {
        $documentation = [];
        $concerns = [];

        foreach ($findings as $finding) {
            if ($finding->status->value !== 'improvement') {
                continue;
            }

            if ($finding->category->value === 'Documentation') {
                $documentation[] = $finding->ruleIdentifier.': '.$finding->title.' — '.$finding->message;
            }

            $concerns[] = $finding->ruleIdentifier.': '.$finding->title.' — '.$finding->message;
        }

        if ($documentation === []) {
            $documentation[] = 'No documentation findings to report.';
        }

        if ($concerns === []) {
            $concerns[] = 'No concerns flagged by deterministic checks.';
        }

        return [$documentation, $concerns];
    }

    /**
     * @param  array<int, AnalysisFinding>  $findings
     * @return list<string>
     */
    private function maintainability(array $findings): array
    {
        $notes = [];

        foreach ($findings as $finding) {
            if ($finding->status->value === 'unknown') {
                $notes[] = $finding->ruleIdentifier.': '.$finding->title.' — could not be inspected: '.$finding->message;
            }
        }

        if ($notes === []) {
            $notes[] = 'All inspected checks produced a verdict.';
        }

        return $notes;
    }

    /**
     * @param  array<int, AnalysisFinding>  $findings
     * @return list<string>
     */
    private function recommendations(array $findings, GitHubRepositoryMetadata $metadata): array
    {
        $recommendations = [];

        foreach ($findings as $finding) {
            if ($finding->status->value === 'improvement' && $finding->recommendation !== null && $finding->recommendation !== '') {
                $recommendations[] = $finding->ruleIdentifier.': '.$finding->recommendation;
            }
        }

        if ($recommendations === []) {
            $recommendations[] = 'No deterministic improvements pending for '.$metadata->fullName.'.';
        }

        return $recommendations;
    }
}
