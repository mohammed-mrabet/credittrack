import { CurrencyPipe, DatePipe } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { Client, Invoice, InvoiceStatus } from '../../core/models';
import { StatusBadgeComponent } from '../../shared/status-badge.component';

@Component({
  selector: 'app-invoice-list',
  imports: [CurrencyPipe, DatePipe, FormsModule, RouterLink, StatusBadgeComponent],
  templateUrl: './invoice-list.component.html',
})
export class InvoiceListComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly route = inject(ActivatedRoute);

  readonly tabs: { value: InvoiceStatus | ''; label: string }[] = [
    { value: '', label: 'Toutes' },
    { value: 'overdue', label: 'En retard' },
    { value: 'unpaid', label: 'À échéance' },
    { value: 'paid', label: 'Payées' },
  ];

  readonly invoices = signal<Invoice[]>([]);
  readonly clients = signal<Client[]>([]);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  status: InvoiceStatus | '' = '';
  clientId: number | null = null;
  q = '';

  readonly total = computed(() => this.invoices().reduce((sum, i) => sum + i.amount, 0));

  ngOnInit(): void {
    const statut = this.route.snapshot.queryParamMap.get('statut');
    if (statut === 'paid' || statut === 'unpaid' || statut === 'overdue') {
      this.status = statut;
    }
    const client = Number(this.route.snapshot.queryParamMap.get('client'));
    if (client > 0) {
      this.clientId = client;
    }
    this.api.getClients().subscribe((c) => this.clients.set(c));
    this.load();
  }

  setStatus(status: InvoiceStatus | ''): void {
    this.status = status;
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.api.getInvoices({ status: this.status, clientId: this.clientId, q: this.q }).subscribe({
      next: (list) => {
        this.invoices.set(list);
        this.error.set(null);
        this.loading.set(false);
      },
      error: () => {
        this.error.set('Erreur lors du chargement des factures.');
        this.loading.set(false);
      },
    });
  }

  pay(invoice: Invoice): void {
    this.api.markAsPaid(invoice.id).subscribe({
      next: () => this.load(),
      error: (e) => this.error.set(e.error?.error ?? 'Paiement impossible.'),
    });
  }

  remove(invoice: Invoice): void {
    if (!confirm(`Supprimer la facture ${invoice.number} ?`)) return;
    this.api.deleteInvoice(invoice.id).subscribe(() => this.load());
  }
}
