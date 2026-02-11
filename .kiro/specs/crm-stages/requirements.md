# Requirements Document

## Introduction

Прототип CRM-системы на базе Joomla + PHP, реализующий логику стадий воронки продаж. Менеджер не может "перепрыгнуть" вперёд без выполнения обязательных действий на текущей стадии. Система включает карточку компании, журнал событий, управление переходами между стадиями и валидацию условий переходов.

## Glossary

- **Stage_Engine**: Движок управления стадиями CRM, отвечающий за валидацию и выполнение переходов между стадиями
- **Company_Card**: Карточка компании, отображающая текущую стадию, доступные действия, инструкции и историю событий
- **Event_Journal**: Журнал событий компании, хранящий все действия и переходы в хронологическом порядке
- **Stage**: Одна из стадий воронки: Ice, Touched, Aware, Interested, demo_planned, Demo_done, Committed, Customer, Activated, Null (терминальная/отказная стадия)
- **LPR**: Лицо, принимающее решение (ЛПР)
- **Discovery_Form**: Форма дискавери — анкета с информацией о потребностях клиента
- **Demo**: Демонстрация продукта клиенту
- **KP**: Коммерческое предложение
- **Invoice**: Счёт на оплату
- **Certificate**: Удостоверение, выданное клиенту после активации
- **Manager**: Менеджер отдела продаж, работающий с карточкой компании
- **Action_Service**: Сервис выполнения действий (звонок, отправка КП, планирование демо и т.д.)
- **Transition_Validator**: Компонент, проверяющий выполнение условий для перехода на следующую стадию

## Requirements

### Requirement 1: Управление стадиями компании

**User Story:** As a Manager, I want to see the current stage of a company and transition it to the next stage, so that I can track the sales pipeline progress.

#### Acceptance Criteria

1. THE Stage_Engine SHALL support the following ordered stages: Ice, Touched, Aware, Interested, demo_planned, Demo_done, Committed, Customer, Activated, and a terminal Null stage reachable from any stage
2. WHEN a Manager requests a stage transition, THE Transition_Validator SHALL verify all exit conditions of the current stage are met before allowing the transition
3. IF a Manager attempts to transition without meeting exit conditions, THEN THE Stage_Engine SHALL reject the transition and return a descriptive error listing unmet conditions
4. WHEN a valid transition occurs, THE Event_Journal SHALL record the transition with timestamp, source stage, target stage, and Manager identifier
5. THE Stage_Engine SHALL only allow forward transitions to the immediately next stage (no skipping stages)
6. IF a Manager attempts to skip a stage, THEN THE Stage_Engine SHALL reject the transition and return an error indicating sequential transitions are required

### Requirement 2: Стадия Ice → Touched

**User Story:** As a Manager, I want to make calls from the Ice stage and record conversations with decision-makers, so that I can move the company to the Touched stage.

#### Acceptance Criteria

1. WHILE a company is in the Ice stage, THE Company_Card SHALL display only a call button as the available action
2. WHEN a call is answered, THE Company_Card SHALL display a comment form and a discovery form
3. WHEN a Manager records a conversation with an LPR and adds a comment, THE Transition_Validator SHALL allow transition from Ice to Touched
4. IF a Manager attempts to transition from Ice to Touched without a recorded LPR conversation, THEN THE Stage_Engine SHALL reject the transition

### Requirement 3: Стадия Touched → Aware

**User Story:** As a Manager, I want to fill in the discovery form after speaking with the decision-maker, so that I can move the company to the Aware stage.

#### Acceptance Criteria

1. WHILE a company is in the Touched stage, THE Company_Card SHALL display the discovery form as the primary action
2. WHILE a company is in the Touched stage, THE Stage_Engine SHALL restrict planning demos and sending KP
3. WHEN a Manager submits a completed discovery form, THE Transition_Validator SHALL allow transition from Touched to Aware
4. IF a Manager attempts to transition from Touched to Aware without a completed discovery form, THEN THE Stage_Engine SHALL reject the transition

### Requirement 4: Стадия Aware → Interested

**User Story:** As a Manager, I want to plan a demo after completing discovery, so that I can move the company to the Interested stage.

#### Acceptance Criteria

1. WHILE a company is in the Aware stage, THE Company_Card SHALL display a demo planning button
2. WHILE a company is in the Aware stage, THE Stage_Engine SHALL restrict planning demos, conducting demos, creating invoices and sending KP
3. WHEN a Manager sets a demo date and time, THE Transition_Validator SHALL allow transition from Aware to Interested
4. IF a Manager attempts to transition from Aware to Interested without a scheduled demo, THEN THE Stage_Engine SHALL reject the transition

### Requirement 5: Стадия Interested → demo_planned

**User Story:** As a Manager, I want to confirm the demo is planned with a specific date, so that I can move the company to the demo_planned stage.

#### Acceptance Criteria

1. WHILE a company is in the Interested stage, THE Company_Card SHALL display the scheduled demo date and a demo execution button
2. WHILE a company is in the Interested stage, THE Stage_Engine SHALL restrict creating invoices and sending KP
3. WHEN a demo date exists in the system, THE Transition_Validator SHALL allow transition from Interested to demo_planned
4. IF a Manager attempts to transition from Interested to demo_planned without a demo date, THEN THE Stage_Engine SHALL reject the transition

### Requirement 6: Стадия demo_planned → Demo_done

**User Story:** As a Manager, I want to conduct the demo and have it registered, so that I can move the company to the Demo_done stage.

#### Acceptance Criteria

