import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { Client, Dashboard, Invoice, InvoiceFilters, NewClient, NewInvoice } from './models';

@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = environment.apiUrl;

  getDashboard(): Observable<Dashboard> {
    return this.http.get<Dashboard>(`${this.baseUrl}/dashboard`);
  }

  getInvoices(filters: InvoiceFilters = {}): Observable<Invoice[]> {
    let params = new HttpParams();
    if (filters.status) params = params.set('status', filters.status);
    if (filters.clientId) params = params.set('clientId', filters.clientId);
    if (filters.q?.trim()) params = params.set('q', filters.q.trim());
    return this.http.get<Invoice[]>(`${this.baseUrl}/invoices`, { params });
  }

  createInvoice(invoice: NewInvoice): Observable<Invoice> {
    return this.http.post<Invoice>(`${this.baseUrl}/invoices`, invoice);
  }

  markAsPaid(id: number, paidAt?: string): Observable<Invoice> {
    return this.http.post<Invoice>(`${this.baseUrl}/invoices/${id}/pay`, paidAt ? { paidAt } : {});
  }

  deleteInvoice(id: number): Observable<void> {
    return this.http.delete<void>(`${this.baseUrl}/invoices/${id}`);
  }

  getClients(): Observable<Client[]> {
    return this.http.get<Client[]>(`${this.baseUrl}/clients`);
  }

  createClient(client: NewClient): Observable<Client> {
    return this.http.post<Client>(`${this.baseUrl}/clients`, client);
  }
}
