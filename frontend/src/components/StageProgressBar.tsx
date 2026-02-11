'use client';

import { motion } from 'framer-motion';
import { STAGES_ORDER, STAGE_CONFIG, type StageCode } from '@/lib/stages';
import { Check, Circle } from 'lucide-react';

interface Props {
  currentStage: StageCode;
  onStageSelect?: (stage: StageCode) => void;
}

export default function StageProgressBar({ currentStage, onStageSelect }: Props) {
  const currentIndex = STAGES_ORDER.indexOf(currentStage);
  const isNull = currentStage === 'Null';
  const clickable = !!onStageSelect;

  return (
    <div className="w-full py-8">
      <div className="flex items-center justify-between relative">
        {/* Background Line */}
        <div className="absolute top-1/2 left-0 w-full h-[2px] bg-white/10 -translate-y-1/2 -z-10" />

        {/* Progress Line */}
        <motion.div
          className="absolute top-1/2 left-0 h-[2px] -translate-y-1/2 -z-10"
          style={{ backgroundColor: isNull ? '#6B7280' : '#3B82F6' }}
          initial={{ width: 0 }}
          animate={{ width: isNull ? '0%' : `${(currentIndex / (STAGES_ORDER.length - 1)) * 100}%` }}
          transition={{ duration: 0.6, ease: 'easeOut' }}
        />

        {STAGES_ORDER.map((stage, index) => {
          const isCompleted = !isNull && index < currentIndex;
          const isCurrent = !isNull && index === currentIndex;
          const config = STAGE_CONFIG[stage];

          return (
            <div
              key={stage}
              className="flex flex-col items-center group relative"
              onClick={() => clickable && onStageSelect?.(stage)}
            >
              <motion.div
                whileHover={clickable ? { scale: 1.15 } : {}}
                className={`
                  w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300
                  ${clickable ? 'cursor-pointer' : 'cursor-default'}
                  ${isCompleted ? 'bg-blue-500 shadow-[0_0_15px_rgba(59,130,246,0.5)]' : ''}
                  ${isCurrent ? 'bg-zinc-900 border-2 border-blue-500 ring-4 ring-blue-500/20' : ''}
                  ${!isCompleted && !isCurrent ? 'bg-zinc-800 border border-white/10 text-white/40' : 'text-white'}
                `}
              >
                {isCompleted ? (
                  <Check size={16} strokeWidth={3} />
                ) : isCurrent ? (
                  <div className="w-2 h-2 rounded-full bg-blue-500 animate-pulse" />
                ) : (
                  <Circle size={10} className="opacity-40" />
                )}
              </motion.div>

              <div className={`
                absolute -bottom-8 whitespace-nowrap text-[10px] font-medium tracking-wide transition-colors
                ${isCurrent ? 'text-blue-400' : 'text-zinc-500'}
              `}>
                {config.label.toUpperCase()}
              </div>

              {/* Tooltip */}
              <div className="absolute -top-12 opacity-0 group-hover:opacity-100 transition-opacity bg-zinc-900 border border-white/10 px-3 py-1.5 rounded-lg text-[10px] whitespace-nowrap pointer-events-none z-20">
                {config.mls} · {config.exitCondition || 'Терминальная'}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
