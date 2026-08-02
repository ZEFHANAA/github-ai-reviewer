<?php

namespace App\Services\AI\Providers;

use App\AI\AIReviewRequest;
use App\AI\AIReviewResponse;
use App\Analysis\AnalysisFinding;
use App\Contracts\AIReviewProviderInterface;
use App\ValueObjects\GitHubRepositoryMetadata;

/**
 * Deterministic stand-in provider. Never calls a network and derives every
 * emitted string only from the authoritative deterministic report, so the
 * output always begins with a real rule ID (contract: SafeAIReviewService).
 */
final class FakeAIReviewProvider implements AIReviewProviderInterface
{
    public function review(AIReviewRequest $request): AIReviewResponse
    {
        $metadata = $request->metadata;
        $report = $request->report;

        [$documentation, $concerns] = $this->documentationAndConcerns($report->findings);

        return new AIReviewResponse(
            repositorySummary: $this->summary($report->findings, $metadata),
            documentationObservations: $documentation,
            maintainabilityObservations: $this->maintainability($report->findings, $metadata),
            potentialConcerns: $concerns,
            prioritizedRecommendations: $this->recommendations($report->findings, $metadata),
        );
    }

    /**
     * @param  array<int, AnalysisFinding>  $findings
     */
    private function summary(array $findings, GitHubRepositoryMetadata $metadata): string
    {
        if ($findings === []) {
            return '[STRUCT-MANIFEST-001] No deterministic checks are available for this repository.';
        }

        $first = $findings[0];

        return sprintf(
            '%s %s covers %d deterministic checks. %s',
            $this->reference($first),
            $metadata->fullName,
            count($findings),
            'The scores and findings above remain authoritative.'
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
                $documentation[] = $this->reference($finding).$finding->title.' — '.$finding->message;
            }

            $concerns[] = $this->reference($finding).$finding->title.' — '.$finding->message;
        }

        if ($documentation === []) {
            $documentation[] = $this->fallback($findings, 'No documentation improvements were flagged by the deterministic checks.');
        }

        if ($concerns === []) {
            $concerns[] = $this->fallback($findings, 'No concerns were flagged by the deterministic checks.');
        }

        return [$documentation, $concerns];
    }

    /**
     * @param  array<int, AnalysisFinding>  $findings
     * @return list<string>
     */
    private function maintainability(array $findings, GitHubRepositoryMetadata $metadata): array
    {
        $notes = [];

        foreach ($findings as $finding) {
            if ($finding->status->value === 'unknown') {
                $notes[] = $this->reference($finding).$finding->title.' — could not be inspected: '.$finding->message;
            }
        }

        if ($notes === []) {
            $notes[] = $this->fallback($findings, 'All inspected checks produced a verdict for '.$metadata->fullName.'.');
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
                $recommendations[] = $this->reference($finding).$finding->recommendation;
            }
        }

        if ($recommendations === []) {
            $recommendations[] = $this->fallback($findings, 'No deterministic improvements are pending for '.$metadata->fullName.'.');
        }

        return $recommendations;
    }

    private function reference(AnalysisFinding $finding): string
    {
        return '['.$finding->ruleIdentifier.'] ';
    }

    /**
     * Choose a valid rule ID to anchor fallback prose so every emitted string
     * still satisfies the deterministic-rule-reference contract.
     *
     * @param  array<int, AnalysisFinding>  $findings
     */
    private function fallback(array $findings, string $message): string
    {
        $ruleId = $findings === [] ? 'STRUCT-MANIFEST-001' : $findings[0]->ruleIdentifier;

        return '['.$ruleId.'] '.$message;
    }
}