1. WHILE a company is in the demo_planned stage, THE Company_Card SHALL display a demo link button for conducting the demo
2. WHEN a demo link click is registered, THE Event_Journal SHALL record a demo_conducted event with timestamp
3. WHEN a demo_conducted event exists, THE Transition_Validator SHALL allow transition from demo_planned to Demo_done
4. IF a Manager attempts to transition from demo_planned to Demo_done without a registered demo event, THEN THE Stage_Engine SHALL reject the transition

### Requirement 7: Стадия Demo_done → Committed

**User Story:** As a Manager, I want to create an invoice or send a KP after the demo, so that I can move the company to the Committed stage.

#### Acceptance Criteria

1. WHILE a company is in the Demo_done stage, THE Company_Card SHALL display buttons for creating an invoice and sending a KP
2. WHEN the demo was conducted less than 60 days ago, THE Transition_Validator SHALL allow actions in the Demo_done stage
3. IF the demo was conducted more than 60 days ago, THEN THE Stage_Engine SHALL require a new demo before proceeding
4. WHEN an invoice or KP exists for the company, THE Transition_Validator SHALL allow transition from Demo_done to Committed
5. IF a Manager attempts to transition from Demo_done to Committed without an invoice or KP, THEN THE Stage_Engine SHALL reject the transition

### Requirement 8: Стадия Committed → Customer

**User Story:** As a Manager, I want to record payment receipt, so that I can move the company to the Customer stage.

#### Acceptance Criteria

1. WHILE a company is in the Committed stage, THE Company_Card SHALL display payment status information
2. WHEN a payment is recorded for the company, THE Transition_Validator SHALL allow transition from Committed to Customer
3. IF a Manager attempts to transition from Committed to Customer without a recorded payment, THEN THE Stage_Engine SHALL reject the transition

### Requirement 9: Стадия Customer → Activated

**User Story:** As a Manager, I want to record the first certificate issuance, so that I can move the company to the Activated stage.

#### Acceptance Criteria

1. WHILE a company is in the Customer stage, THE Company_Card SHALL display certificate issuance status
2. WHEN at least one certificate is issued for the company, THE Transition_Validator SHALL allow transition from Customer to Activated
3. IF a Manager attempts to transition from Customer to Activated without an issued certificate, THEN THE Stage_Engine SHALL reject the transition

### Requirement 10: Журнал событий

**User Story:** As a Manager, I want to see the full history of events for a company, so that I can understand the context and progress of the deal.

#### Acceptance Criteria

1. THE Event_Journal SHALL record the following event types: contact_attempt, lpr_conversation, discovery_filled, demo_planned, demo_conducted, invoice_created, kp_sent, payment_received, certificate_issued, stage_transition
2. WHEN an event is recorded, THE Event_Journal SHALL store the event type, timestamp, Manager identifier, company identifier, and event-specific payload as JSON
3. THE Company_Card SHALL display the event history in reverse chronological order
4. THE Event_Journal SHALL support filtering events by type

### Requirement 11: Карточка компании

**User Story:** As a Manager, I want to see a company card with all relevant information, so that I can efficiently manage the sales process.

#### Acceptance Criteria

1. THE Company_Card SHALL display the current stage name and stage code
2. THE Company_Card SHALL display the list of available actions for the current stage
3. THE Company_Card SHALL display an instruction/script block relevant to the current stage
4. THE Company_Card SHALL display the event history of the company
5. WHEN a stage transition occurs, THE Company_Card SHALL update the displayed stage and available actions

### Requirement 12: Сериализация и хранение данных

**User Story:** As a developer, I want company and event data to be reliably stored and retrieved, so that the system maintains data integrity.

#### Acceptance Criteria

1. THE Stage_Engine SHALL serialize company state to JSON for API responses
2. THE Stage_Engine SHALL deserialize company state from JSON for API requests
3. FOR ALL valid company states, serializing then deserializing SHALL produce an equivalent company state (round-trip property)
4. THE Event_Journal SHALL serialize event payloads to JSON for storage
5. FOR ALL valid event objects, serializing then deserializing SHALL produce an equivalent event object (round-trip property)

### Requirement 13: Стадия Null (отказ)

**User Story:** As a Manager, I want to move a company to the Null stage at any point, so that I can mark deals that are lost or abandoned.

#### Acceptance Criteria

1. THE Stage_Engine SHALL allow transition to the Null stage from any active stage without additional conditions
2. WHEN a company transitions to the Null stage, THE Event_Journal SHALL record the transition with the source stage
3. WHILE a company is in the Null stage, THE Company_Card SHALL display no available actions
4. THE Stage_Engine SHALL treat the Null stage as terminal with no further transitions allowed

### Requirement 14: Ограничения действий по стадиям

**User Story:** As a Manager, I want the system to prevent me from performing actions not allowed at the current stage, so that the sales process follows the correct sequence.

#### Acceptance Criteria

1. WHILE a company is in the Ice or Touched stage, THE Action_Service SHALL restrict creating invoices, sending KP, planning demos, and conducting demos
2. WHILE a company is in the Aware stage, THE Action_Service SHALL restrict creating invoices and sending KP
3. WHILE a company is in the Interested or demo_planned stage, THE Action_Service SHALL restrict creating invoices and sending KP
4. WHILE a company is in the Demo_done stage, THE Action_Service SHALL allow creating invoices and sending KP
5. IF a Manager attempts a restricted action for the current stage, THEN THE Action_Service SHALL reject the action and return an error listing allowed actions
