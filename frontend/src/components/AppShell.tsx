'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { useState, type ReactNode } from 'react';
import { useAuth } from './AuthProvider';

export default function AppShell({ children }: { children: ReactNode }) {
  const { user, logout } = useAuth();
  const router = useRouter();
  const pathname = usePathname();
  const [loggingOut, setLoggingOut] = useState(false);

  const handleLogout = async () => {
    setLoggingOut(true);
    try {
      await logout();
      router.replace('/login');
    } finally {
      setLoggingOut(false);
    }
  };

  const links = [
    { href: '/dashboard', label: 'Dashboard' },
    { href: '/requests', label: 'Service requests' },
  ];

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
          <Link href="/dashboard" className="font-semibold text-slate-950">Service Request Tracker</Link>
          <nav className="hidden items-center gap-1 sm:flex" aria-label="Main navigation">
            {links.map((link) => {
              const active = pathname === link.href || pathname.startsWith(`${link.href}/`);
              return (
                <Link key={link.href} href={link.href} className={`rounded-md px-3 py-2 text-sm font-medium ${active ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'}`}>
                  {link.label}
                </Link>
              );
            })}
          </nav>
          <div className="flex items-center gap-3">
            {user && <span className="hidden text-sm text-slate-600 md:block">{user.name}</span>}
            <button type="button" onClick={handleLogout} disabled={loggingOut} className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50 disabled:opacity-60">
              {loggingOut ? 'Signing out...' : 'Logout'}
            </button>
          </div>
        </div>
        <nav className="flex gap-1 border-t border-slate-100 px-4 py-2 sm:hidden" aria-label="Mobile navigation">
          {links.map((link) => (
            <Link key={link.href} href={link.href} className="rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
              {link.label}
            </Link>
          ))}
        </nav>
      </header>
      <main className="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-8">{children}</main>
    </div>
  );
}
