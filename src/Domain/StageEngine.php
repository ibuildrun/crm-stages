<?php

declare(strict_types=1);

namespace GlavPro\CrmStages\Domain;

use GlavPro\CrmStages\DTO\TransitionResult;

/**
 * Основной движок переходов между стадиями воронки.
 *
 * Координирует валидацию условий выхода, проверку последовательности
 * и ограничения действий для компаний в CRM-воронке.
 */
final class StageEngine
{
    /**
     * @param TransitionValidator $validator Валидатор условий выхода из стадий
     * @param StageMap $stageMap Карта стадий воронки с порядком и метаданными
     * @param ActionRestrictions $actionRestrictions Ограничения действий по стадиям
     */
    public function __construct(
        private readonly TransitionValidator $validator,
        private readonly StageMap $stageMap,
        private readonly ActionRestrictions $actionRestrictions,
    ) {}

    /**
     * Попытка перевести компанию на следующую стадию воронки.
     *
     * Проверяет, что текущая стадия не терминальная, что существует
     * следующая стадия, и что все условия выхода выполнены.
     *
     * @param string $currentStage Код текущей стадии компании
     * @param array<array<string, mixed>> $events Список событий компании для проверки условий выхода
     * @return TransitionResult Результат перехода: успех с новой стадией или ошибка с причинами
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
     * Попытка перевести компанию в конкретную целевую стадию.
     *
     * Допускает только последовательный переход на следующую стадию.
     * Если целевая стадия — Null, делегирует в transitionToNull().
     *
     * @param string $currentStage Код текущей стадии компании
     * @param string $targetStage Код целевой стадии
     * @param array<array<string, mixed>> $events Список событий компании для проверки условий выхода
     * @return TransitionResult Результат перехода: успех с новой стадией или ошибка с причинами
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
     * Перевод компании в стадию Null (отказ).
     *
     * Null доступен из любой нетерминальной стадии.
     * Из Null и Activated переход невозможен.
     *
     * @param string $currentStage Код текущей стадии компании
     * @return TransitionResult Результат перехода: успех со стадией Null или ошибка
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

    /**
     * Получить список доступных действий для указанной стадии.
     *
     * @param string $stageCode Код стадии
     * @return string[] Массив кодов разрешённых действий
     */
    public function getAvailableActions(string $stageCode): array
    {
        return $this->actionRestrictions->getAllowedActions($stageCode);
    }
}
