<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 12mm;
            size: A4 portrait;
        }
        
        body {
            font-family: 'Poppins', Arial, sans-serif;
            font-size: 9pt;
            color: #000;
            line-height: 1.3;
        }
        
        .header {
            margin-bottom: 25px;
        }
        
        .header table {
            width: 100%;
        }
        
        .logo {
            font-size: 20pt;
            font-weight: 700;
            color: #000;
            letter-spacing: 1px;
        }
        
        .header-right {
            text-align: right;
        }
        
        .header-right h1 {
            font-size: 22pt;
            font-weight: 400;
            color: #000;
            margin: 0 0 4px 0;
        }
        
        .header-right p {
            font-size: 8pt;
            color: #000;
            margin: 1px 0;
        }
        
        .invoice-info {
            margin: 20px 0 25px 0;
        }
        
        .invoice-info table {
            width: 100%;
        }
        
        .invoice-info td {
            vertical-align: top;
            padding: 0;
        }
        
        .invoice-to {
            width: 50%;
        }
        
        .invoice-to h3 {
            font-size: 8pt;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 6px 0;
            font-weight: 600;
        }
        
        .invoice-to p {
            margin: 2px 0;
            font-size: 9pt;
            color: #000;
        }
        
        .invoice-details {
            width: 50%;
            text-align: right;
        }
        
        .invoice-number {
            font-size: 15pt;
            font-weight: 700;
            color: #000;
            margin: 0 0 10px 0;
        }
        
        .detail-row {
            margin: 3px 0;
        }
        
        .detail-label {
            font-size: 8pt;
            color: #000;
            display: inline-block;
            width: 100px;
            text-align: right;
            margin-right: 8px;
        }
        
        .detail-value {
            font-size: 9pt;
            color: #000;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            background: #e8f5e9;
            color: #000;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .items-table {
            width: 100%;
            margin: 20px 0 15px 0;
            border-collapse: collapse;
        }
        
        .items-table thead th {
            background: #f5f5f5;
            padding: 8px 8px;
            text-align: left;
            font-size: 8pt;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-weight: 700;
            border-bottom: 2px solid #000;
        }
        
        .items-table thead th.text-right {
            text-align: right;
        }
        
        .items-table tbody td {
            padding: 10px 8px;
            font-size: 9pt;
            color: #000;
            border-bottom: 1px solid #ddd;
        }
        
        .items-table tbody td.text-right {
            text-align: right;
        }
        
        .item-description {
            font-weight: 600;
            font-size: 9pt;
        }
        
        .item-details {
            font-size: 8pt;
            color: #000;
            margin-top: 2px;
        }
        
        .totals {
            margin-top: 15px;
            float: right;
            width: 280px;
        }
        
        .totals table {
            width: 100%;
        }
        
        .totals td {
            padding: 5px 0;
            font-size: 9pt;
        }
        
        .totals .label {
            color: #000;
            text-align: left;
        }
        
        .totals .amount {
            text-align: right;
            font-weight: 600;
            color: #000;
        }
        
        .totals .subtotal {
            border-top: 1px solid #ddd;
        }
        
        .totals .total {
            border-top: 2px solid #000;
            font-size: 12pt;
            font-weight: 700;
            padding-top: 8px;
        }
        
        .payment-terms {
            clear: both;
            margin-top: 25px;
            padding: 12px;
            background: #f9f9f9;
            border-left: 3px solid #000;
        }
        
        .payment-terms h3 {
            font-size: 9pt;
            font-weight: 700;
            margin: 0 0 6px 0;
            color: #000;
        }
        
        .payment-terms p {
            margin: 3px 0;
            font-size: 8pt;
            color: #000;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 7pt;
            color: #000;
        }
        
        .footer p {
            margin: 2px 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <table>
            <tr>
                <td style="width: 50%;">
                    <div class="logo">ALPRAIL</div>
                    <p style="font-size: 8pt; color: #000; margin: 3px 0 0 0;">
                        Route de Lausanne 1<br>
                        1000 Lausanne, Suisse
                    </p>
                </td>
                <td style="width: 50%;">
                    <div class="header-right">
                        <h1>FACTURE</h1>
                        <p>Tél: +41 21 555 00 00</p>
                        <p>Email: contact@alprail.net</p>
                        <p>TVA: CHE-123.456.789</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Invoice Info -->
    <div class="invoice-info">
        <table>
            <tr>
                <td class="invoice-to">
                    <h3>Facturé à</h3>
                    <p style="font-weight: 700; font-size: 10pt;">{{ $invoice->organization->name }}</p>
                    <p>{{ $invoice->organization->address }}</p>
                    <p>{{ $invoice->organization->zip_code }} {{ $invoice->organization->city }}</p>
                    <p style="margin-top: 5px;">{{ $invoice->organization->email }}</p>
                    <p>{{ $invoice->organization->phone }}</p>
                </td>
                <td class="invoice-details">
                    <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Date d'émission</span>
                        <span class="detail-value">{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Date d'échéance</span>
                        <span class="detail-value">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</span>
                    </div>
                    
                    @if($invoice->paid_at)
                    <div class="detail-row">
                        <span class="detail-label">Date paiement</span>
                        <span class="detail-value">{{ \Carbon\Carbon::parse($invoice->paid_at)->format('d/m/Y') }}</span>
                    </div>
                    @endif
                    
                    <div class="detail-row" style="margin-top: 8px;">
                        <span class="status-badge">
                            @if($invoice->status === 'paid') PAYÉE
                            @elseif($invoice->status === 'pending') EN ATTENTE
                            @elseif($invoice->status === 'overdue') EN RETARD
                            @else ANNULÉE
                            @endif
                        </span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Items -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 45%;">Description</th>
                <th style="width: 25%;">Période</th>
                <th class="text-right" style="width: 12%;">Qté</th>
                <th class="text-right" style="width: 18%;">Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="item-description">{{ $invoice->subscription->subscriptionPlan->name }}</div>
                    <div class="item-details">{{ $invoice->subscription->subscriptionPlan->description }}</div>
                </td>
                <td>
                    <div style="font-size: 8pt; color: #000;">
                        {{ \Carbon\Carbon::parse($invoice->subscription->start_date)->format('d/m/Y') }}<br>
                        {{ \Carbon\Carbon::parse($invoice->subscription->end_date)->format('d/m/Y') }}
                    </div>
                </td>
                <td class="text-right">1</td>
                <td class="text-right" style="font-weight: 600;">{{ number_format($invoice->amount, 2, '.', ' ') }} {{ $invoice->currency }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <table>
            <tr class="subtotal">
                <td class="label">Sous-total</td>
                <td class="amount">{{ number_format($invoice->amount, 2, '.', ' ') }} {{ $invoice->currency }}</td>
            </tr>
            <tr>
                <td class="label">TVA ({{ $invoice->tax_amount > 0 ? number_format(($invoice->tax_amount / $invoice->amount) * 100, 1) : '0' }}%)</td>
                <td class="amount">{{ number_format($invoice->tax_amount, 2, '.', ' ') }} {{ $invoice->currency }}</td>
            </tr>
            <tr class="total">
                <td class="label">TOTAL</td>
                <td class="amount">{{ number_format($invoice->total_amount, 2, '.', ' ') }} {{ $invoice->currency }}</td>
            </tr>
        </table>
    </div>

    <!-- Payment Terms -->
    <div class="payment-terms">
        <h3>Modalités de paiement</h3>
        <p><strong>IBAN:</strong> CH93 0076 2011 6238 5295 7 • <strong>BIC:</strong> UBSWCHZH80A • <strong>Banque:</strong> UBS Switzerland AG</p>
        <p><strong>Référence:</strong> {{ $invoice->invoice_number }} • Paiement sous 30 jours</p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Merci de votre confiance • Pour toute question: contact@alprail.net</p>
        <p>Alprail • Route de Lausanne 1 • 1000 Lausanne • Suisse</p>
    </div>
</body>
</html>