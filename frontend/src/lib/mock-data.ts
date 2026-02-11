import type { Company, CrmEvent } from './types';
import type { StageCode } from './stages';
import { STAGES_ORDER } from './stages';

// Мок-данные для демонстрации (заменяются на API-вызовы в проде)

const COMPANY_NAMES = [
  'ООО «ТехноПром»',
  'ЗАО «СтройМастер»',
  'ООО «АгроТех»',
  'ИП Иванов',
  'ООО «МедиаГрупп»',
  'ООО «ЛогистикПро»',
  'ЗАО «ЭнергоСервис»',
  'ООО «ФинКонсалт»',
  'ООО «ПромБезопасность»',
  'ИП Петрова',
  'ООО «ИТ-Решения»',
  'ЗАО «ТрансКом»',
];

export function createMockCompanies(): Company[] {
  const stages: StageCode[] = [
    'Ice', 'Ice', 'Touched', 'Aware', 'Interested',
    'demo_planned', 'Demo_done', 'Committed', 'Customer',
    'Activated', 'Null', 'Touched',
  ];
  return COMPANY_NAMES.map((name, i) => ({
    id: i + 1,
    name,
    stage_code: stages[i],
    stage_name: stages[i],
    created_at: `2026-0${Math.min(i % 3 + 1, 2)}-${String(10 + i).slice(0, 2)} 09:00:00`,
    updated_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
    created_by: 1,
  }));
}

export function createMockCompany(stage: StageCode = 'Touched', id: number = 1): Company {
  const name = COMPANY_NAMES[(id - 1) % COMPANY_NAMES.length] || `Компания #${id}`;
  return {
    id,
    name,
    stage_code: stage,
    stage_name: stage,
    created_at: '2026-01-15 09:00:00',
    updated_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
    created_by: 1,
  };
}

const EVENT_LABELS: Record<string, string> = {
  contact_attempt: 'Попытка контакта',
  lpr_conversation: 'Разговор с ЛПР',
  discovery_filled: 'Дискавери заполнена',
  demo_planned: 'Демо запланировано',
  demo_conducted: 'Демо проведено',
  invoice_created: 'Счёт создан',
  kp_sent: 'КП отправлено',
  payment_received: 'Оплата получена',
  certificate_issued: 'Удостоверение выдано',
  stage_transition: 'Переход стадии',
};

export function getEventLabel(type: string): string {
  return EVENT_LABELS[type] || type;
}

const EVENT_ICONS: Record<string, string> = {
  contact_attempt: 'Phone',
  lpr_conversation: 'MessageSquare',
  discovery_filled: 'ClipboardList',
  demo_planned: 'Calendar',
  demo_conducted: 'Play',
  invoice_created: 'FileText',
  kp_sent: 'Send',
  payment_received: 'CreditCard',
  certificate_issued: 'Award',
  stage_transition: 'ArrowRight',
};

export function getEventIcon(type: string): string {
  return EVENT_ICONS[type] || 'Circle';
}

export function createMockEvents(stage: StageCode): CrmEvent[] {
  const events: CrmEvent[] = [];
  let id = 1;
  const now = new Date();

  const addEvent = (type: string, daysAgo: number, payload: Record<string, unknown> = {}) => {
    const d = new Date(now);
    d.setDate(d.getDate() - daysAgo);
    events.push({
      id: id++,
      company_id: 1,
      manager_id: 1,
      type,
      payload,
      created_at: d.toISOString().slice(0, 19).replace('T', ' '),
    });
  };

  const stageIndex = STAGES_ORDER.indexOf(stage as (typeof STAGES_ORDER)[number]);

  if (stageIndex >= 1) {
    addEvent('lpr_conversation', 14, { comment: 'Поговорили с директором, заинтересован в обучении по ОТ' });
    addEvent('stage_transition', 14, { from: 'Ice', to: 'Touched' });
  }
  if (stageIndex >= 2) {
    addEvent('discovery_filled', 10, { needs: 'Охрана труда, 50 сотрудников', budget: '150 000 ₽' });
    addEvent('stage_transition', 10, { from: 'Touched', to: 'Aware' });
  }
  if (stageIndex >= 3) {
    addEvent('demo_planned', 7, { scheduled_at: '2026-02-20 14:00:00' });
    addEvent('stage_transition', 7, { from: 'Aware', to: 'Interested' });
  }
  if (stageIndex >= 4) {
    addEvent('stage_transition', 5, { from: 'Interested', to: 'demo_planned' });
  }
  if (stageIndex >= 5) {
    addEvent('demo_conducted', 3, {});
    addEvent('stage_transition', 3, { from: 'demo_planned', to: 'Demo_done' });
  }
  if (stageIndex >= 6) {
    addEvent('invoice_created', 2, { amount: 150000 });
    addEvent('stage_transition', 2, { from: 'Demo_done', to: 'Committed' });
  }
  if (stageIndex >= 7) {
    addEvent('payment_received', 1, { amount: 150000 });
    addEvent('stage_transition', 1, { from: 'Committed', to: 'Customer' });
  }
  if (stageIndex >= 8) {
    addEvent('certificate_issued', 0, { number: 'УД-2026-00142' });
    addEvent('stage_transition', 0, { from: 'Customer', to: 'Activated' });
  }

  addEvent('contact_attempt', 15, { comment: 'Первый звонок, не дозвонились' });
  addEvent('contact_attempt', 14, { comment: 'Перезвонили, ответил секретарь' });

  return events.sort((a, b) => b.created_at.localeCompare(a.created_at));
}

/** Get mock company by ID with a deterministic stage */
export function getMockCompanyById(id: number): Company | null {
  const companies = createMockCompanies();
  return companies.find(c => c.id === id) || null;
}
