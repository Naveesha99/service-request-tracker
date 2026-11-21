import { apiRequest } from './api';
import type {
  CreateRequestPayload,
  PaginatedRequests,
  RequestHistoryRecord,
  RequestPriority,
  RequestStatus,
  ServiceRequest,
  UpdateStatusPayload,
} from './types';

export type RequestFilters = {
  search?: string;
  status?: RequestStatus | '';
  priority?: RequestPriority | '';
  page?: number;
  per_page?: number;
};

export async function listRequests(filters: RequestFilters = {}): Promise<PaginatedRequests> {
  const params = new URLSearchParams();
  if (filters.search?.trim()) params.set('search', filters.search.trim());
  if (filters.status) params.set('status', filters.status);
  if (filters.priority) params.set('priority', filters.priority);
  if (filters.page) params.set('page', String(filters.page));
  if (filters.per_page) params.set('per_page', String(filters.per_page));

  const query = params.toString();
  return apiRequest<PaginatedRequests>(`/api/requests${query ? `?${query}` : ''}`);
}

export async function createRequest(payload: CreateRequestPayload) {
  return apiRequest<{ message: string; data: ServiceRequest }>('/api/requests', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export async function getRequest(id: number) {
  return apiRequest<{ data: ServiceRequest }>(`/api/requests/${id}`);
}

export async function getRequestHistory(id: number) {
  return apiRequest<{ data: RequestHistoryRecord[] }>(`/api/requests/${id}/history`);
}

export async function updateRequestStatus(id: number, payload: UpdateStatusPayload) {
  return apiRequest<{ data: ServiceRequest }>(`/api/requests/${id}/status`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  });
}
