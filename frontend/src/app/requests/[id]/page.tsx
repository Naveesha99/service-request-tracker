'use client';

import Link from 'next/link';
import { useParams, useRouter } from 'next/navigation';
import { useCallback, useEffect, useState } from 'react';
import AppShell from '@/components/AppShell';
import ProtectedPage from '@/components/ProtectedPage';
import LoadingState from '@/components/LoadingState';
import { StatusBadge, PriorityBadge } from '@/components/Badges';
import { useAuth } from '@/components/AuthProvider';
import { ApiError } from '@/lib/api';
import { getRequest, getRequestHistory, updateRequestStatus } from '@/lib/requests';
import type { RequestHistoryRecord, RequestStatus, ServiceRequest } from '@/lib/types';

export default function RequestDetailPage() {
  return <ProtectedPage><AppShell><RequestDetail /></AppShell></ProtectedPage>;
}

function RequestDetail() {
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const { refreshUser } = useAuth();
  const id = Number(params.id);
  const [request, setRequest] = useState<ServiceRequest | null>(null);
  const [history, setHistory] = useState<RequestHistoryRecord[]>([]);
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);
  const [error, setError] = useState('');
  const [conflict, setConflict] = useState('');
  const [remark, setRemark] = useState('');

  const load = useCallback(async () => {
    if (!Number.isInteger(id) || id < 1) {
      setError('Request not found.');
      setLoading(false);
      return;
    }
    setLoading(true);
    setError('');
    try {
      const [requestResponse, historyResponse] = await Promise.all([getRequest(id), getRequestHistory(id)]);
      setRequest(requestResponse.data);
      setHistory(historyResponse.data);
    } catch (caught) {
      if (caught instanceof ApiError && caught.status === 401) {
        await refreshUser();
        router.replace('/login');
        return;
      }
      if (caught instanceof ApiError && caught.status === 404) setError('Request not found or it is not owned by you.');
      else setError(caught instanceof Error ? caught.message : 'Unable to load the request.');
    } finally {
      setLoading(false);
    }
  }, [id, refreshUser, router]);

  useEffect(() => {
    const timer = window.setTimeout(() => { void load(); }, 0);
    return () => window.clearTimeout(timer);
  }, [load]);

  const changeStatus = async (status: RequestStatus) => {
    if (!request) return;
    if (!window.confirm(`Change this request to ${status === 'IN_PROGRESS' ? 'In progress' : 'Resolved'}?`)) return;

    setUpdating(true);
    setConflict('');
    setError('');
    try {
      await updateRequestStatus(id, {
        status,
        expected_version: request.version,
        remark: remark.trim() || undefined,
      });
      setRemark('');
      await load();
    } catch (caught) {
      if (caught instanceof ApiError) {
        if (caught.status === 409) {
          setConflict(caught.message);
          await load();
        } else if (caught.status === 401) {
          await refreshUser();
          router.replace('/login');
        } else if (caught.status === 422) {
          setError(caught.message);
        } else setError(caught.message);
      } else setError('Unable to update the request.');
    } finally {
      setUpdating(false);
    }
  };

  if (loading) return <LoadingState label="Loading request..." />;

  return (
    <div>
      <Link href="/requests" className="text-sm font-medium text-slate-600 hover:text-slate-900">← Back to requests</Link>
      {error && <div role="alert" className="mt-5 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</div>}
      {conflict && <div role="alert" className="mt-5 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">{conflict} The latest server state is shown below; no update was retried automatically.</div>}
      {!request ? <div className="mt-6 rounded-lg border border-slate-200 bg-white p-8 text-center text-sm text-slate-600">The request could not be displayed.</div> : (
        <>
          <header className="mt-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div><p className="text-sm text-slate-500">Request #{request.id}</p><h1 className="mt-1 text-2xl font-semibold">{request.title}</h1></div>
            <div className="flex gap-2"><PriorityBadge priority={request.priority} /><StatusBadge status={request.status} /></div>
          </header>

          <section className="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
              <div className="rounded-lg border border-slate-200 bg-white p-5"><h2 className="font-semibold">Description</h2><p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">{request.description}</p></div>
              <div className="rounded-lg border border-slate-200 bg-white p-5"><h2 className="font-semibold">Status history</h2>{history.length === 0 ? <p className="mt-3 text-sm text-slate-500">No status changes yet.</p> : (
                <ol className="mt-4 space-y-4">{history.map((entry) => (
                  <li key={entry.id} className="border-l-2 border-slate-300 pl-4"><p className="text-sm font-medium">{entry.from_status.replace('_', ' ')} → {entry.to_status.replace('_', ' ')}</p>{entry.remark && <p className="mt-1 text-sm text-slate-600">{entry.remark}</p>}<p className="mt-1 text-xs text-slate-500">{entry.actor.name} · {new Date(entry.created_at).toLocaleString()}</p></li>
                ))}</ol>
              )}</div>
            </div>

            <aside className="h-fit rounded-lg border border-slate-200 bg-white p-5">
              <h2 className="font-semibold">Request details</h2>
              <dl className="mt-4 space-y-3 text-sm"><div><dt className="text-slate-500">Priority</dt><dd className="mt-1"><PriorityBadge priority={request.priority} /></dd></div><div><dt className="text-slate-500">Status</dt><dd className="mt-1"><StatusBadge status={request.status} /></dd></div><div><dt className="text-slate-500">Current version</dt><dd className="mt-1 font-medium">{request.version}</dd></div><div><dt className="text-slate-500">Created</dt><dd className="mt-1">{new Date(request.created_at).toLocaleString()}</dd></div><div><dt className="text-slate-500">Updated</dt><dd className="mt-1">{new Date(request.updated_at).toLocaleString()}</dd></div></dl>

              {request.status !== 'RESOLVED' && (
                <div className="mt-6 border-t border-slate-200 pt-5"><label htmlFor="remark" className="text-sm font-medium">Remark (optional)</label><textarea id="remark" value={remark} maxLength={500} rows={3} onChange={(event) => setRemark(event.target.value)} className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" /><button type="button" disabled={updating} onClick={() => changeStatus(request.status === 'OPEN' ? 'IN_PROGRESS' : 'RESOLVED')} className="mt-3 w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">{updating ? 'Updating...' : request.status === 'OPEN' ? 'Move to In progress' : 'Resolve request'}</button></div>
              )}
            </aside>
          </section>
        </>
      )}
    </div>
  );
}
