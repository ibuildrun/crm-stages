'use client';

import type { StageCode } from '@/lib/stages';
import { STAGE_CONFIG } from '@/lib/stages';
import { SnowflakeIcon, WaveIcon, EyeIcon, CrownIcon } from '@/icons/StageIcons';
import { ThumbsUp, Calendar, CheckCircle, Handshake, Star, XCircle } from 'lucide-react';

function StageIcon({ stage, color }: { stage: StageCode; color: string }) {
  switch (stage) {
    case 'Ice': return <SnowflakeIcon size={16} stroke={color} />;
    case 'Touched': return <WaveIcon size={16} stroke={color} />;
    case 'Aware': return <EyeIcon size={16} stroke={color} />;
    case 'Interested': return <ThumbsUp size={16} color={color} strokeWidth={1.5} />;
    case 'demo_planned': return <Calendar size={16} color={color} strokeWidth={1.5} />;
    case 'Demo_done': return <CheckCircle size={16} color={color} strokeWidth={1.5} />;
    case 'Committed': return <Handshake size={16} color={color} strokeWidth={1.5} />;
    case 'Customer': return <Star size={16} color={color} strokeWidth={1.5} />;
    case 'Activated': return <CrownIcon size={16} stroke={color} />;
    case 'Null': return <XCircle size={16} color={color} strokeWidth={1.5} />;
    default: return null;
  }
}

export default function StageBadge({ stage }: { stage: StageCode }) {
  const config = STAGE_CONFIG[stage];
  if (!config) return null;

  return (
    <div
      className="flex items-center gap-2 px-3 py-1 rounded-full border text-xs font-medium"
      style={{
        borderColor: `${config.color}40`,
        backgroundColor: `${config.color}15`,
        color: config.color,
      }}
    >
      <StageIcon stage={stage} color={config.color} />
      <span>{config.label}</span>
    </div>
  );
}
