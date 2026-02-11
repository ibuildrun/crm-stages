<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Controller;

use GlavPro\CrmStages\Service\CompanyService;

/**
 * Контроллер компаний.
 *
 * Обрабатывает HTTP-запросы для получения данных компании
 * и управления переходами между стадиями.
 */
class CompanyController
{
    /**
     * @param CompanyService $companyService Сервис управления компаниями
     */
    public function __construct(
        private readonly CompanyService $companyService,
    ) {}

    /**
     * Получить карточку компании.
     *
     * GET /api/companies/{id}
     *
     * @param int $id Идентификатор компании
     * @param int $managerId Идентификатор менеджера
     * @return array{success: bool, data?: array, errors?: array} Ответ API
     */
    public function show(int $id, int $managerId): array
    {
        try {
            $card = $this->companyService->getCompanyCard($id);
            return ['success' => true, 'data' => $card->toArray()];
        } catch (\RuntimeException $e) {
            return ['success' => false, 'errors' => [['code' => 'COMPANY_NOT_FOUND', 'message' => $e->getMessage()]]];
        }
    }

    /**
     * Перевести компанию на следующую стадию.
     *
     * POST /api/companies/{id}/transition
     *
     * @param int $id Идентификатор компании
     * @param int $managerId Идентификатор менеджера
     * @return array{success: bool, data?: array, errors?: array} Ответ API
     */
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

    /**
     * Перевести компанию в стадию Null (отказ).
     *
     * POST /api/companies/{id}/transition-null
     *
     * @param int $id Идентификатор компании
     * @param int $managerId Идентификатор менеджера
     * @return array{success: bool, data?: array, errors?: array} Ответ API
     */
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
