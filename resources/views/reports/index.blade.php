@extends('layouts.app')

@section('title', 'Transfer reports')

@section('content')
    <main class="dashboard-main">
        <x-dashboard-sidebar active="reports" />

        <section class="dashboard-content">
            <div class="dashboard-welcome">
                <div>
                    <p class="eyebrow"><span></span> Transfer history</p>
                    <h1>Your transfer <em>reports.</em></h1>
                    <p>Review delivery details, link sharing and download activity for every transfer.</p>
                </div>
                <a class="new-transfer-button" href="{{ url('/') }}"><span>+</span> New transfer</a>
            </div>

            <section class="recent-card mt-9" aria-labelledby="report-title">
                <div class="recent-heading">
                    <div><p class="section-label">REPORTS</p><h2 id="report-title">Sent transfer list</h2></div>
                    <span class="text-[10px] text-[#8a938c]">Search, sort and filter your history</span>
                </div>

                <div class="report-datatable">
                    <table id="transferReportsTable" class="transfer-table report-table" data-source="{{ route('reports.data') }}">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Sender email</th>
                                <th>Receiver email</th>
                                <th>Message</th>
                                <th>File name</th>
                                <th>View file</th>
                                <th>Files / Size</th>
                                <th>Sent time</th>
                                <th>Expiry time</th>
                                <th>Download time</th>
                                <th>Status</th>
                                <th>Sharing activity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </section>
        </section>
    </main>
@endsection

@push('scripts')
    @vite('resources/js/reports.js')
@endpush
