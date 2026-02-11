'use client';

import * as Icons from 'lucide-react';

interface Props {
  name: string;
  size?: number;
  className?: string;
  strokeWidth?: number;
}

export default function IconRenderer({ name, size = 18, className, strokeWidth = 1.5 }: Props) {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const Icon = (Icons as any)[name];
  if (!Icon) return null;
  return <Icon size={size} className={className} strokeWidth={strokeWidth} />;
}
