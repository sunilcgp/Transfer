@props(['active'])

<aside class="dashboard-sidebar">
    <nav aria-label="Dashboard navigation">
        <p class="sidebar-label">WORKSPACE</p>
        <a @class(['sidebar-link', 'active' => $active === 'overview']) href="{{ route('dashboard') }}" @if ($active === 'overview') aria-current="page" @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/></svg>
            Overview
        </a>
        <a class="sidebar-link" href="{{ url('/') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V3m0 0L7 8m5-5 5 5M4 14v6h16v-6"/></svg>
            New transfer
        </a>
        <a @class(['sidebar-link', 'active' => $active === 'reports']) href="{{ route('reports.index') }}" @if ($active === 'reports') aria-current="page" @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/></svg>
            Reports
        </a>
        <a @class(['sidebar-link', 'active' => $active === 'scheduled']) href="{{ route('scheduled.index') }}" @if ($active === 'scheduled') aria-current="page" @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v15H4zM8 3v4m8-4v4M4 10h16"/></svg>
            Scheduled
        </a>
    </nav>

    <div class="sidebar-tip">
        <span class="tip-icon">&#10022;</span>
        <strong>Quick tip</strong>
        <p>Your links expire automatically, so old files never linger forever.</p>
    </div>
</aside>
