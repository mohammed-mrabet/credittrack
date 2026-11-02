import { CurrencyPipe, DatePipe, DecimalPipe } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { Dashboard } from '../../core/models';

@Component({
  selector: 'app-dashboard',
  imports: [CurrencyPipe, DatePipe, DecimalPipe, RouterLink],
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent implements OnInit {
  private readonly api = inject(ApiService);

  readonly data = signal<Dashboard | null>(null);
  readonly error = signal<string | null>(null);

  /** Largeur de chaque barre du classement, relative au plus gros encours. */
  readonly maxOutstanding = computed(() =>
    Math.max(1, ...(this.data()?.topClients.map((c) => c.outstanding) ?? [])),
  );

  ngOnInit(): void {
    this.api.getDashboard().subscribe({
      next: (d) => this.data.set(d),
      error: () => this.error.set("Impossible de joindre l'API. Le backend est-il démarré ?"),
    });
  }
}
