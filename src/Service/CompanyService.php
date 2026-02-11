<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Service;

use GlavPro\CrmStages\Domain\StageEngine;
use GlavPro\CrmStages\Domain\StageMap;
use GlavPro\CrmStages\DTO\CompanyCardDTO;
use GlavPro\CrmStages\DTO\CompanyDTO;
use GlavPro\CrmStages\DTO\TransitionResult;
use GlavPro\CrmStages\Repository\CompanyRepository;

class CompanyService
{
    public function __construct(
        private readonly CompanyRepository $companyRepo,
        private readonly EventService $eventService,
        private readonly StageEngine $stageEngine,
        private readonly StageMap $stageMap,
    ) {}

    public function getCompany(int $id): CompanyDTO
    {
        $company = $this->companyRepo->findById($id);
        if ($company === null) {
            throw new \RuntimeException("Company {$id} not found");
        }
        return $company;
    }

    public function transitionStage(int $companyId, int $managerId): TransitionResult
    {
        $company = $this->getCompany($companyId);
        $events = $this->eventService->getEventsAsArrays($companyId);

        $result = $this->stageEngine->transition($company->stageCode, $events);

        if ($result->success && $result->newStage !== null) {
            $stageInfo = $this->stageMap->getStageInfo($result->newStage);
            $this->companyRepo->updateStage($companyId, $company->stageCode, $result->newStage, $stageInfo->name);

            $this->eventService->recordEvent($companyId, $managerId, 'stage_transition', [
                'from' => $company->stageCode,
                'to' => $result->newStage,
            ]);
        }

        return $result;
    }

    public function transitionToNull(int $companyId, int $managerId): TransitionResult
    {
        $company = $this->getCompany($companyId);
        $result = $this->stageEngine->transitionToNull($company->stageCode);

        if ($result->success) {
            $stageInfo = $this->stageMap->getStageInfo('Null');
            $this->companyRepo->updateStage($companyId, $company->stageCode, 'Null', $stageInfo->name);

            $this->eventService->recordEvent($companyId, $managerId, 'stage_transition', [
                'from' => $company->stageCode,
                'to' => 'Null',
            ]);
        }

        return $result;
    }

    public function getCompanyCard(int $companyId): CompanyCardDTO
    {
        $company = $this->getCompany($companyId);
        $stageInfo = $this->stageMap->getStageInfo($company->stageCode);
        $actions = $this->stageEngine->getAvailableActions($company->stageCode);
        $events = $this->eventService->getEvents($companyId);

        return new CompanyCardDTO(
            company: $company,
            stageInfo: $stageInfo,
            availableActions: $actions,
            instruction: $stageInfo->instruction,
            events: $events,
        );
    }
}
