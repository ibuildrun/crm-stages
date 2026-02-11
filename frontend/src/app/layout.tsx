import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'ГлавПро CRM — Управление стадиями',
  description: 'CRM-система управления воронкой продаж',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="ru" className="dark">
      <body className="bg-[#0B0D0E] text-white min-h-screen">{children}</body>
    </html>
  );
}
