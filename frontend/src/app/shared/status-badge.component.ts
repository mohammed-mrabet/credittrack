import { Component, computed, input } from '@angular/core';
import { InvoiceStatus } from '../core/models';

const LABELS: Record<InvoiceStatus, string> = {
  paid: 'Payée',
  unpaid: 'À échéance',
  overdue: 'En retard',
};

@Component({
  selector: 'app-status-badge',
  template: `<span [class]="'badge badge--' + status()">{{ label() }}</span>`,
})
export class StatusBadgeComponent {
  readonly status = input.required<InvoiceStatus>();
  readonly label = computed(() => LABELS[this.status()]);
}

export { LABELS as STATUS_LABELS };
