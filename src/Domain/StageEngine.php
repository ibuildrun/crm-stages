<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Domain;

use GlavPro\CrmStages\DTO\TransitionResult;

final class StageEngine
{
    public function __construct(
        private readonly TransitionValidator $validator,
        private readonly StageMap $stageMap,
        private readonly ActionRestrictions $actionRestrictions,
    ) {}

    /**
     * Attempt to transition a company to the next stage.
     *
     * @param string $currentStage
     * @param array  $events
     * @return TransitionResult
     */
    public function transition(string $currentStage, array $events): TransitionResult
    {
        if ($this->stageMap->isTerminal($currentStage)) {
            return TransitionResult::fail(["Стадия {$currentStage} является терминальной, переходы невозможны"]);
        }

        $nextStage = $this->stageMap->getNextStage($currentStage);
        if ($nextStage === null) {
            return TransitionResult::fail(["Нет следующей стадии после {$currentStage}"]);
        }

        $validation = $this->validator->validate($currentStage, $events);
        if (!$validation->isValid) {
            return TransitionResult::fail($validation->errors);
        }

        return TransitionResult::ok($nextStage);
    }

    /**
     * Attempt to transition a company to a specific target stage.
     * Only allows transition to the immediately next stage.
     */
    public function transitionTo(string $currentStage, string $targetStage, array $events): TransitionResult
    {
        if ($targetStage === 'Null') {
            return $this->transitionToNull($currentStage);
        }

        if ($this->stageMap->isTerminal($currentStage)) {
            return TransitionResult::fail(["Стадия {$currentStage} является терминальной, переходы невозможны"]);
        }

        $nextStage = $this->stageMap->getNextStage($currentStage);
        if ($nextStage !== $targetStage) {
            return TransitionResult::fail([
                "Переход из {$currentStage} в {$targetStage} невозможен. Допустим только последовательный переход в {$nextStage}."
            ]);
        }

        return $this->transition($currentStage, $events);
    }

    /**
     * Transition to Null (rejection) from any active stage.
     */
    public function transitionToNull(string $currentStage): TransitionResult
    {
        if ($currentStage === 'Null') {
            return TransitionResult::fail(['Компания уже в стадии Null, дальнейшие переходы невозможны']);
        }

        if ($currentStage === 'Activated') {
            return TransitionResult::fail(['Стадия Activated является терминальной, переходы невозможны']);
        }

        return TransitionResult::ok('Null');
    }

    /** @return string[] */
    public function getAvailableActions(string $stageCode): array
    {
        return $this->actionRestrictions->getAllowedActions($stageCode);
    }
}
