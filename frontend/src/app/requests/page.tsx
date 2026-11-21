'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import AppShell from '@/components/AppShell';
import ProtectedPage from '@/components/ProtectedPage';
import LoadingState from '@/components/LoadingState';
import Pagination from '@/components/Pagination';
import { StatusBadge, PriorityBadge } from '@/components/Badges';
import { listRequests } from '@/lib/requests';
import type { PaginationMeta, RequestPriority, RequestStatus, ServiceRequest } from '@/lib/types';

export default function RequestsPage() {
  return <ProtectedPage><AppShell><RequestList /></AppShell></ProtectedPage>;
}

function RequestList() {
  const [search, setSearch] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [status, setStatus] = useState<RequestStatus | ''>('');
  const [priority, setPriority] = useState<RequestPriority | ''>('');
  const [page, setPage] = useState(1);
  const [requests, setRequests] = useState<ServiceRequest[]>([]);
  const [meta, setMeta] = useState<PaginationMeta>({ current_page: 1, per_page: 10, total: 0, last_page: 1 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    const timer = window.setTimeout(() => {
      setDebouncedSearch(search);
      setPage(1);
    }, 350);
    return () => window.clearTimeout(timer);
  }, [search]);

  useEffect(() => {
    listRequests({ search: debouncedSearch, status, priority, page, per_page: 10 })
      .then((response) => {
        setRequests(response.data);
        setMeta(response.meta);
        if (response.meta.last_page > 0 && page > response.meta.last_page) setPage(response.meta.last_page);
      })
      .catch((caught) => setError(caught instanceof Error ? caught.message : 'Unable to load requests.'))
      .finally(() => setLoading(false));
  }, [debouncedSearch, status, priority, page]);

  const clearFilters = () => {
    setSearch('');
    setDebouncedSearch('');
    setStatus('');
    setPriority('');
    setPage(1);
  };

  return (
    <div>
      <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><p className="text-sm text-slate-500">Requests</p><h1 className="mt-1 text-2xl font-semibold">Service requests</h1></div>
        <Link href="/requests/create" className="rounded-md bg-slate-900 px-4 py-2 text-center text-sm font-semibold text-white">Create request</Link>
      </div>

      <div className="mt-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-[1fr_160px_160px_auto]">
        <div><label htmlFor="search" className="block text-xs font-medium text-slate-600">Search title</label><input id="search" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search requests" className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" /></div>
        <div><label htmlFor="status" className="block text-xs font-medium text-slate-600">Status</label><select id="status" value={status} onChange={(event) => { setStatus(event.target.value as RequestStatus | ''); setPage(1); }} className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"><option value="">All statuses</option><option>OPEN</option><option>IN_PROGRESS</option><option>RESOLVED</option></select></div>
        <div><label htmlFor="priority" className="block text-xs font-medium text-slate-600">Priority</label><select id="priority" value={priority} onChange={(event) => { setPriority(event.target.value as RequestPriority | ''); setPage(1); }} className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"><option value="">All priorities</option><option>LOW</option><option>MEDIUM</option><option>HIGH</option></select></div>
        <button type="button" onClick={clearFilters} className="self-end rounded-md border border-slate-300 px-3 py-2 text-sm font-medium">Clear</button>
      </div>

      {error && <div role="alert" className="mt-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</div>}
      {loading ? <LoadingState label="Loading requests..." /> : requests.length === 0 ? (
        <div className="mt-6 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center"><p className="font-medium">No requests found</p><p className="mt-1 text-sm text-slate-500">Try clearing filters or create a new request.</p></div>
      ) : (
        <div className="mt-6 space-y-3">
          {requests.map((request) => (
            <Link key={request.id} href={`/requests/${request.id}`} className="block rounded-lg border border-slate-200 bg-white p-4 hover:border-slate-300">
              <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p className="font-semibold">{request.title}</p><p className="mt-1 line-clamp-2 text-sm text-slate-600">{request.description}</p><p className="mt-2 text-xs text-slate-500">Created {new Date(request.created_at).toLocaleString()}</p></div><div className="flex gap-2"><PriorityBadge priority={request.priority} /><StatusBadge status={request.status} /></div></div>
            </Link>
          ))}
          <Pagination meta={meta} onPageChange={setPage} />
        </div>
      )}
    </div>
  );
}
