<?php

namespace App\Services;

use App\Models\CompanyPolicy;

class PolicyMetricsService
{
    public static function count(int $companyId, ?string $documentType = null): array
    {
        $query = CompanyPolicy::where('company_id', $companyId);

        if ($documentType) {
            $query->where('document_type', $documentType);
        }

        $total = (clone $query)->count();
        $draft = (clone $query)->where('status', 'draft')->count();
        $published = (clone $query)->where('status', 'published')->count();
        $archived = (clone $query)->where('status', 'archived')->count();

        return [
            'total' => $total,
            'draft' => $draft,
            'published' => $published,
            'archived' => $archived,
        ];
    }
}
