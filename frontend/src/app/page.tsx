'use client';

import Link from 'next/link';
import { motion } from 'framer-motion';
import { STAGES_ORDER, STAGE_CONFIG } from '@/lib/stages';
import { createMockCompanies } from '@/lib/mock-data';
import Navbar from '@/components/Navbar';
import PageLoader from '@/components/PageLoader';
import { ArrowRight, Building2, TrendingUp, Users, CheckCircle2, ChevronLeft, ChevronRight } from 'lucide-react';
import { useRef, useState, useCallback, useEffect } from 'react';

export default function DashboardPage() {
  const companies = createMockCompanies();

  const byStage: Record<string, typeof companies> = {};
  for (const c of companies) {
    if (!byStage[c.stage_code]) byStage[c.stage_code] = [];
    byStage[c.stage_code].push(c);
  }

  const activeCompanies = companies.filter(c => c.stage_code !== 'Null' && c.stage_code !== 'Activated');
  const wonCompanies = companies.filter(c => c.stage_code === 'Activated');
  const lostCompanies = companies.filter(c => c.stage_code === 'Null');

  const scrollRef = useRef<HTMLDivElement>(null);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(true);
  const isDragging = useRef(false);
  const dragStartX = useRef(0);
  const dragScrollLeft = useRef(0);
  const wasDragged = useRef(false);

  const updateScrollState = useCallback(() => {
    const el = scrollRef.current;
    if (!el) return;
    setCanScrollLeft(el.scrollLeft > 2);
    setCanScrollRight(el.scrollLeft + el.clientWidth < el.scrollWidth - 2);
  }, []);

  useEffect(() => {
    const el = scrollRef.current;
    if (!el) return;
    updateScrollState();
    el.addEventListener('scroll', updateScrollState, { passive: true });
    window.addEventListener('resize', updateScrollState);
    return () => {
      el.removeEventListener('scroll', updateScrollState);
      window.removeEventListener('resize', updateScrollState);
    };
  }, [updateScrollState]);

  const scroll = (direction: 'left' | 'right') => {
    const el = scrollRef.current;
    if (!el) return;
    const columnWidth = 224;
    const delta = direction === 'left' ? -columnWidth * 2 : columnWidth * 2;
    el.scrollBy({ left: delta, behavior: 'smooth' });
    // Update state after smooth scroll finishes
    setTimeout(updateScrollState, 350);
  };

  const onMouseDown = (e: React.MouseEvent) => {
    const el = scrollRef.current;
    if (!el) return;
    isDragging.current = true;
    wasDragged.current = false;
    dragStartX.current = e.pageX - el.offsetLeft;
    dragScrollLeft.current = el.scrollLeft;
    el.style.cursor = 'grabbing';
    el.style.userSelect = 'none';
  };

  const onMouseMove = (e: React.MouseEvent) => {
    if (!isDragging.current) return;
    const el = scrollRef.current;
    if (!el) return;
    e.preventDefault();
    const x = e.pageX - el.offsetLeft;
    const walk = x - dragStartX.current;
    if (Math.abs(walk) > 3) wasDragged.current = true;
    el.scrollLeft = dragScrollLeft.current - walk;
  };

  const onMouseUp = () => {
    isDragging.current = false;
    const el = scrollRef.current;
    if (el) {
      el.style.cursor = 'grab';
      el.style.userSelect = '';
    }
  };

  const onClickCapture = (e: React.MouseEvent) => {
    if (wasDragged.current) {
      e.preventDefault();
      e.stopPropagation();
      wasDragged.current = false;
    }
  };

  return (
    <PageLoader message="Синхронизация воронки..." delay={1000}>
      <Navbar />
      <main className="max-w-7xl mx-auto p-6 space-y-6">
        {/* Stats */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
          {[
            { label: 'Всего компаний', value: companies.length, icon: Building2, color: 'text-blue-400' },
            { label: 'В работе', value: activeCompanies.length, icon: TrendingUp, color: 'text-amber-400' },
            { label: 'Выиграно', value: wonCompanies.length, icon: CheckCircle2, color: 'text-emerald-400' },
            { label: 'Отказ', value: lostCompanies.length, icon: Users, color: 'text-zinc-400' },
          ].map((stat, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.1 }}
              className="glass-card p-6 rounded-2xl flex items-center justify-between group cursor-default"
            >
              <div>
                <p className="text-zinc-500 text-[10px] font-bold uppercase tracking-widest mb-1">{stat.label}</p>
                <p className="text-2xl font-bold">{stat.value}</p>
              </div>
              <stat.icon size={18} className={stat.color} />
            </motion.div>
          ))}
        </div>

        {/* Funnel board */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.3 }}
          className="glass-card rounded-[24px] overflow-hidden"
        >
          <div className="p-6 border-b border-white/5 flex items-center justify-between">
            <div>
              <h2 className="text-sm font-semibold">Воронка продаж</h2>
              <p className="text-xs text-zinc-500 mt-1">Канбан-доска по стадиям</p>
            </div>
            <div className="flex gap-1">
              <button
                onClick={() => scroll('left')}
                disabled={!canScrollLeft}
                className="p-2 rounded-lg border border-white/10 hover:bg-white/10 disabled:opacity-20 disabled:cursor-default transition-all"
                aria-label="Прокрутить влево"
              >
                <ChevronLeft size={16} />
              </button>
              <button
                onClick={() => scroll('right')}
                disabled={!canScrollRight}
                className="p-2 rounded-lg border border-white/10 hover:bg-white/10 disabled:opacity-20 disabled:cursor-default transition-all"
                aria-label="Прокрутить вправо"
              >
                <ChevronRight size={16} />
              </button>
            </div>
          </div>

          <div
            ref={scrollRef}
            className="overflow-x-auto scrollbar-hide cursor-grab"
            onMouseDown={onMouseDown}
            onMouseMove={onMouseMove}
            onMouseUp={onMouseUp}
            onMouseLeave={onMouseUp}
            onClickCapture={onClickCapture}
          >
            <div className="flex gap-0 min-w-max">
              {STAGES_ORDER.map(stage => {
                const config = STAGE_CONFIG[stage];
                const stageCompanies = byStage[stage] || [];
                return (
                  <div
                    key={stage}
                    className="w-56 shrink-0 border-r border-white/5 last:border-r-0"
                  >
                    <div className="p-3 border-b border-white/5 bg-white/[0.02]">
                      <div className="flex items-center justify-between mb-1">
                        <span className="text-[10px] font-bold uppercase tracking-wider" style={{ color: config.color }}>
                          {config.mls}
                        </span>
                        <span className="text-[10px] text-zinc-600 bg-white/5 px-1.5 py-0.5 rounded-full">
                          {stageCompanies.length}
                        </span>
                      </div>
                      <p className="text-xs font-medium truncate">{config.label}</p>
                    </div>

                    <div className="p-2 space-y-2 min-h-[200px]">
                      {stageCompanies.map(company => (
                        <Link
                          key={company.id}
                          href={`/companies/${company.id}`}
                          className="block p-3 rounded-xl bg-white/[0.03] border border-white/5 hover:border-white/15 hover:bg-white/[0.06] transition-all group"
                        >
                          <p className="text-xs font-medium truncate group-hover:text-blue-400 transition-colors">
                            {company.name}
                          </p>
                          <p className="text-[10px] text-zinc-600 mt-1">ID: {company.id}</p>
                        </Link>
                      ))}
                      {stageCompanies.length === 0 && (
                        <div className="flex items-center justify-center h-20 text-zinc-700 text-[10px]">
                          Пусто
                        </div>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </motion.div>

        {/* Null + Activated section */}
        {(lostCompanies.length > 0 || wonCompanies.length > 0) && (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {wonCompanies.length > 0 && (
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.5 }}
                className="glass-card rounded-2xl p-6"
              >
                <h3 className="text-xs font-semibold text-emerald-400 uppercase tracking-wider mb-3">Активирована (выиграно)</h3>
                <div className="space-y-2">
                  {wonCompanies.map(c => (
                    <Link key={c.id} href={`/companies/${c.id}`} className="flex items-center justify-between p-2 rounded-lg hover:bg-white/5 transition-colors">
                      <span className="text-xs">{c.name}</span>
                      <ArrowRight size={12} className="text-zinc-600" />
                    </Link>
                  ))}
                </div>
              </motion.div>
            )}
            {lostCompanies.length > 0 && (
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.6 }}
                className="glass-card rounded-2xl p-6"
              >
                <h3 className="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-3">Отказ (Null)</h3>
                <div className="space-y-2">
                  {lostCompanies.map(c => (
                    <Link key={c.id} href={`/companies/${c.id}`} className="flex items-center justify-between p-2 rounded-lg hover:bg-white/5 transition-colors">
                      <span className="text-xs text-zinc-400">{c.name}</span>
                      <ArrowRight size={12} className="text-zinc-700" />
                    </Link>
                  ))}
                </div>
              </motion.div>
            )}
          </div>
        )}
      </main>
    </PageLoader>
  );
}
