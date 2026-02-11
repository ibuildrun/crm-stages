'use client';

import { useState, useCallback, useRef, useEffect } from 'react';
import Link from 'next/link';
import { motion, AnimatePresence } from 'framer-motion';
import {
  CheckCircle, FileText, Copy, Check,
  HelpCircle, Loader, ArrowRightLeft, XCircle, Star, MoreHorizontal, ArrowLeft,
  Download, Printer, Trash2,
} from 'lucide-react';
import type { StageCode } from '@/lib/stages';
import {
  STAGE_CONFIG, STAGES_ORDER, getAllowedActions, getRestrictedActions, isTerminal,
} from '@/lib/stages';
import type { Company, CrmEvent } from '@/lib/types';
import { createMockEvents } from '@/lib/mock-data';
import StageBadge from '@/components/StageBadge';
import StageProgressBar from '@/components/StageProgressBar';
import ActionCard from '@/components/ActionCard';
import Timeline from '@/components/Timeline';

interface Toast { id: number; message: string; type: 'success' | 'error' | 'info'; }

function ToastContainer({ toasts }: { toasts: Toast[] }) {
  return (
    <div className="fixed bottom-8 right-8 z-50 flex flex-col gap-2 pointer-events-none">
      <AnimatePresence>
        {toasts.map(t => (
          <motion.div
            key={t.id}
            initial={{ opacity: 0, y: 20, scale: 0.95, x: 40 }}
            animate={{ opacity: 1, y: 0, scale: 1, x: 0 }}
            exit={{ opacity: 0, x: 40, scale: 0.95 }}
            transition={{ type: 'spring', stiffness: 400, damping: 25 }}
            className={`
              pointer-events-auto px-4 py-2.5 rounded-xl text-xs font-medium shadow-lg flex items-center gap-2
              ${t.type === 'success' ? 'bg-emerald-500/90 text-white' : ''}
              ${t.type === 'error' ? 'bg-red-500/90 text-white' : ''}
              ${t.type === 'info' ? 'bg-blue-500/90 text-white' : ''}
            `}
          >
            {t.type === 'success' && <CheckCircle size={14} />}
            {t.type === 'error' && <XCircle size={14} />}
            {t.type === 'info' && <ArrowRightLeft size={14} />}
            {t.message}
          </motion.div>
        ))}
      </AnimatePresence>
    </div>
  );
}

