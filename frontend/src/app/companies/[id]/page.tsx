import Link from 'next/link';
import { createMockCompanies, getMockCompanyById } from '@/lib/mock-data';
import Navbar from '@/components/Navbar';
import CompanyCardView from '@/components/CompanyCardView';

export function generateStaticParams() {
  return createMockCompanies().map(c => ({ id: String(c.id) }));
}

export default function CompanyCardPage({ params }: { params: { id: string } }) {
  const company = getMockCompanyById(parseInt(params.id, 10));

  if (!company) {
    return (
      <>
        <Navbar />
        <main className="max-w-7xl mx-auto p-6">
          <p className="text-zinc-400 text-sm">Компания не найдена</p>
          <Link href="/companies" className="text-blue-400 text-xs mt-2 inline-block hover:underline">
            ← К списку компаний
          </Link>
        </main>
      </>
    );
  }

  return (
    <>
      <Navbar />
      <CompanyCardView company={company} />
    </>
  );
}
