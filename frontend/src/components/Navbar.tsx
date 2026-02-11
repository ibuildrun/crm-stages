'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { Search } from 'lucide-react';

const NAV_ITEMS = [
  { href: '/', label: 'Воронка' },
  { href: '/companies', label: 'Компании' },
];

export default function Navbar() {
  const pathname = usePathname() ?? '/';

  return (
    <nav className="border-b border-white/5 bg-black/20 backdrop-blur-lg sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
        <div className="flex items-center gap-8">
          <Link href="/" className="flex items-center gap-2">
            <div className="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center font-bold text-sm">Г</div>
            <span className="font-bold tracking-tight">ГлавПро CRM</span>
          </Link>
          <div className="hidden md:flex items-center gap-6 text-sm text-zinc-400">
            {NAV_ITEMS.map(item => {
              const isActive = item.href === '/'
                ? pathname === '/'
                : pathname.startsWith(item.href);
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className={`hover:text-white cursor-pointer transition-colors ${isActive ? 'text-white' : ''}`}
                >
                  {item.label}
                </Link>
              );
            })}
          </div>
        </div>
        <div className="flex items-center gap-4">
          <div className="relative hidden sm:block">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500" size={14} />
            <input
              type="text"
              placeholder="Поиск сделок..."
              className="bg-white/5 border border-white/10 rounded-full pl-9 pr-4 py-1.5 text-xs focus:ring-1 focus:ring-blue-500 outline-none transition-all w-48 focus:w-64"
            />
          </div>
          <div className="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-500 to-purple-500 border border-white/20" />
        </div>
      </div>
    </nav>
  );
}
