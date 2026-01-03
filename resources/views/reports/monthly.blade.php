<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rapport Mensuel - {{ $month }}</title>
    <style>
        @page { margin: 15mm; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #3776c5;
        }
        .logo {
            font-size: 24pt;
            font-weight: 800;
            color: #3776c5;
            margin-bottom: 5px;
        }
        .title {
            font-size: 18pt;
            font-weight: 700;
            color: #000;
            margin-bottom: 5px;
        }
        .subtitle {
            font-size: 10pt;
            color: #666;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .stat-row {
            display: table-row;
        }
        .stat-box {
            display: table-cell;
            width: 33.33%;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            text-align: center;
        }
        .stat-label {
            font-size: 8pt;
            color: #666;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 20pt;
            font-weight: 700;
            color: #3776c5;
        }
        .stat-change {
            font-size: 7pt;
            color: #22c55e;
            margin-top: 3px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 12pt;
            font-weight: 700;
            color: #3776c5;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e0e0e0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8pt;
        }
        table thead {
            background: #3776c5;
            color: white;
        }
        table th {
            padding: 6px;
            text-align: left;
            font-weight: 600;
        }
        table td {
            padding: 5px 6px;
            border-bottom: 1px solid #e0e0e0;
        }
        table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7pt;
            color: #999;
            padding: 10px;
            border-top: 1px solid #e0e0e0;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: 600;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .two-col {
            display: table;
            width: 100%;
        }
        .col {
            display: table-cell;
            width: 50%;
            padding-right: 10px;
        }
        .col:last-child {
            padding-right: 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">ALPRAIL</div>
        <div class="title">Rapport Mensuel d'Activité</div>
        <div class="subtitle">{{ $month }} • Période du {{ $period_start }} au {{ $period_end }}</div>
        <div class="subtitle" style="font-size: 8pt; margin-top: 5px;">Généré le {{ $generated_at }}</div>
    </div>

    <!-- KPI Stats -->
    <div class="stats-grid">
        <div class="stat-row">
            <div class="stat-box">
                <div class="stat-label">MEMBRES TOTAL</div>
                <div class="stat-value">{{ $total_members }}</div>
                <div class="stat-change">+{{ $new_members }} ce mois</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">ABONNEMENTS ACTIFS</div>
                <div class="stat-value">{{ $active_subscriptions }}</div>
                <div class="stat-change">+{{ $new_subscriptions }} nouveaux</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">REVENU DU MOIS</div>
                <div class="stat-value">{{ number_format($total_revenue, 0, '.', ' ') }}</div>
                <div class="stat-change">CHF</div>
            </div>
        </div>
    </div>

    <!-- Revenue Section -->
    <div class="section">
        <div class="section-title">Performance Financière</div>
        <table>
            <thead>
                <tr>
                    <th>Indicateur</th>
                    <th style="text-align: right;">Valeur</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Revenue total payé</td>
                    <td style="text-align: right; font-weight: 600;">{{ number_format($total_revenue, 2) }} CHF</td>
                </tr>
                <tr>
                    <td>Revenue en attente</td>
                    <td style="text-align: right;">{{ number_format($pending_revenue, 2) }} CHF</td>
                </tr>
                <tr>
                    <td>Nombre de factures émises</td>
                    <td style="text-align: right;">{{ $invoices_count }}</td>
                </tr>
                <tr>
                    <td>Factures payées</td>
                    <td style="text-align: right;">{{ $paid_invoices }}</td>
                </tr>
                <tr>
                    <td>Taux de paiement</td>
                    <td style="text-align: right; font-weight: 600; color: #22c55e;">{{ $payment_rate }}%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Subscriptions Section -->
    <div class="section">
        <div class="section-title">Distribution des Packs</div>
        <table>
            <thead>
                <tr>
                    <th>Pack</th>
                    <th style="text-align: center;">Abonnements</th>
                    <th style="text-align: right;">Prix (CHF)</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plan_distribution as $plan)
                <tr>
                    <td>{{ $plan->name }}</td>
                    <td style="text-align: center;">{{ $plan->count }}</td>
                    <td style="text-align: right;">{{ number_format($plan->price_chf, 2) }}</td>
                    <td style="text-align: right; font-weight: 600;">{{ number_format($plan->price_chf * $plan->count, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="page-break-after: always;"></div>

    <!-- Top Organizations -->
    <div class="section">
        <div class="section-title">Top 10 Organisations</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Organisation</th>
                    <th style="text-align: center;">Abonnements</th>
                    <th style="text-align: center;">Factures</th>
                </tr>
            </thead>
            <tbody>
                @foreach($top_organizations as $index => $org)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $org->name }}</td>
                    <td style="text-align: center;">{{ $org->subscriptions_count }}</td>
                    <td style="text-align: center;">{{ $org->invoices_count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Recent Invoices -->
    <div class="section">
        <div class="section-title">Dernières Factures du Mois</div>
        <table>
            <thead>
                <tr>
                    <th>N° Facture</th>
                    <th>Organisation</th>
                    <th>Date</th>
                    <th style="text-align: right;">Montant</th>
                    <th style="text-align: center;">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recent_invoices as $invoice)
                <tr>
                    <td style="font-weight: 600;">{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->organization->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($invoice->created_at)->format('d/m/Y') }}</td>
                    <td style="text-align: right;">{{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}</td>
                    <td style="text-align: center;">
                        @if($invoice->status === 'paid')
                        <span class="badge badge-success">Payée</span>
                        @elseif($invoice->status === 'pending')
                        <span class="badge badge-warning">En attente</span>
                        @else
                        <span class="badge badge-danger">En retard</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Activity Summary -->
    <div class="section">
        <div class="section-title">Activité du Mois</div>
        <p style="margin-bottom: 10px;"><strong>Total d'actions enregistrées :</strong> {{ number_format($activity_count) }}</p>
        <table>
            <thead>
                <tr>
                    <th>Type d'action</th>
                    <th style="text-align: right;">Nombre</th>
                </tr>
            </thead>
            <tbody>
                @foreach($top_actions as $action)
                <tr>
                    <td>{{ ucfirst($action->action) }}</td>
                    <td style="text-align: right; font-weight: 600;">{{ $action->count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>ALPRAIL - Route de Lausanne 1, 1000 Lausanne, Suisse | contact@alprail.net | +41 21 555 00 00</p>
        <p>© {{ date('Y') }} ALPRAIL. Tous droits réservés. Document confidentiel.</p>
    </div>
</body>
</html>