import { Component } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: './app.component.html',
  styleUrl: './app.component.scss',
})
export class AppComponent {
  readonly nav = [
    { path: '/', label: 'Tableau de bord', exact: true },
    { path: '/factures', label: 'Factures', exact: false },
    { path: '/clients', label: 'Clients', exact: false },
  ];
}
