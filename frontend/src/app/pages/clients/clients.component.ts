import { DatePipe } from '@angular/common';
import { Component, OnInit, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/api.service';
import { ApiErrors, Client } from '../../core/models';

@Component({
  selector: 'app-clients',
  imports: [DatePipe, ReactiveFormsModule, RouterLink],
  templateUrl: './clients.component.html',
})
export class ClientsComponent implements OnInit {
  private readonly api = inject(ApiService);
  private readonly fb = inject(FormBuilder);

  readonly clients = signal<Client[]>([]);
  readonly serverErrors = signal<ApiErrors>({});

  readonly form = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.maxLength(150)]],
    email: ['', Validators.email],
    phone: [''],
  });

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.api.getClients().subscribe((c) => this.clients.set(c));
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.api.createClient(this.form.getRawValue()).subscribe({
      next: () => {
        this.form.reset();
        this.serverErrors.set({});
        this.load();
      },
      error: (e) => this.serverErrors.set(e.error?.errors ?? {}),
    });
  }
}
