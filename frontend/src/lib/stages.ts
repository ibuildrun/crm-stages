// Зеркало бэкенда: StageMap.php, ActionRestrictions.php

export type StageCode =
  | 'Ice' | 'Touched' | 'Aware' | 'Interested'
  | 'demo_planned' | 'Demo_done' | 'Committed'
  | 'Customer' | 'Activated' | 'Null';

export interface StageConfig {
  color: string;
  label: string;
  mls: string;
  instruction: string;
  exitCondition: string | null;
}

export const STAGES_ORDER: StageCode[] = [
  'Ice', 'Touched', 'Aware', 'Interested',
  'demo_planned', 'Demo_done', 'Committed',
  'Customer', 'Activated',
];

export const STAGE_CONFIG: Record<StageCode, StageConfig> = {
  Ice:          { color: '#3B82F6', label: 'Ice',          mls: 'C0', instruction: 'Совершите звонок компании. При ответе зафиксируйте разговор с ЛПР и оставьте комментарий.', exitCondition: 'Разговор с ЛПР' },
  Touched:      { color: '#60A5FA', label: 'Touched',      mls: 'C1', instruction: 'Заполните форму дискавери на основе разговора с ЛПР.', exitCondition: 'Заполненная форма дискавери' },
  Aware:        { color: '#2DD4BF', label: 'Aware',        mls: 'C2', instruction: 'Запланируйте демонстрацию продукта. Укажите дату и время.', exitCondition: 'Запланированная демонстрация' },
  Interested:   { color: '#10B981', label: 'Interested',   mls: 'W1', instruction: 'Подтвердите дату демонстрации и подготовьтесь к проведению.', exitCondition: 'Демо с датой и временем' },
  demo_planned: { color: '#F59E0B', label: 'Demo Planned', mls: 'W2', instruction: 'Проведите демонстрацию по ссылке. Нажмите кнопку проведения демо.', exitCondition: 'Проведённая демонстрация' },
  Demo_done:    { color: '#F97316', label: 'Demo Done',    mls: 'W3', instruction: 'Создайте счёт или отправьте КП. Демо действительно 60 дней.', exitCondition: 'Счёт или КП (демо < 60 дней)' },
  Committed:    { color: '#EC4899', label: 'Committed',    mls: 'H1', instruction: 'Ожидайте оплату от клиента. Отслеживайте статус счёта.', exitCondition: 'Подтверждение оплаты' },
  Customer:     { color: '#8B5CF6', label: 'Customer',     mls: 'H2', instruction: 'Оформите и выдайте удостоверение клиенту.', exitCondition: 'Выданное удостоверение' },
  Activated:    { color: '#22C55E', label: 'Activated',     mls: 'A1', instruction: 'Клиент активирован. Сделка завершена.', exitCondition: null },
  Null:         { color: '#6B7280', label: 'Отказ',         mls: 'N0', instruction: 'Сделка отклонена или потеряна.', exitCondition: null },
};

export interface ActionDef {
  key: string;
  title: string;
  description: string;
  icon: string;
  eventType: string;
}

export const ALL_ACTIONS: ActionDef[] = [
  { key: 'call',              title: 'Позвонить',             description: 'Совершить звонок клиенту',           icon: 'Phone',      eventType: 'contact_attempt' },
  { key: 'fill_discovery',    title: 'Заполнить дискавери',   description: 'Заполнить форму потребностей',       icon: 'ClipboardList', eventType: 'discovery_filled' },
  { key: 'plan_demo',         title: 'Запланировать демо',    description: 'Назначить дату демонстрации',        icon: 'Calendar',   eventType: 'demo_planned' },
  { key: 'conduct_demo',      title: 'Провести демо',        description: 'Запустить демонстрацию по ссылке',   icon: 'Play',       eventType: 'demo_conducted' },
  { key: 'create_invoice',    title: 'Создать счёт',         description: 'Выставить счёт клиенту',             icon: 'FileText',   eventType: 'invoice_created' },
  { key: 'send_kp',           title: 'Отправить КП',         description: 'Отправить коммерческое предложение', icon: 'Send',       eventType: 'kp_sent' },
  { key: 'record_payment',    title: 'Зафиксировать оплату', description: 'Подтвердить получение оплаты',       icon: 'CreditCard', eventType: 'payment_received' },
  { key: 'issue_certificate', title: 'Выдать удостоверение', description: 'Оформить и выдать удостоверение',    icon: 'Award',      eventType: 'certificate_issued' },
];

// Ограничения из ActionRestrictions.php
const RESTRICTIONS: Record<StageCode, string[]> = {
  Ice:          ['create_invoice', 'send_kp', 'plan_demo', 'conduct_demo'],
  Touched:      ['create_invoice', 'send_kp', 'plan_demo', 'conduct_demo'],
  Aware:        ['create_invoice', 'send_kp', 'conduct_demo'],
  Interested:   ['create_invoice', 'send_kp'],
  demo_planned: ['create_invoice', 'send_kp'],
  Demo_done:    [],
  Committed:    [],
  Customer:     [],
  Activated:    [],
  Null:         ['call', 'fill_discovery', 'plan_demo', 'conduct_demo', 'create_invoice', 'send_kp', 'record_payment', 'issue_certificate'],
};

export function getAllowedActions(stage: StageCode): ActionDef[] {
  const restricted = RESTRICTIONS[stage] || [];
  return ALL_ACTIONS.filter(a => !restricted.includes(a.key));
}

export function getRestrictedActions(stage: StageCode): ActionDef[] {
  const restricted = RESTRICTIONS[stage] || [];
  return ALL_ACTIONS.filter(a => restricted.includes(a.key));
}

export function isTerminal(stage: StageCode): boolean {
  return stage === 'Null' || stage === 'Activated';
}