export default function CompanyCardView({ company }: { company: Company }) {
  const [stage, setStage] = useState<StageCode>(company.stage_code);
  const [events, setEvents] = useState<CrmEvent[]>(() => createMockEvents(company.stage_code));
  const [transitioning, setTransitioning] = useState(false);
  const [copied, setCopied] = useState(false);
  const [toasts, setToasts] = useState<Toast[]>([]);
  const [showNullConfirm, setShowNullConfirm] = useState(false);
  const [starred, setStarred] = useState(false);
  const [showMenu, setShowMenu] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);

  const config = STAGE_CONFIG[stage];
  const allowed = getAllowedActions(stage);
  const restricted = getRestrictedActions(stage);

  const addToast = useCallback((message: string, type: Toast['type'] = 'info') => {
    const id = Date.now();
    setToasts(prev => [...prev, { id, message, type }]);
    setTimeout(() => setToasts(prev => prev.filter(t => t.id !== id)), 3000);
  }, []);

  const handleAction = useCallback((_actionKey: string, eventType: string, title: string) => {
    // No global overlay — ActionCard handles its own loading state
    const newEvent: CrmEvent = {
      id: Date.now(),
      company_id: company.id,
      manager_id: 1,
      type: eventType,
      payload: {},
      created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
    };
    setEvents(prev => [newEvent, ...prev]);
    addToast(`✓ ${title}`, 'success');
  }, [addToast, company.id]);

  const handleTransition = useCallback(() => {
    if (isTerminal(stage) || transitioning) return;
    setTransitioning(true);
    setTimeout(() => {
      const idx = STAGES_ORDER.indexOf(stage);
      const next = STAGES_ORDER[idx + 1];
      if (next) {
        const transitionEvent: CrmEvent = {
          id: Date.now(),
          company_id: company.id,
          manager_id: 1,
          type: 'stage_transition',
          payload: { from: stage, to: next },
          created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
        };
        setStage(next);
        setEvents(prev => [transitionEvent, ...prev]);
        addToast(`Переход: ${STAGE_CONFIG[stage].label} → ${STAGE_CONFIG[next].label}`, 'success');
      }
      setTransitioning(false);
    }, 600);
  }, [stage, transitioning, addToast, company.id]);

  const handleTransitionNull = useCallback(() => {
    if (stage === 'Null' || transitioning) {
      if (stage === 'Null') addToast('Компания уже в стадии Null', 'error');
      setShowNullConfirm(false);
      return;
    }
    setShowNullConfirm(false);
    setTransitioning(true);
    setTimeout(() => {
      const transitionEvent: CrmEvent = {
        id: Date.now(),
        company_id: company.id,
        manager_id: 1,
        type: 'stage_transition',
        payload: { from: stage, to: 'Null' },
        created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
      };
      setStage('Null');
      setEvents(prev => [transitionEvent, ...prev]);
      addToast('Компания переведена в Null (отказ)', 'info');
      setTransitioning(false);
    }, 400);
  }, [stage, transitioning, addToast, company.id]);

  const handleCopy = () => {
    navigator.clipboard?.writeText(config.instruction).catch(() => {});
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  // Close menu on outside click
  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        setShowMenu(false);
      }
    }
    if (showMenu) {
      document.addEventListener('mousedown', handleClickOutside);
      return () => document.removeEventListener('mousedown', handleClickOutside);
    }
  }, [showMenu]);

  const handleExport = () => {
    setShowMenu(false);
    addToast('Экспорт данных компании...', 'info');
  };

  const handlePrint = () => {
    setShowMenu(false);
    window.print();
  };

  return (
    <>
      <main className="max-w-7xl mx-auto p-6 space-y-6">
        <Link href="/companies" className="inline-flex items-center gap-1.5 text-xs text-zinc-500 hover:text-white transition-colors">
          <ArrowLeft size={12} />
          Компании
        </Link>

        {/* Company Card Main Container */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="glass-card rounded-[24px] overflow-hidden shadow-2xl"
        >
          {/* Header Section */}
          <div className="p-8 border-b border-white/5">
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
              <div className="space-y-2">
                <div className="flex items-center gap-3">
                  <h1 className="text-2xl font-bold tracking-tight">{company.name}</h1>
                  <CheckCircle className="text-blue-500 fill-blue-500/10" size={20} />
                </div>
                <div className="flex items-center gap-3 text-xs text-zinc-500">
                  <span>ID: {company.id}</span>
                  <span>·</span>
                  <span>Создана: {company.created_at.slice(0, 10)}</span>
                  {isTerminal(stage) ? (
                    <>
                      <div className="w-1 h-1 rounded-full bg-zinc-500" />
                      <span className="text-zinc-500">{stage === 'Null' ? 'Отказ' : 'Завершена'}</span>
                    </>
                  ) : (
                    <>
                      <div className="w-1 h-1 rounded-full bg-emerald-500" />
                      <span className="text-emerald-500">Активна</span>
                    </>
                  )}
                </div>
              </div>

              <div className="flex items-center gap-4">
                <StageBadge stage={stage} />
                <div className="h-8 w-[1px] bg-white/10 mx-2" />
                <div className="flex -space-x-2">
                  <div className="w-8 h-8 rounded-full border-2 border-[#0B0D0E] bg-blue-500 flex items-center justify-center text-[10px] font-bold">М</div>
                  <div className="w-8 h-8 rounded-full border-2 border-[#0B0D0E] bg-purple-500 flex items-center justify-center text-[10px] font-bold">А</div>
                </div>
                {!isTerminal(stage) && (
                  <>
                    <button
                      onClick={handleTransition}
                      disabled={transitioning}
                      className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-xs font-medium transition-all disabled:opacity-50 active:scale-95"
                    >
                      {transitioning ? (
                        <Loader size={12} className="animate-spin" />
                      ) : (
                        <ArrowRightLeft size={12} />
                      )}
                      {transitioning ? 'Переход...' : 'Далее'}
                    </button>
                    <button
                      onClick={() => setShowNullConfirm(true)}
                      disabled={transitioning}
                      className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-xs font-medium text-zinc-400 transition-all disabled:opacity-50 active:scale-95"
                    >
                      <XCircle size={12} />
                      Отказ
                    </button>
                  </>
                )}
                <button
                  onClick={() => { setStarred(s => !s); addToast(starred ? 'Убрано из избранного' : 'Добавлено в избранное', starred ? 'info' : 'success'); }}
                  className="p-2 text-zinc-400 hover:text-yellow-400 transition-colors active:scale-90"
                  aria-label={starred ? 'Убрать из избранного' : 'Добавить в избранное'}
                >
                  <Star size={20} className={starred ? 'fill-yellow-400 text-yellow-400' : ''} />
                </button>
                <div className="relative" ref={menuRef}>
                  <button
                    onClick={() => setShowMenu(v => !v)}
                    className={`p-2 transition-colors active:scale-90 ${showMenu ? 'text-white' : 'text-zinc-400 hover:text-white'}`}
                    aria-label="Дополнительные действия"
                  >
                    <MoreHorizontal size={20} />
                  </button>
                  <AnimatePresence>
                    {showMenu && (
                      <motion.div
                        initial={{ opacity: 0, scale: 0.95, y: -4 }}
                        animate={{ opacity: 1, scale: 1, y: 0 }}
                        exit={{ opacity: 0, scale: 0.95, y: -4 }}
                        transition={{ duration: 0.15 }}
                        className="absolute right-0 top-full mt-1 w-44 rounded-xl bg-zinc-900 border border-white/10 shadow-xl overflow-hidden z-50"
                      >
                        <button onClick={handleExport} className="flex items-center gap-2 w-full px-3 py-2.5 text-xs text-zinc-300 hover:bg-white/5 transition-colors">
                          <Download size={14} /> Экспорт
                        </button>
                        <button onClick={handlePrint} className="flex items-center gap-2 w-full px-3 py-2.5 text-xs text-zinc-300 hover:bg-white/5 transition-colors">
                          <Printer size={14} /> Печать
                        </button>
                        <div className="border-t border-white/5" />
                        <button onClick={() => { setShowMenu(false); addToast('Удаление недоступно в демо-режиме', 'error'); }} className="flex items-center gap-2 w-full px-3 py-2.5 text-xs text-red-400 hover:bg-red-500/10 transition-colors">
                          <Trash2 size={14} /> Удалить
                        </button>
                      </motion.div>
                    )}
                  </AnimatePresence>
                </div>
              </div>
            </div>

            {/* Stage Progress Bar */}
            <StageProgressBar currentStage={stage} onStageSelect={transitioning ? undefined : setStage} />
          </div>

          {/* Three Column Layout */}
          <div className="grid grid-cols-1 md:grid-cols-12 gap-0 border-t border-white/5 h-[560px]">

            {/* Column 1: Sales Script / Instruction */}
            <div className="md:col-span-3 p-8 border-r border-white/5 space-y-6 overflow-y-auto scrollbar-hide">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2 text-zinc-400">
                  <FileText size={18} />
                  <span className="text-xs font-semibold uppercase tracking-wider">Инструкция</span>
                </div>
                <button
                  onClick={handleCopy}
                  className="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-xs transition-all active:scale-95"
                >
                  <AnimatePresence mode="wait">
                    {copied ? (
                      <motion.div
                        key="copied"
                        initial={{ scale: 0 }}
                        animate={{ scale: 1 }}
                        className="flex items-center gap-1 text-emerald-500"
                      >
                        <Check size={14} />
                        <span>Скопировано</span>
                      </motion.div>
                    ) : (
                      <motion.div
                        key="copy"
                        initial={{ scale: 0 }}
                        animate={{ scale: 1 }}
                        className="flex items-center gap-1"
                      >
                        <Copy size={14} />
                        <span>Копировать</span>
                      </motion.div>
                    )}
                  </AnimatePresence>
                </button>
              </div>

              <div className="space-y-6">
                <h2 className="text-lg font-semibold leading-tight text-white/90">
                  {config.instruction}
                </h2>
              </div>

              {/* Exit Condition Tip */}
              {config.exitCondition && (
                <div className="p-4 rounded-xl bg-blue-500/5 border border-blue-500/10 space-y-2 mt-auto">
                  <div className="flex items-center gap-2 text-blue-400">
                    <HelpCircle size={14} />
                    <span className="text-[10px] font-bold uppercase tracking-widest">Условие перехода</span>
                  </div>
                  <p className="text-xs text-zinc-500 italic">
                    {config.exitCondition}
                  </p>
                </div>
              )}

              {isTerminal(stage) && (
                <div className="p-4 rounded-xl bg-zinc-800/50 border border-zinc-700/50">
                  <p className="text-xs text-zinc-500">Терминальная стадия. Дальнейшие переходы невозможны.</p>
                </div>
              )}
            </div>

            {/* Column 2: Available Actions */}
            <div className="md:col-span-5 p-8 border-r border-white/5 space-y-6 overflow-y-auto scrollbar-hide">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2 text-zinc-400">
                  <HelpCircle size={18} />
                  <span className="text-xs font-semibold uppercase tracking-wider">Доступные действия</span>
                </div>
                <span className="text-[10px] text-zinc-600">{allowed.length} из {allowed.length + restricted.length}</span>
              </div>

              <div className="grid grid-cols-1 gap-4">
                {allowed.map(action => (
                  <ActionCard
                    key={action.key}
                    icon={action.icon}
                    title={action.title}
                    description={action.description}
                    locked={false}
                    onExecute={() => handleAction(action.key, action.eventType, action.title)}
                  />
                ))}
                {restricted.map(action => (
                  <ActionCard
                    key={action.key}
                    icon={action.icon}
                    title={action.title}
                    description={action.description}
                    locked={true}
                    requirement={`Недоступно на стадии ${config.label}`}
                  />
                ))}
              </div>
            </div>

            {/* Column 3: Activity Log */}
            <div className="md:col-span-4 p-8 bg-black/10 overflow-y-auto scrollbar-hide">
              <Timeline events={events} />
            </div>

          </div>
        </motion.div>

        {/* Footer Stats */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
          {[
            { label: 'Стадия', value: config.mls, trend: config.label },
            { label: 'Событий', value: String(events.length), trend: 'всего' },
            { label: 'Доступно действий', value: String(allowed.length), trend: `из ${allowed.length + restricted.length}` },
            { label: 'Статус', value: isTerminal(stage) ? 'Завершена' : 'Активна', trend: isTerminal(stage) ? '—' : config.exitCondition || '' },
          ].map((stat, i) => (
            <div key={i} className="glass-card p-6 rounded-2xl flex items-center justify-between group cursor-default">
              <div>
                <p className="text-zinc-500 text-[10px] font-bold uppercase tracking-widest mb-1">{stat.label}</p>
                <p className="text-xl font-bold">{stat.value}</p>
              </div>
              <div className="text-emerald-500 text-xs font-medium bg-emerald-500/10 px-2 py-1 rounded-lg max-w-[100px] truncate">
                {stat.trend}
              </div>
            </div>
          ))}
        </div>
      </main>

      {/* Null Confirm Modal */}
      <AnimatePresence>
        {showNullConfirm && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
            onClick={() => setShowNullConfirm(false)}
          >
            <motion.div
              initial={{ scale: 0.95, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.95, opacity: 0 }}
              className="glass-card rounded-2xl p-6 max-w-sm mx-4 space-y-4"
              onClick={e => e.stopPropagation()}
            >
              <h3 className="font-semibold">Перевести в Null (отказ)?</h3>
              <p className="text-xs text-zinc-400">Компания будет переведена в терминальную стадию. Дальнейшие переходы станут невозможны.</p>
              <div className="flex gap-3 justify-end">
                <button onClick={() => setShowNullConfirm(false)} className="px-3 py-1.5 rounded-lg bg-zinc-800 text-xs hover:bg-zinc-700 transition-all active:scale-95">Отмена</button>
                <button onClick={handleTransitionNull} className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-600 text-xs hover:bg-red-500 transition-all active:scale-95">
                  <XCircle size={12} />
                  Подтвердить
                </button>
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Loading Overlay */}
      <AnimatePresence>
        {transitioning && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm"
          >
            <motion.div
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.9, opacity: 0 }}
              className="flex flex-col items-center gap-3"
            >
              <Loader className="animate-spin text-blue-500" size={32} />
              <p className="text-zinc-400 text-xs font-medium animate-pulse">Обработка...</p>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>

      <ToastContainer toasts={toasts} />
    </>
  );
}
