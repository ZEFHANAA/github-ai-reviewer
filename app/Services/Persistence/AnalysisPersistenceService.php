<?php

namespace App\Services\Persistence;

use App\AI\AIReviewOutcome;
use App\Analysis\AnalysisReport;
use App\Enums\AnalysisStatus;
use App\Enums\FindingSource;
use App\Models\Analysis;
use App\Models\Repository;
use App\ValueObjects\RepositorySnapshot;

final class AnalysisPersistenceService
{
    public function persist(RepositorySnapshot $snapshot, AnalysisReport $report, AIReviewOutcome $ai): Analysis
    {
        $repository = Repository::firstOrCreate(
            ['owner' => $snapshot->metadata->owner, 'name' => $snapshot->metadata->name],
            [
                'full_name' => $snapshot->metadata->fullName,
                'url' => $snapshot->metadata->url,
                'description' => $snapshot->metadata->description,
                'primary_language' => $snapshot->metadata->primaryLanguage,
                'default_branch' => $snapshot->metadata->defaultBranch,
                'stars_count' => $snapshot->metadata->starsCount,
                'forks_count' => $snapshot->metadata->forksCount,
                'github_created_at' => $snapshot->metadata->createdAt?->toDateTimeString(),
                'github_updated_at' => $snapshot->metadata->updatedAt?->toDateTimeString(),
            ],
        );

        $analysis = Analysis::create([
            'repository_id' => $repository->id,
            'status' => $ai->isAvailable ? AnalysisStatus::Completed : AnalysisStatus::Partial,
            'overall_score' => $report->finalScore,
            'category_scores' => $report->categoryScores,
            'summary' => $report->summary,
            'ai_summary' => $ai?->response?->repositorySummary,
            'github_commit_sha' => null,
        ]);

        foreach ($report->findings as $finding) {
            $analysis->findings()->create([
                'source' => FindingSource::Rule,
                'rule_identifier' => $finding->ruleIdentifier,
                'category' => $finding->category->value,
                'severity' => $finding->severity->value,
                'status' => $finding->status->value,
                'title' => $finding->title,
                'message' => $finding->message,
                'evidence' => $finding->evidence,
                'recommendation' => $finding->recommendation,
            ]);
        }

        return $analysis;
    }
}
