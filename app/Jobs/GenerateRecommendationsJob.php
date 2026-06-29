<?php

namespace App\Jobs;

use App\Services\Recommendation\RecommendationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateRecommendationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $limitPerProduct = 8,
    ) {
        $this->afterCommit();
    }

    public function handle(RecommendationService $recommendationService): void
    {
        $count = $recommendationService->generate($this->limitPerProduct);

        Log::info('recommendations.generated', [
            'count' => $count,
            'limit_per_product' => $this->limitPerProduct,
        ]);
    }
}
