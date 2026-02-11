<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Controller;

use GlavPro\CrmStages\Service\CompanyService;

class CompanyController
{
    public function __construct(
        private readonly CompanyService $companyService,
    ) {}

    /** GET /api/companies/{id} */
    public function show(int $id, int $managerId): array
    {
        try {
            $card = $this->companyService->getCompanyCard($id);
            return ['success' => true, 'data' => $card->toArray()];
        } catch (\RuntimeException $e) {
            return ['success' => false, 'errors' => [['code' => 'COMPANY_NOT_FOUND', 'message' => $e->getMessage()]]];
        }
    }

    /** POST /api/companies/{id}/transition */
    public function transition(int $id, int $managerId): array
    {
        try {
            $result = $this->companyService->transitionStage($id, $managerId);
            if ($result->success) {
                return ['success' => true, 'data' => $result->toArray()];
            }
            return [
                'success' => false,
                'errors' => array_map(fn(string $e) => [
                    'code' => 'TRANSITION_CONDITIONS_NOT_MET',
                    'message' => $e,
                ], $result->errors),
            ];
        } catch (\RuntimeException $e) {
            return ['success' => false, 'errors' => [['code' => 'STAGE_CONFLICT', 'message' => $e->getMessage()]]];
        }
    }

    /** POST /api/companies/{id}/transition-null */
    public function transitionNull(int $id, int $managerId): array
    {
        try {
            $result = $this->companyService->transitionToNull($id, $managerId);
            if ($result->success) {
                return ['success' => true, 'data' => $result->toArray()];
            }
            return [
                'success' => false,
                'errors' => array_map(fn(string $e) => [
                    'code' => 'NULL_STAGE_TERMINAL',
                    'message' => $e,
                ], $result->errors),
            ];
        } catch (\RuntimeException $e) {
            return ['success' => false, 'errors' => [['code' => 'COMPANY_NOT_FOUND', 'message' => $e->getMessage()]]];
        }
    }
}
