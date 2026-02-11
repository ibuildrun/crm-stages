'use client';

import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Lock, HelpCircle, Loader, CheckCircle } from 'lucide-react';
import IconRenderer from './IconRenderer';

interface Props {
  icon: string;
  title: string;
  description: string;
  locked: boolean;
  requirement?: string;
  onExecute?: () => void;
}

export default function ActionCard({ icon, title, description, locked, requirement, onExecute }: Props) {
  const [shaking, setShaking] = useState(false);
  const [executing, setExecuting] = useState(false);
  const [done, setDone] = useState(false);

  const handleClick = () => {
    if (locked) {
      setShaking(true);
      setTimeout(() => setShaking(false), 400);
      return;
    }
    if (executing || done) return;

    setExecuting(true);
    onExecute?.();

    // Show executing state, then brief success checkmark
    setTimeout(() => {
      setExecuting(false);
      setDone(true);
      setTimeout(() => setDone(false), 1200);
    }, 500);
  };

  return (
    <motion.div
      whileHover={!locked && !executing ? { y: -4, scale: 1.01 } : {}}
      onClick={handleClick}
      className={`
        glass-card p-5 rounded-2xl transition-all duration-300 relative overflow-hidden group
        ${locked ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer hover:shadow-2xl hover:shadow-blue-500/10'}
        ${shaking ? 'shake' : ''}
        ${executing ? 'ring-1 ring-blue-500/30' : ''}
        ${done ? 'ring-1 ring-emerald-500/30' : ''}
      `}
    >
      <div className="flex gap-4 items-start">
        <div
          className={`
            p-3 rounded-xl transition-colors
            ${locked ? 'bg-zinc-800 text-zinc-500' : ''}
            ${executing ? 'bg-blue-500/20 text-blue-400' : ''}
            ${done ? 'bg-emerald-500/20 text-emerald-400' : ''}
            ${!locked && !executing && !done ? 'bg-blue-500/10 text-blue-500 group-hover:bg-blue-500 group-hover:text-white' : ''}
          `}
        >
          <AnimatePresence mode="wait">
            {executing ? (
              <motion.div key="spin" initial={{ scale: 0, rotate: -90 }} animate={{ scale: 1, rotate: 0 }} exit={{ scale: 0 }}>
                <Loader size={24} className="animate-spin" />
              </motion.div>
            ) : done ? (
              <motion.div key="done" initial={{ scale: 0 }} animate={{ scale: 1 }} exit={{ scale: 0 }}>
                <CheckCircle size={24} />
              </motion.div>
            ) : (
              <motion.div key="icon" initial={{ scale: 0.8 }} animate={{ scale: 1 }}>
                <IconRenderer name={icon} size={24} />
              </motion.div>
            )}
          </AnimatePresence>
        </div>
        <div className="flex-1">
          <div className="flex items-center justify-between mb-1">
            <h3 className="font-semibold text-[15px]">
              {executing ? 'Выполняется...' : done ? 'Готово' : title}
            </h3>
            {locked ? (
              <Lock size={14} className="text-zinc-500" />
            ) : (
              <div className={`w-2 h-2 rounded-full transition-colors ${done ? 'bg-emerald-500 scale-125' : 'bg-emerald-500'}`} />
            )}
          </div>
          <p className="text-zinc-400 text-xs leading-relaxed">{description}</p>
        </div>
      </div>

      {locked && requirement && (
        <div className="mt-3 flex items-center gap-2 text-[10px] text-zinc-500 bg-black/20 p-2 rounded-lg">
          <HelpCircle size={12} />
          <span>{requirement}</span>
        </div>
      )}

      {/* Executing progress bar at bottom */}
      {executing && (
        <motion.div
          className="absolute bottom-0 left-0 h-[2px] bg-blue-500"
          initial={{ width: '0%' }}
          animate={{ width: '100%' }}
          transition={{ duration: 0.5, ease: 'easeOut' }}
        />
      )}
    </motion.div>
  );
}
