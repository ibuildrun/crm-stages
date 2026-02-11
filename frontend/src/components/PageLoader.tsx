'use client';

import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Loader } from 'lucide-react';

interface Props {
  message?: string;
  delay?: number;
  children: React.ReactNode;
}

export default function PageLoader({ message = 'Загрузка данных...', delay = 800, children }: Props) {
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const timer = setTimeout(() => setLoading(false), delay);
    return () => clearTimeout(timer);
  }, [delay]);

  if (loading) {
    return (
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        className="min-h-screen flex items-center justify-center bg-[#0B0D0E]"
      >
        <div className="flex flex-col items-center gap-4">
          <Loader className="animate-spin text-blue-500" size={32} />
          <p className="text-zinc-500 text-sm font-medium animate-pulse">{message}</p>
        </div>
      </motion.div>
    );
  }

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      transition={{ duration: 0.3 }}
    >
      {children}
    </motion.div>
  );
}
