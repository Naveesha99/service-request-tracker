'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import AppShell from '@/components/AppShell';
import ProtectedPage from '@/components/ProtectedPage';
import LoadingState from '@/components/LoadingState';
import { StatusBadge, PriorityBadge } from '@/components/Badges';
import { useAuth } from '@/components/AuthProvider';
import { listRequests } from '@/lib/requests';
import type { ServiceRequest } from '@/lib/types';

export default function DashboardPage() {
  return <ProtectedPage><AppShell><Dashboard /></AppShell></ProtectedPage>;
}

function Dashboard() {
  const { user } = useAuth();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [recent, setRecent] = useState<ServiceRequest[]>([]);
  const [counts, setCounts] = useState({ total: 0, open: 0, progress: 0, resolved: 0 });

  useEffect(() => {
    Promise.all([
      listRequests({ per_page: 5, page: 1 }),
      listRequests({ status: 'OPEN', per_page: 1, page: 1 }),
      listRequests({ status: 'IN_PROGRESS', per_page: 1, page: 1 }),
      listRequests({ status: 'RESOLVED', per_page: 1, page: 1 }),
    ]).then(([all, open, progress, resolved]) => {
      setRecent(all.data);
      setCounts({ total: all.meta.total, open: open.meta.total, progress: progress.meta.total, resolved: resolved.meta.total });
    }).catch((caught) => setError(caught instanceof Error ? caught.message : 'Unable to load dashboard.'))
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <LoadingState label="Loading dashboard..." />;

  return (
    <div>
      <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="text-sm text-slate-500">Dashboard</p>
          <h1 className="mt-1 text-2xl font-semibold text-slate-950">Welcome, {user?.name}</h1>
        </div>
        <Link href="/requests/create" className="rounded-md bg-slate-900 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-slate-800">Create request</Link>
      </div>

      {error && <div role="alert" className="mt-6 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</div>}

      <section className="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Request summary">
        {[
          ['Total', counts.total], ['Open', counts.open], ['In progress', counts.progress], ['Resolved', counts.resolved],
        ].map(([label, value]) => (
          <div key={String(label)} className="rounded-lg border border-slate-200 bg-white p-4">
            <p className="text-sm text-slate-500">{label}</p>
            <p className="mt-2 text-2xl font-semibold">{value}</p>
          </div>
        ))}
      </section>

      <section className="mt-8">
        <div className="flex items-center justify-between">
          <h2 className="text-lg font-semibold">Recent requests</h2>
          <Link href="/requests" className="text-sm font-medium text-slate-700 underline underline-offset-4">View all</Link>
        </div>
        {recent.length === 0 ? (
          <div className="mt-4 rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">No service requests yet.</div>
        ) : (
          <div className="mt-4 space-y-3">
            {recent.map((request) => (
              <Link key={request.id} href={`/requests/${request.id}`} className="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 hover:border-slate-300 sm:flex-row sm:items-center sm:justify-between">
                <div><p className="font-medium">{request.title}</p><p className="mt-1 text-xs text-slate-500">Updated {new Date(request.updated_at).toLocaleString()}</p></div>
                <div className="flex gap-2"><PriorityBadge priority={request.priority} /><StatusBadge status={request.status} /></div>
              </Link>
            ))}
          </div>
        )}
      </section>
    </div>
  );
}
