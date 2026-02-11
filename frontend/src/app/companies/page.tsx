'use client';

import { useState } from 'react';
import Link from 'next/link';
import { motion } from 'framer-motion';
import { STAGE_CONFIG, STAGES_ORDER, type StageCode } from '@/lib/stages';
import { createMockCompanies } from '@/lib/mock-data';
import Navbar from '@/components/Navbar';
import PageLoader from '@/components/PageLoader';
import StageBadge from '@/components/StageBadge';
import { Search, ArrowRight, Filter } from 'lucide-react';

export default function CompaniesPage() {
  const companies = createMockCompanies();
  const [search, setSearch] = useState('');
  const [stageFilter, setStageFilter] = useState<StageCode | 'all'>('all');

  const filtered = companies.filter(c => {
    const matchesSearch = c.name.toLowerCase().includes(search.toLowerCase());
    const matchesStage = stageFilter === 'all' || c.stage_code === stageFilter;
    return matchesSearch && matchesStage;
  });

  const allStages: (StageCode | 'all')[] = ['all', ...STAGES_ORDER, 'Null'];

  return (
    <PageLoader message="Загрузка компаний..." delay={700}>
      <Navbar />
      <main className="max-w-7xl mx-auto p-6 space-y-6">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
        >
          <div>
            <h1 className="text-lg font-bold">Компании</h1>
            <p className="text-xs text-zinc-500">{filtered.length} из {companies.length}</p>
          </div>
        </motion.div>

        {/* Filters */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
          className="flex flex-col sm:flex-row gap-3"
        >
          <div className="relative flex-1">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500" />
            <input
              type="text"
              placeholder="Поиск по названию..."
              value={search}
              onChange={e => setSearch(e.target.value)}
              className="w-full pl-9 pr-4 py-2 rounded-xl bg-white/5 border border-white/10 text-xs focus:outline-none focus:border-blue-500/50 focus:ring-1 focus:ring-blue-500 transition-all"
            />
          </div>
          <div className="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-hide">
            <Filter size={12} className="text-zinc-500 shrink-0" />
            {allStages.map(s => (
              <button
                key={s}
                onClick={() => setStageFilter(s)}
                className={`
                  px-2.5 py-1 rounded-lg text-[10px] font-medium whitespace-nowrap transition-colors
                  ${stageFilter === s
                    ? 'bg-blue-500/20 text-blue-400 border border-blue-500/30'
                    : 'bg-white/5 text-zinc-500 border border-white/5 hover:bg-white/10'}
                `}
              >
                {s === 'all' ? 'Все' : STAGE_CONFIG[s]?.label || s}
              </button>
            ))}
          </div>
        </motion.div>

        {/* Table */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="glass-card rounded-[24px] overflow-hidden"
        >
          <div className="overflow-x-auto scrollbar-hide">
            <table className="w-full text-xs">
              <thead>
                <tr className="border-b border-white/5 text-zinc-500">
                  <th className="text-left p-4 font-semibold">ID</th>
                  <th className="text-left p-4 font-semibold">Компания</th>
                  <th className="text-left p-4 font-semibold">Стадия</th>
                  <th className="text-left p-4 font-semibold">MLS</th>
                  <th className="text-left p-4 font-semibold">Создана</th>
                  <th className="text-right p-4 font-semibold"></th>
                </tr>
              </thead>
              <tbody>
                {filtered.map((company, index) => {
                  const config = STAGE_CONFIG[company.stage_code];
                  return (
                    <motion.tr
                      key={company.id}
                      initial={{ opacity: 0, x: -10 }}
                      animate={{ opacity: 1, x: 0 }}
                      transition={{ delay: index * 0.05 }}
                      className="border-b border-white/5 hover:bg-white/[0.03] transition-colors"
                    >
                      <td className="p-4 text-zinc-500">{company.id}</td>
                      <td className="p-4">
                        <Link
                          href={`/companies/${company.id}`}
                          className="font-medium hover:text-blue-400 transition-colors"
                        >
                          {company.name}
                        </Link>
                      </td>
                      <td className="p-4">
                        <StageBadge stage={company.stage_code} />
                      </td>
                      <td className="p-4 text-zinc-400">{config?.mls}</td>
                      <td className="p-4 text-zinc-500">{company.created_at.slice(0, 10)}</td>
                      <td className="p-4 text-right">
                        <Link
                          href={`/companies/${company.id}`}
                          className="inline-flex items-center gap-1 text-zinc-500 hover:text-blue-400 transition-colors"
                        >
                          Открыть <ArrowRight size={12} />
                        </Link>
                      </td>
                    </motion.tr>
                  );
                })}
                {filtered.length === 0 && (
                  <tr>
                    <td colSpan={6} className="p-8 text-center text-zinc-600">
                      Компании не найдены
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </motion.div>
      </main>
    </PageLoader>
  );
}
