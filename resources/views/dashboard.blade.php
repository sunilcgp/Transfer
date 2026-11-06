@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <main class="dashboard-main">
        <x-dashboard-sidebar active="overview" />

        <section class="dashboard-content">
            <div class="dashboard-welcome">
                <div>
                    <p class="eyebrow"><span></span> Dashboard overview</p>
                    <h1>Good to see you, <em>{{ str(auth()->user()->name)->before(' ') }}.</em></h1>
                    <p>Everything you've shared, all in one place.</p>
                </div>
                <a class="new-transfer-button" href="{{ url('/') }}"><span>+</span> New transfer</a>
            </div>

            <nav class="dashboard-period-filter" aria-label="Dashboard report period">
                <span>Report period</span>
                <div>
                    <a @class(['active' => $period === 'week']) href="{{ route('dashboard', ['period' => 'week']) }}">Weekly</a>
                    <a @class(['active' => $period === 'month']) href="{{ route('dashboard', ['period' => 'month']) }}">Monthly</a>
                    <a @class(['active' => $period === 'quarter']) href="{{ route('dashboard', ['period' => 'quarter']) }}">3 Months</a>
                    <a @class(['active' => $period === 'year']) href="{{ route('dashboard', ['period' => 'year']) }}">Yearly</a>
                </div>
            </nav>

            <div class="stats-grid" aria-label="Transfer statistics">
                <article class="stat-card featured-stat"><div class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v13H4zM8 4h8v3M8 12h8m-4-4v8"/></svg></div><p>Total transfers</p><strong>{{ $totalTransfers }}</strong><span>{{ ['week' => 'Last 7 days', 'month' => 'Last month', 'quarter' => 'Last 3 months', 'year' => 'Last year'][$period] }}</span></article>
                <article class="stat-card"><div class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zm0-13v5l3 2"/></svg></div><p>Active links</p><strong>{{ $activeTransfers }}</strong><span>Available now</span></article>
                <article class="stat-card"><div class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3h8l4 4v14H8zM16 3v5h4M4 7v13h4"/></svg></div><p>Files sent</p><strong>{{ $totalFiles }}</strong><span>Across transfers</span></article>
                <article class="stat-card"><div class="stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3c5 0 8 2 8 4s-3 4-8 4-8-2-8-4 3-4 8-4zm8 4v5c0 2-3 4-8 4s-8-2-8-4V7m16 5v5c0 2-3 4-8 4s-8-2-8-4v-5"/></svg></div><p>Storage used</p><strong>{{ $storageUsed }}</strong><span>Your transfer total</span></article>
            </div>

            <section class="recent-card" aria-labelledby="recent-title">
                <div class="recent-heading">
                    <div><p class="section-label">ACTIVITY</p><h2 id="recent-title">Recent transfers</h2></div>
                    <a href="{{ route('reports.index') }}">View reports <span>&rarr;</span></a>
                </div>

                @if ($transfers->isEmpty())
                    <div class="empty-transfers">
                        <div class="empty-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5M4 15v5h16v-5"/></svg></div>
                        <h3>No transfers yet</h3>
                        <p>Your sent files will appear here once you create your first transfer.</p>
                        <a class="empty-cta" href="{{ url('/') }}">Send your first files <span>&rarr;</span></a>
                    </div>
                @else
                    <div class="transfer-table-wrap">
                        <table class="transfer-table">
                            <thead><tr><th>Transfer</th><th>Recipients</th><th>Size</th><th>Status</th><th>Sent</th><th><span class="sr-only">Open</span></th></tr></thead>
                            <tbody>
                            @foreach ($transfers as $transfer)
                                <tr>
                                    <td><span class="table-file-icon">FILE</span><span><strong>{{ $transfer->file_count }} {{ Str::plural('file', $transfer->file_count) }}</strong><small>{{ Str::limit($transfer->message ?: 'File transfer', 34) }}</small></span></td>
                                    <td>{{ count($transfer->recipient_emails) }} {{ Str::plural('person', count($transfer->recipient_emails)) }}</td>
                                    <td>{{ Number::fileSize($transfer->total_size) }}</td>
                                    <td>@if ($transfer->expires_at->isFuture())<span class="status-pill active-pill"><i></i> Active</span>@else<span class="status-pill expired-pill"><i></i> Expired</span>@endif</td>
                                    <td>{{ $transfer->created_at->diffForHumans() }}</td>
                                    <td><a class="row-link" href="{{ route('transfers.show', $transfer->token) }}" aria-label="Open transfer">&rarr;</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </section>
    </main>
@endsection
