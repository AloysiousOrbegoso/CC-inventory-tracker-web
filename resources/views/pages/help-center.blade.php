@extends('layouts.sidebar')

@section('title', 'Help Center')

@section('content')
<div class="mb-6">
    <div class="text-[22px] font-extrabold tracking-tight">Help Center</div>
    <div class="text-[13px] text-ink-2 mt-0.5">Quick answers for running your branches on NITA</div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="tile">
        <div class="tile__title">Managing workers</div>
        <div class="text-[12.5px] text-ink-2 mt-2 leading-relaxed">Add, edit, and clock workers in and out from <a href="{{ route('branches') }}" class="text-accent no-underline hover:underline">Employees</a>. Set hourly rates and generate payslips from <a href="{{ route('salary.index') }}" class="text-accent no-underline hover:underline">Salary</a>.</div>
    </div>
    <div class="tile">
        <div class="tile__title">Tracking discrepancies</div>
        <div class="text-[12.5px] text-ink-2 mt-2 leading-relaxed">Stock variances flagged during shift counts appear under <a href="{{ route('alerts') }}" class="text-accent no-underline hover:underline">Alerts</a>. Review or dismiss each to keep the leakage numbers on your dashboard accurate.</div>
    </div>
    <div class="tile">
        <div class="tile__title">Reading your reports</div>
        <div class="text-[12.5px] text-ink-2 mt-2 leading-relaxed"><a href="{{ route('analytics') }}" class="text-accent no-underline hover:underline">Analytics</a> is for trends and comparisons; <a href="{{ route('reports') }}" class="text-accent no-underline hover:underline">Reports</a> gives you flat, exportable tables for bookkeeping.</div>
    </div>
    <div class="tile">
        <div class="tile__title">Uploading documents</div>
        <div class="text-[12.5px] text-ink-2 mt-2 leading-relaxed">Permits, licenses, and contracts live in <a href="{{ route('legal-papers.index') }}" class="text-accent no-underline hover:underline">Legal Papers</a> with automatic expiry flags. Applicant resumes are stored under <a href="{{ route('hiring.index') }}" class="text-accent no-underline hover:underline">Hiring</a>.</div>
    </div>
</div>

<div class="card p-6 mt-4">
    <div class="text-sm font-bold mb-1.5">Still need help?</div>
    <div class="text-[12.5px] text-ink-2">Reach out to your account administrator, or check the <a href="{{ url('/api-docs') }}" class="text-accent no-underline hover:underline">API documentation</a> if you're integrating with NITA programmatically.</div>
</div>
@endsection
