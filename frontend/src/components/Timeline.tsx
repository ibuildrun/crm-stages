'use client';

import { useState } from 'react';
import { motion } from 'framer-motion';
import type { CrmEvent } from '@/lib/types';
import { getEventLabel, getEventIcon } from '@/lib/mock-data';
import IconRenderer from './IconRenderer';
import { Filter } from 'lucide-react';

interface Props {
  events: CrmEvent[];
}

function formatTime(dateStr: string): string {
  const d = new Date(dateStr.replace(' ', 'T'));
  const now = new Date();
  const diffMs = now.getTime() - d.getTime();
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffDays === 0) {
    return d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
  }
  if (diffDays === 1) return 'Вчера';
  if (diffDays < 7) return `${diffDays} дн. назад`;
  return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
}

function formatPayload(event: CrmEvent): string | null {
  const p = event.payload;
  if (!p || Object.keys(p).length === 0) return null;

  if (event.type === 'stage_transition') {
    return `${p.from} → ${p.to}`;
  }
  if (p.comment) return String(p.comment);
  if (p.amount) return `${Number(p.amount).toLocaleString('ru-RU')} ₽`;
  if (p.number) return `№ ${p.number}`;
  if (p.scheduled_at) return `Дата: ${String(p.scheduled_at).slice(0, 16)}`;
  if (p.needs) return String(p.needs);
  return null;
}

export default function Timeline({ events }: Props) {
  const [filter, setFilter] = useState<string | null>(null);
  const [filterOpen, setFilterOpen] = useState(false);

  const filtered = filter ? events.filter(e => e.type === filter) : events;
  const eventTypes = [...new Set(events.map(e => e.type))];

  return (
    <div className="flex flex-col h-full">
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-2">
          <h2 className="text-sm font-semibold uppercase tracking-wider text-zinc-400">
            История событий
          </h2>
          <div className="w-5 h-5 flex items-center justify-center bg-blue-500/20 text-blue-500 rounded-full text-[10px] font-bold">
            {filtered.length}
          </div>
        </div>
        <div className="relative">
          <button
            onClick={() => setFilterOpen(prev => !prev)}
            className={`p-2 rounded-lg transition-colors ${filterOpen ? 'bg-white/10 text-white' : 'hover:bg-white/5 text-zinc-500'}`}
          >
            <Filter size={16} />
          </button>
          {filterOpen && (
            <div className="absolute right-0 top-full mt-1 z-30 bg-zinc-900 border border-white/10 rounded-lg p-1.5 min-w-[160px] shadow-xl">
              <button
                onClick={() => { setFilter(null); setFilterOpen(false); }}
                className={`w-full text-left px-2 py-1.5 rounded text-[11px] transition-colors ${!filter ? 'text-blue-400 bg-blue-500/10' : 'text-zinc-400 hover:bg-white/5'}`}
              >
                Все события
              </button>
              {eventTypes.map(t => (
                <button
                  key={t}
                  onClick={() => { setFilter(t); setFilterOpen(false); }}
                  className={`w-full text-left px-2 py-1.5 rounded text-[11px] transition-colors ${filter === t ? 'text-blue-400 bg-blue-500/10' : 'text-zinc-400 hover:bg-white/5'}`}
                >
                  {getEventLabel(t)}
                </button>
              ))}
            </div>
          )}
        </div>
      </div>

      <div className="flex-1 overflow-y-auto pr-2 space-y-6 scrollbar-hide">
        {filtered.map((event, index) => (
          <motion.div
            key={event.id}
            initial={{ opacity: 0, x: 20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: index * 0.1 }}
            className="relative flex gap-4"
          >
            {/* Thread Line */}
            {index !== filtered.length - 1 && (
              <div className="absolute left-[15px] top-8 bottom-[-24px] w-[1px] bg-white/5" />
            )}

            <div
              className={`
                w-8 h-8 rounded-full flex items-center justify-center shrink-0 z-10
                ${event.type === 'stage_transition'
                  ? 'bg-blue-500/20 text-blue-400 ring-4 ring-blue-500/5'
                  : 'bg-zinc-800 text-zinc-400'}
              `}
            >
              <IconRenderer name={getEventIcon(event.type)} size={14} />
            </div>

            <div className="flex-1 pb-2">
              <div className="flex items-center justify-between mb-1">
                <span className="text-sm font-medium">{getEventLabel(event.type)}</span>
                <span className="text-[10px] text-zinc-500">{formatTime(event.created_at)}</span>
              </div>
              {formatPayload(event) && (
                <p className="text-zinc-400 text-xs leading-relaxed mb-2">
                  {formatPayload(event)}
                </p>
              )}
              <div className="flex items-center gap-2">
                <div className="w-4 h-4 rounded-full bg-zinc-700 flex items-center justify-center text-[8px] font-bold">
                  М
                </div>
                <span className="text-[10px] text-zinc-500">Менеджер #{event.manager_id}</span>
              </div>
            </div>
          </motion.div>
        ))}

        {filtered.length === 0 && (
          <p className="text-zinc-600 text-xs text-center py-8">Нет событий</p>
        )}
      </div>
    </div>
  );
}
