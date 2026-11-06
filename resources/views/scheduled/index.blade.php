@extends('layouts.app')

@section('title', 'Scheduled transfers')

@section('content')
    <main class="dashboard-main">
        <x-dashboard-sidebar active="scheduled" />

        <section class="dashboard-content">
            <div class="dashboard-welcome">
                <div>
                    <p class="eyebrow"><span></span> Delivery planner</p>
                    <h1>Scheduled <em>transfers.</em></h1>
                    <p>Files are emailed automatically at the selected date and time.</p>
                </div>
                <a class="new-transfer-button" href="{{ url('/') }}"><span>+</span> Schedule transfer</a>
            </div>

            <section class="recent-card mt-9" aria-labelledby="scheduled-title">
                <div class="recent-heading">
                    <div><p class="section-label">SCHEDULE</p><h2 id="scheduled-title">Planned deliveries</h2></div>
                    <span class="text-[10px] text-[#8a938c]">{{ $transfers->total() }} scheduled</span>
                </div>

                @if ($transfers->isEmpty())
                    <div class="empty-transfers">
                        <div class="empty-icon">&#9716;</div>
                        <h3>No scheduled transfers</h3>
                        <p>Choose “Schedule for later” while creating a transfer.</p>
                        <a class="empty-cta" href="{{ url('/') }}">Schedule your first transfer <span>&rarr;</span></a>
                    </div>
                @else
                    <div class="transfer-table-wrap">
                        <table class="transfer-table scheduled-table">
                            <thead><tr><th>Transfer</th><th>Recipients</th><th>Scheduled for</th><th>Expires</th><th>Status</th><th>Link</th></tr></thead>
                            <tbody>
                            @foreach ($transfers as $transfer)
                                <tr>
                                    <td><span class="table-file-icon">FILE</span><span><strong>{{ $transfer->file_count }} {{ Str::plural('file', $transfer->file_count) }}</strong><small>{{ Str::limit($transfer->message ?: 'Scheduled file transfer', 38) }}</small></span></td>
                                    <td>{{ count($transfer->recipient_emails) }} {{ Str::plural('person', count($transfer->recipient_emails)) }}</td>
                                    <td><strong>{{ $transfer->scheduled_at->format('d M Y') }}</strong><small>{{ $transfer->scheduled_at->format('h:i A') }} UTC</small></td>
                                    <td>{{ $transfer->expires_at->format('d M Y') }}</td>
                                    <td>
                                        @if ($transfer->sent_at)
                                            <span class="status-pill downloaded-pill"><i></i> Sent</span>
                                        @else
                                            <span class="status-pill scheduled-pill"><i></i> Scheduled</span>
                                        @endif
                                    </td>
                                    <td>@if ($transfer->sent_at)<a class="row-link" href="{{ route('transfers.show', $transfer->token) }}" aria-label="Open transfer">&rarr;</a>@else<span class="report-muted">Available after send</span>@endif</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="scheduled-pagination">{{ $transfers->links() }}</div>
                @endif
            </section>
        </section>
    </main>
@endsection
